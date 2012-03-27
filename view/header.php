<div id="wp-app-store" class="wrap">

<div class="header">
    <?php screen_icon(); ?>
    <h2><?php echo $this->wpas->title; ?></h2>
    <div class="actions">
        <span class="logged-in" style="display: none;">
            Logged in as <strong class="email"></strong>
        
            <span class="purchases">
                <span class="sep">|</span>
                <a href="<?php echo $this->wpas->purchases_url; ?>" class="purchases">Purchases</a>
            </span>

            <span class="bonuses">
                <span class="sep">|</span>
                <a href="<?php echo $this->wpas->bonuses_url; ?>" class="bonuses">Bonuses</a>
            </span>
            
            <span class="sep">|</span>
            <a href="<?php echo $this->wpas->edit_profile_url; ?>" class="edit-profile">Edit Profile</a>

            <span class="sep">|</span>
            <a href="<?php echo $this->wpas->logout_url; ?>" class="logout">Logout</a>
        </span>

        <span class="logged-out" style="display: none;">
            <a href="<?php echo $this->wpas->store_login_url; ?>" class="login">Sign in</a>
            or <a href="<?php echo $this->wpas->register_url; ?>" class="login">Create an account</a>
        </span>
    </div>
</div>

<div class="content-wrapper">

<script>
var WPAPPSTORE = {};
WPAPPSTORE.API_URL = '<?php echo addslashes( $this->wpas->api_url ); ?>';
</script>
