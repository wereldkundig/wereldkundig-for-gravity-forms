<?php

use Wereldkundig\GravityForms\SubscribeField;

function wk_gf_editor_stubs(bool $can_manage = true): void {
    Brain\Monkey\Functions\when('esc_html_e')->echoArg();
    Brain\Monkey\Functions\when('esc_html')->returnArg();
    Brain\Monkey\Functions\when('esc_url')->returnArg();
    Brain\Monkey\Functions\when('gform_tooltip')->justReturn('');
    Brain\Monkey\Functions\when('admin_url')->alias(static fn ($path): string => 'https://site.test/wp-admin/' . $path);
    Brain\Monkey\Functions\when('current_user_can')->justReturn($can_manage);
    Brain\Monkey\Functions\when('sanitize_html_class')->alias(static fn ($value): string => preg_replace('/[^A-Za-z0-9_-]/', '', (string) $value));
}

it('uses the expected Gravity Forms field metadata', function () {
    $field = new SubscribeField();

    expect($field->type)->toBe('wk_gf_subscribe')
        ->and($field->get_form_editor_field_title())->toBe('Wereldkundig subscribe me')
        ->and($field->get_field_css_class())->toBe('gfield--type-choice')
        ->and($field->is_conditional_logic_supported())->toBeTrue();
});

it('renders Gravity Forms-compatible consent checkbox markup', function () {
    $field        = new SubscribeField();
    $field->id    = 5;
    $field->label = 'Subscribe me';

    $html = $field->get_field_input(['id' => 7], '1');

    expect($html)->toContain("class='ginput_container ginput_container_checkbox'")
        ->toContain("class='gchoice gchoice_7_5_1'")
        ->toContain("name='input_5'")
        ->toContain("id='choice_7_5_1'")
        ->toContain("checked=\"checked\"")
        ->toContain('Subscribe me');
});

it('exports consent as Yes only when checked', function () {
    $field     = new SubscribeField();
    $field->id = 5;

    expect($field->get_value_export(['5' => '1']))->toBe('Yes')
        ->and($field->get_value_export(['5' => '']))->toBe('');
});

it('points to the form\'s Wereldkundig feeds for the themes, without calling the API', function () {
    wk_gf_editor_stubs();
    Brain\Monkey\Functions\expect('get_option')->never();
    Brain\Monkey\Functions\expect('wp_remote_request')->never();

    ob_start();
    SubscribeField::editor_settings(0, 3);
    $html = ob_get_clean();

    expect($html)->toContain('<a href="https://site.test/wp-admin/admin.php?page=gf_edit_forms&view=settings&subview=wereldkundig&id=3">Wereldkundig feed</a>')
        ->not->toContain('wk-gf-theme-checkbox');
});

it('keeps the checked by default setting a boolean when the form is saved', function () {
    $field                     = new SubscribeField();
    $field->wk_gf_default_checked = '1';

    $field->sanitize_settings();

    expect($field->wk_gf_default_checked)->toBeTrue();
});

function wk_gf_default_checked_field(): SubscribeField {
    $field                     = new SubscribeField();
    $field->id                 = 5;
    $field->label              = 'Subscribe me';
    $field->wk_gf_default_checked = true;

    return $field;
}

it('renders the checkbox checked on a fresh form when checked by default', function () {
    Brain\Monkey\Functions\when('rgpost')->justReturn('');

    expect(wk_gf_default_checked_field()->get_field_input(['id' => 7], ''))->toContain('checked="checked"');
});

it('keeps the checkbox unchecked by default unless the setting is on', function () {
    Brain\Monkey\Functions\when('rgpost')->justReturn('');
    $field        = new SubscribeField();
    $field->id    = 5;
    $field->label = 'Subscribe me';

    expect($field->get_field_input(['id' => 7], ''))->not->toContain('checked="checked"');
});

it('keeps an unchecked box unchecked after a submit, even when checked by default', function () {
    Brain\Monkey\Functions\when('rgpost')->alias(static fn ($name): string => 'is_submit_7' === $name ? '1' : '');

    expect(wk_gf_default_checked_field()->get_field_input(['id' => 7], ''))->not->toContain('checked="checked"');
});

it('never shows an unchecked entry as checked by default', function () {
    Brain\Monkey\Functions\when('rgpost')->justReturn('');
    $field = Mockery::mock(SubscribeField::class)->makePartial();
    $field->shouldReceive('is_entry_detail')->andReturn(true);
    $field->id                 = 5;
    $field->label              = 'Subscribe me';
    $field->wk_gf_default_checked = true;

    expect($field->get_field_input(['id' => 7], ''))->not->toContain('checked="checked"');
});
