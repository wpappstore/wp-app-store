<div id="wp-app-store" class="wrap">

<div class="header">
    <?php screen_icon(); ?>
    <h2><?php echo $this->wpas->title; ?></h2>
    <div class="breadcrumbs">
        <?php
        /*
        $crumbs = array();
        switch ( $_GET['page'] ) {
            case 'wp-app-store':
                
                if ( isset( $_GET['wpas-action'] ) && 'view_product' == $_GET['wpas-action'] ) {
                    if ( 'theme' == $_GET['wpas-ptype'] ) {
                        $crumbs[] = '<a href="">' . __( 'Themes', 'wp-app-store' ) . '</a>';
                    }
                    else {
                        $crumbs[] = '<a href="">' . __( 'Plugins', 'wp-app-store' ) . '</a>';
                    }
                }
                
            break;
        }
        */
        ?>
    </div>    
    <div class="actions">
        <?php if ( $this->wpas->user ) : $user = $this->wpas->user; ?>
        Logged in as <strong><?php echo $user->fname . ' ' . $user->lname; ?></strong>
        <span class="sep">|</span>
        <a href="<?php echo $this->wpas->purchases_url; ?>" class="purchases">Purchases</a>
        <span class="sep">|</span>
        <a href="<?php echo $this->wpas->edit_profile_url; ?>" class="purchases">Edit Profile</a>
        <span class="sep">|</span>
        <a href="<?php echo $this->wpas->store_logout_url; ?>" class="logout">Logout</a>
        <?php else : ?>
        <a href="<?php echo $this->wpas->store_login_url; ?>" class="login">Sign in</a>
        or <a href="<?php echo $this->wpas->register_url; ?>" class="login">Create an account</a>
        <?php endif; ?>
    </div>
</div>