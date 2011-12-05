<?php
class WPAS_Theme_Upgrader_Skin extends Theme_Installer_Skin {
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
					<h3><?php echo $action, ' ', __( 'Theme', 'wp-app-store' ), ': ', $product->title; ?></h3>
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
		if ( empty($this->upgrader->result['destination_name']) )
			return;

		$theme_info = $this->upgrader->theme_info();
		if ( empty($theme_info) )
			return;
		$name = $theme_info['Name'];
		$stylesheet = $this->upgrader->result['destination_name'];
		$template = !empty($theme_info['Template']) ? $theme_info['Template'] : $stylesheet;

		$preview_link = htmlspecialchars( add_query_arg( array('preview' => 1, 'template' => $template, 'stylesheet' => $stylesheet, 'preview_iframe' => 1, 'TB_iframe' => 'true' ), trailingslashit(esc_url(get_option('home'))) ) );
		$activate_link = wp_nonce_url("themes.php?action=activate&amp;template=" . urlencode($template) . "&amp;stylesheet=" . urlencode($stylesheet), 'switch-theme_' . $template);

		$install_actions = array(
			'preview' => '<a href="' . $preview_link . '" class="thickbox thickbox-preview" title="' . esc_attr(sprintf(__('Preview &#8220;%s&#8221;'), $name)) . '">' . __('Preview') . '</a>',
			'activate' => '<a href="' . $activate_link .  '" class="activatelink" title="' . esc_attr( sprintf( __('Activate &#8220;%s&#8221;'), $name ) ) . '">' . __('Activate') . '</a>'
		);

		if ( is_network_admin() && current_user_can( 'manage_network_themes' ) )
			$install_actions['network_enable'] = '<a href="' . esc_url( wp_nonce_url( 'themes.php?action=enable&amp;theme=' . $template, 'enable-theme_' . $template ) ) . '" title="' . esc_attr__( 'Enable this theme for all sites in this network' ) . '" target="_parent">' . __( 'Network Enable' ) . '</a>';

        $install_actions['themes_page'] = '<a href="' . self_admin_url('themes.php') . '" title="' . esc_attr__('Themes page') . '" target="_parent">' . __('View Installed Themes', 'wp-app-store') . '</a>';

		if ( ! $this->result || is_wp_error($this->result) || is_network_admin() )
			unset( $install_actions['activate'], $install_actions['preview'] );

		$install_actions = apply_filters('install_theme_complete_actions', $install_actions, $this->api, $stylesheet, $theme_info);
		if ( ! empty($install_actions) )
			$this->feedback(implode(' | ', (array)$install_actions));
	}
}