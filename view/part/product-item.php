<a href="<?php echo $this->product_url(); ?>" class="product product-<?php echo $product->id; ?>">
    <div class="screenshot">
        <img src="<?php echo $product->image->src; ?>" width="<?php echo $product->image->width; ?>" height="<?php echo $product->image->height; ?>" alt="<?php echo esc_attr( $product->title ); ?>" />
    </div>
    <div class="caption">
        <h4><?php echo $product->title; ?></h4>
        <p class="publisher">by <?php echo $this->publisher_list(); ?></p>
    </div>
</a>
