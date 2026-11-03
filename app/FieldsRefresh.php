<?php

namespace Wereldkundig\GravityForms;

use Wereldkundig\GravityForms\Api\Client;

/**
 * Refreshes the cached themes and subscriber fields in the background with WP-Cron (hourly by default),
 * so new themes on the platform show up without saving the settings, and no admin page waits on the API.
 * Brevo gets fresh lists from a 5 minute transient instead, refetched by whichever request needs them.
 */
class FieldsRefresh {
	public const HOOK = 'wk_gf_refresh_fields';

	public static function register( Addon $addon ): void {
		add_action( self::HOOK, static fn () => self::run( $addon ) );
	}

	/**
	 * Scheduled while the API is configured, unscheduled otherwise. Cheap when nothing changes:
	 * the cron array is an autoloaded option.
	 */
	public static function sync(): void {
		if ( ! function_exists( 'wp_next_scheduled' ) ) {
			return;
		}

		if ( ! Client::is_configured() ) {
			self::unschedule();
			return;
		}

		$recurrence = self::recurrence();
		if ( wp_next_scheduled( self::HOOK ) ) {
			if ( wp_get_schedule( self::HOOK ) === $recurrence ) {
				return;
			}

			// The wk_gf_refresh_fields_recurrence filter changed: schedule again with the new interval.
			self::unschedule();
		}

		wp_schedule_event( time() + self::initial_delay(), $recurrence, self::HOOK );
	}

	/**
	 * Seconds until the first scheduled refresh after the API becomes configured.
	 * Later runs follow the recurrence interval.
	 *
	 * @return int Filtered via wk_gf_refresh_fields_initial_delay (default HOUR_IN_SECONDS).
	 */
	private static function initial_delay(): int {
		/**
		 * Seconds until the first background refresh after the API becomes configured. Default one hour.
		 *
		 * Later runs follow wk_gf_refresh_fields_recurrence. Negative values count as 0.
		 *
		 * @param int $delay
		 */
		$delay = (int) apply_filters( 'wk_gf_refresh_fields_initial_delay', HOUR_IN_SECONDS );

		return max( 0, $delay );
	}

	public static function unschedule(): void {
		if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
			wp_clear_scheduled_hook( self::HOOK );
		}
	}

	/**
	 * A failed refresh keeps the cached themes and stores the error for the settings page, like a failed save does.
	 */
	public static function run( Addon $addon ): void {
		if ( ! Client::is_configured() ) {
			self::unschedule();
			return;
		}

		$response = ( new Client() )->refresh_fields();
		if ( is_wp_error( $response ) ) {
			$addon->log_error( __METHOD__ . '(): Could not refresh subscribe fields: ' . $response->get_error_message() );
			Client::store_fields_error( $response, 'cron' );
			return;
		}

		$addon->log_debug( __METHOD__ . '(): Subscribe fields refreshed.' );
	}

	/**
	 * @return string A WP-Cron schedule name: hourly (default), twicedaily, daily, or one added with cron_schedules.
	 */
	private static function recurrence(): string {
		/**
		 * The WP-Cron schedule of the background refresh: hourly (default), twicedaily, daily, or one added with cron_schedules.
		 *
		 * Unknown schedule names fall back to hourly.
		 *
		 * @param string $recurrence
		 */
		$recurrence = (string) apply_filters( 'wk_gf_refresh_fields_recurrence', 'hourly' );

		return array_key_exists( $recurrence, wp_get_schedules() ) ? $recurrence : 'hourly';
	}
}
