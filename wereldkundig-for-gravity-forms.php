<?php
/**
 * Plugin Name: Wereldkundig for Gravity Forms
 * Description: Add people to your Wereldkundig themes when they fill in your Gravity Forms.
 * Version: 1.0.0
 * Requires at least: 6.9
 * Requires PHP: 8.2
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wereldkundig-for-gravity-forms
 * Domain Path: /languages
 */

namespace Wereldkundig\GravityForms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION = '1.0.0';

// Core classes load without Composer, so a deploy without vendor/ still works.
spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = __NAMESPACE__ . '\\';
		if ( 0 !== strncmp( $class_name, $prefix, strlen( $prefix ) ) ) {
			return;
		}

		$file = __DIR__ . '/app/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

// Optional: Composer's autoloader, for any third-party packages added later.
if ( is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

( new Component() )->register_hooks();

// Only the current site: after a network deactivation other sites keep the event, which then runs nothing.
register_deactivation_hook( __FILE__, array( FieldsRefresh::class, 'unschedule' ) );
