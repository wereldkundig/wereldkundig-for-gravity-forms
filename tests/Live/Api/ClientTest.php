<?php

/**
 * Real calls to the test platform configured in .env.local.tests.
 * Skipped when WK_TEST_API_URL (or, per test, the credentials) is empty.
 */

use Brain\Monkey\Functions;
use Wereldkundig\GravityForms\Api\Client;

function wk_gf_live_env(string $key): string {
    return trim((string) getenv($key));
}

/**
 * Route the client through real HTTP: wp_remote_request() becomes a curl call.
 */
function wk_gf_live_api(?string $auth = null): void {
    if ('' === wk_gf_live_env('WK_TEST_API_URL')) {
        test()->markTestSkipped('WK_TEST_API_URL is not set in .env.local.tests.');
    }

    $GLOBALS['wk_gf_test_filter_values']['wk_gf_api_use_mock'] = false;

    Functions\when('get_option')->justReturn([
        'api_url'      => wk_gf_live_env('WK_TEST_API_URL'),
        'api_auth'     => $auth ?? wk_gf_live_env('WK_TEST_API_AUTH'),
    ]);
    Functions\when('update_option')->justReturn(true);
    Functions\when('trailingslashit')->alias(static fn ($value): string => rtrim((string) $value, '/') . '/');
    Functions\when('wp_json_encode')->alias(static fn ($value) => json_encode($value));
    Functions\when('wp_remote_retrieve_response_code')->alias(static fn ($response): int => (int) $response['response']['code']);
    Functions\when('wp_remote_retrieve_body')->alias(static fn ($response): string => (string) $response['body']);
    Functions\when('wp_remote_request')->alias(static function ($url, $args) {
        $headers = [];
        foreach ($args['headers'] ?? [] as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST  => $args['method'],
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => (int) ($args['timeout'] ?? 15),
        ]);
        if (isset($args['body'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $args['body']);
        }
        if ('' !== wk_gf_live_env('WK_TEST_API_CA_FILE')) {
            curl_setopt($curl, CURLOPT_CAINFO, wk_gf_live_env('WK_TEST_API_CA_FILE'));
        }

        $body = curl_exec($curl);
        if (false === $body) {
            $error = curl_error($curl);
            curl_close($curl);

            return new WP_Error('http_request_failed', $error);
        }

        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return ['response' => ['code' => $code], 'body' => $body];
    });
}

function wk_gf_live_require_credentials(): void {
    if ('' === wk_gf_live_env('WK_TEST_API_AUTH')) {
        test()->markTestSkipped('WK_TEST_API_AUTH is not set in .env.local.tests.');
    }
}

it('is refused by the platform with wrong credentials', function () {
    wk_gf_live_api(base64_encode('wk-gf-tests:not-the-password'));

    $result = (new Client())->request('GET', '/v1/subscribe/fields');

    expect($result)->toBeInstanceOf(WP_Error::class)
        ->and($result->get_error_data()['status'] ?? null)->toBeIn([401, 403]);
});

it('reads the themes from the platform', function () {
    wk_gf_live_require_credentials();
    wk_gf_live_api();

    $themes = (new Client())->themes_setup(true);

    expect($themes)->not->toBeEmpty();
    foreach ($themes as $theme) {
        expect($theme['id'])->toMatch('/^theme-.+/')
            ->and($theme['name'])->toBeString()->not->toBeEmpty()
            ->and($theme['categories'])->toBeArray();
    }
});

it('subscribes to the first theme on the platform', function () {
    wk_gf_live_require_credentials();
    $email = wk_gf_live_env('WK_TEST_API_SUBSCRIBE_EMAIL');
    if ('' === $email || ! str_contains($email, '@')) {
        $this->markTestSkipped('WK_TEST_API_SUBSCRIBE_EMAIL is not set in .env.local.tests.');
    }

    // Unique address so a repeat run is a create, not member-exists (upsert off by default).
    [$local, $domain] = explode('@', $email, 2);
    $email            = sprintf('%s+wk-%s@%s', $local, bin2hex(random_bytes(3)), $domain);

    wk_gf_live_api();
    $client = new Client();
    $themes = $client->themes();
    expect($themes)->not->toBeEmpty();

    $result = $client->subscribe((string) array_key_first($themes), [
        'email'      => $email,
        'first_name' => 'Wereldkundig',
        'last_name'  => 'GF test',
    ]);

    expect($result)->toBeArray()
        ->and($result['user_id'] ?? null)->not->toBeEmpty()
        ->and($result['created'] ?? null)->toBeTrue();
});

it('updates an existing subscriber when upsert is on', function () {
    wk_gf_live_require_credentials();
    $email = wk_gf_live_env('WK_TEST_API_SUBSCRIBE_EMAIL');
    if ('' === $email) {
        $this->markTestSkipped('WK_TEST_API_SUBSCRIBE_EMAIL is not set in .env.local.tests.');
    }

    wk_gf_live_api();
    $client = new Client();
    $themes = $client->themes();
    expect($themes)->not->toBeEmpty();
    $theme = (string) array_key_first($themes);

    // Ensure the address exists, then update it.
    $client->subscribe($theme, [
        'email'      => $email,
        'first_name' => 'Wereldkundig',
        'last_name'  => 'GF test',
    ], true);

    $result = $client->subscribe($theme, [
        'email'      => $email,
        'first_name' => 'Wereldkundig',
        'last_name'  => 'GF upsert',
    ], true);

    expect($result)->toBeArray()
        ->and($result['user_id'] ?? null)->not->toBeEmpty()
        ->and($result['created'] ?? null)->toBeFalse();
});
