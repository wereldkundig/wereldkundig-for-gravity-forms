<?php

namespace Wereldkundig\GravityForms\Api;

use Wereldkundig\GravityForms\Addon;

/**
 * Wereldkundig Subscriber API, see https://docs.wereldkundig.nl/api
 *
 * A subscriber joins the platform's themes (option keys `theme-{id}`), optionally only some of their categories.
 */
class Client {
	public const FIELDS_OPTION = 'wk_gf_api_fields';

	/** Why the last fetch of the fields failed (settings save or background refresh); removed by the next successful fetch. */
	public const FIELDS_ERROR_OPTION = 'wk_gf_api_fields_error';

	/** @var array<string,mixed> Details of the last request, sent to Ray. */
	private $debug = array();

	/** @var array<string,array<mixed>> fields_setup() results for this request, per cache_key(), so repeated lookups hit the option and API once. */
	private static $memo = array();

	/**
	 * @return array<string,string> Theme option key => theme name.
	 */
	public function themes(): array {
		$choices = array();

		foreach ( $this->themes_setup() as $theme ) {
			$choices[ $theme['id'] ] = $theme['name'];
		}

		return $choices;
	}

	/**
	 * Themes from the cached GET /v1/subscribe/fields response.
	 *
	 * @param bool $skip_cache
	 * @param bool $fetch      False to only read the cache, never call the API (e.g. in the form editor).
	 * @return array<int,array{id:string,name:string,categories:array<string,string>}>
	 */
	public function themes_setup( $skip_cache = false, $fetch = true ): array {
		$response = $this->fields_setup( $skip_cache, $fetch );
		if ( ! is_array( $response['options']['categories'] ?? null ) ) {
			return array();
		}

		$themes = array();
		foreach ( $response['options']['categories'] as $theme ) {
			if ( ! is_array( $theme ) ) {
				continue;
			}

			$id = $this->theme_key( $theme['id'] ?? '' );
			if ( '' === $id ) {
				continue;
			}

			$themes[] = array(
				'id'         => $id,
				'name'       => isset( $theme['name'] ) ? $this->decode( (string) $theme['name'] ) : $id,
				'categories' => $this->normalize_categories( $theme['categories'] ?? array() ),
			);
		}

		return $themes;
	}

	/**
	 * The full GET /v1/subscribe/fields response (`fields` and `options`), cached in an option.
	 * The option is refreshed when the add-on settings are saved, or when it belongs to other credentials.
	 * Within a request the result is kept in memory, a failed fetch included, so the API is called at most once.
	 *
	 * @param bool $skip_cache
	 * @param bool $fetch      False to only read the cache: empty when there is none, without calling the API.
	 * @return array<mixed>
	 */
	public function fields_setup( $skip_cache = false, $fetch = true ): array {
		$key = $this->cache_key();
		if ( ! $skip_cache && array_key_exists( $key, self::$memo ) ) {
			return self::$memo[ $key ];
		}

		if ( ! $skip_cache && function_exists( 'get_option' ) ) {
			$cached = get_option( self::FIELDS_OPTION );
			if ( is_array( $cached ) && ( $cached['key'] ?? null ) === $key && is_array( $cached['response'] ?? null ) ) {
				self::$memo[ $key ] = $cached['response'];

				return self::$memo[ $key ];
			}
		}

		// Not remembered, so a later lookup in this request that may fetch still can.
		if ( ! $fetch ) {
			return array();
		}

		$response = $this->refresh_fields();

		self::$memo[ $key ] = is_wp_error( $response ) ? array() : $response;

		return self::$memo[ $key ];
	}

	/**
	 * When the cached fields were fetched, if the cache belongs to the current credentials. Never calls the API.
	 *
	 * @return int|null Unix time, or null without a valid cache.
	 */
	public function cached_at(): ?int {
		$cached = function_exists( 'get_option' ) ? get_option( self::FIELDS_OPTION ) : null;
		if ( ! is_array( $cached ) || ( $cached['key'] ?? null ) !== $this->cache_key() || ! is_array( $cached['response'] ?? null ) ) {
			return null;
		}

		return (int) ( $cached['fetched_at'] ?? 0 );
	}

	/**
	 * Fetch GET /v1/subscribe/fields and store it in the option. A failed request leaves the option untouched.
	 *
	 * @return array<mixed>|\WP_Error
	 */
	public function refresh_fields() {
		$response = $this->request( 'GET', '/v1/subscribe/fields' );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! is_array( $response['fields'] ?? null ) && ! is_array( $response['options'] ?? null ) ) {
			return new \WP_Error( 'wk_gf_api_fields_invalid', __( 'Invalid subscribe fields response.', 'wereldkundig-for-gravity-forms' ) );
		}

		if ( function_exists( 'update_option' ) ) {
			update_option(
				self::FIELDS_OPTION,
				array(
					'key'        => $this->cache_key(),
					'fetched_at' => time(),
					'response'   => $response,
				),
				false
			);
		}

		self::$memo[ $this->cache_key() ] = $response;
		self::clear_fields_error();

		return $response;
	}

	public function flush_fields_cache(): void {
		self::$memo = array();
		if ( function_exists( 'delete_option' ) ) {
			delete_option( self::FIELDS_OPTION );
		}
		self::clear_fields_error();
	}

	/**
	 * @return array{message:string,code:string,time:int,source?:string}|null The stored fetch error, null when the last fetch succeeded.
	 */
	public static function fields_error(): ?array {
		$error = function_exists( 'get_option' ) ? get_option( self::FIELDS_ERROR_OPTION ) : null;
		if ( ! is_array( $error ) || '' === (string) ( $error['message'] ?? '' ) ) {
			return null;
		}

		$stored = array(
			'message' => (string) $error['message'],
			'code'    => (string) ( $error['code'] ?? '' ),
			'time'    => (int) ( $error['time'] ?? 0 ),
		);
		if ( isset( $error['source'] ) ) {
			$stored['source'] = (string) $error['source'];
		}

		return $stored;
	}

	/**
	 * @param string $source What fetched: 'settings' (a save) or 'cron' (the background refresh).
	 */
	public static function store_fields_error( \WP_Error $error, string $source = 'settings' ): void {
		if ( function_exists( 'update_option' ) ) {
			update_option(
				self::FIELDS_ERROR_OPTION,
				array(
					'message' => $error->get_error_message(),
					'code'    => (string) $error->get_error_code(),
					'time'    => time(),
					'source'  => $source,
				),
				false
			);
		}
	}

	public static function clear_fields_error(): void {
		if ( function_exists( 'delete_option' ) ) {
			delete_option( self::FIELDS_ERROR_OPTION );
		}
	}

	/**
	 * Subscriber fields from the cached GET /v1/subscribe/fields response.
	 * Empty when the fields can't be loaded; there is no fallback, as subscribing would fail too.
	 *
	 * @return array<string,array{type:string,label:string,required:bool}> Field key => definition.
	 */
	public function subscriber_fields(): array {
		$response = $this->fields_setup();
		$fields   = array();

		foreach ( (array) ( $response['fields'] ?? array() ) as $key => $field ) {
			$key = (string) $key;
			if ( ! is_array( $field ) || ! preg_match( '/^[a-z0-9_]+$/i', $key ) ) {
				continue;
			}

			$fields[ $key ] = array(
				'type'     => isset( $field['type'] ) ? (string) $field['type'] : 'text',
				'label'    => isset( $field['label'] ) ? $this->decode( (string) $field['label'] ) : $key,
				'required' => ! empty( $field['required'] ),
			);
		}

		return $fields;
	}

	public function theme_exists( mixed $theme_id ): bool {
		return null !== $this->find_theme( $theme_id );
	}

	/**
	 * @param mixed $theme_id
	 * @param bool  $fetch    False to only read the cache, never call the API (e.g. in the feed list).
	 * @return array{id:string,name:string,categories:array<string,string>}|null The cached theme, or null when the platform doesn't have it.
	 */
	public function find_theme( $theme_id, $fetch = true ): ?array {
		$theme_id = (string) $theme_id;
		if ( '' === $theme_id ) {
			return null;
		}

		foreach ( $this->themes_setup( false, $fetch ) as $theme ) {
			if ( $theme['id'] === $theme_id ) {
				return $theme;
			}
		}

		return null;
	}

	/**
	 * POST /v2/subscribe. Creates the subscriber and adds them to the theme(s), in one request.
	 *
	 * Empty fields are left out of the payload; only email is always included.
	 * Without `$upsert`, an email that is already a member of the platform site is refused (`member-exists`).
	 * With `$upsert`, that member is updated and the themes are merged; omitted profile fields keep their value.
	 *
	 * @param string|string[]|array<string,string[]> $themes     Theme option key(s), e.g. "theme-1" or ["theme-1", "theme-2"],
	 *                                                           or theme => category option keys, e.g. ["theme-1" => [], "theme-2" => ["category-36-37"]].
	 *                                                           No categories means the whole theme.
	 * @param array<string,mixed>                    $subscriber Subscriber field key => value, e.g. email, first_name, middle_name.
	 *                                                           Empty and non-scalar values are left out.
	 * @param bool                                   $upsert     When true, update an existing platform member instead of failing.
	 * @return array<mixed>|\WP_Error The response's `data` (e.g. user_id, created, rejected_options) on success.
	 */
	public function subscribe( $themes, array $subscriber, bool $upsert = false ) {
		// Email is the only field always sent: the API needs it to find or create the subscriber.
		// Submission already rejects entries without a valid email before we get here.
		$payload = array(
			'email'  => (string) ( $subscriber['email'] ?? '' ),
			'upsert' => $upsert,
		);

		// Every other field, first_name and last_name included, is only sent when filled.
		// An empty or unmapped field is dropped, not sent as "", so it can't clear a value
		// already stored on the platform (e.g. a resubscribe from a form without a name field).
		foreach ( $subscriber as $key => $value ) {
			$key = (string) $key;
			if ( isset( $payload[ $key ] ) || 'options' === $key || ! is_scalar( $value ) || '' === (string) $value || ! preg_match( '/^[a-z0-9_]+$/i', $key ) ) {
				continue;
			}

			$payload[ $key ] = (string) $value;
		}

		$payload['options'] = $this->subscribe_options( $themes );

		$response = $this->request( 'POST', '/v2/subscribe', $payload );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['success'] ) ) {
			return new \WP_Error(
				'wk_gf_api_subscribe_failed',
				! empty( $response['message'] ) ? (string) $response['message'] : __( 'Wereldkundig API request failed.', 'wereldkundig-for-gravity-forms' )
			);
		}

		return is_array( $response['data'] ?? null ) ? $response['data'] : array();
	}

	/**
	 * The `options` of POST /v2/subscribe: a whole theme as `theme-{id}`, or only its chosen categories.
	 *
	 * Chosen categories are sent without their theme on purpose. The platform adds the theme of every category
	 * itself, but a theme it receives also gets all of that theme's main categories ("select-all-{id}"), which
	 * would subscribe to the whole theme after all.
	 *
	 * @param string|string[]|array<string,string[]> $themes See subscribe().
	 * @return array<string,bool>
	 */
	private function subscribe_options( $themes ): array {
		$options = array();
		foreach ( (array) $themes as $key => $value ) {
			[$id, $categories] = is_int( $key ) ? array( $value, array() ) : array( $key, $value );

			$categories = array_filter(
				array_map( 'strval', (array) $categories ),
				static fn ( string $category ): bool => (bool) preg_match( '/^(category-\d+(-\d+)?|select-all-\d+)$/', $category )
			);

			if ( ! $categories ) {
				$options[ $this->theme_key( $id ) ] = true;
				continue;
			}

			foreach ( $categories as $category ) {
				$options[ $category ] = true;
			}
		}

		return $options;
	}

	/**
	 * HTTP wrapper. Only the test suite gets the mock, see use_mock().
	 *
	 * @param string              $method
	 * @param string              $path
	 * @param array<string,mixed> $body
	 * @return array<mixed>|\WP_Error
	 */
	public function request( $method, $path, array $body = array() ) {
		$method      = strtoupper( $method );
		$this->debug = array();
		$result      = $this->send( $method, $path, $body );

		// Debug: every API call and its result to Ray (spatie-ray plugin), when it is active.
		if ( function_exists( 'ray' ) ) {
			ray(
				array(
					'request' => array_merge(
						array(
							'method' => $method,
							'path'   => $path,
							'body'   => $body,
						),
						$this->debug
					),
					'result'  => is_wp_error( $result )
					? array(
						'error'   => $result->get_error_code(),
						'message' => $result->get_error_message(),
						'data'    => $result->get_error_data(),
					)
					: $result,
				)
			)->label( 'Wereldkundig API ' . $method . ' ' . $path )->color( is_wp_error( $result ) ? 'red' : 'green' );
		}

		return $result;
	}

	/**
	 * @param string              $method
	 * @param string              $path
	 * @param array<string,mixed> $body
	 * @return array<mixed>|\WP_Error
	 */
	private function send( $method, $path, array $body ) {
		if ( self::use_mock( $method, $path, $body ) ) {
			$this->debug['mode'] = 'mock';

			return $this->mock( $method, $path, $body );
		}

		$this->debug['mode'] = 'live';

		$credentials = self::credentials();
		if ( ! self::is_configured() ) {
			return new \WP_Error(
				'wk_gf_api_credentials_missing',
				__( 'Configure the Wereldkundig API URL and Basic Auth code under Forms > Settings > Wereldkundig, or define WK_GF_API_URL and WK_GF_API_AUTH in wp-config.php.', 'wereldkundig-for-gravity-forms' ),
				array( 'status' => 500 )
			);
		}

		$url = $this->url( $path );
		// The Basic Auth code must not travel over plain http; constants and the URL filter skip the settings validation.
		if ( ! self::is_secure_url( $url ) ) {
			return new \WP_Error(
				'wk_gf_api_insecure_url',
				__( 'The Wereldkundig API URL must use https:// (plain http is only allowed for localhost and *.test).', 'wereldkundig-for-gravity-forms' ),
				array( 'status' => 500 )
			);
		}

		/**
		 * The API request timeout in seconds. Default 10.
		 *
		 * @param int    $timeout
		 * @param string $method  GET or POST.
		 * @param string $path    /v1/subscribe/fields or /v1/subscribe.
		 */
		$timeout = (int) apply_filters( 'wk_gf_api_timeout', 10, $method, $path );

		$args = array(
			'method'  => $method,
			'timeout' => $timeout,
			'headers' => array(
				'Authorization' => 'Basic ' . $credentials['auth'],
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json',
			),
		);

		if ( 'GET' !== $method ) {
			$json = wp_json_encode( $body );
			if ( false === $json ) {
				return new \WP_Error( 'wk_gf_api_json_encode', __( 'Could not encode the API request.', 'wereldkundig-for-gravity-forms' ) );
			}

			$args['body'] = $json;
		}

		$this->debug['url']  = $url;
		$this->debug['args'] = array_replace_recursive( $args, array( 'headers' => array( 'Authorization' => 'Basic ' . substr( $credentials['auth'], 0, 4 ) . '…' ) ) );

		$response = wp_remote_request( $url, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code    = (int) wp_remote_retrieve_response_code( $response );
		$raw     = wp_remote_retrieve_body( $response );
		$decoded = json_decode( $raw, true );

		$this->debug['status']   = $code;
		$this->debug['raw_body'] = $raw;

		if ( $code < 200 || $code >= 300 ) {
			// A WordPress REST error has code and message; a problem details response (RFC 7807, e.g. 409 Member Exists) has type, title and detail.
			$decoded = is_array( $decoded ) ? $decoded : array();
			$message = (string) ( $decoded['message'] ?? $decoded['detail'] ?? $decoded['title'] ?? '' );
			$error   = (string) ( $decoded['code'] ?? $decoded['type'] ?? '' );

			return new \WP_Error(
				'' !== $error ? $error : 'wk_gf_api_http',
				'' !== $message ? $message : __( 'API request failed.', 'wereldkundig-for-gravity-forms' ),
				array( 'status' => $code )
			);
		}

		if ( ! is_array( $decoded ) ) {
			return new \WP_Error( 'wk_gf_api_json', __( 'Invalid API response.', 'wereldkundig-for-gravity-forms' ) );
		}

		return $decoded;
	}

	/**
	 * Constants in wp-config.php win over the add-on settings.
	 * `auth` is the base64 Basic Auth code supplied by Wereldkundig, without the "Basic " prefix.
	 *
	 * @return array{url:string,auth:string}
	 */
	public static function credentials(): array {
		$settings = function_exists( 'get_option' ) ? get_option( Addon::SETTINGS_OPTION, array() ) : array();
		// Gravity Forms 2.7.17+ stores add-on settings encrypted (a JSON string) when GF_ENCRYPTION_KEY is defined.
		if ( is_string( $settings ) && method_exists( Addon::class, 'get_encryptor' ) ) { // @phpstan-ignore function.alreadyNarrowedType (Missing before Gravity Forms 2.7.17.)
			$settings = Addon::get_instance()->get_encryptor()->decrypt( $settings );
		}
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$value = static fn ( string $constant, string $setting ): string => defined( $constant ) && '' !== trim( (string) constant( $constant ) )
			? trim( (string) constant( $constant ) )
			: trim( (string) ( $settings[ $setting ] ?? '' ) );

		return array(
			'url'  => $value( 'WK_GF_API_URL', 'api_url' ),
			'auth' => trim( (string) preg_replace( '/^Basic\s+/i', '', $value( 'WK_GF_API_AUTH', 'api_auth' ) ) ),
		);
	}

	public static function is_configured(): bool {
		$credentials = self::credentials();

		return '' !== $credentials['url'] && '' !== $credentials['auth'];
	}

	/**
	 * The mock only runs under the test suite: tests/bootstrap.php defines WK_GF_RUNNING_TESTS and PHPUnit must be loaded.
	 * On a site it never runs, not even as a fallback for missing credentials or an unreachable API,
	 * because the mock always succeeds and would silently drop real subscribers.
	 * Inside the tests, the wk_gf_api_use_mock filter switches it off to exercise the live HTTP path.
	 *
	 * @param string              $method
	 * @param string              $path
	 * @param array<string,mixed> $body
	 */
	private static function use_mock( $method, $path, array $body ): bool {
		if ( ! defined( 'WK_GF_RUNNING_TESTS' ) || ! class_exists( 'PHPUnit\\Framework\\TestCase', false ) ) {
			return false;
		}

		return (bool) apply_filters( 'wk_gf_api_use_mock', true, $method, $path, $body );
	}

	/**
	 * https, or plain http only for local development hosts (localhost, *.localhost, *.test).
	 * A URL without scheme counts as https, like base_url() treats it.
	 */
	public static function is_secure_url( string $url ): bool {
		$url = trim( $url );
		if ( '' === $url ) {
			return false;
		}

		if ( ! preg_match( '#^[a-z][a-z0-9+.-]*://#i', $url ) ) {
			$url = 'https://' . $url;
		}

		$parts  = wp_parse_url( $url );
		$scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
		$host   = strtolower( (string) ( $parts['host'] ?? '' ) );
		if ( '' === $host ) {
			return false;
		}

		if ( 'https' === $scheme ) {
			return true;
		}

		return 'http' === $scheme && ( 'localhost' === $host || (bool) preg_match( '/\.(test|localhost)$/', $host ) );
	}

	/**
	 * Accepts "1", "theme-1" or "theme_1" and returns "theme-1".
	 *
	 * @param mixed $id
	 */
	private function theme_key( $id ): string {
		$id = (string) $id;
		if ( '' === $id ) {
			return '';
		}

		return preg_match( '/^theme[-_]/', $id ) ? 'theme-' . substr( $id, 6 ) : 'theme-' . $id;
	}

	/**
	 * Platform domain or full URL; the REST namespace is added when missing.
	 */
	private function base_url(): string {
		$url = self::credentials()['url'];
		if ( '' !== $url && ! preg_match( '#^https?://#i', $url ) ) {
			$url = 'https://' . $url;
		}

		$url = rtrim( $url, '/' );
		if ( '' !== $url && ! preg_match( '#/wp-json/wereldkundig$#', $url ) ) {
			$url .= '/wp-json/wereldkundig';
		}

		/**
		 * The API base URL, e.g. https://subdomein.domein.nl, from the settings or WK_GF_API_URL.
		 *
		 * The settings page doesn't validate a filtered URL, but every request still refuses plain http
		 * except for `localhost`, `*.localhost` and `*.test`.
		 *
		 * @param string $url
		 */
		return (string) apply_filters( 'wk_gf_api_base_url', $url );
	}

	private function url( string $path ): string {
		return trailingslashit( $this->base_url() ) . ltrim( $path, '/' );
	}

	private function cache_key(): string {
		return md5( strtolower( $this->base_url() ) . '|' . self::credentials()['auth'] );
	}

	/**
	 * The platform sends WordPress titles with HTML entities (e.g. "&#8211;"); output is escaped again on render.
	 */
	private function decode( string $text ): string {
		return html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * Theme categories with their children flattened, for display only.
	 *
	 * @param mixed $categories
	 * @return array<string,string>
	 */
	private function normalize_categories( $categories ): array {
		if ( ! is_array( $categories ) ) {
			return array();
		}

		$normalized = array();
		foreach ( $categories as $category ) {
			if ( ! is_array( $category ) || ! isset( $category['id'] ) ) {
				continue;
			}

			$id                = (string) $category['id'];
			$normalized[ $id ] = isset( $category['name'] ) ? $this->decode( (string) $category['name'] ) : $id;

			foreach ( $category['children'] ?? array() as $child ) {
				if ( is_array( $child ) && isset( $child['id'] ) ) {
					$normalized[ (string) $child['id'] ] = isset( $child['name'] ) ? $this->decode( (string) $child['name'] ) : (string) $child['id'];
				}
			}
		}

		return $normalized;
	}

	/**
	 * Stand-in for GET /v1/subscribe/fields and POST /v2/subscribe, shaped like the OpenAPI spec. Always succeeds.
	 * Test suite only, see use_mock().
	 *
	 * @param string              $method
	 * @param string              $path
	 * @param array<string,mixed> $body
	 * @return array<string,mixed>
	 */
	private function mock( $method, $path, array $body ): array {
		if ( 'GET' === $method && '/v1/subscribe/fields' === $path ) {
			return array(
				'fields'  => array(
					'first_name'  => array(
						'type'     => 'text',
						'label'    => 'Voornaam',
						'required' => false,
					),
					'middle_name' => array(
						'type'     => 'text',
						'label'    => 'Tussenvoegsel',
						'required' => false,
					),
					'last_name'   => array(
						'type'     => 'text',
						'label'    => 'Achternaam',
						'required' => false,
					),
					'email'       => array(
						'type'     => 'email',
						'label'    => 'E-mail',
						'required' => true,
					),
					'company'     => array(
						'type'     => 'text',
						'label'    => 'Organisatie',
						'required' => false,
					),
				),
				'options' => array(
					'title'      => 'Onderwerpen',
					'categories' => array(
						array(
							'id'         => 'theme-1',
							'name'       => 'Nieuwsbrief',
							'categories' => array(
								array(
									'id'       => 'category-3',
									'name'     => 'Nieuws',
									'children' => array(),
								),
							),
						),
						array(
							'id'         => 'theme-2',
							'name'       => 'Evenementen',
							'categories' => array(
								array(
									'id'       => 'category-4',
									'name'     => 'Events',
									'children' => array(),
								),
							),
						),
						array(
							'id'         => 'theme-5',
							'name'       => 'Persberichten',
							'categories' => null,
						),
					),
				),
			);
		}

		return array(
			'success' => true,
			'message' => 'Subscriber saved.',
			'data'    => array(
				'user_id' => 1,
				'created' => true,
				'options' => $body['options'] ?? array(),
			),
		);
	}
}
