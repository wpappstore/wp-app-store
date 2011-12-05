<?php
class WPAS_Plugin_Upgrader_Skin extends Plugin_Installer_Skin {
    public $wpas_view = null;
	public $wpas_product = null;

	function header() {
		if ( $this->done_header )
			return;
		$this->done_header = true;

        $this->wpas_view->header();
		
		if ( $this->wpas_is_upgrade ) {
			$action = __( 'Upgrading', 'wp-app-store' );
		}
		else {
			$action = __( 'Installing', 'wp-app-store' );
		}

		$product = $this->wpas_product;
		?>
		
		<div class="installing">
	
			<img class="featured-image" src="<?php echo $product->image->src; ?>" alt="<?php echo esc_attr( $product->title ); ?>" />
			
			<div class="details">
	
				<div class="section-title">
					<h3><?php echo $action, ' ', __( 'Plugin', 'wp-app-store' ), ': ', $product->title; ?></h3>
				</div>
		
		<?php
	}
	
    function footer() {
		?>
		
			</div>
		
		</div>
		
		<?php
		$this->wpas_view->footer();
	}

	function after() {

		$plugin_file = $this->upgrader->plugin_info();

		$install_actions = array();

		$from = isset($_GET['from']) ? stripslashes($_GET['from']) : 'plugins';

		if ( 'import' == $from )
			$install_actions['activate_plugin'] = '<a href="' . wp_nonce_url('plugins.php?action=activate&amp;from=import&amp;plugin=' . $plugin_file, 'activate-plugin_' . $plugin_file) . '" title="' . esc_attr__('Activate this plugin') . '" target="_parent">' . __('Activate Plugin &amp; Run Importer') . '</a>';
		else
			$install_actions['activate_plugin'] = '<a href="' . wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin_file, 'activate-plugin_' . $plugin_file) . '" title="' . esc_attr__('Activate this plugin') . '" target="_parent">' . __('Activate Plugin') . '</a>';

		if ( is_multisite() && current_user_can( 'manage_network_plugins' ) ) {
			$install_actions['network_activate'] = '<a href="' . wp_nonce_url('plugins.php?action=activate&amp;networkwide=1&amp;plugin=' . $plugin_file, 'activate-plugin_' . $plugin_file) . '" title="' . esc_attr__('Activate this plugin for all sites in this network') . '" target="_parent">' . __('Network Activate') . '</a>';
			unset( $install_actions['activate_plugin'] );
		}

		$install_actions['plugins_page'] = '<a href="' . self_admin_url('plugins.php') . '" title="' . esc_attr__('View Installed Plugins', 'wp-app-store') . '" target="_parent">' . __('View Installed Plugins', 'wp-app-store') . '</a>';

		if ( ! $this->result || is_wp_error($this->result) ) {
			unset( $install_actions['activate_plugin'] );
			unset( $install_actions['network_activate'] );
		}
		$install_actions = apply_filters('install_plugin_complete_actions', $install_actions, $this->api, $plugin_file);
		if ( ! empty($install_actions) )
			$this->feedback(implode(' | ', (array)$install_actions));
	}
}