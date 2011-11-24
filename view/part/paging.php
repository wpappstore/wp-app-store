<?php if ( $paging->total > 1 ) : ?>

    <div class="paging">
    
        <?php
        $end_size = 1;
        $mid_size = 2;
        
        if ( !$paging->current ) $paging->current = 1;
        
        $prev = $paging->current - 1;
        if ( $prev == 1 ) {
            $prev_url = remove_query_arg( 'wpas-page' );
        }
        elseif ( $prev > 1 ) {
            $prev_url = add_query_arg( array ( 'wpas-page' => $prev ) );
        }
        else {
            $prev_url = '';
        }
        
        if ( $prev_url ) {
            printf( '<a href="%s" class="prev">%s</a>', $prev_url, __( '&#8592; Previous', 'wp-app-store' ) );
        }
        
        $dots = false;
        for ( $i = 1; $i <= $paging->total; $i++ ) {
            if ( $i == $paging->current || $i <= $end_size || ( $paging->current && $i >= $paging->current - $mid_size && $i <= $paging->current + $mid_size ) || $i > $paging->total - $end_size ) {
                $url = add_query_arg( array ( 'wpas-page' => $i ) );
                $current = ( $paging->current == $i ) ? ' class="current"' : '';
                printf( '<a href="%s" %s>%d</a>', $url, $current, $i );
                $dots = true;
            }
            elseif ( $dots ) {
                echo '<span class="dots">...</span>';
                $dots = false;
            }
        }
        
        $next = $paging->current + 1;
        if ( $next <= $paging->total ) {
            $next_url = add_query_arg( array ( 'wpas-page' => $next ) );
        }
        else {
            $next_url = '';
        }
        
        if ( $next_url ) {
            printf( '<a href="%s" class="prev">%s</a>', $next_url, __( 'Next &#8594;', 'wp-app-store' ) );
        }
        ?>
    
    </div>

<?php
endif;
