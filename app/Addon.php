<?php

namespace Wereldkundig\GravityForms;

use Wereldkundig\GravityForms\Api\Client;

class Addon extends \GFFeedAddOn {
	public const SETTINGS_OPTION = 'gravityformsaddon_wereldkundig_settings';

	private static ?self $instance = null;

	// Gravity Forms declares these without a type, so they can only be typed in PHPDoc.
	/** @var string */
	protected $_version = VERSION;
	/** @var string */
	protected $_min_gravityforms_version = '2.5.0';
	/** @var string */
	protected $_slug = 'wereldkundig';
	/** @var string */
	protected $_path;
	/** @var string */
	protected $_full_path;
	/** @var string */
	protected $_title = 'Wereldkundig';
	/** @var string */
	protected $_short_title = 'Wereldkundig';

	// Own capabilities for the API settings and uninstall, so they can be granted separately (e.g. with Members).
	// Users with gform_full_access (administrators) pass every check. Feeds stay with whoever may edit forms.
	/** @var string[] */
	protected $_capabilities = array( 'gravityforms_wereldkundig', 'gravityforms_wereldkundig_uninstall' );
	/** @var string */
	protected $_capabilities_settings_page = 'gravityforms_wereldkundig';
	/** @var string */
	protected $_capabilities_form_settings = 'gravityforms_edit_forms';
	/** @var string */
	protected $_capabilities_uninstall = 'gravityforms_wereldkundig_uninstall';

	// Feeds run after the submission, like Brevo: Gravity Forms queues them and sends a non-blocking loopback
	// request to admin-ajax.php at shutdown, with a WP-Cron healthcheck as fallback. Switch off per form or feed
	// with the gform_is_feed_asynchronous filter, e.g. when loopback requests are blocked.
	/** @var bool */
	protected $_async_feed_processing = true;

	public function __construct() {
		$this->_full_path = dirname( __DIR__ ) . '/wereldkundig-for-gravity-forms.php';
		// Derived, so a renamed or symlinked plugin folder still matches the activated plugin.
		$this->_path = function_exists( 'plugin_basename' )
			? plugin_basename( $this->_full_path )
			: basename( dirname( __DIR__ ) ) . '/wereldkundig-for-gravity-forms.php';
		parent::__construct();
	}

	/**
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @return void
	 */
	public function init() {
		parent::init();
		add_filter( 'gform_field_map_choices', array( $this, 'hide_inactive_name_inputs' ), 10, 2 );
		FieldsRefresh::register( $this );
	}

	/**
	 * @return void
	 */
	public function init_admin() {
		parent::init_admin();
		// Also schedules the refresh for sites configured before it existed, or only with constants.
		FieldsRefresh::sync();
	}

	/**
	 * @return string
	 */
	public function get_menu_icon() {
		return 'dashicons-email-alt';
	}

	/**
	 * @return bool
	 */
	public function can_create_feed() {
		return Client::is_configured();
	}

	/**
	 * @param int|array<string,mixed> $id The feed ID or feed.
	 * @return bool
	 */
	public function can_duplicate_feed( $id ) {
		return true;
	}

	/**
	 * Shown on the form's Wereldkundig feed page instead of the feeds while the API isn't configured.
	 * A configured API that fails shows its stored error on the feed settings instead, see api_error_notice().
	 *
	 * @return string
	 */
	public function configure_addon_message() {
		$settings = esc_html__( 'Wereldkundig settings', 'wereldkundig-for-gravity-forms' );
		if ( self::can_manage_settings() ) {
			$settings = sprintf( '<a href="%s">%s</a>', esc_url( self::settings_url() ), $settings );
		}

		return sprintf(
			/* translators: %s: "Wereldkundig settings", linked to the settings page for users who may open it. */
			esc_html__( 'Enter the platform URL and Basic Auth code under %s (Forms > Settings > Wereldkundig) to start sending subscribers.', 'wereldkundig-for-gravity-forms' ),
			$settings
		);
	}

	/**
	 * @return array<string,string>
	 */
	public function feed_list_columns() {
		return array(
			'feedName'  => esc_html__( 'Name', 'wereldkundig-for-gravity-forms' ),
			'wk_themes' => esc_html__( 'Themes', 'wereldkundig-for-gravity-forms' ),
		);
	}

	/**
	 * The feed's theme names in the feed list, from the cache only; unknown theme IDs as they are.
	 *
	 * @param array<string,mixed> $feed
	 * @return string
	 */
	public function get_column_value_wk_themes( $feed ) {
		$client = new Client();
		$names  = array();
		foreach ( array_keys( self::feed_themes( $feed ) ) as $theme_id ) {
			$theme   = $client->find_theme( $theme_id, false );
			$names[] = $theme['name'] ?? $theme_id;
		}

		return esc_html( implode( ', ', $names ) );
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function feed_settings_fields() {
		$field_map = $this->fields_for_feed_mapping();
		$themes    = $this->register_setting_field( 'wk_themes', ThemesSettingField::class ) && ( new Client() )->themes_setup();

		return array(
			array(
				'title'  => esc_html__( 'Wereldkundig Feed', 'wereldkundig-for-gravity-forms' ),
				'fields' => array(
					array(
						'name' => 'api_error_notice',
						'type' => 'html',
						'html' => array( $this, 'api_error_notice' ),
					),
					array(
						'name'          => 'feedName',
						'label'         => esc_html__( 'Name', 'wereldkundig-for-gravity-forms' ),
						'type'          => 'text',
						'required'      => true,
						'default_value' => $this->get_default_feed_name(),
						'class'         => 'medium',
					),
					...$this->subscribe_field_settings(),
					$themes ? array(
						'name'     => 'wk_themes',
						'label'    => esc_html__( 'Themes', 'wereldkundig-for-gravity-forms' ),
						'type'     => 'wk_themes',
						'required' => true,
						'tooltip'  => '<h6>' . esc_html__( 'Themes', 'wereldkundig-for-gravity-forms' ) . '</h6>' . esc_html__( 'Subscribers are added to every checked Wereldkundig theme. Check categories of a theme to subscribe to only those; without checked categories the whole theme is subscribed.', 'wereldkundig-for-gravity-forms' ),
					) : array(
						'name'  => 'wk_themes_unavailable',
						'label' => esc_html__( 'Themes', 'wereldkundig-for-gravity-forms' ),
						'type'  => 'html',
						'html'  => '<div class="alert gforms_note_error">' . esc_html__( 'Could not load the themes from Wereldkundig. Check the connection under Forms > Settings > Wereldkundig and save those settings to try again. The checked themes are kept.', 'wereldkundig-for-gravity-forms' ) . '</div>',
					),
					$field_map ? array(
						'name'        => 'mapped_fields',
						'label'       => esc_html__( 'Map Fields', 'wereldkundig-for-gravity-forms' ),
						'type'        => 'field_map',
						'field_map'   => $field_map,
						'tooltip'     => '<h6>' . esc_html__( 'Map Fields', 'wereldkundig-for-gravity-forms' ) . '</h6>' . esc_html__( 'Select which form field fills each Wereldkundig subscriber field. Map at least the email address: without it, the subscriber is not sent.', 'wereldkundig-for-gravity-forms' ),
						'description' => esc_html__( 'An empty mapping is not sent. To fill empty email and name mappings from the first matching form field, turn on auto-mapping with the wk_gf_auto_map_fields filter.', 'wereldkundig-for-gravity-forms' ),
					) : array(
						'name'  => 'mapped_fields_unavailable',
						'label' => esc_html__( 'Map Fields', 'wereldkundig-for-gravity-forms' ),
						'type'  => 'html',
						'html'  => '<div class="alert gforms_note_error">' . esc_html__( 'Could not load the subscriber fields from Wereldkundig. Check the connection under Forms > Settings > Wereldkundig and save those settings to try again. Existing mappings are kept.', 'wereldkundig-for-gravity-forms' ) . '</div>',
					),
					array(
						'name'    => 'feed_condition',
						'label'   => esc_html__( 'Conditional Logic', 'wereldkundig-for-gravity-forms' ),
						'type'    => 'feed_condition',
						'tooltip' => '<h6>' . esc_html__( 'Conditional Logic', 'wereldkundig-for-gravity-forms' ) . '</h6>' . esc_html__( 'When enabled, the subscriber is only sent when the condition is met. The subscribe checkbox chosen above must still be checked.', 'wereldkundig-for-gravity-forms' ),
					),
				),
			),
		);
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function plugin_settings_fields() {
		$description = esc_html__( 'Connect to your Wereldkundig platform with the Basic Auth code supplied by Wereldkundig.', 'wereldkundig-for-gravity-forms' );

		$constants = array_filter(
			array( 'WK_API_URL', 'WK_API_AUTH' ),
			static function ( $constant ) {
				return defined( $constant ) && '' !== trim( (string) constant( $constant ) );
			}
		);
		if ( $constants ) {
			$description .= '<br /><strong>' . esc_html(
				sprintf(
				/* translators: %s: comma separated constant names. */
					__( '%s defined in wp-config.php, so the matching field below is ignored.', 'wereldkundig-for-gravity-forms' ),
					implode( ', ', $constants )
				)
			) . '</strong>';
		}

		$masked = $this->register_setting_field( 'wk_api_auth', ApiAuthSettingField::class );

		return array(
			array(
				'title'       => esc_html__( 'Wereldkundig API', 'wereldkundig-for-gravity-forms' ),
				'description' => wp_kses_post( $description ),
				'fields'      => array(
					array(
						'name' => 'api_status_notice',
						'type' => 'html',
						'html' => array( $this, 'api_status_notice' ),
					),
					array(
						'name'                => 'api_url',
						'label'               => esc_html__( 'Platform URL', 'wereldkundig-for-gravity-forms' ),
						'type'                => 'text',
						'class'               => 'large',
						'placeholder'         => 'https://subdomein.domein.nl',
						'validation_callback' => array( $this, 'validate_api_url' ),
					),
					array_filter(
						array(
							'name'         => 'api_auth',
							'label'        => esc_html__( 'Basic Auth code', 'wereldkundig-for-gravity-forms' ),
							// Without the masked type (older Gravity Forms), at least hide the code on screen.
							'type'         => $masked ? 'wk_api_auth' : 'text',
							'input_type'   => $masked ? null : 'password',
							'class'        => 'large',
							'autocomplete' => 'off',
							'readonly'     => defined( 'WK_API_AUTH' ) && '' !== trim( (string) constant( 'WK_API_AUTH' ) ) ? 'readonly' : null,
							'description'  => esc_html__( 'The base64 encoded credentials, without "Basic ". Only the start is shown once saved; leave it as is to keep the saved code, or paste a new one.', 'wereldkundig-for-gravity-forms' ),
						)
					),
					array(
						'type' => 'save',
					),
				),
			),
		);
	}

	/**
	 * The Wereldkundig tab under Forms > Settings.
	 */
	public static function settings_url(): string {
		return admin_url( 'admin.php?page=gf_settings&subview=wereldkundig' );
	}

	/**
	 * Matches $_capabilities_settings_page; Gravity Forms full access (administrators) passes too.
	 */
	public static function can_manage_settings(): bool {
		return current_user_can( 'gform_full_access' ) || current_user_can( 'gravityforms_wereldkundig' );
	}

	/**
	 * Registers one of our settings field types (the masked Basic Auth code, the feed's themes).
	 * Gravity Forms only loads its settings field classes on demand.
	 *
	 * @param string       $type
	 * @param class-string $class_name
	 * @return bool Whether the type is available; false (older Gravity Forms) falls back to a core field or a notice.
	 */
	private function register_setting_field( string $type, string $class_name ) {
		$fields = 'Gravity_Forms\Gravity_Forms\Settings\Fields';
		if ( ! class_exists( $fields ) && class_exists( 'GFCommon' ) ) {
			require_once \GFCommon::get_base_path() . '/includes/settings/class-fields.php';
		}

		if ( ! class_exists( $fields ) || ! class_exists( 'Gravity_Forms\Gravity_Forms\Settings\Fields\Text' ) ) {
			return false;
		}

		if ( ! $fields::exists( $type ) ) {
			$fields::register( $type, $class_name );
		}

		return true;
	}

	/**
	 * The Subscribe checkbox settings, by the number of subscribe fields on the form whose feed is being edited:
	 * - none: a notice to add one;
	 * - one: no choice to make, the feed stores that field (see Submission::feed_subscribe_field());
	 * - several: a required select.
	 *
	 * @return list<array<string,mixed>>
	 */
	private function subscribe_field_settings(): array {
		$form   = $this->get_current_form();
		$fields = Submission::subscribe_fields( is_array( $form ) ? $form : array() );
		$label  = esc_html__( 'Subscribe checkbox', 'wereldkundig-for-gravity-forms' );

		if ( ! $fields ) {
			return array(
				array(
					'name'  => 'subscribe_field_unavailable',
					'label' => $label,
					'type'  => 'html',
					'html'  => '<div class="alert gforms_note_error">' . esc_html__( 'This form has no Wereldkundig subscribe me field. Add one in the form editor: the feed then uses it, no need to edit the feed again.', 'wereldkundig-for-gravity-forms' ) . '</div>',
				),
			);
		}

		if ( 1 === count( $fields ) ) {
			$only = (string) $fields[0]->id;

			return array(
				array(
					'name'  => 'subscribe_field_only',
					'label' => $label,
					'type'  => 'html',
					'html'  => '<p>' . sprintf(
						/* translators: %s: the field label and ID, like "Nieuwsbrief (field 5)". */
						esc_html__( '%s, the only subscribe checkbox of this form.', 'wereldkundig-for-gravity-forms' ),
						'<code>' . esc_html( self::subscribe_field_label( $fields[0] ) ) . '</code>'
					) . '</p>',
				),
				// Stored, so the feed keeps this checkbox when another subscribe field is added later.
				array(
					'name'          => 'subscribe_field',
					'type'          => 'hidden',
					'default_value' => $only,
					'save_callback' => function () use ( $only ) {
						return $only;
					},
				),
			);
		}

		$choices = array(
			array(
				'label' => esc_html__( 'Select a checkbox', 'wereldkundig-for-gravity-forms' ),
				'value' => '',
			),
		);
		foreach ( $fields as $field ) {
			$choices[] = array(
				'label' => esc_html( self::subscribe_field_label( $field ) ),
				'value' => (string) $field->id,
			);
		}

		return array(
			array(
				'name'     => 'subscribe_field',
				'label'    => $label,
				'type'     => 'select',
				'required' => true,
				'choices'  => $choices,
				'tooltip'  => '<h6>' . $label . '</h6>' . esc_html__( 'The Wereldkundig subscribe me field of this form that gives consent. The feed only sends the subscriber when this checkbox is checked. For other themes behind another checkbox, add a feed for that checkbox.', 'wereldkundig-for-gravity-forms' ),
			),
		);
	}

	/**
	 * A subscribe field by its admin label, or its label, and its ID.
	 */
	private static function subscribe_field_label( \GF_Field $field ): string {
		$label = (string) rgar( $field, 'adminLabel' );
		$label = '' !== $label ? $label : (string) rgar( $field, 'label' );

		return sprintf(
			/* translators: 1: field label, 2: field ID. */
			__( '%1$s (field %2$s)', 'wereldkundig-for-gravity-forms' ),
			$label,
			(string) $field->id
		);
	}

	/**
	 * The feed's themes: theme ID => category option keys, none meaning the whole theme.
	 *
	 * @param array<string,mixed>|null $feed
	 * @return array<string,string[]>
	 */
	public static function feed_themes( $feed ): array {
		return self::clean_themes( rgars( (array) $feed, 'meta/wk_themes' ) );
	}

	/**
	 * The posted themes setting, [theme ID => [theme => 1, categories => [...]]], as theme ID => category option keys.
	 * Only checked themes; categories of an unchecked theme are dropped.
	 *
	 * @param mixed $value
	 * @return array<string,string[]>
	 */
	public static function themes_from_posted( $value ): array {
		$themes = array();
		foreach ( is_array( $value ) ? $value : array() as $theme_id => $posted ) {
			if ( is_array( $posted ) && ! empty( $posted['theme'] ) ) {
				$themes[ $theme_id ] = is_array( $posted['categories'] ?? null ) ? $posted['categories'] : array();
			}
		}

		return self::clean_themes( $themes );
	}

	/**
	 * Only valid theme IDs, each with only valid category option keys: category-{id}, category-{parent}-{id} or select-all-{id}.
	 *
	 * @param mixed $themes Theme ID => category option keys.
	 * @return array<string,string[]>
	 */
	public static function clean_themes( $themes ): array {
		$clean = array();
		foreach ( is_array( $themes ) ? $themes : array() as $theme_id => $categories ) {
			$theme_id = trim( (string) $theme_id );
			if ( ! preg_match( '/^[a-z0-9_-]+$/i', $theme_id ) ) {
				continue;
			}

			$ids = array();
			foreach ( is_array( $categories ) ? $categories : array() as $id ) {
				$id = is_scalar( $id ) ? trim( (string) $id ) : '';
				if ( preg_match( '/^(category-\d+(-\d+)?|select-all-\d+)$/', $id ) ) {
					$ids[ $id ] = $id;
				}
			}

			$clean[ $theme_id ] = array_values( $ids );
		}

		return $clean;
	}

	/**
	 * The first 4 characters and a fixed run of asterisks. Nothing from the end and no length:
	 * the end of a base64 Basic Auth code is the end of the password.
	 */
	public static function mask_secret( string $secret ): string {
		$secret = trim( $secret );
		if ( '' === $secret ) {
			return '';
		}

		return substr( $secret, 0, min( 4, intdiv( strlen( $secret ), 3 ) ) ) . str_repeat( '*', 16 );
	}

	/**
	 * Base64 never contains "*", so a posted value with one is the unchanged mask: keep the saved code.
	 */
	public static function keep_secret( string $posted, string $previous ): string {
		$posted = trim( $posted );

		return str_contains( $posted, '*' ) ? $previous : $posted;
	}

	/**
	 * The Basic Auth code is sent with every request, so refuse plain http outside local development hosts.
	 *
	 * @param \Gravity_Forms\Gravity_Forms\Settings\Fields\Base $field
	 * @param string                                            $value
	 * @return void
	 */
	public function validate_api_url( $field, $value ) {
		$value = trim( (string) $value );
		if ( '' !== $value && ! Client::is_secure_url( $value ) ) {
			$field->set_error( esc_html__( 'Use an https:// URL. Plain http is only allowed for localhost and *.test.', 'wereldkundig-for-gravity-forms' ) );
		}
	}

	/**
	 * Gravity Forms removes the settings and feeds; the cached subscribe fields are ours to remove.
	 *
	 * @return bool
	 */
	public function uninstall() {
		( new Client() )->flush_fields_cache();
		FieldsRefresh::unschedule();
		parent::uninstall();

		// GFFeedAddOn::uninstall() returns nothing; only false would cancel the uninstall.
		return true;
	}

	/**
	 * Saving the settings (possibly another platform or user) fetches all subscribe fields into the cache option.
	 *
	 * @param array<string,mixed> $settings
	 * @return void
	 */
	public function update_plugin_settings( $settings ) {
		parent::update_plugin_settings( $settings );

		$client = new Client();
		FieldsRefresh::sync();
		if ( ! Client::is_configured() ) {
			$client->flush_fields_cache();
			return;
		}

		// A successful fetch removes a stored error itself; a failed one is kept until then, see api_status_notice().
		$response = $client->refresh_fields();
		if ( is_wp_error( $response ) ) {
			$this->log_error( __METHOD__ . '(): Could not fetch subscribe fields: ' . $response->get_error_message() );
			Client::store_fields_error( $response, 'settings' );
		}
	}

	/**
	 * Connection status at the top of the settings page, from stored data only: it never calls the API.
	 * A failed fetch (after saving, or by the background refresh) wins; otherwise a valid cache for the current
	 * credentials means connected.
	 * Rendered as a callable, so it runs after Gravity Forms has processed a save in the same request.
	 *
	 * @return string
	 */
	public function api_status_notice() {
		$error = $this->api_error_notice();
		if ( '' !== $error ) {
			return $error;
		}

		$client     = new Client();
		$fetched_at = Client::is_configured() ? $client->cached_at() : null;
		if ( null === $fetched_at ) {
			return '';
		}

		$count = count( $client->themes_setup( false, false ) );

		// WordPress green (#00a32a) border on its lightest tint (#edfaef), with dark green (#003008) text.
		return sprintf(
			'<div class="wk-api-status-notice" style="padding: 8px 12px; border-left: 4px solid #00a32a; background: #edfaef; color: #003008;"><strong>%s</strong><br />%s: %s<br />%s</div>',
			esc_html__( 'Successfully connected to Wereldkundig.', 'wereldkundig-for-gravity-forms' ),
			esc_html__( 'Platform', 'wereldkundig-for-gravity-forms' ),
			esc_html( Client::credentials()['url'] ),
			esc_html(
				sprintf(
				/* translators: 1: number of themes, 2: date and time the themes were fetched. */
					_n( 'Connected, %1$d theme (fetched %2$s).', 'Connected, %1$d themes (fetched %2$s).', $count, 'wereldkundig-for-gravity-forms' ),
					$count,
					$this->format_time( $fetched_at )
				)
			)
		);
	}

	/**
	 * The stored fetch error, on the settings page and on the feed settings; empty when the last fetch succeeded.
	 *
	 * @return string
	 */
	public function api_error_notice() {
		$error = Client::fields_error();
		if ( ! $error ) {
			return '';
		}

		return sprintf(
			'<div class="wk-api-error-notice" role="alert" style="padding: 8px 12px; border-left: 4px solid #d63638; background: #fcf0f1; color: #3f0001;"><strong>%s</strong><br />%s<br />%s</div>',
			'cron' === ( $error['source'] ?? '' )
					? esc_html__( 'The background refresh of the Wereldkundig themes failed.', 'wereldkundig-for-gravity-forms' )
					: esc_html__( 'Settings saved, but the Wereldkundig API call failed.', 'wereldkundig-for-gravity-forms' ),
			esc_html(
				sprintf(
					/* translators: 1: date and time of the failed call, 2: API error message. */
					__( 'Last attempt %1$s: %2$s', 'wereldkundig-for-gravity-forms' ),
					$this->format_time( (int) $error['time'] ),
					$error['message']
				)
			),
			esc_html__( 'The themes and subscriber fields were not updated. Check the URL and Basic Auth code, then save the settings to try again. The background refresh also retries every hour.', 'wereldkundig-for-gravity-forms' )
		);
	}

	private function format_time( int $time ): string {
		return function_exists( 'wp_date' )
			? (string) wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $time )
			: gmdate( 'Y-m-d H:i', $time );
	}

	/**
	 * Without subscriber fields or themes from the platform the mapping or themes aren't rendered, so they aren't
	 * posted either; keep the saved ones instead of wiping them.
	 *
	 * @param int                 $feed_id
	 * @param int                 $form_id
	 * @param array<string,mixed> $settings
	 * @return int
	 */
	public function save_feed_settings( $feed_id, $form_id, $settings ) {
		$keep_mapping = ! $this->fields_for_feed_mapping();
		$keep_themes  = ! ( new Client() )->themes_setup();
		if ( $feed_id && ( $keep_mapping || $keep_themes ) ) {
			$feed = $this->get_feed( $feed_id );
			foreach ( (array) ( $feed['meta'] ?? array() ) as $key => $value ) {
				$keep = ( $keep_mapping && 0 === strpos( (string) $key, 'mapped_fields_' ) ) || ( $keep_themes && 'wk_themes' === $key );
				if ( $keep && ! array_key_exists( $key, $settings ) ) {
					$settings[ $key ] = $value;
				}
			}
		}

		return parent::save_feed_settings( $feed_id, $form_id, $settings );
	}

	/**
	 * Every subscriber field the platform offers. None are required here: an empty mapping is left out, or auto-detected on submit when wk_gf_auto_map_fields is on.
	 *
	 * @return array<int,array{name:string,label:string,required:bool,field_type?:string[]}>
	 */
	public function fields_for_feed_mapping(): array {
		$map = array();

		foreach ( ( new Client() )->subscriber_fields() as $key => $field ) {
			$item = array(
				'name'     => $key,
				'label'    => esc_html( $field['label'] ),
				'required' => false,
			);
			if ( 'email' === $field['type'] ) {
				$item['field_type'] = array( 'email', 'hidden', 'text' );
			}

			$map[] = $item;
		}

		return $map;
	}

	/**
	 * Drop Name field parts that are switched off in the form editor (e.g. prefix, middle, suffix) from our feed's field map.
	 *
	 * @param mixed $choices The field map choices; anything else from another filter is returned unchanged.
	 * @param int   $form_id
	 * @return mixed
	 */
	public function hide_inactive_name_inputs( $choices, $form_id ) {
		if ( $this->_slug !== rgget( 'subview' ) || ! is_array( $choices ) || ! class_exists( 'GFAPI' ) ) {
			return $choices;
		}

		$form   = \GFAPI::get_form( $form_id );
		$hidden = array();
		foreach ( (array) rgar( $form, 'fields', array() ) as $field ) {
			if ( 'name' !== rgar( $field, 'type' ) || ! is_array( rgar( $field, 'inputs' ) ) ) {
				continue;
			}

			foreach ( $field->inputs as $input ) {
				if ( ! empty( $input['isHidden'] ) ) {
					$hidden[] = (string) $input['id'];
				}
			}
		}

		return $hidden ? $this->without_choices( $choices, $hidden ) : $choices;
	}

	/**
	 * @param array<mixed> $choices Choices, possibly grouped under 'choices'.
	 * @param string[]     $values  Choice values to remove.
	 * @return array<mixed>
	 */
	private function without_choices( array $choices, array $values ): array {
		$filtered = array();
		foreach ( $choices as $key => $choice ) {
			if ( is_array( rgar( $choice, 'choices' ) ) ) {
				$choice['choices'] = array_values( $this->without_choices( $choice['choices'], $values ) );
			} elseif ( in_array( (string) rgar( $choice, 'value' ), $values, true ) ) {
				continue;
			}

			$filtered[ $key ] = $choice;
		}

		return $filtered;
	}

	/**
	 * Gravity Forms saves the result as the feed status of the entry: success for true, failed with the code and
	 * message for a WP_Error, nothing for null (skipped).
	 *
	 * @param array<string,mixed>     $feed
	 * @param array<int|string,mixed> $entry
	 * @param array<string,mixed>     $form
	 * @return true|\WP_Error|null
	 */
	public function process_feed( $feed, $entry, $form ) {
		return ( new Submission( $this ) )->handle( $entry, $form, $feed );
	}

	/**
	 * Gravity Forms only retries a failed background feed when gform_max_async_feed_attempts is above 1 (default 1).
	 * A retry can't fix these, so they never are: a bad theme, email or setup, a request body that can't be encoded,
	 * or the platform refusing the request (4xx). A timeout, a server error (5xx) and unreachable themes can be retried.
	 *
	 * @param bool                    $retry
	 * @param mixed                   $error The WP_Error from process_feed(); Gravity Forms passes it on without a check.
	 * @param array<string,mixed>     $feed
	 * @param array<int|string,mixed> $entry
	 * @param array<string,mixed>     $form
	 * @return bool
	 */
	public function is_feed_error_retryable( $retry, $error, $feed, $entry, $form ) {
		if ( $error instanceof \WP_Error ) {
			$status = (int) rgar( (array) $error->get_error_data(), 'status' );
			$final  = in_array( $error->get_error_code(), array( 'wk_theme_invalid', 'wk_subscribe_field_missing', 'wk_email_invalid', 'wk_api_credentials_missing', 'wk_api_insecure_url', 'wk_api_json_encode' ), true )
				|| ( $status >= 400 && $status < 500 && ! in_array( $status, array( 408, 429 ), true ) );
			if ( $final ) {
				$retry = false;
			}
		}

		// Gravity Forms 2.9.25+ adds the gform_is_feed_error_retryable filter.
		return method_exists( \GFFeedAddOn::class, 'is_feed_error_retryable' ) // @phpstan-ignore function.alreadyNarrowedType (Missing before Gravity Forms 2.9.25.)
			? parent::is_feed_error_retryable( $retry, $error, $feed, $entry, $form )
			: (bool) $retry;
	}
}
