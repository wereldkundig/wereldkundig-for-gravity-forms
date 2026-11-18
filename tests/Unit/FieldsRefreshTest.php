<?php

use Brain\Monkey\Functions;
use Wereldkundig\GravityForms\Addon;
use Wereldkundig\GravityForms\Api\Client;
use Wereldkundig\GravityForms\FieldsRefresh;

beforeEach(function () {
    Functions\when('plugin_basename')->alias(static fn ($file): string => basename($file));
    Functions\when('wp_get_schedules')->justReturn(['hourly' => [], 'twicedaily' => [], 'daily' => []]);
    if (! defined('HOUR_IN_SECONDS')) {
        define('HOUR_IN_SECONDS', 3600);
    }
});

function wk_gf_configured(bool $configured = true): void {
    Functions\when('get_option')->justReturn($configured ? ['api_url' => 'platform.example.nl', 'api_auth' => 'abc'] : []);
}

it('schedules the hourly refresh when the API is configured', function () {
    wk_gf_configured();
    Functions\when('wp_next_scheduled')->justReturn(false);
    Functions\expect('wp_schedule_event')->once()->with(Mockery::type('int'), 'hourly', FieldsRefresh::HOOK);

    FieldsRefresh::sync();
});

it('leaves an existing refresh with the same interval alone', function () {
    wk_gf_configured();
    Functions\when('wp_next_scheduled')->justReturn(1767225600);
    Functions\when('wp_get_schedule')->justReturn('hourly');
    Functions\expect('wp_schedule_event')->never();
    Functions\expect('wp_clear_scheduled_hook')->never();

    FieldsRefresh::sync();
});

it('reschedules the refresh when the interval filter changes', function () {
    wk_gf_configured();
    $GLOBALS['wk_gf_test_filter_values']['wk_gf_refresh_fields_recurrence'] = 'daily';
    Functions\when('wp_next_scheduled')->justReturn(1767225600);
    Functions\when('wp_get_schedule')->justReturn('hourly');
    Functions\expect('wp_clear_scheduled_hook')->once()->with(FieldsRefresh::HOOK);
    Functions\expect('wp_schedule_event')->once()->with(Mockery::type('int'), 'daily', FieldsRefresh::HOOK);

    FieldsRefresh::sync();
});

it('falls back to hourly for an unknown interval', function () {
    wk_gf_configured();
    $GLOBALS['wk_gf_test_filter_values']['wk_gf_refresh_fields_recurrence'] = 'every_second';
    Functions\when('wp_next_scheduled')->justReturn(false);
    Functions\expect('wp_schedule_event')->once()->with(Mockery::type('int'), 'hourly', FieldsRefresh::HOOK);

    FieldsRefresh::sync();
});

it('unschedules the refresh without API credentials', function () {
    wk_gf_configured(false);
    Functions\expect('wp_clear_scheduled_hook')->once()->with(FieldsRefresh::HOOK);
    Functions\expect('wp_schedule_event')->never();

    FieldsRefresh::sync();
});

it('refreshes the cached fields in the background', function () {
    wk_gf_configured();
    $stored = null;
    Functions\when('update_option')->alias(static function ($name, $value) use (&$stored) {
        if (Client::FIELDS_OPTION === $name) {
            $stored = $value;
        }

        return true;
    });
    Functions\expect('delete_option')->once()->with(Client::FIELDS_ERROR_OPTION);

    FieldsRefresh::run(new Addon());

    expect($stored['response']['options']['categories'])->toHaveCount(3);
});

it('keeps the cache and stores the error when the background refresh fails', function () {
    wk_gf_configured();
    Functions\when('trailingslashit')->alias(static fn ($value): string => rtrim((string) $value, '/') . '/');
    Functions\when('wp_remote_request')->justReturn(new WP_Error('http_request_failed', 'cURL error 28: Operation timed out'));
    $GLOBALS['wk_gf_test_filter_values']['wk_gf_api_use_mock'] = false;

    $stored = [];
    Functions\when('update_option')->alias(static function ($name, $value) use (&$stored) {
        $stored[$name] = $value;

        return true;
    });
    Functions\expect('delete_option')->never();

    FieldsRefresh::run(new Addon());

    expect($stored)->not->toHaveKey(Client::FIELDS_OPTION)
        ->and($stored[Client::FIELDS_ERROR_OPTION]['source'])->toBe('cron')
        ->and($stored[Client::FIELDS_ERROR_OPTION]['message'])->toBe('cURL error 28: Operation timed out');
});

it('unschedules instead of calling the API once the credentials are gone', function () {
    wk_gf_configured(false);
    Functions\expect('wp_remote_request')->never();
    Functions\expect('wp_clear_scheduled_hook')->once()->with(FieldsRefresh::HOOK);

    FieldsRefresh::run(new Addon());
});

it('says when the failed fetch was the background refresh', function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('wp_date')->alias(static fn ($format, $time): string => gmdate('Y-m-d H:i', $time));
    $error = ['message' => 'Bad gateway', 'code' => 'wk_gf_api_http', 'time' => 1767225600, 'source' => 'cron'];
    Functions\when('get_option')->alias(static fn ($name) => Client::FIELDS_ERROR_OPTION === $name ? $error : '');

    expect((new Addon())->api_status_notice())
        ->toContain('The background refresh of the Wereldkundig themes failed.')
        ->not->toContain('Settings saved');
});

it('processes feeds in the background', function () {
    $async = (new ReflectionProperty(Addon::class, '_async_feed_processing'))->getValue(new Addon());

    expect($async)->toBeTrue();
});
