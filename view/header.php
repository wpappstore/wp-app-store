<div id="wp-app-store" class="wrap">

<div class="header">
    <?php screen_icon(); ?>
    <h2><?php echo $this->wpas->title; ?></h2>
    <div class="actions">
        <?php if ( $this->wpas->user ) : $user = $this->wpas->user; ?>
        Logged in as <strong><?php echo $user->fname . ' ' . $user->lname; ?></strong>
        <span class="sep">|</span>
        <a href="<?php echo $this->wpas->purchases_url; ?>" class="purchases">Purchases</a>
        <span class="sep">|</span>
        <a href="<?php echo $this->wpas->bonuses_url; ?>" class="bonuses">Bonuses</a>
        <span class="sep">|</span>
        <a href="<?php echo $this->wpas->edit_profile_url; ?>" class="edit-profile">Edit Profile</a>
        <span class="sep">|</span>
        <a href="<?php echo $this->wpas->store_logout_url; ?>" class="logout">Logout</a>
        <?php else : ?>
        <a href="<?php echo $this->wpas->store_login_url; ?>" class="login">Sign in</a>
        or <a href="<?php echo $this->wpas->register_url; ?>" class="login">Create an account</a>
        <?php endif; ?>
    </div>
</div>

<div class="content-wrapper">
