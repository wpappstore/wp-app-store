(function($) {

    $(document).ready(function() {
        $('.archive .product-grid .product:nth-child(3n)').addClass('last-col').after('<div style="clear: both;"></div>');
        $('.home .product-grid .product:nth-child(2n+1)').addClass('last-col').after('<div style="clear: both;"></div>');
        
        $('#wp-app-store > .home').each(function() {
            $('.grid-sep', this).height($('.product-grid', this).height());
        });
        
        var $screenshots = $('.screenshots li'),
            count = $screenshots.size();
        if (count > 5) {
            var per_row = Math.ceil(count/2);
            $screenshots.eq(per_row-1).addClass('last-col').after('<div style="clear: both;"></div>');
        }
        else {
            $('.screenshots li:nth-child(5n)').addClass('last-col');
        }
        
        $('.expandable').each(function() {
            var $exp = $(this),
                more_txt = 'Show More <span>&#9660;</span>',
                $more = $('<a href="" class="more">' + more_txt + '</a>');
            $exp.hide().after($more);
            $more.toggle(function() {
                $exp.show();
                $(this).html('Show Less <span>&#9650;</span>');
                return false;
            },
            function() {
                $exp.hide();
                $(this).html(more_txt);
                return false;
            });
        });
        
        $('#wp-app-store .install.buy .install-button,\
          #wp-app-store > .header .login,\
          #wp-app-store > .header .logout,\
          #wp-app-store > .header .edit-profile').click(function(e) {
            popup_window($(this).attr('href'), 'wpas-popup', 675, 360, e);
            return false;
        });
        
        $("a[rel^='prettyPhoto[product-screenshots]']").prettyPhoto({
            show_title: true,
            social_tools: ''
        });
        
        /*
        $('form.archive-filter input').click(function() {
            this.form.submit();
        });
        */
        
    });
    
    function popup_window( url, name, width, height, e ) {
        var top = window.screenY + ($(window).height() / 2) - (height / 2),
            left = window.screenX + ($(window).width() / 2) - (width / 2);
        var p = window.open(url, name, 'width=' + width + ',height=' + height + ',location=1,scrollbars=1,top=' + top + ',left=' + left);
        p.focus();
    }

})(jQuery);
