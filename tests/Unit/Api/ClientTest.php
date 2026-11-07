<?php

use Brain\Monkey\Functions;
use Wereldkundig\GravityForms\Api\Client;

function wk_client_live_api(array $response_body, int $code = 200, ?array &$captured = null): void {
    $GLOBALS['wk_test_filter_values']['wk_gf_api_use_mock'] = false;

    Functions\when('get_option')->justReturn([
        'api_url'      => 'platform.example.nl',
        'api_auth'     => 'Basic ' . base64_encode('api-user:app pass'),
    ]);
    Functions\when('delete_option')->justReturn(true);
    Functions\when('trailingslashit')->alias(static fn ($value): string => rtrim((string) $value, '/') . '/');
    Functions\when('wp_json_encode')->alias(static fn ($value) => json_encode($value));
    Functions\when('wp_remote_retrieve_response_code')->justReturn($code);
    Functions\when('wp_remote_retrieve_body')->justReturn(json_encode($response_body));
    Functions\when('wp_remote_request')->alias(static function ($url, $args) use (&$captured) {
        $captured = ['url' => $url, 'args' => $args];

        return [];
    });
}

it('reads the themes from the subscribe fields endpoint', function () {
    $captured = null;
    wk_client_live_api([
        'fields'  => ['email' => ['type' => 'email', 'label' => 'E-mail', 'required' => true]],
        'options' => [
            'title'      => 'Onderwerpen',
            'categories' => [
                [
                    'id'         => '1',
                    'name'       => 'Nieuwsbrief',
                    'categories' => [
                        ['id' => 'category-3', 'name' => 'Nieuws', 'children' => [['id' => 'category-7', 'name' => 'Lokaal']]],
                    ],
                ],
                ['id' => 'theme-2', 'name' => 'Pers', 'categories' => null],
                ['name' => 'No id'],
            ],
        ],
    ], 200, $captured);
    Functions\when('update_option')->justReturn(true);

    $themes = (new Client())->themes_setup(true);

    expect($captured['url'])->toBe('https://platform.example.nl/wp-json/wereldkundig/v1/subscribe/fields')
        ->and($captured['args']['method'])->toBe('GET')
        ->and($captured['args']['headers']['Authorization'])->toBe('Basic ' . base64_encode('api-user:app pass'))
        ->and($themes)->toBe([
            [
                'id'         => 'theme-1',
                'name'       => 'Nieuwsbrief',
                'categories' => ['category-3' => 'Nieuws', 'category-7' => 'Lokaal'],
            ],
            [
                'id'         => 'theme-2',
                'name'       => 'Pers',
                'categories' => [],
            ],
        ]);
});

it('serves themes from the fields option', function () {
    $GLOBALS['wk_test_filter_values']['wk_gf_api_use_mock'] = false;
    $settings = ['api_url' => 'platform.example.nl', 'api_auth' => 'abc'];
    Functions\when('get_option')->alias(static function ($name) use ($settings) {
        return Client::FIELDS_OPTION === $name
            ? [
                'key'      => md5('https://platform.example.nl/wp-json/wereldkundig|abc'),
                'response' => ['options' => ['categories' => [['id' => 'theme-9', 'name' => 'Cached']]]],
            ]
            : $settings;
    });
    Functions\expect('wp_remote_request')->never();

    expect((new Client())->themes())->toBe(['theme-9' => 'Cached']);
});

it('refetches when the fields option belongs to other credentials', function () {
    $captured = null;
    wk_client_live_api(['fields' => [], 'options' => ['categories' => [['id' => '4', 'name' => 'Fresh']]]], 200, $captured);
    Functions\expect('update_option')->once();

    expect((new Client())->themes())->toBe(['theme-4' => 'Fresh'])
        ->and($captured['url'])->toEndWith('/v1/subscribe/fields');
});

it('stores the full fields response in an option on refresh', function () {
    $body = [
        'fields'  => ['email' => ['type' => 'email', 'label' => 'E-mail', 'required' => true]],
        'options' => ['title' => 'Onderwerpen', 'categories' => []],
    ];
    wk_client_live_api($body);
    Functions\expect('update_option')->once()->andReturnUsing(static function ($name, $value, $autoload) use ($body) {
        expect($name)->toBe(Client::FIELDS_OPTION)
            ->and($value['response'])->toBe($body)
            ->and($value['key'])->toBe(md5('https://platform.example.nl/wp-json/wereldkundig|' . base64_encode('api-user:app pass')))
            ->and($autoload)->toBeFalse();

        return true;
    });

    expect((new Client())->refresh_fields())->toBe($body);
});

it('keeps the fields option when the refresh fails', function () {
    wk_client_live_api(['code' => 'rest_forbidden', 'message' => 'Nope'], 403);
    Functions\expect('update_option')->never();

    expect((new Client())->refresh_fields())->toBeInstanceOf(WP_Error::class);
});

it('posts the theme as an option to the subscribe endpoint', function () {
    $captured = null;
    wk_client_live_api(['success' => true, 'data' => ['user_id' => 3]], 201, $captured);

    $result = (new Client())->subscribe('theme-2', [
        'email'       => 'reader@example.com',
        'first_name'  => 'Vincent',
        'middle_name' => 'van',
        'last_name'   => 'Gogh',
        'company'     => '',
        'options'     => 'ignored',
    ]);

    expect($result)->toBe(['user_id' => 3])
        ->and($captured['url'])->toBe('https://platform.example.nl/wp-json/wereldkundig/v1/subscribe')
        ->and(json_decode($captured['args']['body'], true))->toBe([
            'email'       => 'reader@example.com',
            'first_name'  => 'Vincent',
            'middle_name' => 'van',
            'last_name'   => 'Gogh',
            'options'     => ['theme-2' => true],
        ]);
});

it('drops empty subscriber fields from the subscribe payload', function () {
    $captured = null;
    wk_client_live_api(['success' => true], 201, $captured);

    (new Client())->subscribe('theme-2', [
        'email'       => 'reader@example.com',
        'first_name'  => '',
        'middle_name' => '',
        'last_name'   => '',
    ]);

    expect(json_decode($captured['args']['body'], true))->toBe([
        'email'   => 'reader@example.com',
        'options' => ['theme-2' => true],
    ]);
});

it('returns the API error message on a failed request', function () {
    wk_client_live_api(['code' => 'rest_invalid', 'message' => 'Geen geldig thema.', 'data' => ['status' => 400]], 400);

    $result = (new Client())->subscribe('theme-2', ['email' => 'reader@example.com']);

    expect($result)->toBeInstanceOf(WP_Error::class)
        ->and($result->get_error_message())->toBe('Geen geldig thema.');
});

it('reads all subscriber fields from the fields option', function () {
    $GLOBALS['wk_test_filter_values']['wk_gf_api_use_mock'] = false;
    $settings = ['api_url' => 'platform.example.nl', 'api_auth' => 'abc'];
    Functions\when('get_option')->alias(static function ($name) use ($settings) {
        return Client::FIELDS_OPTION === $name
            ? [
                'key'      => md5('https://platform.example.nl/wp-json/wereldkundig|abc'),
                'response' => ['fields' => [
                    'first_name'  => ['type' => 'text', 'label' => 'Voornaam', 'required' => false],
                    'middle_name' => ['type' => 'text', 'label' => 'Tussenvoegsel'],
                    'email'       => ['type' => 'email', 'label' => 'E-mail', 'required' => true],
                    'bad key!'    => ['type' => 'text', 'label' => 'Nope'],
                ]],
            ]
            : $settings;
    });

    expect((new Client())->subscriber_fields())->toBe([
        'first_name'  => ['type' => 'text', 'label' => 'Voornaam', 'required' => false],
        'middle_name' => ['type' => 'text', 'label' => 'Tussenvoegsel', 'required' => false],
        'email'       => ['type' => 'email', 'label' => 'E-mail', 'required' => true],
    ]);
});

it('has no subscriber fields when the API is unreachable', function () {
    wk_client_live_api(['message' => 'down'], 500);

    expect((new Client())->subscriber_fields())->toBe([]);
});

it('decodes HTML entities in theme names', function () {
    wk_client_live_api(['fields' => [], 'options' => ['categories' => [['id' => 'theme-12865', 'name' => 'TEst &#8211; check &amp; tel']]]]);
    Functions\when('update_option')->justReturn(true);

    expect((new Client())->themes())->toBe(['theme-12865' => 'TEst – check & tel']);
});

it('requires credentials before calling the live API', function () {
    $GLOBALS['wk_test_filter_values']['wk_gf_api_use_mock'] = false;
    Functions\when('get_option')->justReturn([]);
    Functions\expect('wp_remote_request')->never();

    $result = (new Client())->request('GET', '/v1/subscribe/fields');

    expect($result)->toBeInstanceOf(WP_Error::class)
        ->and($result->get_error_code())->toBe('wk_api_credentials_missing');
});

it('does not fall back to the mock when credentials are missing', function () {
    $GLOBALS['wk_test_filter_values']['wk_gf_api_use_mock'] = false;
    Functions\when('get_option')->justReturn([]);
    Functions\expect('wp_remote_request')->never();

    $result = (new Client())->subscribe('theme-1', ['email' => 'reader@example.com']);

    expect($result)->toBeInstanceOf(WP_Error::class)
        ->and($result->get_error_code())->toBe('wk_api_credentials_missing')
        ->and((new Client())->themes())->toBe([]);
});

it('refuses to send credentials over plain http', function () {
    wk_client_live_api(['success' => true]);
    Functions\when('get_option')->justReturn(['api_url' => 'http://platform.example.nl', 'api_auth' => 'abc']);
    Functions\expect('wp_remote_request')->never();

    $result = (new Client())->subscribe('theme-2', ['email' => 'reader@example.com']);

    expect($result)->toBeInstanceOf(WP_Error::class)
        ->and($result->get_error_code())->toBe('wk_api_insecure_url');
});

it('only accepts https or local http API URLs', function (string $url, bool $secure) {
    expect(Client::is_secure_url($url))->toBe($secure);
})->with([
    ['https://platform.example.nl', true],
    ['platform.example.nl', true],
    ['http://wereldkundig-platform.test', true],
    ['http://localhost:8080', true],
    ['http://platform.example.nl', false],
    ['http://example.test.evil.nl', false],
    ['ftp://platform.example.nl', false],
    ['', false],
]);

it('uses the mock inside the test suite', function () {
    Functions\expect('wp_remote_request')->never();
    Functions\when('get_option')->justReturn([]);

    expect((new Client())->subscribe('theme-1', ['email' => 'reader@example.com']))->toBeArray();
});

it('sends chosen categories without their theme, and a theme without categories whole', function () {
    $captured = null;
    wk_client_live_api(['success' => true], 201, $captured);

    (new Client())->subscribe([
        'theme-1'     => [],
        'theme-13281' => ['category-36-37', 'select-all-40', 'not-a-category'],
    ], ['email' => 'reader@example.com']);

    expect(json_decode($captured['args']['body'], true)['options'])->toBe([
        'theme-1'        => true,
        'category-36-37' => true,
        'select-all-40'  => true,
    ]);
});

it('reads the message of a problem details error response', function () {
    wk_client_live_api(['type' => 'member-exists', 'title' => 'Member Exists', 'status' => 409, 'detail' => 'Already a list member.'], 409);

    $result = (new Client())->subscribe('theme-1', ['email' => 'reader@example.com']);

    expect($result->get_error_code())->toBe('member-exists')
        ->and($result->get_error_message())->toBe('Already a list member.');
});

it('fails when the platform does not report success', function () {
    wk_client_live_api(['success' => false, 'message' => 'Nope.'], 200);

    $result = (new Client())->subscribe('theme-1', ['email' => 'reader@example.com']);

    expect($result->get_error_code())->toBe('wk_api_subscribe_failed')
        ->and($result->get_error_message())->toBe('Nope.');
});

it('reads settings that Gravity Forms stored encrypted', function () {
    $encrypted = json_encode(['encrypted' => true, 'value' => 'ciphertext']);
    Functions\when('get_option')->justReturn($encrypted);

    $encryptor = Mockery::mock();
    $encryptor->shouldReceive('decrypt')->with($encrypted)->andReturn(['api_url' => 'platform.example.nl', 'api_auth' => 'abc']);
    $addon = Mockery::mock(Wereldkundig\GravityForms\Addon::class)->makePartial();
    $addon->shouldReceive('get_encryptor')->andReturn($encryptor);
    (new ReflectionProperty(Wereldkundig\GravityForms\Addon::class, 'instance'))->setValue(null, $addon);

    try {
        expect(Client::credentials())->toBe(['url' => 'platform.example.nl', 'auth' => 'abc']);
    } finally {
        (new ReflectionProperty(Wereldkundig\GravityForms\Addon::class, 'instance'))->setValue(null, null);
    }
});

it('calls the API once per request for repeated lookups, a failure included', function () {
    wk_client_live_api(['message' => 'down'], 500);
    $calls = 0;
    Functions\when('wp_remote_request')->alias(static function () use (&$calls) {
        $calls++;

        return [];
    });

    $client = new Client();

    expect($client->themes())->toBe([])
        ->and($client->themes_setup())->toBe([])
        ->and((new Client())->subscriber_fields())->toBe([])
        ->and($calls)->toBe(1);
});

it('only reads the cache when fetching is off', function () {
    wk_client_live_api(['fields' => [], 'options' => ['categories' => [['id' => '4', 'name' => 'Fresh']]]]);
    Functions\when('get_option')->justReturn(['api_url' => 'platform.example.nl', 'api_auth' => 'abc']);
    Functions\expect('wp_remote_request')->never();

    expect((new Client())->themes_setup(false, false))->toBe([]);
});

it('gives up on the API after 10 seconds', function () {
    $captured = null;
    wk_client_live_api(['success' => true], 200, $captured);

    (new Client())->subscribe('theme-2', ['email' => 'reader@example.com']);

    expect($captured['args']['timeout'])->toBe(10);
});
