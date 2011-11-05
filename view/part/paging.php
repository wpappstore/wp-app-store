<?php if ( $paging->total > 1 ) : ?>

    <div class="paging">
    
        <?php
        $end_size = 1;
        $mid_size = 2;
        
        if ( !$paging->current ) $paging->current = 1;
        
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
        ?>
    
    </div>

<?php
endif;
