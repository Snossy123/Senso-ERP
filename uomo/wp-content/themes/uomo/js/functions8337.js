(function ($) {
    "use strict";

    if (!$.apusThemeExtensions)
        $.apusThemeExtensions = {};
    
    function ApusThemeCore() {
        var self = this;
        // self.init();
    };

    ApusThemeCore.prototype = {
        /**
         *  Initialize
         */
        init: function() {
            var self = this;
            
            self.preloadSite();

            // slick init
            self.initSlick($("[data-carousel=slick]"));

            // Unveil init
            setTimeout(function(){
                self.layzyLoadImage();
            }, 200);
            
            // isoto
            self.initIsotope();

            // Sticky Header
            self.initHeaderSticky('main-sticky-header');
            self.initHeaderSticky('header-mobile');

            // back to top
            self.backToTop();

            self.loginOffcanvas();

            // popup image
            self.popupImage();

            $('[data-toggle="tooltip"]').tooltip();

            self.initPopupNewsletter();
            
            self.initUserInfo();

            self.initMobileMenu();

            self.initVerticalMenu();

            self.mainMenuInit();
            
            self.changePaddingTopContent();

            $(window).resize(function(){
                setTimeout(function(){
                    self.changePaddingTopContent();
                }, 50);
            });

            $('.footer-search-btn').on('click', function(){
                $('.footer-search-mobile').toggleClass('active');
            });
            $('.more').on('click', function(){
                $('.wrapper-morelink').toggleClass('active');
            });
            
            $('.navbar-wrapper .show-navbar-sidebar').on('click', function(){
                $(this).closest('.navbar-wrapper').find('.navbar-sidebar-wrapper').addClass('active');
                $(this).closest('.navbar-wrapper').find('.navbar-sidebar-overlay').addClass('active');
                $("body").css("overflow-y", "hidden");
            });
            $('.close-navbar-sidebar, .navbar-sidebar-overlay').on('click', function(){
                $(this).closest('.navbar-wrapper').find('.navbar-sidebar-wrapper').removeClass('active');
                $(this).closest('.navbar-wrapper').find('.navbar-sidebar-overlay').removeClass('active');
                $("body").css("overflow-y", "inherit");
            });

            // search
            $('.apus-search-form.style1 .show-search-header').on('click', function(){
                $(this).closest('.apus-search-form').toggleClass('active');
            });

            $('body').on('mouseenter', '.share-post-more', function(){
                $(this).find('.bo-social-icons').addClass('active');
            }).on('mouseleave', '.share-post-more', function(){
                $(this).find('.bo-social-icons').removeClass('active');
            })

            $(document.body).on('click', '.nav [data-toggle="dropdown"]' ,function(){
                if(  this.href && this.href != '#'){
                    window.location.href = this.href;
                }
            });

            if ( $('#commentform .form-control').length ) {
                $('#commentform .form-control').each(function(){
                    var content = $(this).val();
                    if ( content ) {
                        $(this).addClass('has-value');
                    } else {
                        $(this).removeClass('has-value');
                    }
                });
                
                $('#commentform .form-control').on('change', function(){
                    var content = $(this).val();
                    if ( content ) {
                        $(this).addClass('has-value');
                    } else {
                        $(this).removeClass('has-value');
                    }
                });
            }

            if ( $('form.register .form-control').length ) {
                $('form.register .form-control').each(function(){
                    var content = $(this).val();
                    if ( content ) {
                        $(this).addClass('has-value');
                    } else {
                        $(this).removeClass('has-value');
                    }
                });
                
                $('form.register .form-control').on('change', function(){
                    var content = $(this).val();
                    if ( content ) {
                        $(this).addClass('has-value');
                    } else {
                        $(this).removeClass('has-value');
                    }
                });
            }
            if ( $('form.login .form-control').length ) {
                $('form.login .form-control').each(function(){
                    var content = $(this).val();
                    if ( content ) {
                        $(this).addClass('has-value');
                    } else {
                        $(this).removeClass('has-value');
                    }
                });
                
                $('form.login .form-control').on('change', function(){
                    var content = $(this).val();
                    if ( content ) {
                        $(this).addClass('has-value');
                    } else {
                        $(this).removeClass('has-value');
                    }
                });
            }

            if ( $('form.edit-account .form-control').length ) {
                $('form.edit-account .form-control').each(function(){
                    var content = $(this).val();
                    if ( content ) {
                        $(this).addClass('has-value');
                    } else {
                        $(this).removeClass('has-value');
                    }
                });
                
                $('form.edit-account .form-control').on('change', function(){
                    var content = $(this).val();
                    if ( content ) {
                        $(this).addClass('has-value');
                    } else {
                        $(this).removeClass('has-value');
                    }
                });
            }
            self.loadExtension();
        },
        /**
         *  Extensions: Load scripts
         */
        loadExtension: function() {
            var self = this;
            
            if ($.apusThemeExtensions.quantity_increment) {
                $.apusThemeExtensions.quantity_increment.call(self);
            }

            if ($.apusThemeExtensions.shop) {
                $.apusThemeExtensions.shop.call(self);
            }

            if ($.apusThemeExtensions.store_map) {
                $.apusThemeExtensions.store_map.call(self);
            }
        },
        initSlick: function(element) {
            var self = this;
            element.each( function(){
                var config = {
                    infinite: false,
                    arrows: $(this).data( 'nav' ),
                    dots: $(this).data( 'pagination' ),
                    slidesToShow: 4,
                    slidesToScroll: 4,
                    prevArrow:"<button type='button' class='slick-arrow slick-prev'><i class='flaticon-back'></i></span><span class='textnav'>"+ uomo_opts.previous +"</span></button>",
                    nextArrow:"<button type='button' class='slick-arrow slick-next'><span class='textnav'>"+ uomo_opts.next +"</span><i class='flaticon-next'></i></button>",
                };
            
                var slick = $(this);
                if( $(this).data('items') ){
                    config.slidesToShow = $(this).data( 'items' );
                    var slidestoscroll = $(this).data( 'items' );
                }
                if( $(this).data('infinite') ){
                    config.infinite = true;
                }
                if( $(this).data('autoplay') ){
                    config.autoplay = true;
                    config.autoplaySpeed = 2500;
                    config.pauseOnHover = true;
                }
                if( $(this).data('disable_draggable') ){
                    config.touchMove = false;
                    config.draggable = false;
                    config.swipe = false;
                    config.swipeToSlide = false;
                }
                if( $(this).data('centermode') ){
                    config.centerMode = true;
                }
                if( $(this).data('vertical') ){
                    config.vertical = true;
                }
                if( $(this).data('rows') ){
                    config.rows = $(this).data( 'rows' );
                }
                if( $(this).data('asnavfor') ){
                    config.asNavFor = $(this).data( 'asnavfor' );
                }
                if( $(this).data('slidestoscroll') ){
                    var slidestoscroll = $(this).data( 'slidestoscroll' );
                }
                if( $(this).data('focusonselect') ){
                    config.focusOnSelect = $(this).data( 'focusonselect' );
                }
                config.slidesToScroll = slidestoscroll;

                if ($(this).data('large')) {
                    var desktop = $(this).data('large');
                } else {
                    var desktop = config.items;
                }
                if ($(this).data('smalldesktop')) {
                    var smalldesktop = $(this).data('smalldesktop');
                } else {
                    if ($(this).data('large')) {
                        var smalldesktop = $(this).data('large');
                    } else{
                        var smalldesktop = config.items;
                    }
                }
                if ($(this).data('medium')) {
                    var medium = $(this).data('medium');
                } else {
                    var medium = config.items;
                }
                if ($(this).data('smallmedium')) {
                    var smallmedium = $(this).data('smallmedium');
                } else {
                    var smallmedium = 2;
                }
                if ($(this).data('extrasmall')) {
                    var extrasmall = $(this).data('extrasmall');
                } else {
                    var extrasmall = 2;
                }
                if ($(this).data('smallest')) {
                    var smallest = $(this).data('smallest');
                } else {
                    var smallest = 1;
                }


                if ($(this).data('slidestoscroll_large')) {
                    var slidestoscroll_desktop = $(this).data('slidestoscroll_large');
                } else {
                    var slidestoscroll_desktop = config.slidesToScroll;
                }
                if ($(this).data('slidestoscroll_smalldesktop')) {
                    var slidestoscroll_smalldesktop = $(this).data('slidestoscroll_smalldesktop');
                } else {
                    if ($(this).data('slidestoscroll_large')) {
                        var slidestoscroll_smalldesktop = $(this).data('slidestoscroll_large');
                    } else{
                        var slidestoscroll_smalldesktop = config.items;
                    }
                }
                if ($(this).data('slidestoscroll_medium')) {
                    var slidestoscroll_medium = $(this).data('slidestoscroll_medium');
                } else {
                    var slidestoscroll_medium = config.items;
                }
                if ($(this).data('slidestoscroll_smallmedium')) {
                    var slidestoscroll_smallmedium = $(this).data('slidestoscroll_smallmedium');
                } else {
                    var slidestoscroll_smallmedium = smallmedium;
                }
                if ($(this).data('slidestoscroll_extrasmall')) {
                    var slidestoscroll_extrasmall = $(this).data('slidestoscroll_extrasmall');
                } else {
                    var slidestoscroll_extrasmall = extrasmall;
                }
                if ($(this).data('slidestoscroll_smallest')) {
                    var slidestoscroll_smallest = $(this).data('slidestoscroll_smallest');
                } else {
                    var slidestoscroll_smallest = smallest;
                }
                config.responsive = [
                    {
                        breakpoint: 321,
                        settings: {
                            slidesToShow: smallest,
                            slidesToScroll: slidestoscroll_smallest,
                        }
                    },
                    {
                        breakpoint: 580,
                        settings: {
                            slidesToShow: extrasmall,
                            slidesToScroll: slidestoscroll_extrasmall,
                        }
                    },
                    {
                        breakpoint: 769,
                        settings: {
                            slidesToShow: smallmedium,
                            slidesToScroll: slidestoscroll_smallmedium
                        }
                    },
                    {
                        breakpoint: 981,
                        settings: {
                            slidesToShow: medium,
                            slidesToScroll: slidestoscroll_medium
                        }
                    },
                    {
                        breakpoint: 1200,
                        settings: {
                            slidesToShow: smalldesktop,
                            slidesToScroll: slidestoscroll_smalldesktop
                        }
                    },
                    {
                        breakpoint: 1501,
                        settings: {
                            slidesToShow: desktop,
                            slidesToScroll: slidestoscroll_desktop
                        }
                    }
                ];

                if ( $('html').attr('dir') == 'rtl' ) {
                    config.rtl = true;
                }

                $(this).slick( config );

            } );

            // Fix owl in bootstrap tabs
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var target = $(e.target).attr("href");
                var $slick = $(".slick-carousel", target);

                if ($slick.length > 0 && $slick.hasClass('slick-initialized')) {
                    $slick.slick('refresh');
                }
                self.layzyLoadImage();
            });
        },
        layzyLoadImage: function() {
            $(window).off('scroll.unveil resize.unveil lookup.unveil');
            var $images = $('.image-wrapper:not(.image-loaded) .unveil-image'); // Get un-loaded images only
            if ($images.length) {
                $images.unveil(1, function() {
                    $(this).load(function() {
                        $(this).parents('.image-wrapper').first().addClass('image-loaded');
                        $(this).removeAttr('data-src');
                        $(this).removeAttr('data-srcset');
                        $(this).removeAttr('data-sizes');
                    });
                });
            }

            var $images = $('.product-image:not(.image-loaded) .unveil-image'); // Get un-loaded images only
            if ($images.length) {
                $images.unveil(1, function() {
                    $(this).load(function() {
                        $(this).parents('.product-image').first().addClass('image-loaded');
                    });
                });
            }
        },
        initIsotope: function() {
            $('.isotope-items').each(function(){  
                var $container = $(this);
                
                $container.imagesLoaded( function(){
                    $container.isotope({
                        itemSelector : '.isotope-item',
                        transformsEnabled: true,         // Important for videos
                        masonry: {
                            columnWidth: $container.data('columnwidth')
                        }
                    }); 
                });
            });

            /*---------------------------------------------- 
             *    Apply Filter        
             *----------------------------------------------*/
            $('.isotope-filter li a').on('click', function(){
               
                var parentul = $(this).parents('ul.isotope-filter').data('related-grid');
                $(this).parents('ul.isotope-filter').find('li a').removeClass('active');
                $(this).addClass('active');
                var selector = $(this).attr('data-filter'); 
                $('#'+parentul).isotope({ filter: selector }, function(){ });
                
                return(false);
            });
        },
        initHeaderSticky: function(main_sticky_class) {
            if ( $('.' + main_sticky_class).length ) {

                if ( typeof Waypoint !== 'undefined' ) {
                    
                    if ( $('.' + main_sticky_class) && typeof Waypoint.Sticky !== 'undefined' ) {
                        
                        var sticky = new Waypoint.Sticky({
                            element: $('.' + main_sticky_class)[0],
                            wrapper: '<div class="main-sticky-header-wrapper">',
                            offset: '-10px',
                            stuckClass: 'sticky-header'
                        });
                    }
                }
            }
        },
        backToTop: function () {
            $(window).scroll(function () {
                if ($(this).scrollTop() > 400) {
                    $('#back-to-top').addClass('active');
                } else {
                    $('#back-to-top').removeClass('active');
                }
            });
            $('#back-to-top').on('click', function () {
                $('html, body').animate({scrollTop: '0px'}, 800);
                return false;
            });
        },
        popupImage: function() {
            // popup
            $(".popup-image").magnificPopup({type:'image'});
            $('.popup-video').magnificPopup({
                disableOn: 700,
                type: 'iframe',
                mainClass: 'mfp-fade',
                removalDelay: 160,
                preloader: false,
                fixedContentPos: false
            });

            $('.widget-gallery').each(function(){
                var tagID = $(this).attr('id');
                $('#' + tagID).magnificPopup({
                    delegate: '.popup-image-gallery',
                    type: 'image',
                    tLoading: 'Loading image #%curr%...',
                    mainClass: 'mfp-img-mobile',
                    gallery: {
                        enabled: true,
                        navigateByImgClick: true,
                        preload: [0,1] // Will preload 0 - before current, and 1 after the current image
                    }
                });
            });

            $('.sizeguides-btn').magnificPopup({
                mainClass: 'apus-mfp-zoom-in zoom-sizeguides',
                type:'inline',
                midClick: true,
            });
        },
        preloadSite: function() {
            // preload page
            if ( $('body').hasClass('apus-body-loading') ) {
                setTimeout(function(){
                    $('body').removeClass('apus-body-loading');
                    $('.apus-page-loading').fadeOut(100);
                }, 150);
            }
        },
        initPopupNewsletter: function() {
            var self = this;

            if ($('.popupnewsletter').length > 0) {
                setTimeout(function(){
                    var hiddenmodal = self.getCookie('hidde_popup_newsletter');
                    if (hiddenmodal == "") {
                        var popup_content = $('.popupnewsletter').html();
                        $.magnificPopup.open({
                            mainClass: 'apus-mfp-zoom-in popupnewsletter-wrapper',
                            modal:true,
                            items    : {
                                src : popup_content,
                                type: 'inline'
                            },
                            callbacks: {
                                close: function() {
                                    var dont = $('.close-dont-show').attr('data-dont');
                                    if ( dont === 'yes' ) {
                                        self.setCookie('hidde_popup_newsletter', 1, 30);
                                    }
                                }
                            }
                        });
                    }
                }, 3000);
            }
            $('body').on('click', '.apus-mfp-close', function(e){
                e.preventDefault();
                $.magnificPopup.close();
            });
            $('body').on('click', '.close-dont-show', function(e){
                e.preventDefault();
                $(this).attr('data-dont', 'yes');
                $.magnificPopup.close();
            });
        },
        initUserInfo: function() {
            $('.login.popup').on('click', function(e) {
                e.preventDefault();
                var popup_content = $(this).parent().find('.header-customer-login-wrapper').html();
                $.magnificPopup.open({
                    mainClass: 'apus-mfp-zoom-in login-wrapper',
                    modal:true,
                    items    : {
                        src : popup_content,
                        type: 'inline'
                    }
                });
            });
        },

        loginOffcanvas: function() {
            $('.offcanvas-account').on('click', function(){
                if ( $('.offcanvas-content-login').hasClass('active') ) {
                    $('.offcanvas-content-login').removeClass('active');
                    $('.overlay-offcanvas-content-login').removeClass('active');
                } else {
                    $('.offcanvas-content-login').addClass('active');
                    $('.overlay-offcanvas-content-login').addClass('active');
                }
            });
            $('.overlay-offcanvas-content-login, .close-offcanvas-account').on('click', function(){
                $('.offcanvas-content-login').removeClass('active');
                $('.overlay-offcanvas-content-login').removeClass('active');
            });
        },

        initVerticalMenu: function() {
            // mobile menu
            $('.show-hover').on('click', function (e) {
                e.stopPropagation();
                $('.show-hover .content-vertical').toggle(350);           
            });
            $('body').on('click', function() {
                $('.show-hover .content-vertical').slideUp(350);
            });
            $('.content-vertical').on('click', function(e) {
                e.stopPropagation();
            });
            
            $('body:not(.home) .show-in-home').on('click', function (e) {
                e.stopPropagation();
                $('.show-in-home .content-vertical').toggle(350);           
            });
            // show vertical mobile
            $('.mobile-vertical-menu-title').click(function(){
                $('.mobile-vertical-menu').slideToggle();
                $(this).toggleClass('active');
                if ( $(this).find('i').hasClass('fa-angle-down') ) {
                    $(this).find('i').removeClass('fa-angle-down').addClass('fa-angle-up');
                } else {
                    $(this).find('i').addClass('fa-angle-down').removeClass('fa-angle-up');
                }
            });
            $('#vertical-mobile-menu .has-submenu > .icon-toggle').on('click', function (e) {
                e.stopPropagation();
                $(this).parent().find('> .sub-menu').toggle(350);
                if ( $(this).find('i').hasClass('ti-plus') ) {
                    $(this).find('i').removeClass('ti-plus').addClass('ti-minus');
                } else {
                    $(this).find('i').removeClass('ti-minus').addClass('ti-plus');
                }
            });
        },

        mainMenuInit: function() {
            $('.apus-megamenu .megamenu').each(function(e){
                var $this = $(this);
                $this.on('mouseenter', function(){
                    $('body').addClass('show-header-static');
                }).on('mouseleave', function(){
                    $('body').removeClass('show-header-static');
                });
            });
        },

        changePaddingTopContent: function() {
            if ($(window).width() >= 992) {
                if ( $('.apus-header').length ) {
                    var header = $('.apus-header').outerHeight();
                    $('.page-404').css({'padding-top': header});
                    $('body.detail-shop-v4 .archive-shop').css({'padding-top': header});
                    $('body.detail-shop-v4 .archive-shop .image-mains').css({'margin-top': - header});
                }
            } else {
                if ( $('.header-mobile').length ) {
                    var header = $('.header-mobile').outerHeight();
                    $('.page-404').css({'padding-top': header});
                    $('body.detail-shop-v4 .archive-shop').css({'padding-top': 0});
                    $('body.detail-shop-v4 .archive-shop .image-mains').css({'margin-top': 0});
                }
            }
        },

        initMobileMenu: function() {

            // mobile menu
            $('.btn-toggle-canvas,.btn-showmenu').on('click', function (e) {
                e.stopPropagation();
                $('.apus-offcanvas').toggleClass('active');           
                $('.over-dark').toggleClass('active');

                $("#mobile-menu-container").slidingMenu({
                    backLabel: uomo_opts.backlabel
                });
            });
            $('body').on('click', function() {
                if ($('.apus-offcanvas').hasClass('active')) {
                    $('.apus-offcanvas').toggleClass('active');
                    $('.over-dark').toggleClass('active');
                }
            });
            $('.apus-offcanvas').on('click', function(e) {
                e.stopPropagation();
            });
            


            // sidebar mobile
            $('.sidebar-right, .sidebar-left').perfectScrollbar();
            $('body').on('click', '.mobile-sidebar-btn', function(){
                if ( $('.sidebar-left').length > 0 ) {
                    $('.sidebar-left').toggleClass('active');
                } else if ( $('.sidebar-right').length > 0 ) {
                    $('.sidebar-right').toggleClass('active');
                }
                $('.mobile-sidebar-panel-overlay').toggleClass('active');
            });
            $('body').on('click', '.mobile-sidebar-panel-overlay, .close-sidebar-btn', function(){
                if ( $('.sidebar-left').length > 0 ) {
                    $('.sidebar-left').removeClass('active');
                } else if ( $('.sidebar-right').length > 0 ) {
                    $('.sidebar-right').removeClass('active');
                }
                $('.mobile-sidebar-panel-overlay').removeClass('active');
            });
        },
        setCookie: function(cname, cvalue, exdays) {
            var d = new Date();
            d.setTime(d.getTime() + (exdays*24*60*60*1000));
            var expires = "expires="+d.toUTCString();
            document.cookie = cname + "=" + cvalue + "; " + expires+";path=/";
        },
        getCookie: function(cname) {
            var name = cname + "=";
            var ca = document.cookie.split(';');
            for(var i=0; i<ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1);
                if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
            }
            return "";
        }
    }

    $.apusThemeCore = ApusThemeCore.prototype;
    
    
    $.fn.wrapStart = function(numWords){
        return this.each(function(){
            var $this = $(this);
            var node = $this.contents().filter(function(){
                return this.nodeType == 3;
            }).first(),
            text = node.text().trim(),
            first = text.split(' ', 1).join(" ");
            if (!node.length) return;
            node[0].nodeValue = text.slice(first.length);
            node.before('<b>' + first + '</b>');
        });
    };

    $(document).ready(function() {
        // Initialize script
        var apusthemecore_init = new ApusThemeCore();
        apusthemecore_init.init();

        $('.mod-heading .widget-title > span').wrapStart(1);
    });

    jQuery(window).on("elementor/frontend/init", function() {
        
        var apusthemecore_init = new ApusThemeCore();

        // General element
        elementorFrontend.hooks.addAction( "frontend/element_ready/apus_element_brands.default",
            function($scope) {
                apusthemecore_init.initSlick($scope.find('.slick-carousel'));
            }
        );

        elementorFrontend.hooks.addAction( "frontend/element_ready/apus_element_features_box.default",
            function($scope) {
                apusthemecore_init.initSlick($scope.find('.slick-carousel'));
            }
        );

        elementorFrontend.hooks.addAction( "frontend/element_ready/apus_element_posts.default",
            function($scope) {
                apusthemecore_init.initSlick($scope.find('.slick-carousel'));
            }
        );

        elementorFrontend.hooks.addAction( "frontend/element_ready/apus_element_testimonials.default",
            function($scope) {
                apusthemecore_init.initSlick($scope.find('.slick-carousel'));
            }
        );

        elementorFrontend.hooks.addAction( "frontend/element_ready/apus_element_instagram.default",
            function($scope) {
                apusthemecore_init.initSlick($scope.find('.slick-carousel'));
            }
        );

        elementorFrontend.hooks.addAction( "frontend/element_ready/apus_element_woo_products.default",
            function($scope) {
                apusthemecore_init.initSlick($scope.find('.slick-carousel'));
            }
        );

        elementorFrontend.hooks.addAction( "frontend/element_ready/apus_element_woo_product_tabs.default",
            function($scope) {
                apusthemecore_init.initSlick($scope.find('.slick-carousel'));
            }
        );

        elementorFrontend.hooks.addAction( "frontend/element_ready/apus_element_woo_categories.default",
            function($scope) {
                apusthemecore_init.initSlick($scope.find('.slick-carousel'));
            }
        );

        elementorFrontend.hooks.addAction( "frontend/element_ready/apus_element_woo_products_specific.default",
            function($scope) {
                apusthemecore_init.initSlick($scope.find('.slick-carousel'));
            }
        );

        elementorFrontend.hooks.addAction( "frontend/element_ready/apus_element_woo_product_tabs_special.default",
            function($scope) {
                apusthemecore_init.initSlick($scope.find('.slick-carousel'));
            }
        );

        elementorFrontend.hooks.addAction( "frontend/element_ready/apus_element_woo_products_deal.default",
            function($scope) {
                if ( $scope.find('.slick-carousel').length ) {
                    apusthemecore_init.initSlick($scope.find('.slick-carousel'));
                }
            }
        );

        

    });

})(jQuery);

