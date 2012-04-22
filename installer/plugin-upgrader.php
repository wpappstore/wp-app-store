<?php
class WPAS_Plugin_Upgrader extends Plugin_Upgrader {
	function upgrade_strings() {
		$this->strings['up_to_date'] = __('The plugin is at the latest version.', 'wp-app-store');
		$this->strings['no_package'] = __('Update package not available.', 'wp-app-store');
		$this->strings['downloading_package'] = __('Downloading update&#8230;', 'wp-app-store');
		$this->strings['unpack_package'] = __('Unpacking the update&#8230;', 'wp-app-store');
		$this->strings['deactivate_plugin'] = __('Deactivating the plugin&#8230;', 'wp-app-store');
		$this->strings['remove_old'] = __('Removing the old version of the plugin&#8230;', 'wp-app-store');
		$this->strings['remove_old_failed'] = __('Could not remove the old plugin.', 'wp-app-store');
		$this->strings['process_failed'] = __('Plugin update failed.', 'wp-app-store');
		$this->strings['process_success'] = __('Plugin updated successfully.', 'wp-app-store');
	}

	function install_strings() {
		$this->strings['no_package'] = __('Install package not available.', 'wp-app-store');
		$this->strings['downloading_package'] = __('Downloading install package&#8230;', 'wp-app-store');
		$this->strings['unpack_package'] = __('Unpacking the package&#8230;', 'wp-app-store');
		$this->strings['installing_package'] = __('Installing the plugin&#8230;', 'wp-app-store');
		$this->strings['process_failed'] = __('Plugin install failed.', 'wp-app-store');
		$this->strings['process_success'] = __('Plugin installed successfully.', 'wp-app-store');
	}

	function upgrade( $package ) {

		$this->init();
		$this->upgrade_strings();

		add_filter('upgrader_pre_install', array(&$this, 'deactivate_plugin_before_upgrade'), 10, 2);
		add_filter('upgrader_clear_destination', array(&$this, 'delete_old_plugin'), 10, 4);
		//'source_selection' => array(&$this, 'source_selection'), //theres a track ticket to move up the directory for zip's which are made a bit differently, useful for non-.org plugins.

		$this->run(array(
					'package' => $package,
					'destination' => WP_PLUGIN_DIR,
					'clear_destination' => true,
					'clear_working' => true,
					'hook_extra' => array(
								'plugin' => $plugin
					)
				));

		// Cleanup our hooks, incase something else does a upgrade on this connection.
		remove_filter('upgrader_pre_install', array(&$this, 'deactivate_plugin_before_upgrade'));
		remove_filter('upgrader_clear_destination', array(&$this, 'delete_old_plugin'));

		if ( ! $this->result || is_wp_error($this->result) )
			return $this->result;
	}
}