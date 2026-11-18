<?php

namespace Wereldkundig\GravityForms;

use Gravity_Forms\Gravity_Forms\Settings\Fields\Base;
use Wereldkundig\GravityForms\Api\Client;

/**
 * The themes of a feed, each with the categories to subscribe to: a checkbox per theme with its categories below it.
 * Saved as theme ID => category option keys, none meaning the whole theme, see Addon::feed_themes().
 *
 * Only loaded on the feed settings, after Gravity Forms has loaded its settings field classes.
 */
class ThemesSettingField extends Base {
	public $type = 'wk_gf_themes';

	/**
	 * @return string
	 */
	public function markup() {
		$themes = ( new Client() )->themes_setup();
		$prefix = $this->settings->get_input_name_prefix() . '_' . $this->name;
		$value  = $this->get_value();
		// After a failed validation Gravity Forms renders the posted value, [theme ID => [theme => 1, categories => [...]]].
		$first = is_array( $value ) ? reset( $value ) : null;
		$saved = is_array( $first ) && array_key_exists( 'theme', $first ) ? Addon::themes_from_posted( $value ) : Addon::clean_themes( $value );

		$html = $this->get_description() . '<div class="wk-gf-themes">';
		foreach ( $themes as $theme ) {
			$theme_html_id = 'wk_gf_theme_' . sanitize_html_class( $theme['id'] );
			$checked       = array_key_exists( $theme['id'], $saved );

			$html .= '<div class="wk-gf-theme">';
			$html .= sprintf(
				'<div class="wk-gf-theme__header"><input type="checkbox" class="wk-gf-theme-checkbox" id="%1$s" name="%2$s[%3$s][theme]" value="1" %4$s /><label for="%1$s">%5$s</label></div>',
				esc_attr( $theme_html_id ),
				esc_attr( $prefix ),
				esc_attr( $theme['id'] ),
				$checked ? 'checked="checked"' : '',
				esc_html( $theme['name'] )
			);

			if ( $theme['categories'] ) {
				$html .= '<div class="wk-gf-theme-categories">';
				foreach ( $theme['categories'] as $category_id => $category_name ) {
					$category_html_id = $theme_html_id . '_' . sanitize_html_class( $category_id );
					// Indent the child categories, keyed category-{parent}-{id}, below their "Alles in ..." parent.
					$html .= sprintf(
						'<div class="wk-gf-category%1$s"><input type="checkbox" class="wk-gf-category-checkbox" id="%2$s" name="%3$s[%4$s][categories][]" value="%5$s" data-theme="%4$s" %6$s %7$s /><label for="%2$s">%8$s</label></div>',
						preg_match( '/^category-\d+-\d+$/', $category_id ) ? ' wk-gf-category--child' : '',
						esc_attr( $category_html_id ),
						esc_attr( $prefix ),
						esc_attr( $theme['id'] ),
						esc_attr( $category_id ),
						$checked && in_array( $category_id, $saved[ $theme['id'] ], true ) ? 'checked="checked"' : '',
						$checked ? '' : 'disabled="disabled"',
						esc_html( $category_name )
					);
				}
				$html .= '</div>';
			}

			$html .= '</div>';
		}

		$html .= '</div><p class="description wk-gf-themes-help">' . esc_html__( 'A checked theme without checked categories is subscribed whole.', 'wereldkundig-for-gravity-forms' ) . '</p>';

		return $html . $this->get_error_icon();
	}

	/**
	 * At least one theme.
	 *
	 * @param mixed $value Posted value.
	 * @return void
	 */
	public function do_validation( $value ) {
		if ( ! Addon::themes_from_posted( $value ) ) {
			$this->set_error( esc_html__( 'Check at least one theme.', 'wereldkundig-for-gravity-forms' ) );
		}
	}

	/**
	 * @param mixed $value Posted value.
	 * @return array<string,string[]>
	 */
	public function save( $value ) {
		return Addon::themes_from_posted( $value );
	}
}
