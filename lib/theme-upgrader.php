<?php
class WPAS_Theme_Upgrader extends Theme_Upgrader {
	function upgrade_strings() {
		$this->strings['up_to_date'] = __('The theme is at the latest version.', 'wp-app-store');
		$this->strings['no_package'] = __('Update package not available.', 'wp-app-store');
		$this->strings['downloading_package'] = __('Downloading update&#8230;', 'wp-app-store');
		$this->strings['unpack_package'] = __('Unpacking the update&#8230;', 'wp-app-store');
		$this->strings['remove_old'] = __('Removing the old version of the theme&#8230;', 'wp-app-store');
		$this->strings['remove_old_failed'] = __('Could not remove the old theme.', 'wp-app-store');
		$this->strings['process_failed'] = __('Theme update failed.', 'wp-app-store');
		$this->strings['process_success'] = __('Theme updated successfully.', 'wp-app-store');
	}

	function install_strings() {
        $this->strings['no_package'] = __('Install package not available.', 'wp-app-store');
		$this->strings['downloading_package'] = __('Downloading install package&#8230;', 'wp-app-store');
		$this->strings['unpack_package'] = __('Unpacking the package&#8230;', 'wp-app-store');
		$this->strings['installing_package'] = __('Installing the theme&#8230;', 'wp-app-store');
		$this->strings['process_failed'] = __('Theme install failed.', 'wp-app-store');
		$this->strings['process_success'] = __('Theme installed successfully.', 'wp-app-store');
	}
}