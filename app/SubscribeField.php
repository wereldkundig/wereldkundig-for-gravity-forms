<?php

namespace Wereldkundig\GravityForms;

// GF_Field declares its methods and properties without types, so overrides are only typed in PHPDoc.
class SubscribeField extends \GF_Field {
	/** @var string */
	public $type = 'wk_subscribe';

	/** @var bool Render the checkbox checked on a fresh form. Off by default: a pre-checked box is no valid GDPR consent. */
	public $wk_default_checked = false;

	/**
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'Wereldkundig subscribe me', 'gravityformswereldkundig' );
	}

	/**
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Adds a subscribe checkbox. The Wereldkundig feed of the form chooses the themes it subscribes to.', 'gravityformswereldkundig' );
	}

	/**
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'gform-icon--mail';
	}

	/**
	 * @return array<string,string>
	 */
	public function get_form_editor_button() {
		return array(
			'group'       => 'standard_fields',
			'text'        => $this->get_form_editor_field_title(),
			'icon'        => $this->get_form_editor_field_icon(),
			'description' => $this->get_form_editor_field_description(),
		);
	}

	/**
	 * @return string[]
	 */
	public function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'description_setting',
			'css_class_setting',
			'rules_setting',
			'visibility_setting',
			'wk_feed_notice_setting',
			'wk_default_checked_setting',
		);
	}

	/**
	 * @return bool
	 */
	public function is_conditional_logic_supported() {
		return true;
	}

	/**
	 * @return void
	 */
	public function sanitize_settings() {
		parent::sanitize_settings();

		$this->wk_default_checked = ! empty( $this->wk_default_checked );
	}

	/**
	 * @return string
	 */
	public function get_field_css_class() {
		return 'gfield--type-choice';
	}

	/**
	 * Hide the default GF label; the checkbox label is the field label.
	 *
	 * @param string|array<mixed> $value
	 * @param bool                $force_frontend_label
	 * @param array<string,mixed> $form
	 * @return string
	 */
	public function get_field_content( $value, $force_frontend_label, $form ) {
		$validation_message = ( $this->failed_validation && ! empty( $this->validation_message ) )
			? sprintf( "<div class='gfield_description validation_message'>%s</div>", $this->validation_message )
			: '';
		$is_form_editor     = $this->is_form_editor();
		$is_entry_detail    = $this->is_entry_detail();
		$is_admin           = $is_form_editor || $is_entry_detail;

		$admin_buttons = $this->get_admin_buttons();
		$description   = $this->get_description( $this->description, 'gfield_description' );

		if ( $this->is_description_above( $form ) ) {
			$clear         = $is_admin ? "<div class='gf_clear'></div>" : '';
			$field_content = sprintf( '%s%s{FIELD}%s%s', $admin_buttons, $description, $validation_message, $clear );
		} else {
			$field_content = sprintf( '%s{FIELD}%s%s', $admin_buttons, $description, $validation_message );
		}

		return $field_content;
	}

	/**
	 * @param array<string,mixed>          $form
	 * @param string|array<mixed>          $value
	 * @param array<int|string,mixed>|null $entry
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id         = absint( $form['id'] );
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
		$id              = (int) $this->id;
		$field_id        = $is_entry_detail || $is_form_editor || 0 === $form_id ? "input_{$id}" : "input_{$form_id}_{$id}";
		$disabled_text   = $is_form_editor ? 'disabled="disabled"' : '';
		$checked         = $this->is_checked( $value ) || $this->renders_checked_by_default( $form_id, $value ) ? 'checked="checked"' : '';
		$tabindex        = $this->get_tabindex();
		$label           = $this->get_field_label( false, $value );
		$choice_html_id  = $is_entry_detail || $is_form_editor || 0 === $form_id ? $id . '_1' : $form_id . '_' . $id . '_1';

		$choice_markup = sprintf(
			"<div class='gchoice gchoice_%s'>
								<input class='gfield-choice-input' name='input_%s' type='checkbox'  value='1' %s id='choice_%s' %s %s />
								<label for='choice_%s' id='label_%s' class='gform-field-label gform-field-label--type-inline'>%s</label>
							</div>",
			esc_attr( $choice_html_id ),
			esc_attr( (string) $id ),
			$checked,
			esc_attr( $choice_html_id ),
			$tabindex,
			$disabled_text,
			esc_attr( $choice_html_id ),
			esc_attr( $choice_html_id ),
			wp_kses_post( $label )
		);

		return sprintf(
			"<div class='ginput_container ginput_container_checkbox'><div class='gfield_checkbox ' id='%s'>%s</div></div>",
			esc_attr( $field_id ),
			$choice_markup
		);
	}

	/**
	 * The "Checked by default" setting only applies to a fresh form. After a submit (a validation error, or
	 * going back a page) the visitor's own choice is rendered, so an unchecked box stays unchecked.
	 * Entries are never shown checked unless they were.
	 *
	 * @param int   $form_id
	 * @param mixed $value
	 */
	private function renders_checked_by_default( $form_id, $value ): bool {
		if ( empty( $this->wk_default_checked ) || $this->is_entry_detail() || ! rgblank( $value ) ) {
			return false;
		}

		return $this->is_form_editor() || rgblank( rgpost( 'is_submit_' . $form_id ) );
	}

	/**
	 * @param string|array<mixed>     $value
	 * @param array<int|string,mixed> $entry Before Gravity Forms 3 the currency; not used.
	 * @param bool                    $use_text
	 * @param string                  $format
	 * @param string                  $media
	 * @return string
	 */
	public function get_value_entry_detail( $value, $entry = array(), $use_text = false, $format = 'html', $media = 'screen' ) {
		if ( rgblank( $value ) ) {
			return '';
		}

		return esc_html__( 'Yes', 'gravityformswereldkundig' );
	}

	/**
	 * @param array<int|string,mixed> $entry
	 * @param string                  $input_id
	 * @param bool                    $use_text
	 * @param bool                    $is_csv
	 * @return string
	 */
	public function get_value_export( $entry, $input_id = '', $use_text = false, $is_csv = false ) {
		$value = rgar( $entry, (string) $this->id );

		return rgblank( $value ) ? '' : __( 'Yes', 'gravityformswereldkundig' );
	}

	public static function register_editor(): void {
		add_action( 'gform_field_standard_settings', array( self::class, 'editor_settings' ), 10, 2 );
		add_action( 'gform_editor_js', array( self::class, 'editor_js' ) );
		add_filter( 'gform_tooltips', array( self::class, 'tooltips' ) );
	}

	/**
	 * @param int|string $position
	 * @param int|string $form_id
	 */
	public static function editor_settings( $position, $form_id ): void {
		if ( 0 !== (int) $position ) {
			return;
		}

		$feeds = admin_url( 'admin.php?page=gf_edit_forms&view=settings&subview=wereldkundig&id=' . absint( $form_id ) );
		?>
		<li class="wk_feed_notice_setting field_setting">
			<label class="section_label"><?php esc_html_e( 'Themes', 'gravityformswereldkundig' ); ?></label>
			<?php // A WordPress info notice: blue (#72aee6) border on its lightest tint (#f0f6fc), with the default text color (#1d2327). ?>
			<div class="wk-feed-notice" role="note" style="padding: 8px 12px; border-left: 4px solid #72aee6; background: #f0f6fc; color: #1d2327;">
				<?php
				printf(
					/* translators: %s: "Wereldkundig feed", linked to the form's Wereldkundig feeds. */
					esc_html__( 'Choose the themes in a %s of this form, which also chooses this checkbox. Without a feed, nothing is sent.', 'gravityformswereldkundig' ),
					sprintf( '<a href="%s">%s</a>', esc_url( $feeds ), esc_html__( 'Wereldkundig feed', 'gravityformswereldkundig' ) )
				);
				?>
			</div>
		</li>
		<li class="wk_default_checked_setting field_setting">
			<input type="checkbox" id="field_wk_default_checked" onclick="SetFieldProperty('wk_default_checked', this.checked);" />
			<label for="field_wk_default_checked" class="inline">
				<?php esc_html_e( 'Checked by default', 'gravityformswereldkundig' ); ?>
				<?php gform_tooltip( 'wk_default_checked' ); ?>
			</label>
			<?php // A WordPress info notice: blue (#72aee6) border on its lightest tint (#f0f6fc), with the default text color (#1d2327). ?>
			<div class="wk-default-checked-warning" role="note" style="display: none; margin-top: 8px; padding: 8px 12px; border-left: 4px solid #72aee6; background: #f0f6fc; color: #1d2327;">
				<?php esc_html_e( 'A pre-checked box is not valid consent under the GDPR. Only use this when visitors expect to be subscribed by submitting this form.', 'gravityformswereldkundig' ); ?>
			</div>
		</li>
		<?php
	}

	public static function editor_js(): void {
		?>
		<script type="text/javascript">
			function SetDefaultValues_wk_subscribe(field) {
				field.label = <?php echo wp_json_encode( __( 'Wereldkundig subscribe me', 'gravityformswereldkundig' ) ); ?>;
			}

			jQuery(document).on('gform_load_field_settings', function (evt, field) {
				if (field.type !== 'wk_subscribe') {
					return;
				}
				// The feed only asks which checkbox when the form has several; with one, it uses that one.
				var subscribeFields = window.form ? form.fields.filter(function (formField) { return formField.type === 'wk_subscribe'; }).length : 0;
				jQuery('.wk_feed_notice_setting').toggle(subscribeFields > 1);
				jQuery('#field_wk_default_checked').prop('checked', !! field.wk_default_checked);
				jQuery('.wk-default-checked-warning').toggle(!! field.wk_default_checked);
			});

			if (window.gform) {
				gform.addAction('gform_post_set_field_property', function (name, field) {
					if (name === 'label' && field.type === 'wk_subscribe') {
						jQuery('#field_' + field.id + ' .gfield_checkbox label').text(field.label);
					}
					if (name === 'wk_default_checked' && field.type === 'wk_subscribe') {
						jQuery('#field_' + field.id + ' .gfield_checkbox input').prop('checked', !! field.wk_default_checked);
						jQuery('.wk-default-checked-warning').toggle(!! field.wk_default_checked);
					}
				});
			}
		</script>
		<?php
	}

	/**
	 * @param array<string,string> $tooltips
	 * @return array<string,string>
	 */
	public static function tooltips( $tooltips ): array {
		$tooltips['wk_default_checked'] = '<h6>' . esc_html__( 'Checked by default', 'gravityformswereldkundig' ) . '</h6>' . esc_html__( 'Shows the checkbox already checked when the form loads. Visitors can still uncheck it; that choice is kept when the form is shown again after a validation error.', 'gravityformswereldkundig' );

		return $tooltips;
	}

	private function is_checked( mixed $value ): bool {
		if ( is_array( $value ) ) {
			return in_array( '1', $value, true );
		}

		return ! rgblank( $value ) && '0' !== (string) $value;
	}
}
