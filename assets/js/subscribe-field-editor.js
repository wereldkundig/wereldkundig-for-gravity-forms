(function ($) {
	'use strict';

	window.SetDefaultValues_wk_gf_subscribe = function (field) {
		field.label = wkGfSubscribeFieldEditor.defaultLabel;
	};

	$(document).on('gform_load_field_settings', function (evt, field) {
		if (field.type !== 'wk_gf_subscribe') {
			return;
		}

		// The feed only asks which checkbox when the form has several; with one, it uses that one.
		var subscribeFields = window.form
			? form.fields.filter(function (formField) {
					return formField.type === 'wk_gf_subscribe';
			  }).length
			: 0;

		$('.wk_gf_feed_notice_setting').toggle(subscribeFields > 1);
		$('#field_wk_gf_default_checked').prop('checked', !!field.wk_gf_default_checked);
		$('.wk-gf-default-checked-warning').toggle(!!field.wk_gf_default_checked);
	});

	if (window.gform) {
		gform.addAction('gform_post_set_field_property', function (name, field) {
			if (name === 'label' && field.type === 'wk_gf_subscribe') {
				$('#field_' + field.id + ' .gfield_checkbox label').text(field.label);
			}

			if (name === 'wk_gf_default_checked' && field.type === 'wk_gf_subscribe') {
				$('#field_' + field.id + ' .gfield_checkbox input').prop('checked', !!field.wk_gf_default_checked);
				$('.wk-gf-default-checked-warning').toggle(!!field.wk_gf_default_checked);
			}
		});
	}
})(jQuery);
