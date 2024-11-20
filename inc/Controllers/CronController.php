<?php

namespace Inc\Controllers;

use Inc\API\ApiController;

class CronController {
	public function __construct() {
	}

	/**
	 * @return void
	 */
	public function register(): void {
		$interval = get_option( 'sync_interval', 'hourly' ); // default to hourly if no option is set

		if ( ! wp_next_scheduled( 'canopy_listings_sync' ) ) {
			wp_schedule_event( time(), $interval, 'canopy_listings_sync' );
		}
		add_action( 'canopy_listings_sync', [ new ApiController(), 'processLocationPost' ] );
	}

	public function canopyAddCustomSchedules( $schedules ) {
		$schedules['hourly'] = [
			'interval' => 3600,
			'display'  => __( 'Hourly' ),
		];
		$schedules['twicedaily'] = [
			'interval' => 43200,
			'display'  => __( 'Twice Daily' ),
		];
		$schedules['daily'] = [
			'interval' => 86400,
			'display'  => __( 'Daily' ),
		];

		return $schedules;
	}
}