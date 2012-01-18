<?php $this->header(); ?>

<div class="product single">

    <div class="sidebar">
        <img class="featured-image" src="<?php echo $product->image->src; ?>" alt="<?php echo esc_attr( $product->title ); ?>" />
        
        <?php
        $css_class = '';
        
        $price = ( $product->price ) ? $product->price : 0;
        $price = '$' . number_format( $price );
        
        if ( $is_purchased ) {
            $url = $this->wpas->get_install_url( $product );
            $button_txt = __( 'Install', 'wp-app-store' );
        }
        else {
            $url = $this->wpas->get_buy_url( $product );
            if ( $is_bonus_applicable ) {
                $button_txt = __( 'Use Bonus', 'wp-app-store' );
            }
            else {
                $button_txt = __( 'Buy &amp; Install', 'wp-app-store' );
            }
            $css_class = 'buy';
        }
        
        if ( $product->product_type == 'theme' ) {
            $category_url = $this->wpas->themes_url . '&wpas-categories[]=' . $product->category->id;
            $installed_url = '<a class="more" href="' . self_admin_url('themes.php') . '">' . __( 'View installed themes &#8594;', 'wp-app-store' ) . '</a>';
        }
        else {
            $category_url = $this->wpas->plugins_url . '&wpas-categories[]=' . $product->category->id;
            $installed_url = '<a class="more" href="' . self_admin_url('plugins.php') . '">' . __( 'View installed plugins &#8594;', 'wp-app-store' ) . '</a>';
        }
        ?>
        <div class="install <?php echo $css_class; ?>">
            <?php if ( $installed_version = $this->product_installed_version() ) : ?>
                <div class="installed-msg">
                    <?php if ( $is_purchased ) : ?>
                        <?php printf( __( 'This %s is already installed.', 'wp-app-store' ), $product->product_type ); ?><br />
                        <?php echo $installed_url; ?><br />
                        <a class="more" href="<?php echo $this->wpas->purchases_url; ?>"><?php _e( 'Review your purchases &#8594;', 'wp-app-store' ); ?></a>
                    <?php else : ?>
                        <?php printf( __( 'This %s is already installed but was not purchased through this store.', 'wp-app-store' ), $product->product_type ); ?><br />
                        <?php echo $installed_url; ?><br />
                    <?php endif; ?>
                </div>
                <div class="price"><?php echo $price; ?></div>
            <?php else : ?>
                <a href="<?php echo $url; ?>" class="install-button"><?php echo $button_txt; ?></a>
                <div class="price"><?php echo $price; ?></div>
                <?php if ( $is_purchased ) : ?>
                <div class="note">
                    <?php printf( __( 'You have already purchased this %s.', 'wp-app-store' ), $product->product_type ); ?><br />
                    <a class="more" href="<?php echo $this->wpas->purchases_url; ?>"><?php _e( 'Review your purchases &#8594;', 'wp-app-store' ); ?></a>
                </div>
                <?php elseif ( $is_bonus_applicable ) : ?>
                <div class="note">
                    <?php printf( __( 'You may use a bonus credit to get this %s for free.', 'wp-app-store' ), $product->product_type ); ?><br />
                    <a class="more" href="<?php echo $this->wpas->bonuses_url; ?>"><?php _e( 'Review your bonuses &#8594;', 'wp-app-store' ); ?></a>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if ( $product->bonus_count && !$is_purchased && !$is_bonus_applicable ) : ?>
            <p class="bonus-details">
                <strong>
                <?php if ( $product->bonus_count > 1 ) : ?>
                    <?php printf( __( '%s Bonus %ss Included', 'wp-app-store' ), $product->bonus_count, ucfirst( $product->product_type ) ); ?>
                <?php else : ?>
                    <?php printf( __( '%s Bonus %s Included', 'wp-app-store' ), $product->bonus_count, ucfirst( $product->product_type ) ); ?>
                <?php endif; ?>
                </strong>
                -
                <?php printf( __( 'Your account will receive %s bonus credits
                instantly after purchase.', 'wp-app-store' ), $product->bonus_count ); ?>
            </p>
        <?php endif; ?>
        
        <ul class="info">
            <li>
                <?php _e( 'Category:', 'wp-app-store' ); ?>
                <a href="<?php echo $category_url; ?>"><?php echo $product->category->name; ?></a>
            </li>
            <li>
                <?php _e( 'Updated:', 'wp-app-store' ); ?>
                <?php echo $product->version_date; ?>
            </li>
            <li>
                <?php _e( 'Version:', 'wp-app-store' ); ?>
                <?php echo $product->version; ?>
            </li>
            <li>
                <?php _e( 'Languages:', 'wp-app-store' ); ?>
                <?php echo $product->languages; ?>
            </li>
            <li>
                <?php echo $product->legal; ?>
            </li>
        </ul>

        <div class="requirements">
            <p><strong><?php _e( 'Requirements:', 'wp-app-store' ); ?></strong>
            WordPress <?php echo $product->required_wp_version; ?> or higher.
            <?php echo $product->other_requirements; ?>
            </p>

            <p>You are using <strong>WordPress <?php echo get_bloginfo( 'version' ); ?></strong>.</p>
        </div>
    </div>
    
    <div class="main">
        <h2><?php echo ucfirst($product->product_type); ?>: <?php echo $product->title; ?></h2>
        <p class="publishers">by <?php echo $this->publisher_list( true ); ?></p>
        
        <div class="copy">
            <?php echo force_balance_tags( $this->product_description() ); ?>
        </div>
        
        <?php
        if ( $product->links ) :
            ?>
            
            <ul class="links">
            
            <?php
            foreach ( $product->links as $link ) :
                ?>
                
                <li><a href="<?php echo $link->url; ?>" target="_blank"><span><?php echo $link->title; ?></span></a></li>
                
                <?php
            endforeach;
            ?>
            
            </ul>
            
            <?php
        endif;
        ?>

        <?php
        if ( $product->screenshots ) :
            ?>
            
            <div class="screenshots">
                
                <ul>
                
                <?php
                foreach ( $product->screenshots as $screenshot ) :
                    ?>
                    
                    <li>
                        <a href="<?php echo $screenshot->large->src; ?>" target="_blank" rel="prettyPhoto[product-screenshots]" title="">
                            <img src="<?php echo $screenshot->thumb->src; ?>" alt="" />
                        </a>
                    </li>
                    
                    <?php
                endforeach;
                ?>
                
                </ul>
                
            </div>
            
            <?php
        endif;
        ?>
        
    </div>
    
</div>

<?php $this->footer(); ?>