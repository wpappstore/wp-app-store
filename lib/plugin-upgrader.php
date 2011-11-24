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
}