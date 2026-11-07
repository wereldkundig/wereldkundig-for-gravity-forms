<?php

use Brain\Monkey\Functions;
use Wereldkundig\GravityForms\Addon;

function wk_addon_choices(): array {
    return [
        ['label' => 'Select a Value', 'value' => ''],
        'fields' => [
            'label'   => 'Form Fields',
            'choices' => [
                ['label' => 'Name (Full Name)', 'value' => '1', 'type' => 'name'],
                ['label' => 'Name (Prefix)', 'value' => '1.2', 'type' => 'name'],
                ['label' => 'Name (First)', 'value' => '1.3', 'type' => 'name'],
                ['label' => 'Name (Middle)', 'value' => '1.4', 'type' => 'name'],
                ['label' => 'Name (Last)', 'value' => '1.6', 'type' => 'name'],
                ['label' => 'Email', 'value' => '2', 'type' => 'email'],
            ],
        ],
    ];
}

beforeEach(function () {
    Mockery::mock('alias:GFAPI')->shouldReceive('get_form')->andReturn([
        'fields' => [
            (object) [
                'id'     => 1,
                'type'   => 'name',
                'inputs' => [
                    ['id' => '1.2', 'isHidden' => true],
                    ['id' => '1.3'],
                    ['id' => '1.4', 'isHidden' => true],
                    ['id' => '1.6'],
                ],
            ],
            (object) ['id' => 2, 'type' => 'email', 'inputs' => null],
        ],
    ]);
});

it('removes switched-off name parts from the field map choices', function () {
    Functions\when('rgget')->justReturn('wereldkundig');

    $choices = (new Addon())->hide_inactive_name_inputs(wk_addon_choices(), 5);

    expect(array_column($choices['fields']['choices'], 'value'))->toBe(['1', '1.3', '1.6', '2'])
        ->and($choices[0]['value'])->toBe('');
});

it('leaves the field map choices of other add-ons alone', function () {
    Functions\when('rgget')->justReturn('gravityformsmailchimp');

    expect((new Addon())->hide_inactive_name_inputs(wk_addon_choices(), 5))->toBe(wk_addon_choices());
});

it('shows an error instead of the field map when no subscriber fields are available', function () {
    $addon = Mockery::mock(Addon::class)->makePartial();
    $addon->shouldReceive('fields_for_feed_mapping')->andReturn([]);
    $addon->shouldReceive('get_default_feed_name')->andReturn('Wereldkundig Feed 1');

    $fields = array_column($addon->feed_settings_fields()[0]['fields'], null, 'name');

    expect($fields)->not->toHaveKey('mapped_fields')
        ->and($fields['mapped_fields_unavailable']['type'])->toBe('html');
});

it('keeps the saved field mapping when a feed is saved without subscriber fields', function () {
    $addon = Mockery::mock(Addon::class)->makePartial();
    $addon->shouldReceive('fields_for_feed_mapping')->andReturn([]);
    $addon->shouldReceive('get_feed')->with(3)->andReturn(['meta' => [
        'feedName'                  => 'Old name',
        'mapped_fields_middle_name' => '1.4',
        'mapped_fields_company'     => '7',
    ]]);

    expect($addon->save_feed_settings(3, 5, ['feedName' => 'New name']))->toBe([
        'feedName'                  => 'New name',
        'mapped_fields_middle_name' => '1.4',
        'mapped_fields_company'     => '7',
    ]);
});

it('saves the posted field mapping as is when subscriber fields are available', function () {
    $addon = Mockery::mock(Addon::class)->makePartial();
    $addon->shouldReceive('fields_for_feed_mapping')->andReturn([['name' => 'email', 'label' => 'E-mail', 'required' => false]]);
    $addon->shouldNotReceive('get_feed');

    expect($addon->save_feed_settings(3, 5, ['feedName' => 'New name']))->toBe(['feedName' => 'New name']);
});

it('removes the cached subscribe fields on uninstall', function () {
    Functions\expect('delete_option')->once()->with(Wereldkundig\GravityForms\Api\Client::FIELDS_OPTION);
    Functions\expect('delete_option')->once()->with(Wereldkundig\GravityForms\Api\Client::FIELDS_ERROR_OPTION);

    expect((new Addon())->uninstall())->toBeTrue();
});

it('rejects a plain http platform URL in the settings', function () {
    $field = Mockery::mock();
    $field->shouldReceive('set_error')->once()->with(Mockery::pattern('/https/'));

    (new Addon())->validate_api_url($field, 'http://platform.example.nl');
});

it('accepts an https platform URL in the settings', function () {
    $field = Mockery::mock();
    $field->shouldNotReceive('set_error');

    (new Addon())->validate_api_url($field, 'https://platform.example.nl');
    (new Addon())->validate_api_url($field, '');
});

it('derives the plugin path from the plugin folder', function () {
    Functions\when('plugin_basename')->alias(static fn ($file): string => 'renamed-folder/' . basename($file));

    $addon = new Addon();
    $path  = (new ReflectionProperty(Addon::class, '_path'))->getValue($addon);

    expect($path)->toBe('renamed-folder/wereldkundig-for-gravity-forms.php');
});

it('has its own capabilities for the API settings and uninstall', function () {
    Functions\when('plugin_basename')->alias(static fn ($file): string => basename($file));

    $addon = new Addon();
    $cap   = static fn (string $name) => (new ReflectionProperty(Addon::class, $name))->getValue($addon);

    expect($cap('_capabilities_settings_page'))->toBe('gravityforms_wereldkundig')
        ->and($cap('_capabilities_uninstall'))->toBe('gravityforms_wereldkundig_uninstall')
        ->and($cap('_capabilities'))->toBe(['gravityforms_wereldkundig', 'gravityforms_wereldkundig_uninstall']);
});

it('masks the Basic Auth code without revealing its end or length', function () {
    $code = base64_encode('api-user:secret-password');

    expect(Addon::mask_secret($code))->toBe(substr($code, 0, 4) . str_repeat('*', 16))
        ->and(Addon::mask_secret(base64_encode('a:b')))->toBe(substr(base64_encode('a:b'), 0, 1) . str_repeat('*', 16))
        ->and(Addon::mask_secret(''))->toBe('')
        ->and(Addon::mask_secret($code))->not->toContain(substr($code, -4));
});

it('keeps the saved code when the mask is posted back', function () {
    $code = base64_encode('api-user:secret-password');

    expect(Addon::keep_secret(Addon::mask_secret($code), $code))->toBe($code)
        ->and(Addon::keep_secret(' ' . base64_encode('new:code') . ' ', $code))->toBe(base64_encode('new:code'))
        ->and(Addon::keep_secret('', $code))->toBe('');
});

it('falls back to a password field when the masked field type is unavailable', function () {
    Functions\when('plugin_basename')->alias(static fn ($file): string => basename($file));
    Functions\when('wp_kses_post')->returnArg();

    $fields = array_column((new Addon())->plugin_settings_fields()[0]['fields'], null, 'name');

    expect($fields['api_auth']['type'])->toBe('text')
        ->and($fields['api_auth']['input_type'])->toBe('password')
        ->and($fields['api_auth'])->not->toHaveKey('readonly');
});

it('stores the API error when saving the settings fetches nothing', function () {
    Functions\when('plugin_basename')->alias(static fn ($file): string => basename($file));
    Functions\when('get_option')->justReturn(['api_url' => 'platform.example.nl', 'api_auth' => 'abc']);
    Functions\when('trailingslashit')->alias(static fn ($value): string => rtrim((string) $value, '/') . '/');
    Functions\when('wp_remote_request')->justReturn(new WP_Error('http_request_failed', 'cURL error 28: Operation timed out'));
    $GLOBALS['wk_test_filter_values']['wk_gf_api_use_mock'] = false;

    $stored = null;
    Functions\when('update_option')->alias(static function ($name, $value) use (&$stored) {
        if (Wereldkundig\GravityForms\Api\Client::FIELDS_ERROR_OPTION === $name) {
            $stored = $value;
        }

        return true;
    });
    Functions\expect('delete_option')->never();

    (new Addon())->update_plugin_settings(['api_url' => 'platform.example.nl', 'api_auth' => 'abc']);

    expect($stored['message'])->toBe('cURL error 28: Operation timed out')
        ->and($stored['code'])->toBe('http_request_failed')
        ->and($stored['time'])->toBeInt();
});

it('removes the stored API error once fetching succeeds', function () {
    Functions\when('plugin_basename')->alias(static fn ($file): string => basename($file));
    Functions\when('get_option')->justReturn(['api_url' => 'platform.example.nl', 'api_auth' => 'abc']);
    Functions\when('update_option')->justReturn(true);
    $GLOBALS['wk_test_filter_values']['wk_gf_api_use_mock'] = true;
    Functions\expect('delete_option')->once()->with(Wereldkundig\GravityForms\Api\Client::FIELDS_ERROR_OPTION);

    (new Addon())->update_plugin_settings(['api_url' => 'platform.example.nl', 'api_auth' => 'abc']);
});

it('shows the stored API error on the settings page until it is removed', function () {
    Functions\when('plugin_basename')->alias(static fn ($file): string => basename($file));
    Functions\when('esc_html')->returnArg();
    Functions\when('wp_date')->alias(static fn ($format, $time): string => gmdate('Y-m-d H:i', $time));
    $error = ['message' => 'Bad <b>gateway</b>', 'code' => 'wk_api_http', 'time' => 1767225600];
    Functions\when('get_option')->alias(static fn ($name) => Wereldkundig\GravityForms\Api\Client::FIELDS_ERROR_OPTION === $name ? $error : '');

    $html = (new Addon())->api_status_notice();

    expect($html)->toContain('Settings saved, but the Wereldkundig API call failed.')
        ->toContain('Last attempt 2026-01-01 00:00: Bad <b>gateway</b>')
        ->toContain('save the settings to try again');

    Functions\when('get_option')->justReturn(false);

    expect((new Addon())->api_status_notice())->toBe('');
});

it('shows the connection status from the cache, without calling the API', function () {
    Functions\when('plugin_basename')->alias(static fn ($file): string => basename($file));
    Functions\when('esc_html')->returnArg();
    Functions\when('_n')->alias(static fn ($single, $plural, $n): string => 1 === $n ? $single : $plural);
    Functions\when('wp_date')->alias(static fn ($format, $time): string => gmdate('Y-m-d H:i', $time));
    Functions\expect('wp_remote_request')->never();
    $GLOBALS['wk_test_filter_values']['wk_gf_api_use_mock'] = false;
    Functions\when('get_option')->alias(static function ($name) {
        return match ($name) {
            Wereldkundig\GravityForms\Api\Client::FIELDS_ERROR_OPTION => false,
            Wereldkundig\GravityForms\Api\Client::FIELDS_OPTION       => [
                'key'        => md5('https://platform.example.nl/wp-json/wereldkundig|abc'),
                'fetched_at' => 1767225600,
                'response'   => ['options' => ['categories' => [['id' => 'theme-1', 'name' => 'A'], ['id' => 'theme-2', 'name' => 'B']]]],
            ],
            'date_format', 'time_format' => '',
            default => ['api_url' => 'platform.example.nl', 'api_auth' => 'abc'],
        };
    });

    $html = (new Addon())->api_status_notice();

    expect($html)->toContain('Successfully connected to Wereldkundig.')
        ->toContain('Platform: platform.example.nl')
        ->toContain('Connected, 2 themes (fetched 2026-01-01 00:00).');
});

it('shows no connection status without a cache for the current credentials', function () {
    Functions\when('plugin_basename')->alias(static fn ($file): string => basename($file));
    Functions\expect('wp_remote_request')->never();
    $GLOBALS['wk_test_filter_values']['wk_gf_api_use_mock'] = false;
    Functions\when('get_option')->alias(static function ($name) {
        return match ($name) {
            Wereldkundig\GravityForms\Api\Client::FIELDS_ERROR_OPTION => false,
            Wereldkundig\GravityForms\Api\Client::FIELDS_OPTION       => ['key' => md5('other credentials'), 'fetched_at' => 1, 'response' => []],
            default => ['api_url' => 'platform.example.nl', 'api_auth' => 'abc'],
        };
    });

    expect((new Addon())->api_status_notice())->toBe('');
});

it('does not retry feed errors that a retry cannot fix', function (WP_Error $error, bool $retry) {
    Functions\when('plugin_basename')->alias(static fn ($file): string => basename($file));

    expect((new Addon())->is_feed_error_retryable(true, $error, [], [], []))->toBe($retry);
})->with([
    'invalid theme'      => [new WP_Error('wk_theme_invalid', ''), false],
    'invalid email'      => [new WP_Error('wk_email_invalid', ''), false],
    'no checkbox'        => [new WP_Error('wk_subscribe_field_missing', ''), false],
    'member exists'      => [new WP_Error('member-exists', '', ['status' => 409]), false],
    'rate limited'       => [new WP_Error('wk_api_http', '', ['status' => 429]), true],
    'server error'       => [new WP_Error('wk_api_http', '', ['status' => 502]), true],
    'timeout'            => [new WP_Error('http_request_failed', 'cURL error 28'), true],
    'themes unavailable' => [new WP_Error('wk_themes_unavailable', ''), true],
]);

it('links the configure message to the settings for users who may open them', function () {
    Functions\when('plugin_basename')->alias(static fn ($file): string => basename($file));
    Functions\when('esc_url')->returnArg();
    Functions\when('admin_url')->alias(static fn ($path): string => 'https://site.test/wp-admin/' . $path);

    Functions\when('current_user_can')->justReturn(true);
    expect((new Addon())->configure_addon_message())->toContain('<a href="https://site.test/wp-admin/admin.php?page=gf_settings&subview=wereldkundig">Wereldkundig settings</a>');

    Functions\when('current_user_can')->justReturn(false);
    expect((new Addon())->configure_addon_message())->not->toContain('<a ')->toContain('Wereldkundig settings');
});

it('shows the stored API error on the feed settings', function () {
    Functions\when('plugin_basename')->alias(static fn ($file): string => basename($file));
    // Nothing cached and no credentials: the mapping fetch stays offline.
    Functions\when('get_option')->justReturn(false);
    Functions\when('update_option')->justReturn(true);
    Functions\when('delete_option')->justReturn(true);
    Functions\when('esc_html')->returnArg();

    $addon  = Mockery::mock(Addon::class)->makePartial();
    $addon->shouldReceive('get_default_feed_name')->andReturn('Feed');
    $fields = $addon->feed_settings_fields()[0]['fields'];

    expect($fields[0])->toBe(['name' => 'api_error_notice', 'type' => 'html', 'html' => [$addon, 'api_error_notice']])
        ->and($addon->api_error_notice())->toBe('');
});

it('offers the form\'s subscribe checkboxes in the feed settings', function () {
    Functions\when('get_option')->justReturn(false);
    Functions\when('esc_html')->returnArg();

    $addon = Mockery::mock(Addon::class)->makePartial();
    $addon->shouldReceive('get_default_feed_name')->andReturn('Feed');
    $addon->shouldReceive('fields_for_feed_mapping')->andReturn([]);
    $addon->shouldReceive('get_current_form')->andReturn(['fields' => [
        wk_addon_field(5, 'wk_subscribe', ['label' => 'Nieuwsbrief']),
        wk_addon_field(6, 'email', ['label' => 'E-mail']),
        wk_addon_field(7, 'wk_subscribe', ['label' => 'Evenementen', 'adminLabel' => 'Events']),
    ]]);

    $fields = array_column($addon->feed_settings_fields()[0]['fields'], null, 'name');

    expect($fields['subscribe_field']['type'])->toBe('select')
        ->and($fields['subscribe_field']['required'])->toBeTrue()
        ->and($fields['subscribe_field']['choices'])->toBe([
            ['label' => 'Select a checkbox', 'value' => ''],
            ['label' => 'Nieuwsbrief (field 5)', 'value' => '5'],
            ['label' => 'Events (field 7)', 'value' => '7'],
        ]);
});

it('uses the only subscribe checkbox of the form without a select', function () {
    Functions\when('get_option')->justReturn(false);
    Functions\when('esc_html')->returnArg();

    $addon = Mockery::mock(Addon::class)->makePartial();
    $addon->shouldReceive('get_default_feed_name')->andReturn('Feed');
    $addon->shouldReceive('fields_for_feed_mapping')->andReturn([]);
    $addon->shouldReceive('get_current_form')->andReturn(['fields' => [wk_addon_field(5, 'wk_subscribe', ['label' => 'Nieuwsbrief'])]]);

    $fields = array_column($addon->feed_settings_fields()[0]['fields'], null, 'name');

    expect($fields['subscribe_field']['type'])->toBe('hidden')
        ->and($fields['subscribe_field']['default_value'])->toBe('5')
        // Stored on save, also over a field ID from before, so the feed keeps it when another checkbox is added.
        ->and(($fields['subscribe_field']['save_callback'])(null, '9'))->toBe('5')
        ->and($fields['subscribe_field_only']['html'])->toContain('<code>Nieuwsbrief (field 5)</code>, the only subscribe checkbox');
});

it('says so in the feed settings when the form has no subscribe checkbox or no themes are available', function () {
    Functions\when('get_option')->justReturn(false);
    Functions\when('esc_html')->returnArg();

    $addon = Mockery::mock(Addon::class)->makePartial();
    $addon->shouldReceive('get_default_feed_name')->andReturn('Feed');
    $addon->shouldReceive('fields_for_feed_mapping')->andReturn([]);
    $addon->shouldReceive('get_current_form')->andReturn(['fields' => [wk_addon_field(6, 'email')]]);

    $fields = array_column($addon->feed_settings_fields()[0]['fields'], null, 'name');

    expect($fields)->not->toHaveKey('subscribe_field')
        ->not->toHaveKey('wk_themes')
        ->and($fields['subscribe_field_unavailable']['html'])->toContain('no Wereldkundig subscribe me field')
        ->and($fields['wk_themes_unavailable']['html'])->toContain('Could not load the themes');
});

it('keeps the saved themes when a feed is saved without themes from the platform', function () {
    Functions\when('get_option')->justReturn(false);
    Functions\when('update_option')->justReturn(true);
    Functions\when('delete_option')->justReturn(true);
    // No credentials and no mock: no themes.
    $GLOBALS['wk_test_filter_values']['wk_gf_api_use_mock'] = false;

    $addon = Mockery::mock(Addon::class)->makePartial();
    $addon->shouldReceive('fields_for_feed_mapping')->andReturn([['name' => 'email', 'label' => 'E-mail', 'required' => false]]);
    $addon->shouldReceive('get_feed')->with(3)->andReturn(['meta' => [
        'wk_themes'           => ['theme-1' => ['category-3']],
        'mapped_fields_email' => '1',
    ]]);

    expect($addon->save_feed_settings(3, 5, ['feedName' => 'New name', 'mapped_fields_email' => '2']))->toBe([
        'feedName'            => 'New name',
        'mapped_fields_email' => '2',
        'wk_themes'           => ['theme-1' => ['category-3']],
    ]);
});

it('reads the posted themes, only checked themes and valid IDs', function () {
    expect(Addon::themes_from_posted([
        'theme-1'     => ['theme' => '1'],
        'theme-2'     => ['theme' => '1', 'categories' => ['category-3', 'category-3', 'select-all-4', 'category-5-6', 'theme-1', '"><b>']],
        // Unchecked: its categories don't count.
        'theme-3'     => ['categories' => ['category-7']],
        '"><script>'  => ['theme' => '1'],
    ]))->toBe([
        'theme-1' => [],
        'theme-2' => ['category-3', 'select-all-4', 'category-5-6'],
    ])
        ->and(Addon::themes_from_posted(''))->toBe([])
        ->and(Addon::themes_from_posted(null))->toBe([]);
});

it('reads the saved themes of a feed', function () {
    expect(Addon::feed_themes(['meta' => ['wk_themes' => ['theme-1' => [], 'theme-2' => ['category-3', 'x'], '<b>' => []]]]))->toBe(['theme-1' => [], 'theme-2' => ['category-3']])
        ->and(Addon::feed_themes(['meta' => []]))->toBe([])
        ->and(Addon::feed_themes(['meta' => ['wk_themes' => 'theme-1']]))->toBe([]);
});

it('shows the feed\'s theme names in the feed list, from the cache only', function () {
    Functions\when('get_option')->alias(static function ($name) {
        return Wereldkundig\GravityForms\Api\Client::FIELDS_OPTION === $name
            ? ['key' => md5('https://platform.example.nl/wp-json/wereldkundig|abc'), 'response' => ['options' => ['categories' => [['id' => 'theme-1', 'name' => 'Nieuwsbrief']]]]]
            : ['api_url' => 'platform.example.nl', 'api_auth' => 'abc'];
    });
    Functions\when('esc_html')->returnArg();
    Functions\when('trailingslashit')->alias(static fn ($value): string => rtrim((string) $value, '/') . '/');
    Functions\when('plugin_basename')->alias(static fn ($file): string => basename($file));
    Functions\expect('wp_remote_request')->never();
    $GLOBALS['wk_test_filter_values']['wk_gf_api_use_mock'] = false;

    expect((new Addon())->get_column_value_wk_themes(['meta' => ['wk_themes' => ['theme-1' => [], 'theme-9' => []]]]))->toBe('Nieuwsbrief, theme-9');
});

function wk_addon_field(int $id, string $type, array $properties = []): GF_Field {
    $field = new GF_Field();
    foreach (array_merge(['id' => $id, 'type' => $type, 'label' => ''], $properties) as $key => $value) {
        $field->{$key} = $value;
    }

    return $field;
}
