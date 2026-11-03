<?php

use Wereldkundig\GravityForms\SubscribeField;

function wk_editor_stubs(bool $can_manage = true): void {
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

    expect($field->type)->toBe('wk_subscribe')
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

it('asks to configure the API in the editor when there are no credentials, without calling it', function () {
    Brain\Monkey\Functions\when('get_option')->justReturn([]);
    wk_editor_stubs();
    Brain\Monkey\Functions\expect('wp_remote_request')->never();
    $GLOBALS['wk_test_filter_values']['wk_api_use_mock'] = false;

    ob_start();
    SubscribeField::editor_settings(0, 1);
    $html = ob_get_clean();

    expect($html)->toContain('Configure Wereldkundig API settings')
        ->not->toContain('No Wereldkundig themes available');
});

it('says no themes are available in the editor when configured but nothing is cached, without calling the API', function () {
    Brain\Monkey\Functions\when('get_option')->justReturn(['api_url' => 'platform.example.nl', 'api_auth' => 'abc']);
    wk_editor_stubs();
    Brain\Monkey\Functions\expect('wp_remote_request')->never();
    $GLOBALS['wk_test_filter_values']['wk_api_use_mock'] = false;

    ob_start();
    SubscribeField::editor_settings(0, 1);
    $html = ob_get_clean();

    expect($html)->toContain('No Wereldkundig themes available')
        ->not->toContain('Configure Wereldkundig API settings');
});

it('lists the cached themes in the editor', function () {
    Brain\Monkey\Functions\when('get_option')->alias(static function ($name) {
        return Wereldkundig\GravityForms\Api\Client::FIELDS_OPTION === $name
            ? ['key' => md5('https://platform.example.nl/wp-json/wereldkundig|abc'), 'response' => ['options' => ['categories' => [['id' => 'theme-9', 'name' => 'Cached']]]]]
            : ['api_url' => 'platform.example.nl', 'api_auth' => 'abc'];
    });
    wk_editor_stubs();
    Brain\Monkey\Functions\expect('wp_remote_request')->never();
    $GLOBALS['wk_test_filter_values']['wk_api_use_mock'] = false;

    ob_start();
    SubscribeField::editor_settings(0, 1);
    $html = ob_get_clean();

    expect($html)->toContain('class="wk-theme-checkbox" id="wk_theme_theme-9" value="theme-9"')
        ->toContain('>Cached</label>')
        ->not->toContain('wk-theme-notice');
});

it('shows the categories of each theme, children indented', function () {
    Brain\Monkey\Functions\when('get_option')->alias(static function ($name) {
        return Wereldkundig\GravityForms\Api\Client::FIELDS_OPTION === $name
            ? ['key' => md5('https://platform.example.nl/wp-json/wereldkundig|abc'), 'response' => ['options' => ['categories' => [[
                'id'         => 'theme-13281',
                'name'       => 'Gebieden',
                'categories' => [['id' => 'select-all-36', 'name' => 'Alles in Gebieden', 'children' => [['id' => 'category-36-37', 'name' => 'Bergschenhoek']]]],
            ]]]]]
            : ['api_url' => 'platform.example.nl', 'api_auth' => 'abc'];
    });
    wk_editor_stubs();
    $GLOBALS['wk_test_filter_values']['wk_api_use_mock'] = false;

    ob_start();
    SubscribeField::editor_settings(0, 1);
    $html = ob_get_clean();

    expect($html)->toContain('value="select-all-36" data-theme="theme-13281"')
        ->toContain('value="category-36-37" data-theme="theme-13281"')
        ->toContain('<div style="margin-left: 20px;">')
        ->toContain('>Alles in Gebieden</label>');
});

it('reads only valid theme and category IDs of a field', function () {
    expect(SubscribeField::theme_ids_of((object) ['wk_theme_ids' => ['theme-1', 'theme-1', '', '<b>']]))->toBe(['theme-1'])
        ->and(SubscribeField::theme_ids_of((object) []))->toBe([])
        ->and(SubscribeField::category_ids_of((object) ['wk_category_ids' => ['category-1', 'category-1-2', 'select-all-3', 'theme-1', 'x']]))->toBe(['category-1', 'category-1-2', 'select-all-3']);
});

it('keeps only theme and category IDs when the form is saved', function () {
    $field                     = new SubscribeField();
    $field->wk_theme_ids       = ['theme-2', '"><script>'];
    $field->wk_category_ids    = ['category-5', '"><script>'];
    $field->wk_default_checked = '1';

    $field->sanitize_settings();

    expect($field->wk_theme_ids)->toBe(['theme-2'])
        ->and($field->wk_category_ids)->toBe(['category-5'])
        ->and($field->wk_default_checked)->toBeTrue();
});

it('links the editor notice to the Wereldkundig settings page', function () {
    Brain\Monkey\Functions\when('get_option')->justReturn([]);
    wk_editor_stubs();
    $GLOBALS['wk_test_filter_values']['wk_api_use_mock'] = false;

    ob_start();
    SubscribeField::editor_settings(0, 1);
    $html = ob_get_clean();

    expect($html)->toContain('<a href="https://site.test/wp-admin/admin.php?page=gf_settings&subview=wereldkundig" style="color: #3f0001;">Configure Wereldkundig API settings</a>')
        ->toContain('border-left: 4px solid #d63638; background: #fcf0f1; color: #3f0001;');
});

it('shows the editor notice without a link to users who cannot open the settings', function () {
    Brain\Monkey\Functions\when('get_option')->justReturn([]);
    wk_editor_stubs(false);
    $GLOBALS['wk_test_filter_values']['wk_api_use_mock'] = false;

    ob_start();
    SubscribeField::editor_settings(0, 1);
    $html = ob_get_clean();

    expect($html)->toContain('Configure Wereldkundig API settings')
        ->not->toContain('<a href=');
});

function wk_default_checked_field(): SubscribeField {
    $field                     = new SubscribeField();
    $field->id                 = 5;
    $field->label              = 'Subscribe me';
    $field->wk_default_checked = true;

    return $field;
}

it('renders the checkbox checked on a fresh form when checked by default', function () {
    Brain\Monkey\Functions\when('rgpost')->justReturn('');

    expect(wk_default_checked_field()->get_field_input(['id' => 7], ''))->toContain('checked="checked"');
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

    expect(wk_default_checked_field()->get_field_input(['id' => 7], ''))->not->toContain('checked="checked"');
});

it('never shows an unchecked entry as checked by default', function () {
    Brain\Monkey\Functions\when('rgpost')->justReturn('');
    $field = Mockery::mock(SubscribeField::class)->makePartial();
    $field->shouldReceive('is_entry_detail')->andReturn(true);
    $field->id                 = 5;
    $field->label              = 'Subscribe me';
    $field->wk_default_checked = true;

    expect($field->get_field_input(['id' => 7], ''))->not->toContain('checked="checked"');
});
