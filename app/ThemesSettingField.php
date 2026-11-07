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
	public $type = 'wk_themes';

	/**
	 * A bordered list with a row per theme, its categories in an indented column; a checked theme is highlighted.
	 */
	private const STYLES = '
		.wk-themes { border: 1px solid #d5d7e9; border-radius: 4px; background: #fff; overflow: hidden; }
		.wk-theme { padding: 10px 14px; border-top: 1px solid #ecedf8; transition: background-color .15s; }
		.wk-theme:first-child { border-top: 0; }
		.wk-theme:has(.wk-theme-checkbox:checked) { background: #f6f9fc; }
		.wk-theme__header, .wk-category { display: flex; align-items: center; gap: 10px; }
		.wk-themes label { margin: 0; cursor: pointer; line-height: 1.4; }
		.wk-theme__header label { font-weight: 500; color: #242748; }
		.wk-theme-categories { display: grid; gap: 6px; margin: 8px 0 2px 9px; padding-left: 20px; border-left: 2px solid #ecedf8; }
		.wk-theme:has(.wk-theme-checkbox:checked) .wk-theme-categories { border-left-color: #3e7da6; }
		.wk-category label { color: #3a3d5c; }
		.wk-category--child { margin-left: 26px; }
		.wk-category-checkbox:disabled, .wk-category-checkbox:disabled + label { opacity: .5; cursor: not-allowed; }
		.wk-themes-help { margin-top: 8px; }
	';

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

		$html = $this->get_description() . '<div class="wk-themes">';
		foreach ( $themes as $theme ) {
			$theme_html_id = 'wk_theme_' . sanitize_html_class( $theme['id'] );
			$checked       = array_key_exists( $theme['id'], $saved );

			$html .= '<div class="wk-theme">';
			$html .= sprintf(
				'<div class="wk-theme__header"><input type="checkbox" class="wk-theme-checkbox" id="%1$s" name="%2$s[%3$s][theme]" value="1" %4$s onclick="wkToggleThemeCategories(this);" /><label for="%1$s">%5$s</label></div>',
				esc_attr( $theme_html_id ),
				esc_attr( $prefix ),
				esc_attr( $theme['id'] ),
				$checked ? 'checked="checked"' : '',
				esc_html( $theme['name'] )
			);

			if ( $theme['categories'] ) {
				$html .= '<div class="wk-theme-categories">';
				foreach ( $theme['categories'] as $category_id => $category_name ) {
					$category_html_id = $theme_html_id . '_' . sanitize_html_class( $category_id );
					// Indent the child categories, keyed category-{parent}-{id}, below their "Alles in ..." parent.
					$html .= sprintf(
						'<div class="wk-category%1$s"><input type="checkbox" class="wk-category-checkbox" id="%2$s" name="%3$s[%4$s][categories][]" value="%5$s" data-theme="%4$s" %6$s %7$s /><label for="%2$s">%8$s</label></div>',
						preg_match( '/^category-\d+-\d+$/', $category_id ) ? ' wk-category--child' : '',
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

		$html .= '</div><p class="description wk-themes-help">' . esc_html__( 'A checked theme without checked categories is subscribed whole.', 'gravityformswereldkundig' ) . '</p>';
		$html .= '<style>' . self::STYLES . '</style>';

		// Categories only count for a checked theme; disabled ones aren't posted.
		$html .= '<script type="text/javascript">function wkToggleThemeCategories(theme) { var box = theme.closest(".wk-theme"); box && box.querySelectorAll(".wk-category-checkbox").forEach(function (category) { category.disabled = ! theme.checked; }); }</script>';

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
			$this->set_error( esc_html__( 'Check at least one theme.', 'gravityformswereldkundig' ) );
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
