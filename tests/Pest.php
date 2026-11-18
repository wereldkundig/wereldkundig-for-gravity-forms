<?php

use Brain\Monkey\Functions;
use Yoast\WPTestUtils\BrainMonkey\TestCase;

uses(TestCase::class)
    ->beforeEach(function () {
        $GLOBALS['wk_gf_test_filter_values'] = [];
        // Client keeps fields_setup() results per request; every test is a new request.
        (new ReflectionProperty(Wereldkundig\GravityForms\Api\Client::class, 'memo'))->setValue(null, []);

        Functions\when('rgar')->alias(static function ($value, $key, $default = null) {
            if (is_array($value)) {
                return array_key_exists($key, $value) ? $value[$key] : $default;
            }

            if (is_object($value)) {
                return isset($value->{$key}) ? $value->{$key} : $default;
            }

            return $default;
        });

        Functions\when('rgars')->alias(static function ($value, $path, $default = null) {
            foreach (explode('/', $path) as $key) {
                if (!is_array($value) || !array_key_exists($key, $value)) {
                    return $default;
                }
                $value = $value[$key];
            }

            return $value;
        });

        Functions\when('rgblank')->alias(static fn ($value): bool => null === $value || '' === $value || [] === $value);
        Functions\when('sanitize_email')->alias(static fn ($value): string => trim((string) $value));
        Functions\when('sanitize_text_field')->alias(static fn ($value): string => trim(strip_tags((string) $value)));
        Functions\when('is_wp_error')->alias(static fn ($value): bool => $value instanceof WP_Error);
        Functions\when('apply_filters')->alias(static function ($hook, $value) {
            return array_key_exists($hook, $GLOBALS['wk_gf_test_filter_values'])
                ? $GLOBALS['wk_gf_test_filter_values'][$hook]
                : $value;
        });
        Functions\when('__')->alias(static fn ($text): string => $text);
        Functions\when('esc_html__')->alias(static fn ($text): string => $text);
        Functions\when('esc_attr__')->alias(static fn ($text): string => $text);
        Functions\when('esc_attr')->alias(static fn ($value): string => (string) $value);
        Functions\when('wp_kses_post')->alias(static fn ($value): string => (string) $value);
        Functions\when('wp_parse_url')->alias(static fn ($url, $component = -1) => parse_url($url, $component));
        Functions\when('absint')->alias(static fn ($value): int => abs((int) $value));
    })
    ->in('Unit', 'Live');

uses()->group('unit')->in('Unit');
uses()->group('live')->in('Live');
