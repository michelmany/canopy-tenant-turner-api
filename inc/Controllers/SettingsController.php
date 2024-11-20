<?php

namespace Inc\Controllers;

use GuzzleHttp\Exception\GuzzleException;

class SettingsController {
	/**
	 * Render the settings page
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'addSettingsPage' ] );
		add_action( 'admin_init', [ $this, 'registerSettings' ] );
		add_action( 'wp_ajax_manual_sync_action', [ $this, 'manualSyncAction' ] ); // AJAX for manual sync
		add_action( 'admin_notices', [ $this, 'addSyncButton' ] ); // Add button to listing page
	}

	/**
	 * Render the settings page
	 * @return void
	 */
	public function addSyncButton(): void {
		$screen = get_current_screen();
		if ( $screen->post_type !== 'listing' || $screen->base !== 'edit' ) {
			return;
		}
		?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                // Find the location of the Add New button
                const addNewButton = document.querySelector('.page-title-action');

                if (addNewButton) {
                    // Create a new button element
                    const syncButton = document.createElement('a');
                    syncButton.classList.add('page-title-action');
                    syncButton.id = 'manual-sync';
                    syncButton.textContent = 'Sync Listings';
                    syncButton.href = '#'; // Change this to the desired URL for syncing listings

                    // Create a new span element for sync status
                    const syncStatus = document.createElement('span');
                    syncStatus.id = 'sync-status';
                    syncStatus.style.marginLeft = '15px';
                    syncStatus.style.marginRight = '15px';
                    syncStatus.textContent = ' Sync status: ' + (localStorage.getItem('syncStatus') || 'Not started');

                    // Insert the button after the Add New button
                    addNewButton.insertAdjacentElement('afterend', syncButton);

                    // Insert the status span after the sync button
                    syncButton.insertAdjacentElement('afterend', syncStatus);
                }
            });
        </script>
		<?php

		wp_enqueue_script( 'manual-sync-script', plugin_dir_url( __DIR__ ) . '../assets/js/manual-sync.js',
			[ 'jquery' ], null, true );
		wp_localize_script( 'manual-sync-script', 'ajax_object', [ 'ajax_url' => admin_url( 'admin-ajax.php' ) ] );
	}

	/**
	 * Add the settings page
	 * @return void
	 */
	public function addSettingsPage(): void {
		add_submenu_page(
			'edit.php?post_type=listing',
			'Sync Settings',
			'Sync Settings',
			'manage_options',
			'sync_settings',
			[ $this, 'renderSettingsPage' ]
		);
	}

	/**
	 * Render the settings page
	 * @return void
	 */
	public function registerSettings(): void {
		register_setting( 'sync_settings', 'sync_interval' );
		add_settings_section( 'sync_settings_section', 'Sync Interval Settings', null, 'sync_settings' );
		add_settings_field(
			'sync_interval',
			'Sync Interval',
			[ $this, 'renderIntervalField' ],
			'sync_settings',
			'sync_settings_section'
		);
	}

	/**
	 *
	 * @return void
	 */
	public function renderSettingsPage(): void {
		echo '<form action="options.php" method="post">';
		settings_fields( 'sync_settings' );
		do_settings_sections( 'sync_settings' );
		submit_button();
	}

	/**
	 * Render the interval field
	 * @return void
	 */
	public function renderIntervalField(): void {
		$interval = get_option( 'sync_interval', 'hourly' );
		echo '<select name="sync_interval">
                <option value="hourly" ' . selected( $interval, 'hourly', false ) . '>Hourly</option>
                <option value="twicedaily" ' . selected( $interval, 'twicedaily', false ) . '>Twice Daily</option>
                <option value="daily" ' . selected( $interval, 'daily', false ) . '>Daily</option>
              </select>';
	}

	/**
	 * Render the settings page
	 *
	 * @return void
	 * @throws GuzzleException
	 */
	public function manualSyncAction(): void {
		$apiController = new \Inc\API\ApiController();
		$apiController->processLocationPost();
		wp_send_json_success( 'Sync complete!' );
	}
}