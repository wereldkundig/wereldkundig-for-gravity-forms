<?php

namespace Wereldkundig\GravityForms;

class Mapper {
	/** @var Addon */
	private $addon;

	public function __construct( Addon $addon ) {
		$this->addon = $addon;
	}

	/**
	 * @param array<string,mixed>      $form
	 * @param array<int|string,mixed>  $entry
	 * @param array<string,mixed>|null $feed
	 * @return array<string,string> Subscriber field key => value; always has email, first_name, middle_name and last_name.
	 */
	public function map( array $form, array $entry, $feed = null ): array {
		/**
		 * Whether to auto-find Email / First name / Middle name / Last name when the feed has no mapping for that field, or the form has no feed. Default `false`.
		 *
		 * Return true to fill them from the first matching form field. While it is false, a form without a feed has no email address, so nothing is sent.
		 *
		 * @param bool       $enabled
		 * @param array      $form
		 * @param array      $entry
		 * @param array|null $feed
		 */
		$auto_enabled = (bool) apply_filters( 'wk_gf_auto_map_fields', false, $form, $entry, $feed );
		$values       = $auto_enabled ? $this->from_auto( $form, $entry ) : $this->empty_map();

		if ( ! is_array( $feed ) ) {
			return $values;
		}

		$mapped = $this->addon->get_field_map_fields( $feed, 'mapped_fields' );
		foreach ( (array) $mapped as $key => $field_id ) {
			if ( rgblank( $field_id ) ) {
				continue;
			}

			$value          = (string) $this->addon->get_field_value( $form, $entry, $field_id );
			$values[ $key ] = 'email' === $key ? sanitize_email( $value ) : sanitize_text_field( $value );
		}

		return $values;
	}

	/**
	 * First email field, then the first Name field (first, middle and last inputs).
	 *
	 * @param array<string,mixed>     $form
	 * @param array<int|string,mixed> $entry
	 * @return array{email:string,first_name:string,middle_name:string,last_name:string}
	 */
	private function from_auto( array $form, array $entry ): array {
		$values      = $this->empty_map();
		$email_field = $this->first_field_of_type( $form, 'email' );
		if ( $email_field ) {
			$values['email'] = sanitize_email( (string) rgar( $entry, (string) $email_field->id ) );
		}

		$name_field = $this->first_field_of_type( $form, 'name' );
		if ( $name_field ) {
			$parts                 = $this->name_parts( $name_field, $entry );
			$values['first_name']  = $parts['first_name'];
			$values['middle_name'] = $parts['middle_name'];
			$values['last_name']   = $parts['last_name'];

			return $values;
		}

		$first = $this->first_field_matching( $form, '/\b(first\s*name|firstname|voornaam)\b/i' );
		if ( $first ) {
			$values['first_name'] = sanitize_text_field( (string) rgar( $entry, (string) $first->id ) );
		}

		$middle = $this->first_field_matching( $form, '/\b(middle\s*name|middlename|tussenvoegsels?)\b/i' );
		if ( $middle ) {
			$values['middle_name'] = sanitize_text_field( (string) rgar( $entry, (string) $middle->id ) );
		}

		$last = $this->first_field_matching( $form, '/\b(last\s*name|lastname|surname|achternaam)\b/i' );
		if ( $last ) {
			$values['last_name'] = sanitize_text_field( (string) rgar( $entry, (string) $last->id ) );
		}

		if ( '' === $values['first_name'] ) {
			$generic = $this->first_field_matching( $form, '/^(name|naam)$/i' );
			if ( $generic ) {
				$values['first_name'] = sanitize_text_field( (string) rgar( $entry, (string) $generic->id ) );
			}
		}

		return $values;
	}

	/**
	 * @param array<int|string,mixed> $entry
	 * @return array{first_name:string,middle_name:string,last_name:string}
	 */
	private function name_parts( \GF_Field $field, array $entry ): array {
		if ( empty( $field->inputs ) || 'simple' === rgar( $field, 'nameFormat' ) ) {
			return array(
				'first_name'  => sanitize_text_field( (string) rgar( $entry, (string) $field->id ) ),
				'middle_name' => '',
				'last_name'   => '',
			);
		}

		return array(
			'first_name'  => sanitize_text_field( (string) rgar( $entry, $field->id . '.3' ) ),
			'middle_name' => sanitize_text_field( (string) rgar( $entry, $field->id . '.4' ) ),
			'last_name'   => sanitize_text_field( (string) rgar( $entry, $field->id . '.6' ) ),
		);
	}

	/**
	 * @param array<string,mixed> $form
	 */
	private function first_field_of_type( array $form, string $type ): ?\GF_Field {
		foreach ( $form['fields'] as $field ) {
			if ( $field instanceof \GF_Field && isset( $field->type ) && $type === $field->type ) {
				return $field;
			}
		}

		return null;
	}

	/**
	 * @param array<string,mixed> $form
	 */
	private function first_field_matching( array $form, string $pattern ): ?\GF_Field {
		foreach ( $form['fields'] as $field ) {
			if ( ! $field instanceof \GF_Field || ! in_array( $field->type, array( 'text', 'hidden' ), true ) ) {
				continue;
			}
			if ( preg_match( $pattern, (string) $field->label ) ) {
				return $field;
			}
		}

		return null;
	}

	/**
	 * @return array{email:string,first_name:string,middle_name:string,last_name:string}
	 */
	private function empty_map(): array {
		return array(
			'email'       => '',
			'first_name'  => '',
			'middle_name' => '',
			'last_name'   => '',
		);
	}
}
