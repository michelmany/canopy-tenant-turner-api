<?php
/*
 * Plugin Name: Canopy Tenant Turner API
 * Description:       Plugin that will display listings data from the Tenant Turner API.
 * Version:           0.0.1
 * Author:            Michel Many
 * Author URI:        https://michelmany.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       canopy-tenant-turner-api
 * Domain Path:       /languages
 */

use Inc\Init;

defined( 'ABSPATH' ) or die();

define( 'CANOPY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CANOPY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( file_exists( CANOPY_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
	require_once CANOPY_PLUGIN_PATH . 'vendor/autoload.php';
}

/**
 * Initialize all the core classes of the plugin
 */
if ( class_exists( Init::class ) ) {
	Inc\Init::registerServices();
}
