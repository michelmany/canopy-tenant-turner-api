<?php

namespace Inc\Base;

use Timber\Timber;

class BaseController {
	public string $plugin_path;
	public string $plugin_url;
	public string $plugin;

	public function __construct() {
		$this->plugin_path = plugin_dir_path( dirname( __FILE__, 2 ) );
		$this->plugin_url = plugin_dir_url( dirname( __FILE__, 2 ) );
		$this->plugin = plugin_basename( dirname( __FILE__, 3 ) ) . '/canopy-tenant-turner.php';
	}

	public function register(): void {

		add_action( 'init', function () {
			if ( ! class_exists( 'Timber\Timber' ) ) {
				return;
			}

			Timber::$locations = $this->plugin_path . 'views/';
		} );
	}
}
