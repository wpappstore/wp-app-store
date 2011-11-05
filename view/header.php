<div id="wp-app-store" class="wrap">

<div class="header">
    <?php screen_icon(); ?>
    <h2><?php _e( 'App Store', 'wp-app-store' ); ?></h2>
    <div class="breadcrumbs">
        <?php
        $crumbs = array();
        switch ( $_GET['page'] ) {
            case 'wp-app-store':
                
                if ( 'view_product' == $_GET['wpas-action'] ) {
                    if ( 'theme' == $_GET['wpas-ptype'] ) {
                        $crumbs[] = '<a href="">' . __( 'Themes', 'wp-app-store' ) . '</a>';
                    }
                    else {
                        $crumbs[] = '<a href="">' . __( 'Plugins', 'wp-app-store' ) . '</a>';
                    }
                }
                
            break;
        }
        ?>
    </div>    
    <div class="actions">
        <a href="" class="login">Login or Register</a>
    </div>
</div>