<?php

namespace Wereldkundig\GravityForms;

use Wereldkundig\GravityForms\Api\Client;

class Submission {
	/** @var Addon */
	private $addon;

	public function __construct( Addon $addon ) {
		$this->addon = $addon;
	}

	/**
	 * Sends the subscriber of a feed: the feed's subscribe checkbox must be checked, the feed's themes are subscribed.
	 *
	 * @param array<int|string,mixed> $entry
	 * @param array<string,mixed>     $form
	 * @param array<string,mixed>     $feed
	 * @return true|\WP_Error|null True on success, a WP_Error on failure, null when skipped.
	 *                             Gravity Forms saves the error's code and message as the feed status.
	 */
	public function handle( array $entry, array $form, array $feed ) {
		if ( 'spam' === rgar( $entry, 'status' ) ) {
			return null;
		}

		$field = $this->feed_subscribe_field( $form, $feed );
		if ( ! $field ) {
			return $this->fail( $feed, $entry, $form, new \WP_Error( 'wk_gf_subscribe_field_missing', __( 'The subscribe checkbox chosen in the feed is not on the form; choose one in the feed settings. Subscriber not sent.', 'wereldkundig-for-gravity-forms' ) ) );
		}

		if ( ! $this->is_checked( $field, $entry ) ) {
			$this->addon->log_debug( __METHOD__ . '(): Subscribe checkbox not checked; skipping API.' );
			return null;
		}

		// No themes at all means the API isn't configured or reachable, not a bad theme choice.
		$client = new Client();
		if ( ! $client->themes() ) {
			return $this->fail( $feed, $entry, $form, new \WP_Error( 'wk_gf_themes_unavailable', __( 'Could not load the Wereldkundig themes; check the API connection. Subscriber not sent.', 'wereldkundig-for-gravity-forms' ) ) );
		}

		// Prevention: only theme IDs the API knows, and only categories of that theme. IDs are public, not secrets.
		$themes  = array();
		$chosen  = Addon::feed_themes( $feed );
		$invalid = $chosen ? array() : array( '' );
		foreach ( $chosen as $theme_id => $categories ) {
			$theme = $client->find_theme( $theme_id );
			if ( ! $theme ) {
				$invalid[] = $theme_id;
				continue;
			}

			$themes[ $theme['id'] ] = array_values( array_intersect( $categories, array_keys( $theme['categories'] ) ) );
		}

		$error = null;
		if ( $invalid ) {
			$error = $this->fail(
				$feed,
				$entry,
				$form,
				new \WP_Error(
					'wk_gf_theme_invalid',
					sprintf(
					/* translators: %s: comma separated theme IDs, empty when the feed has no theme. */
						__( 'Invalid Wereldkundig theme: %s.', 'wereldkundig-for-gravity-forms' ),
						implode( ', ', array_map( static fn ( $id ): string => '' === $id ? __( 'none selected', 'wereldkundig-for-gravity-forms' ) : $id, array_unique( $invalid ) ) )
					)
				)
			);
		}

		if ( ! $themes ) {
			return $error;
		}

		$subscriber = ( new Mapper( $this->addon ) )->map( $form, $entry, $feed );
		$form_id    = (int) rgar( $form, 'id' );

		/**
		 * The themes to subscribe to: theme ID => category option keys, none meaning the whole theme.
		 * Return an empty array to skip the subscribe. A per-form variant runs after it: wk_gf_subscribe_themes_{form_id}.
		 * For example `['theme-1' => [], 'theme-2' => ['category-3', 'category-4']]` subscribes to the whole of theme-1 and to two categories of theme-2.
		 *
		 * @param array<string,string[]> $themes
		 * @param array                  $entry
		 * @param array                  $form
		 * @param array                  $feed
		 */
		$themes = apply_filters( 'wk_gf_subscribe_themes', $themes, $entry, $form, $feed );

		/**
		 * The themes to subscribe to for one form, e.g. wk_gf_subscribe_themes_3 for form 3. Runs after wk_gf_subscribe_themes.
		 *
		 * @param array<string,string[]> $themes
		 * @param array                  $entry
		 * @param array                  $form
		 * @param array                  $feed
		 */
		$themes = apply_filters( "wk_gf_subscribe_themes_{$form_id}", $themes, $entry, $form, $feed );

		/**
		 * The subscriber fields sent to POST /v1/subscribe (email, first_name, middle_name, last_name, company, ...).
		 * Return false to skip the subscribe. A per-form variant runs after it: wk_gf_subscriber_pre_subscribe_{form_id}.
		 *
		 * @param array<string,string>|false $subscriber
		 * @param array<string,string[]>     $themes
		 * @param array                      $entry
		 * @param array                      $form
		 * @param array                      $feed
		 */
		$subscriber = apply_filters( 'wk_gf_subscriber_pre_subscribe', $subscriber, $themes, $entry, $form, $feed );

		/**
		 * The subscriber fields for one form, e.g. wk_gf_subscriber_pre_subscribe_3 for form 3. Runs after wk_gf_subscriber_pre_subscribe.
		 *
		 * @param array<string,string>|false $subscriber
		 * @param array<string,string[]>     $themes
		 * @param array                      $entry
		 * @param array                      $form
		 * @param array                      $feed
		 */
		$subscriber = apply_filters( "wk_gf_subscriber_pre_subscribe_{$form_id}", $subscriber, $themes, $entry, $form, $feed );

		// The filters can return anything, whatever their documented type.
		if ( ! is_array( $subscriber ) || ! is_array( $themes ) || ! $themes ) { // @phpstan-ignore function.alreadyNarrowedType
			$this->addon->log_debug( __METHOD__ . '(): Skipped by the wk_gf_subscribe_themes or wk_gf_subscriber_pre_subscribe filter.' );
			return $error;
		}

		$subscriber['email'] = sanitize_email( (string) ( $subscriber['email'] ?? '' ) );
		if ( '' === $subscriber['email'] ) {
			return $this->fail( $feed, $entry, $form, new \WP_Error( 'wk_gf_email_invalid', __( 'No valid email address to subscribe.', 'wereldkundig-for-gravity-forms' ) ) );
		}

		$result = $client->subscribe( $themes, $subscriber, Addon::feed_upsert( $feed ) );

		// Debug: the subscribe from this submission and its result to Ray (spatie-ray plugin), when it is active.
		// Client::request() sends the raw API request and response to Ray as well.
		if ( function_exists( 'ray' ) ) {
			ray(
				array(
					'form'       => array(
						'id'    => rgar( $form, 'id' ),
						'title' => rgar( $form, 'title' ),
					),
					'entry_id'   => rgar( $entry, 'id' ),
					'feed_id'    => rgar( $feed, 'id' ),
					'field_id'   => $field->id,
					'chosen'     => $chosen,
					'themes'     => $themes,
					'subscriber' => $subscriber,
					'result'     => is_wp_error( $result ) ? array(
						'error'   => $result->get_error_code(),
						'message' => $result->get_error_message(),
					) : $result,
				)
			)->label( 'Wereldkundig subscribe: form #' . rgar( $form, 'id' ) . ' entry #' . rgar( $entry, 'id' ) )->color( is_wp_error( $result ) ? 'red' : 'green' );
		}

		if ( is_wp_error( $result ) ) {
			return $this->fail( $feed, $entry, $form, $result, $subscriber['email'] );
		}

		$this->addon->log_debug( __METHOD__ . '(): Subscriber sent to Wereldkundig API.' );
		if ( ! empty( $entry['id'] ) ) {
			$this->addon->add_note( $entry['id'], $this->success_note( $client, $subscriber['email'], $themes, $result ), 'success' );
		}

		// The valid themes were subscribed; an invalid one still marks the submission as failed.
		return $error ?? true;
	}

	/**
	 * E.g. "reader@example.com was sent to Wereldkundig (platform user ID 12). Themes: Nieuwsbrief; Evenementen (Nieuws)."
	 *
	 * @param array<string,string[]> $themes
	 * @param array<mixed>           $data The subscribe response's data.
	 */
	private function success_note( Client $client, string $email, array $themes, array $data ): string {
		$names = array();
		foreach ( $themes as $theme_id => $categories ) {
			$theme = $client->find_theme( $theme_id );
			$name  = $theme['name'] ?? (string) $theme_id;
			if ( $categories ) {
				$name .= ' (' . implode( ', ', array_map( static fn ( $id ): string => $theme['categories'][ $id ] ?? (string) $id, (array) $categories ) ) . ')';
			}

			$names[] = $name;
		}

		$updated_existing = array_key_exists( 'created', $data ) && false === $data['created'];

		if ( $updated_existing && isset( $data['user_id'] ) ) {
			$note = sprintf(
				/* translators: 1: email address, 2: user ID on the platform. */
				__( '%1$s was already a Wereldkundig subscriber and was updated (platform user ID %2$s).', 'wereldkundig-for-gravity-forms' ),
				$email,
				(string) $data['user_id']
			);
		} elseif ( isset( $data['user_id'] ) ) {
			$note = sprintf(
				/* translators: 1: email address, 2: user ID on the platform. */
				__( '%1$s was sent to Wereldkundig (platform user ID %2$s).', 'wereldkundig-for-gravity-forms' ),
				$email,
				(string) $data['user_id']
			);
		} else {
			$note = sprintf(
				/* translators: %s: email address. */
				__( '%s was sent to Wereldkundig.', 'wereldkundig-for-gravity-forms' ),
				$email
			);
		}

		$note .= ' ' . sprintf(
			/* translators: %s: semicolon separated theme names, with the chosen categories in parentheses. */
			__( 'Themes: %s.', 'wereldkundig-for-gravity-forms' ),
			implode( '; ', $names )
		);

		if ( ! empty( $data['rejected_options'] ) ) {
			$note .= ' ' . sprintf(
				/* translators: %s: the options the platform did not accept, as JSON. */
				__( 'Rejected by the platform: %s.', 'wereldkundig-for-gravity-forms' ),
				(string) wp_json_encode( $data['rejected_options'] )
			);
		}

		return esc_html( $note );
	}

	/**
	 * The feed's subscribe checkbox. A form with one subscribe field always uses that one, whatever the feed
	 * stored, so a feed saved before the field was added still sends. With several, the feed's choice must
	 * still be on the form; null otherwise.
	 *
	 * @param array<string,mixed> $form
	 * @param array<string,mixed> $feed
	 */
	private function feed_subscribe_field( array $form, array $feed ): ?\GF_Field {
		$fields = self::subscribe_fields( $form );
		if ( 1 === count( $fields ) ) {
			return $fields[0];
		}

		$field_id = (string) rgars( $feed, 'meta/subscribe_field' );
		foreach ( $fields as $field ) {
			if ( '' !== $field_id && (string) $field->id === $field_id ) {
				return $field;
			}
		}

		return null;
	}

	/**
	 * @param array<string,mixed> $form
	 * @return list<\GF_Field> All Wereldkundig subscribe fields on the form, in form order.
	 */
	public static function subscribe_fields( array $form ): array {
		return array_values(
			array_filter(
				(array) rgar( $form, 'fields', array() ),
				static fn ( $field ): bool => $field instanceof \GF_Field && isset( $field->type ) && 'wk_gf_subscribe' === $field->type
			)
		);
	}

	/**
	 * @param array<int|string,mixed> $entry
	 */
	private function is_checked( \GF_Field $field, array $entry ): bool {
		$value = rgar( $entry, (string) $field->id );

		return ! rgblank( $value ) && '0' !== (string) $value;
	}

	/**
	 * Logs the error and adds it to the entry as feed error, with the code so it can be looked up, and the email
	 * once the subscriber was sent.
	 *
	 * @param array<string,mixed>     $feed
	 * @param array<int|string,mixed> $entry
	 * @param array<string,mixed>     $form
	 * @return \WP_Error The same error, to return from handle().
	 */
	private function fail( array $feed, array $entry, array $form, \WP_Error $error, string $email = '' ): \WP_Error {
		$message = '' !== $email
			? sprintf(
				/* translators: 1: email address, 2: error message, 3: error code. */
				__( '%1$s was not sent to Wereldkundig: %2$s (code: %3$s)', 'wereldkundig-for-gravity-forms' ),
				$email,
				$error->get_error_message(),
				(string) $error->get_error_code()
			)
			: sprintf(
				/* translators: 1: error message, 2: error code. */
				__( '%1$s (code: %2$s)', 'wereldkundig-for-gravity-forms' ),
				$error->get_error_message(),
				(string) $error->get_error_code()
			);

		$this->addon->log_error( __METHOD__ . '(): ' . $message );

		// Gravity Forms shows notes through wp_kses_post(); the API message must not add markup.
		if ( ! empty( $entry['id'] ) ) {
			$this->addon->add_feed_error( esc_html( $message ), $feed, $entry, $form );
		}

		return $error;
	}
}
