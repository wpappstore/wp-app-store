<?php $this->header(); ?>

<div class="product single">

    <div class="sidebar">
        <img class="featured-image" src="<?php echo $product->image->src; ?>" alt="<?php echo esc_attr( $product->title ); ?>" />
        
        <?php
        if ( $product->price ) {
            $url = $this->wpas->get_buy_url( $product );
            $button_txt = __( 'Buy &amp; Install', 'wp-app-store' );
            $price = '$' . number_format( $product->price );
            $css_class = 'buy';
        }
        else {
            $url = $this->wpas->get_install_url( $product );
            $button_txt = __( 'Install', 'wp-app-store' );
            $price = __( 'Free', 'wp-app-store' );
            $css_class = '';
        }
        ?>
        <div class="install <?php echo $css_class; ?>">
            <a href="<?php echo $url; ?>" class="install-button"><?php echo $button_txt; ?></a>
            <div class="price"><?php echo $price; ?></div>
        </div>
        
        <ul class="info">
            <li>
                <?php _e( 'Category:', 'wp-app-store' ); ?>
                <?php echo $product->category->name; ?>
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
        <h2><?php echo $product->title; ?></h2>
        <p class="publishers">by <?php echo $this->publisher_list(); ?></p>
        
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
                        <a href="<?php echo $screenshot->large->src; ?>" target="_blank" class="thickbox" rel="product-screenshots">
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