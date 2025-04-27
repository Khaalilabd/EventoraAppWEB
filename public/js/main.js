(function($) {
    "use strict";

    // Gestion du loader
    $(window).on('load', function() {
        $('.fh5co-loader').fadeOut('slow', function() {
            $(this).remove();
        });
    });

    // Initialisation des compteurs
    var counters = function() {
        $('.js-counter').countTo({
            formatter: function(value, options) {
                return value.toFixed(options.decimals);
            },
        });
    };

    var counterWayPoint = function() {
        if ($('#fh5co-counter').length > 0) {
            $('#fh5co-counter').waypoint(function(direction) {
                if (direction === 'down' && !$(this.element).hasClass('animated')) {
                    setTimeout(counters, 400);
                    $(this.element).addClass('animated');
                }
            }, { offset: '90%' });
        }
    };

    // Parallax
    var parallax = function() {
        $(window).stellar();
    };

    // Animation des éléments
    var contentWayPoint = function() {
        var i = 0;
        $('.animate-box').waypoint(function(direction) {
            if (direction === 'down' && !$(this.element).hasClass('animated')) {
                i++;
                $(this.element).addClass('item-animate');
                setTimeout(function() {
                    $('body .animate-box.item-animate').each(function(k) {
                        var el = $(this);
                        setTimeout(function() {
                            var effect = el.data('animate-effect');
                            if (effect === 'fadeIn') {
                                el.addClass('fadeIn animated');
                            } else if (effect === 'fadeInLeft') {
                                el.addClass('fadeInLeft animated');
                            } else if (effect === 'fadeInRight') {
                                el.addClass('fadeInRight animated');
                            } else {
                                el.addClass('fadeInUp animated');
                            }
                            el.removeClass('item-animate');
                        }, k * 200, 'easeInOutExpo');
                    });
                }, 100);
            }
        }, { offset: '85%' });
    };

    // Magnific Popup
    var magnificPopup = function() {
        $('.image-popup').magnificPopup({
            type: 'image',
            removalDelay: 300,
            mainClass: 'mfp-with-zoom',
            gallery: {
                enabled: true
            },
            zoom: {
                enabled: true,
                duration: 300,
                easing: 'ease-in-out',
                opener: function(openerElement) {
                    return openerElement.is('img') ? openerElement : openerElement.find('img');
                }
            }
        });
    };

    // Initialisation du carrousel de témoignages
    var testimonialCarousel = function() {
        var $carousel = $('.owl-carousel-fullwidth');
        if ($carousel.length > 0) {
            $carousel.each(function() {
                var $this = $(this);
                var itemCount = $this.find('.item').length;
                if (itemCount > 0) {
                    $this.owlCarousel({
                        items: 1,
                        loop: itemCount > 1,
                        autoplay: itemCount > 0,
                        autoplayTimeout: 5000,
                        autoplayHoverPause: true,
                        nav: false,
                        dots: itemCount > 1,
                        animateOut: 'fadeOut',
                        animateIn: 'fadeIn'
                    });
                } else {
                    console.log('Aucun élément trouvé pour le carrousel:', $this);
                    $this.hide();
                }
            });
        }
    };

    // Gestion du menu mobile
    var mobileMenu = function() {
        $('.menu-toggle').click(function() {
            $('.fh5co-nav .menu-1 ul').toggleClass('active');
        });

        // Gestion des sous-menus déroulants sur mobile
        $('.fh5co-nav .menu-1 ul li.has-dropdown > a').click(function(e) {
            if ($(window).width() <= 768) {
                e.preventDefault();
                $(this).parent().toggleClass('active');
            }
        });
    };

    // Initialisation au chargement de la page
    $(function() {
        counterWayPoint();
        contentWayPoint();
        parallax();
        magnificPopup();
        testimonialCarousel();
        mobileMenu();
    });

})(jQuery);