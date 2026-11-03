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
	 * @param array<int|string,mixed>  $entry
	 * @param array<string,mixed>      $form
	 * @param array<string,mixed>|null $feed
	 * @return true|\WP_Error|null True on success, a WP_Error on failure, null when skipped.
	 *                             Gravity Forms saves the error's code and message as the feed status.
	 */
	public function handle( array $entry, array $form, $feed = null ) {
		if ( 'spam' === rgar( $entry, 'status' ) ) {
			return null;
		}

		$subscribe_fields = $this->subscribe_fields( $form );
		if ( ! $subscribe_fields ) {
			$this->addon->log_debug( __METHOD__ . '(): Wereldkundig field not on form; skipping API.' );
			return null;
		}

		$checked = array_filter( $subscribe_fields, fn ( $field ): bool => $this->is_checked( $field, $entry ) );
		if ( ! $checked ) {
			$this->addon->log_debug( __METHOD__ . '(): Subscribe checkbox not checked; skipping API.' );
			return null;
		}

		// No themes at all means the API isn't configured or reachable, not a bad theme choice.
		$client = new Client();
		if ( ! $client->themes() ) {
			return $this->fail( $feed, $entry, $form, new \WP_Error( 'wk_themes_unavailable', __( 'Could not load the Wereldkundig themes; check the API connection. Subscriber not sent.', 'gravityformswereldkundig' ) ) );
		}

		// Every checked field adds its themes. Prevention: only theme IDs the API knows, and only categories of that theme. IDs are public, not secrets.
		$themes  = array();
		$invalid = array();
		foreach ( $checked as $field ) {
			$theme_ids = SubscribeField::theme_ids_of( $field );
			if ( ! $theme_ids ) {
				$invalid[] = '';
			}

			$categories = SubscribeField::category_ids_of( $field );
			foreach ( $theme_ids as $theme_id ) {
				$theme = $client->find_theme( $theme_id );
				if ( ! $theme ) {
					$invalid[] = $theme_id;
					continue;
				}

				$chosen                 = array_values( array_intersect( $categories, array_keys( $theme['categories'] ) ) );
				$themes[ $theme['id'] ] = $this->merge_categories( $themes[ $theme['id'] ] ?? null, $chosen );
			}
		}

		$error = null;
		if ( $invalid ) {
			$error = $this->fail(
				$feed,
				$entry,
				$form,
				new \WP_Error(
					'wk_theme_invalid',
					sprintf(
					/* translators: %s: comma separated theme IDs, empty when the field has no theme. */
						__( 'Invalid Wereldkundig theme: %s.', 'gravityformswereldkundig' ),
						implode( ', ', array_map( static fn ( $id ): string => '' === $id ? __( 'none selected', 'gravityformswereldkundig' ) : $id, array_unique( $invalid ) ) )
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
		 * @param array|null             $feed
		 */
		$themes = apply_filters( 'wk_gf_subscribe_themes', $themes, $entry, $form, $feed );

		/**
		 * The themes to subscribe to for one form, e.g. wk_gf_subscribe_themes_3 for form 3. Runs after wk_gf_subscribe_themes.
		 *
		 * @param array<string,string[]> $themes
		 * @param array                  $entry
		 * @param array                  $form
		 * @param array|null             $feed
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
		 * @param array|null                 $feed
		 */
		$subscriber = apply_filters( 'wk_gf_subscriber_pre_subscribe', $subscriber, $themes, $entry, $form, $feed );

		/**
		 * The subscriber fields for one form, e.g. wk_gf_subscriber_pre_subscribe_3 for form 3. Runs after wk_gf_subscriber_pre_subscribe.
		 *
		 * @param array<string,string>|false $subscriber
		 * @param array<string,string[]>     $themes
		 * @param array                      $entry
		 * @param array                      $form
		 * @param array|null                 $feed
		 */
		$subscriber = apply_filters( "wk_gf_subscriber_pre_subscribe_{$form_id}", $subscriber, $themes, $entry, $form, $feed );

		// The filters can return anything, whatever their documented type.
		if ( ! is_array( $subscriber ) || ! is_array( $themes ) || ! $themes ) { // @phpstan-ignore function.alreadyNarrowedType
			$this->addon->log_debug( __METHOD__ . '(): Skipped by the wk_gf_subscribe_themes or wk_gf_subscriber_pre_subscribe filter.' );
			return $error;
		}

		$subscriber['email'] = sanitize_email( (string) ( $subscriber['email'] ?? '' ) );
		if ( '' === $subscriber['email'] ) {
			return $this->fail( $feed, $entry, $form, new \WP_Error( 'wk_email_invalid', __( 'No valid email address to subscribe.', 'gravityformswereldkundig' ) ) );
		}

		$result = $client->subscribe( $themes, $subscriber );

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
					'feed_id'    => is_array( $feed ) ? rgar( $feed, 'id' ) : null,
					'checked'    => array_map(
						static fn ( $field ): array => array(
							'field_id'   => $field->id,
							'themes'     => SubscribeField::theme_ids_of( $field ),
							'categories' => SubscribeField::category_ids_of( $field ),
						),
						array_values( $checked )
					),
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
	 * The same theme from several fields: the whole theme (no categories) wins, otherwise the categories add up.
	 *
	 * @param string[]|null $current Null when the theme isn't chosen yet.
	 * @param string[]      $chosen
	 * @return string[]
	 */
	private function merge_categories( ?array $current, array $chosen ): array {
		if ( null === $current ) {
			return $chosen;
		}

		if ( ! $current || ! $chosen ) {
			return array();
		}

		return array_values( array_unique( array_merge( $current, $chosen ) ) );
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

		$note = isset( $data['user_id'] )
			? sprintf(
				/* translators: 1: email address, 2: user ID on the platform. */
				__( '%1$s was sent to Wereldkundig (platform user ID %2$s).', 'gravityformswereldkundig' ),
				$email,
				(string) $data['user_id']
			)
			: sprintf(
				/* translators: %s: email address. */
				__( '%s was sent to Wereldkundig.', 'gravityformswereldkundig' ),
				$email
			);

		$note .= ' ' . sprintf(
			/* translators: %s: semicolon separated theme names, with the chosen categories in parentheses. */
			__( 'Themes: %s.', 'gravityformswereldkundig' ),
			implode( '; ', $names )
		);

		if ( ! empty( $data['rejected_options'] ) ) {
			$note .= ' ' . sprintf(
				/* translators: %s: the options the platform did not accept, as JSON. */
				__( 'Rejected by the platform: %s.', 'gravityformswereldkundig' ),
				(string) wp_json_encode( $data['rejected_options'] )
			);
		}

		return esc_html( $note );
	}

	/**
	 * @param array<string,mixed> $form
	 * @return list<\GF_Field> All Wereldkundig subscribe fields on the form, in form order.
	 */
	private function subscribe_fields( array $form ): array {
		return array_values(
			array_filter(
				(array) rgar( $form, 'fields', array() ),
				static fn ( $field ): bool => $field instanceof \GF_Field && isset( $field->type ) && 'wk_subscribe' === $field->type
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
	 * Logs the error and adds it to the entry, as feed error (with a feed) or entry note (without), with the code
	 * so it can be looked up, and the email once the subscriber was sent.
	 *
	 * @param array<string,mixed>|null $feed
	 * @param array<int|string,mixed>  $entry
	 * @param array<string,mixed>      $form
	 * @return \WP_Error The same error, to return from handle().
	 */
	private function fail( $feed, array $entry, array $form, \WP_Error $error, string $email = '' ): \WP_Error {
		$message = '' !== $email
			? sprintf(
				/* translators: 1: email address, 2: error message, 3: error code. */
				__( '%1$s was not sent to Wereldkundig: %2$s (code: %3$s)', 'gravityformswereldkundig' ),
				$email,
				$error->get_error_message(),
				(string) $error->get_error_code()
			)
			: sprintf(
				/* translators: 1: error message, 2: error code. */
				__( '%1$s (code: %2$s)', 'gravityformswereldkundig' ),
				$error->get_error_message(),
				(string) $error->get_error_code()
			);

		$this->addon->log_error( __METHOD__ . '(): ' . $message );

		// Gravity Forms shows notes through wp_kses_post(); the API message must not add markup.
		if ( is_array( $feed ) && ! empty( $entry['id'] ) ) {
			$this->addon->add_feed_error( esc_html( $message ), $feed, $entry, $form );
		} elseif ( ! empty( $entry['id'] ) ) {
			$this->addon->add_note( $entry['id'], esc_html( $message ), 'error' );
		}

		return $error;
	}
}
