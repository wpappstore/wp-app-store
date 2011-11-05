(function($) {

    $(document).ready(function() {
        $('.archive .product-grid .product:nth-child(3n+1)').addClass('last-col').after('<div style="clear: both;"></div>');
        
        $('.screenshots li:nth-child(4n)').addClass('last-col');
        
        $('.expandable').each(function() {
            var $exp = $(this),
                $more = $('<a href="" class="more">Show More...</a>');
            $exp.hide().before($more);
            $more.click(function() {
                $exp.show();
                $(this).hide();
                return false;
            });
        });
    });

})(jQuery);
