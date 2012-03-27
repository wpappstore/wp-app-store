(function($) {

    $(document).ready(function() {
        $('.archive .product-grid .product:nth-child(3n)').addClass('last-col').after('<div style="clear: both;"></div>');
        $('.home .product-grid .product:nth-child(2n+1)').addClass('last-col').after('<div style="clear: both;"></div>');
        
        $('#wp-app-store > .home').each(function() {
            $('.grid-sep', this).height($('.product-grid', this).height());
        });
        
        var $screenshots = $('.screenshots li'),
            count = $screenshots.size();
        if (count == 6) {
            $('.screenshots li:nth-child(4n)').addClass('last-col');
        }
        else {
            $('.screenshots li:nth-child(5n)').addClass('last-col');
        }

        if (count > 5) {
            $screenshots.filter('.last-col').after('<div style="clear: both;"></div>');
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
          #wp-app-store > .header .edit-profile').click(function(e) {
            popup_window($(this).attr('href'), 'wpas-popup', 675, 360, e);
            return false;
        });
        
        $("a[rel^='prettyPhoto[product-screenshots]']").prettyPhoto({
            show_title: true,
            social_tools: '',
            deeplinking: false,
            overlay_gallery: false
        });
    
        var $header = $('#wp-app-store > .header');
        
        var url = WPAPPSTORE.API_URL + '/user/menu/?callback=?';
        $.getJSON(url, function( data ) {
            if ( data && data.user ) {
                $('.email', $header).html( data.user.email );
                $('.logged-in', $header).show();
                user_menu_events();
            }
            else {
                $('.logged-out', $header).show();
            }
        });
        
        var user_menu_events = function() {
            $('.logout', $header).click(function() {
                var url = WPAPPSTORE.API_URL + '/user/logout/?callback=?',
                    $anch = $(this);
                $.getJSON(url, function( data ) {
                    document.location.href = $anch.attr('href');
                });
                return false;
            });
        };
        
        var $single = $('#wp-app-store .product.single');
        
        if ( $single.get(0) ) {
            var url = WPAPPSTORE.API_URL + '/user/' + WPAPPSTORE.PRODUCT_TYPE + '/' + WPAPPSTORE.PRODUCT_ID + '/?callback=?';
            $.getJSON(url, function( data ) {
                if ( data ) {
                    $install = $('.install', $single);
                    if ( data.is_purchased ) {
                        $('.not-purchased', $install).hide();
                        $('.purchased', $install).show();
                    }
                    else if ( data.is_bonus_applicable ) {
                        $('.not-purchased', $install).hide();
                        $('.bonus-applicable', $install).show();
                    }
                }
            });
        }
        
        var popup_window = function( url, name, width, height, e ) {
            var top = window.screenY + ($(window).height() / 2) - (height / 2),
                left = window.screenX + ($(window).width() / 2) - (width / 2);
            var p = window.open(url, name, 'width=' + width + ',height=' + height + ',location=1,scrollbars=1,top=' + top + ',left=' + left);
            p.focus();
        };
        
    });

})(jQuery);
