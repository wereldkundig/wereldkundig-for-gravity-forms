<?php

use Wereldkundig\GravityForms\Tests\Unit\Doubles\WPError;
use Yoast\WPTestUtils\BrainMonkey;

require_once __DIR__ . '/Unit/Doubles/WPError.php';
require_once dirname(__DIR__) . '/vendor/yoast/wp-test-utils/src/BrainMonkey/bootstrap.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Lets Client use its mock API; never defined on a site.
define('WK_GF_RUNNING_TESTS', true);

if (! defined('Wereldkundig\GravityForms\VERSION')) {
    define('Wereldkundig\GravityForms\VERSION', 'test');
}

class_alias(WPError::class, 'WP_Error');

/**
 * Minimal .env reader for .env.local.tests: KEY=value, # comments, optional quotes, ${VAR} expansion.
 * Real environment variables win over the file.
 */
(static function (string $file): void {
    if (! is_readable($file)) {
        return;
    }

    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ('' === $line || '#' === $line[0] || ! str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (false !== getenv($key)) {
            continue;
        }

        if (preg_match('/^([\'"])(.*)\1$/', $value, $matches)) {
            $value = $matches[2];
        }

        $value = preg_replace_callback('/\$\{(\w+)\}/', static fn ($m): string => (string) getenv($m[1]), $value);

        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }
})(dirname(__DIR__) . '/.env.local.tests');

if (! class_exists('GFFeedAddOn')) {
    #[AllowDynamicProperties]
    class GFFeedAddOn {
        public function __construct() {}

        public function get_field_map_fields($feed, $name) {
            return [];
        }

        public function get_field_value($form, $entry, $field_id) {
            return $entry[(string) $field_id] ?? '';
        }

        public function log_debug($message) {}

        public function log_error($message) {}

        public function add_note($entry_id, $message, $type) {}

        public function add_feed_error($message, $feed, $entry, $form) {}

        public function update_plugin_settings($settings) {}

        public function get_feed($id) {
            return false;
        }

        public function save_feed_settings($feed_id, $form_id, $settings) {
            return $settings;
        }

        public function uninstall() {
            return true;
        }

        public function init_admin() {}

        // Like GF without GF_ENCRYPTION_KEY: settings pass through unchanged.
        public function get_encryptor() {
            return new class {
                public function decrypt($settings) {
                    return $settings;
                }
            };
        }
    }
}

if (! class_exists('GF_Field')) {
    #[AllowDynamicProperties]
    class GF_Field {
        public function is_entry_detail() {
            return false;
        }

        public function is_form_editor() {
            return false;
        }

        public function get_tabindex() {
            return '';
        }

        public function get_field_label($force_frontend_label, $value) {
            return $this->label ?? '';
        }

        public function get_admin_buttons() {
            return '';
        }

        public function get_description($description, $css_class) {
            return '';
        }

        public function is_description_above($form) {
            return false;
        }

        public function sanitize_settings() {}
    }
}

BrainMonkey\makeDoublesForUnavailableClasses([
    'GFForms',
    'GFAddOn',
    'GF_Fields',
]);
