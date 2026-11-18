<?php

namespace Wereldkundig\GravityForms;

use Gravity_Forms\Gravity_Forms\Settings\Fields\Text;
use Wereldkundig\GravityForms\Api\Client;

/**
 * Basic Auth code setting that never puts the code itself in the page, like Mailchimp for WP does with its API key:
 * it shows a masked value and keeps the saved code when that mask is posted back unchanged.
 *
 * Only loaded on the add-on settings page, after Gravity Forms has loaded its settings field classes.
 */
class ApiAuthSettingField extends Text {
	public $type = 'wk_gf_api_auth';

	/**
	 * Gravity Forms renders this value, so return the mask, never the code. WK_API_AUTH wins over the setting.
	 *
	 * @return string
	 */
	public function get_value() {
		$saved = parent::get_value();
		$value = defined( 'WK_API_AUTH' ) && '' !== trim( (string) constant( 'WK_API_AUTH' ) )
			? Client::credentials()['auth']
			: ( is_string( $saved ) ? $saved : '' );

		return Addon::mask_secret( $value );
	}

	/**
	 * @param string $value Posted value: the unchanged mask, a new code, or empty to remove it.
	 * @return string
	 */
	public function save( $value ) {
		$previous = (array) $this->settings->get_previous_values();

		return Addon::keep_secret( (string) $value, (string) ( $previous[ $this->name ] ?? '' ) );
	}
}
