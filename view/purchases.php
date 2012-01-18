<?php $this->header(); ?>

<div class="purchases">
    
    <div class="section-title">
        <h3><?php _e( 'Purchases', 'wp-app-store' ); ?></h3>
    </div>
        
    <div class="listing">
  
        <?php
        if ( $items ) :

            global $product;
            foreach ( $items as $product ) :
                ?>
                
                <div class="product">
                    <a href="<?php echo $this->product_url(); ?>" class="screenshot">
                        <img src="<?php echo $product->image->src; ?>" width="<?php echo $product->image->width; ?>" height="<?php echo $product->image->height; ?>" alt="<?php echo esc_attr( $product->title ); ?>" />
                    </a>
                    <div class="details">
                        <h4><?php echo ucfirst($product->product_type); ?>: <?php echo $product->title; ?></h4>
                        <p class="publisher"><?php _e( 'by', 'wp-app-store' ); ?> <?php echo $this->publisher_list(); ?></p>
                        <p class="date"><?php _e( 'Purchased', 'wp-app-store' ); ?> <?php echo date( 'F jS, Y', $product->purchase_date ); ?></p>
                    </div>
                    <div class="status">
                        <p><?php _e( 'Latest Release:', 'wp-app-store' ); ?> <?php echo $product->version; ?></p>
                        <?php if ( $installed_version = $this->product_installed_version() ) : ?>
                            <?php if ( version_compare( $installed_version, $product->version ) < 0 ) : ?>
                                <p class="upgrade">
                                    <strong><?php printf( __( 'You currently have version %s installed.', 'wp-app-store' ), $installed_version ); ?></strong>
                                    <div class="button-wrap"><a href="<?php echo $this->wpas->get_upgrade_url( $product ); ?>" class="button" onclick="return confirm('<?php _e( 'Your current files will be overwritten. If you made any modifications to the files they will be lost.\nAre you sure you want to do this?', 'wp-app-store' ); ?>');"><?php _e( 'Upgrade', 'wp-app-store' ); ?></a></div>
                                </p>
                            <?php else : ?>
                                <p class="current">
                                    <?php _e( 'You currently have the latest version installed.', 'wp-app-store' ); ?>
                                </p>
                            <?php endif; ?>
                        <?php else : ?>
                            <p class="install button-wrap">
                                <a href="<?php echo $this->wpas->get_install_url( $product ); ?>" class="button"><?php _e( 'Install', 'wp-app-store' ); ?></a>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="cost">
                        <p><?php _e( 'Total Paid:', 'wp-app-store' ); ?> $<?php echo number_format( $product->total_cost, 2 ); ?></p>
                        <div class="button-wrap"><a target="_blank" href="<?php echo $this->wpas->receipt_url; ?>?wpas-oid=<?php echo urlencode( $product->purchase_id ); ?>" class="button"><?php _e( 'View Receipt', 'wp-app-store' ); ?></a></div>
                    </div>
                </div>    
                
                <?php            
            endforeach;

            $this->render_part( 'paging', compact( 'paging' ) );

        else :
            ?>
            
            <div class="copy">
                <p><?php _e( 'You do not have any purchases yet.'); ?></p>
            </div>
            
            <?php
        endif;
        ?>
        
    </div>
    
</div>

<?php $this->footer(); ?>