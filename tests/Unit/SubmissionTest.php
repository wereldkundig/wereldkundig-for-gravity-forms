<?php

use Brain\Monkey\Functions;
use Wereldkundig\GravityForms\Addon;
use Wereldkundig\GravityForms\Submission;

function wk_gf_submission_field(int $id, string $type, array $properties = []): GF_Field {
    // Gravity Forms gives every form field as a GF_Field object.
    $field = new GF_Field();
    foreach (array_merge([
        'id'    => $id,
        'type'  => $type,
        'label' => '',
    ], $properties) as $key => $value) {
        $field->{$key} = $value;
    }

    return $field;
}

beforeEach(function () {
    // These tests are about themes and sending: the email comes from auto-detection, which is off by default.
    $GLOBALS['wk_gf_test_filter_values']['wk_gf_auto_map_fields'] = true;

    Functions\when('get_option')->justReturn([]);
    Functions\when('update_option')->justReturn(true);
    Functions\when('delete_option')->justReturn(true);
    Functions\when('esc_html')->returnArg();
});

/**
 * A configured site with these cached themes, and a live subscribe request that captures its body.
 *
 * @param array $bodies Captured request bodies.
 */
function wk_gf_submission_live_api(array $themes, ?array &$bodies, array $response = ['success' => true, 'data' => ['user_id' => 12]], int $status = 201): void {
    $GLOBALS['wk_gf_test_filter_values']['wk_gf_api_use_mock'] = false;
    Functions\when('get_option')->alias(static function ($name) use ($themes) {
        return Wereldkundig\GravityForms\Api\Client::FIELDS_OPTION === $name
            ? ['key' => md5('https://platform.example.nl/wp-json/wereldkundig|abc'), 'response' => ['options' => ['categories' => $themes]]]
            : ['api_url' => 'platform.example.nl', 'api_auth' => 'abc'];
    });
    Functions\when('trailingslashit')->alias(static fn ($value): string => rtrim((string) $value, '/') . '/');
    Functions\when('wp_json_encode')->alias(static fn ($value) => json_encode($value));
    Functions\when('wp_remote_retrieve_response_code')->justReturn($status);
    Functions\when('wp_remote_retrieve_body')->justReturn(json_encode($response));
    $bodies = [];
    Functions\when('wp_remote_request')->alias(static function ($url, $args) use (&$bodies) {
        $bodies[] = json_decode($args['body'], true);

        return [];
    });
}

/**
 * A feed for subscribe checkbox 5 with these themes: theme ID => category option keys.
 */
function wk_gf_submission_feed(array $themes = ['theme-2' => []], string $field = '5'): array {
    return ['id' => 7, 'meta' => ['subscribe_field' => $field, 'wk_gf_themes' => $themes]];
}

/**
 * The add-on, with a feed that maps no fields: the email comes from auto-detection.
 */
function wk_gf_submission_addon(): Mockery\MockInterface {
    $addon = Mockery::mock(Addon::class);
    $addon->shouldReceive('get_field_map_fields')->andReturn([]);

    return $addon;
}

function wk_gf_submission_themes(): array {
    return [
        ['id' => 'theme-1', 'name' => 'Nieuwsbrief'],
        ['id' => 'theme-2', 'name' => 'Evenementen'],
        ['id' => 'theme-5', 'name' => 'Persberichten'],
        [
            'id'         => 'theme-13281',
            'name'       => 'Gebieden',
            'categories' => [
                [
                    'id'       => 'select-all-36',
                    'name'     => 'Alles in Gebieden',
                    'children' => [
                        ['id' => 'category-36-37', 'name' => 'Bergschenhoek'],
                        ['id' => 'category-36-38', 'name' => 'Bleiswijk'],
                    ],
                ],
            ],
        ],
    ];
}

it('skips spam entries', function () {
    $result = (new Submission(Mockery::mock(Addon::class)))->handle(
        ['status' => 'spam'],
        ['fields' => []],
        wk_gf_submission_feed()
    );

    expect($result)->toBeNull();
});

it('fails when the form has several subscribe checkboxes and the feed\'s is not one of them', function (array $feed) {
    $addon = wk_gf_submission_addon();
    $addon->shouldReceive('log_error')->once()->with(Mockery::pattern('/\(code: wk_gf_subscribe_field_missing\)/'));
    $addon->shouldReceive('add_feed_error')->once();

    $result = (new Submission($addon))->handle(
        ['id' => 42, '1' => 'reader@example.com', '5' => '1', '6' => '1'],
        ['fields' => [wk_gf_submission_field(1, 'email'), wk_gf_submission_field(5, 'wk_gf_subscribe'), wk_gf_submission_field(6, 'wk_gf_subscribe')]],
        $feed
    );

    expect($result->get_error_code())->toBe('wk_gf_subscribe_field_missing');
})->with([
    'another field'    => [wk_gf_submission_feed(['theme-2' => []], '1')],
    'a removed field'  => [wk_gf_submission_feed(['theme-2' => []], '9')],
    'no field chosen'  => [['id' => 7, 'meta' => ['wk_gf_themes' => ['theme-2' => []]]]],
]);

it('fails when the form has no subscribe checkbox', function () {
    $addon = wk_gf_submission_addon();
    $addon->shouldReceive('log_error')->once()->with(Mockery::pattern('/\(code: wk_gf_subscribe_field_missing\)/'));
    $addon->shouldReceive('add_feed_error')->once();

    $result = (new Submission($addon))->handle(['id' => 42, '1' => 'reader@example.com'], ['fields' => [wk_gf_submission_field(1, 'email')]], wk_gf_submission_feed());

    expect($result->get_error_code())->toBe('wk_gf_subscribe_field_missing');
});

it('uses the only subscribe checkbox of the form, whatever the feed stored', function (array $feed) {
    $addon = wk_gf_submission_addon();
    $addon->shouldReceive('log_debug')->once()->with(Mockery::pattern('/checkbox not checked/'));

    // Unchecked: reaching the consent check shows the field was found.
    $result = (new Submission($addon))->handle(
        ['id' => 42, '1' => 'reader@example.com', '7' => ''],
        ['fields' => [wk_gf_submission_field(1, 'email'), wk_gf_submission_field(7, 'wk_gf_subscribe')]],
        $feed
    );

    expect($result)->toBeNull();
})->with([
    'no field stored, saved before the field was added' => [['id' => 7, 'meta' => ['wk_gf_themes' => ['theme-2' => []]]]],
    'a removed field stored'                            => [wk_gf_submission_feed(['theme-2' => []], '5')],
]);

it('does not subscribe when consent is unchecked', function () {
    $addon = wk_gf_submission_addon();
    $addon->shouldReceive('log_debug')
        ->once()
        ->with(Mockery::pattern('/checkbox not checked/'));

    $result = (new Submission($addon))->handle(
        ['5' => '0'],
        ['fields' => [wk_gf_submission_field(5, 'wk_gf_subscribe')]],
        wk_gf_submission_feed()
    );

    expect($result)->toBeNull();
});

it('only looks at the feed\'s own checkbox', function () {
    $addon = wk_gf_submission_addon();
    $addon->shouldReceive('log_debug')->once()->with(Mockery::pattern('/checkbox not checked/'));

    $form = ['fields' => [wk_gf_submission_field(1, 'email'), wk_gf_submission_field(5, 'wk_gf_subscribe'), wk_gf_submission_field(6, 'wk_gf_subscribe')]];

    expect((new Submission($addon))->handle(['id' => 42, '1' => 'reader@example.com', '5' => '', '6' => '1'], $form, wk_gf_submission_feed()))->toBeNull();
});

it('rejects a theme ID that is not an API theme', function () {
    $addon = wk_gf_submission_addon();
    $addon->shouldReceive('log_error')
        ->once()
        ->with(Mockery::pattern('/Invalid Wereldkundig theme: unknown\. \(code: wk_gf_theme_invalid\)/'));

    $result = (new Submission($addon))->handle(
        ['5' => '1'],
        ['fields' => [wk_gf_submission_field(5, 'wk_gf_subscribe')]],
        wk_gf_submission_feed(['unknown' => []])
    );

    expect($result)->toBeInstanceOf(WP_Error::class)
        ->and($result->get_error_code())->toBe('wk_gf_theme_invalid');
});

it('fails when the feed has no themes', function () {
    $addon = wk_gf_submission_addon();
    $addon->shouldReceive('log_error')->once()->with(Mockery::pattern('/Invalid Wereldkundig theme: none selected\./'));

    $result = (new Submission($addon))->handle(['5' => '1'], ['fields' => [wk_gf_submission_field(5, 'wk_gf_subscribe')]], wk_gf_submission_feed([]));

    expect($result->get_error_code())->toBe('wk_gf_theme_invalid');
});

it('subscribes a valid entry after explicit consent', function () {
    $addon = wk_gf_submission_addon();
    $addon->shouldReceive('log_debug')
        ->once()
        ->with(Mockery::pattern('/Subscriber sent/'));
    $addon->shouldReceive('add_note')
        ->once()
        ->with(42, 'reader@example.com was sent to Wereldkundig (platform user ID 1). Themes: Evenementen.', 'success');

    $form = [
        'fields' => [
            wk_gf_submission_field(1, 'email'),
            wk_gf_submission_field(2, 'name', [
                'inputs' => [['id' => '2.3'], ['id' => '2.6']],
            ]),
            wk_gf_submission_field(5, 'wk_gf_subscribe'),
        ],
    ];
    $entry = [
        'id'  => 42,
        '1'   => 'reader@example.com',
        '2.3' => 'Ada',
        '2.6' => 'Lovelace',
        '5'   => '1',
    ];

    expect((new Submission($addon))->handle($entry, $form, wk_gf_submission_feed()))->toBeTrue();
});

it('sends nothing without an email mapping while auto-detection is off', function () {
    unset($GLOBALS['wk_gf_test_filter_values']['wk_gf_auto_map_fields']);

    $addon = wk_gf_submission_addon();
    $addon->shouldReceive('log_error')
        ->once()
        ->with(Mockery::pattern('/No valid email address to subscribe\. \(code: wk_gf_email_invalid\)/'));
    $addon->shouldReceive('add_feed_error')
        ->once()
        ->with('No valid email address to subscribe. (code: wk_gf_email_invalid)', Mockery::any(), Mockery::any(), Mockery::any());

    $form = [
        'fields' => [
            wk_gf_submission_field(1, 'email'),
            wk_gf_submission_field(5, 'wk_gf_subscribe'),
        ],
    ];

    expect((new Submission($addon))->handle(['id' => 42, '1' => 'reader@example.com', '5' => '1'], $form, wk_gf_submission_feed())->get_error_code())->toBe('wk_gf_email_invalid');
});

it('does not report success when the API is not configured', function () {
    // What a site without credentials gets: no mock, so no fake success.
    $GLOBALS['wk_gf_test_filter_values']['wk_gf_api_use_mock'] = false;
    Functions\expect('wp_remote_request')->never();

    $addon = wk_gf_submission_addon();
    $addon->shouldReceive('log_error')
        ->once()
        ->with(Mockery::pattern('/Could not load the Wereldkundig themes/'));
    $addon->shouldReceive('add_feed_error')
        ->once()
        ->with('Could not load the Wereldkundig themes; check the API connection. Subscriber not sent. (code: wk_gf_themes_unavailable)', Mockery::any(), Mockery::any(), Mockery::any());

    $form = [
        'fields' => [
            wk_gf_submission_field(1, 'email'),
            wk_gf_submission_field(5, 'wk_gf_subscribe'),
        ],
    ];

    expect((new Submission($addon))->handle(['id' => 42, '1' => 'reader@example.com', '5' => '1'], $form, wk_gf_submission_feed())->get_error_code())->toBe('wk_gf_themes_unavailable');
});

it('subscribes to every theme of the feed in one request', function () {
    wk_gf_submission_live_api(wk_gf_submission_themes(), $bodies);

    $addon = wk_gf_submission_addon();
    $addon->shouldReceive('log_debug');
    $addon->shouldReceive('add_note')->once()->with(42, 'reader@example.com was sent to Wereldkundig (platform user ID 12). Themes: Nieuwsbrief; Evenementen.', 'success');

    $form  = ['fields' => [wk_gf_submission_field(1, 'email'), wk_gf_submission_field(5, 'wk_gf_subscribe')]];
    $entry = ['id' => 42, '1' => 'reader@example.com', '5' => '1'];

    expect((new Submission($addon))->handle($entry, $form, wk_gf_submission_feed(['theme-1' => [], 'theme-2' => []])))->toBeTrue()
        ->and($bodies)->toHaveCount(1)
        ->and($bodies[0]['options'])->toBe(['theme-1' => true, 'theme-2' => true]);
});

it('still subscribes the valid themes when one theme of the feed is invalid', function () {
    $addon = wk_gf_submission_addon();
    $addon->shouldReceive('log_debug');
    $addon->shouldReceive('log_error')->once()->with(Mockery::pattern('/Invalid Wereldkundig theme/'));
    $addon->shouldReceive('add_feed_error')->with('Invalid Wereldkundig theme: unknown. (code: wk_gf_theme_invalid)', Mockery::any(), Mockery::any(), Mockery::any())->once();
    $addon->shouldReceive('add_note')->with(42, 'reader@example.com was sent to Wereldkundig (platform user ID 1). Themes: Evenementen.', 'success')->once();

    $form = ['fields' => [wk_gf_submission_field(1, 'email'), wk_gf_submission_field(5, 'wk_gf_subscribe')]];

    expect((new Submission($addon))->handle(['id' => 42, '1' => 'reader@example.com', '5' => '1'], $form, wk_gf_submission_feed(['theme-2' => [], 'unknown' => []]))->get_error_code())->toBe('wk_gf_theme_invalid');
});

it('subscribes to several themes, only the chosen categories of a theme', function () {
    wk_gf_submission_live_api(wk_gf_submission_themes(), $bodies);

    $addon = wk_gf_submission_addon();
    $addon->shouldReceive('log_debug');
    $addon->shouldReceive('add_note')->once()->with(42, 'reader@example.com was sent to Wereldkundig (platform user ID 12). Themes: Nieuwsbrief; Gebieden (Bergschenhoek, Bleiswijk).', 'success');

    $form = ['fields' => [wk_gf_submission_field(1, 'email'), wk_gf_submission_field(5, 'wk_gf_subscribe')]];
    // Categories are only sent for their own theme; category-99 is not one of theme-13281.
    $feed = wk_gf_submission_feed(['theme-1' => [], 'theme-13281' => ['category-36-37', 'category-36-38', 'category-99']]);

    expect((new Submission($addon))->handle(['id' => 42, '1' => 'reader@example.com', '5' => '1'], $form, $feed))->toBeTrue()
        ->and($bodies[0]['options'])->toBe(['theme-1' => true, 'category-36-37' => true, 'category-36-38' => true]);
});

it('lets the subscriber and the themes be changed before subscribing', function () {
    wk_gf_submission_live_api(wk_gf_submission_themes(), $bodies);
    $GLOBALS['wk_gf_test_filter_values']['wk_gf_subscribe_themes_3']       = ['theme-5' => []];
    $GLOBALS['wk_gf_test_filter_values']['wk_gf_subscriber_pre_subscribe'] = ['email' => 'other@example.com', 'company' => 'Wereldkundig'];

    $addon = wk_gf_submission_addon();
    $addon->shouldReceive('log_debug');
    $addon->shouldReceive('add_note')->once()->with(42, 'other@example.com was sent to Wereldkundig (platform user ID 12). Themes: Persberichten.', 'success');

    $form = ['id' => 3, 'fields' => [wk_gf_submission_field(1, 'email'), wk_gf_submission_field(5, 'wk_gf_subscribe')]];

    expect((new Submission($addon))->handle(['id' => 42, '1' => 'reader@example.com', '5' => '1'], $form, wk_gf_submission_feed(['theme-1' => []])))->toBeTrue()
        ->and($bodies[0])->toBe(['email' => 'other@example.com', 'company' => 'Wereldkundig', 'options' => ['theme-5' => true]]);
});

it('skips the subscribe when the filter returns false', function () {
    wk_gf_submission_live_api(wk_gf_submission_themes(), $bodies);
    $GLOBALS['wk_gf_test_filter_values']['wk_gf_subscriber_pre_subscribe'] = false;

    $addon = wk_gf_submission_addon();
    $addon->shouldReceive('log_debug')->once()->with(Mockery::pattern('/Skipped by the/'));

    $form = ['fields' => [wk_gf_submission_field(1, 'email'), wk_gf_submission_field(5, 'wk_gf_subscribe')]];

    expect((new Submission($addon))->handle(['id' => 42, '1' => 'reader@example.com', '5' => '1'], $form, wk_gf_submission_feed(['theme-1' => []])))->toBeNull()
        ->and($bodies)->toBe([]);
});

it('adds the email and error code to the feed error when the platform refuses the subscriber', function () {
    wk_gf_submission_live_api(wk_gf_submission_themes(), $bodies, ['type' => 'member-exists', 'title' => 'Member Exists', 'status' => 409, 'detail' => 'reader@example.com is already a list member.'], 409);

    $feed  = wk_gf_submission_feed(['theme-1' => []]);
    $addon = wk_gf_submission_addon();
    $addon->shouldReceive('log_error');
    $addon->shouldReceive('add_feed_error')->once()->with('reader@example.com was not sent to Wereldkundig: reader@example.com is already a list member. (code: member-exists)', $feed, Mockery::any(), Mockery::any());

    $form   = ['fields' => [wk_gf_submission_field(1, 'email'), wk_gf_submission_field(5, 'wk_gf_subscribe')]];
    $result = (new Submission($addon))->handle(['id' => 42, '1' => 'reader@example.com', '5' => '1'], $form, $feed);

    expect($result->get_error_code())->toBe('member-exists')
        ->and($result->get_error_data())->toBe(['status' => 409]);
});
