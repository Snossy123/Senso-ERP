(function($) {
    "use strict";
    
    var map, mapSidebar, markers, CustomHtmlIcon, group;

    $.extend($.apusThemeCore, {
        /**
         *  Initialize scripts
         */
        store_map_init: function() {
            var self = this;

            if ($('#stores-google-maps').length) {
                L.Icon.Default.imagePath = 'wp-content/themes/uomo/images/';
            }
            
            setTimeout(function(){
                
                self.mapInit('stores-google-maps');

                self.searchStores('stores-google-maps');
            }, 50);
            
            self.googleAuto();

            $(document).on('click', '.view-more-less', function(e){
                e.preventDefault();
                $(this).toggleClass('show-less');
                $(this).closest('.details-store').find('.details-store-inner').slideToggle();
            });
        },
        googleAuto: function(){
            if (typeof google === 'object' && typeof google.maps === 'object') {
                function search_location_initialize() {
                        
                    var input = document.getElementById('filter-location');
                    var autocomplete = new google.maps.places.Autocomplete(input);
                    autocomplete.setTypes([]);

                    autocomplete.addListener( 'place_changed', function () {
                        var place = autocomplete.getPlace();
                        place.toString();
                        if (!place.geometry) {
                            window.alert("No details available for input: '" + place.name + "'");
                            return;
                        }
                        document.getElementById('filter-latitude').value = place.geometry.location.lat();
                        document.getElementById('filter-longitude').value = place.geometry.location.lng();

                        $('form.filter-stores').trigger('submit');
                    });
                }
                google.maps.event.addDomListener(window, 'load', search_location_initialize);
            }
        },
        searchStores: function(map_e_id) {
            var self = this;
            
            $('form.filter-stores').on('submit', function(e){
                e.preventDefault();
                var $this = $(this);
                if (self.mapAjax) {
                    return false;
                }

                $this.addClass('loading');
                var $location = $(this).data('settings');

                var latitude = $(this).find('#filter-latitude').val();
                var longitude = $(this).find('#filter-longitude').val();

                

                self.mapAjax = $.ajax({
                    url: uomo_maps_opts.ajaxurl,
                    type:'POST',
                    dataType: 'html',
                    data: $this.serialize()+"&action=uomo_get_ajax_stores"
                }).done(function(data) {
                    $this.removeClass('loading');
                    $this.closest('.widget-our-stores').find('.store-box-content').html(data);
                    setTimeout(function(){
                        self.updateMakerCards(map_e_id);
                    });

                    self.mapAjax = false;

                    map.flyTo(new L.LatLng(latitude, longitude), uomo_maps_opts.zoom);
                });
            });
        },
        mapInit: function(map_e_id) {
            var self = this;

            var $window = $(window);

            if (!$('#' + map_e_id).length) {
                return;
            }

            map = L.map(map_e_id, {
                scrollWheelZoom: false
            });

            markers = new L.MarkerClusterGroup({
                showCoverageOnHover: false
            });

            
            $window.on('pxg:refreshmap', function() {
                map._onResize();
            });

            $window.on('pxg:simplerefreshmap', function() {
                map._onResize();
            });
            
            if ( uomo_maps_opts.custom_style != '' ) {
                try {
                    var custom_style = $.parseJSON(uomo_maps_opts.custom_style);
                    var tileLayer = L.gridLayer.googleMutant({
                        type: 'roadmap',
                        styles: custom_style
                    });
                } catch(err) {
                    var tileLayer = L.gridLayer.googleMutant({
                        type: 'roadmap'
                    });
                }
            } else {
                var tileLayer = L.gridLayer.googleMutant({
                    type: 'roadmap'
                });
            }
            $('#stores-google-maps').addClass('map--google');


            map.addLayer(tileLayer);

            // check archive/single page
            if ( !$('#'+map_e_id).is('.single-store-map') ) {
                self.updateMakerCards(map_e_id);
            }

            map.setView(new L.LatLng(uomo_maps_opts.latitude, uomo_maps_opts.longitude), uomo_maps_opts.zoom);

            
        },
        updateMakerCards: function(map_e_id) {
            var self = this;
            var $items = $('.store-box-content .store-item');
            if ($('#' + map_e_id).length && typeof map !== "undefined") {
                
                if (!$items.length) {
                    if ( $('#filter-latitude').val() && $('#filter-longitude').val() ) {
                        map.setView([$('#filter-latitude').val(), $('#filter-longitude').val()], 12);
                    }
                    return;
                }

                map.removeLayer(markers);
                markers = new L.MarkerClusterGroup({
                    showCoverageOnHover: false
                });
                $items.each(function(i, obj) {
                    self.addMakerToMap($(obj), true);
                });

                map.addLayer(markers);
            }
        },
        addMakerToMap: function($item, archive) {
            var self = this;
            var marker;

            if ( $item.data('latitude') == "" || $item.data('longitude') == "") {
                return;
            }

            var img_agency = '<img src="'+ uomo_maps_opts.pin_url +'">';
            
            var mapPinHTML = "<div class='map-popup'><div class='icon-wrapper has-img'>" + img_agency + "</div></div>";
            
            var LeafIcon = L.Icon.extend({
                options: {
                    iconUrl : uomo_maps_opts.pin_url,
                    iconSize:     [50, 50],
                    iconAnchor:   [25, 50],
                    popupAnchor:  [0, -50]
                }
            });
            
            marker = L.marker([$item.data('latitude'), $item.data('longitude')], {
                icon: new LeafIcon()
            });

            if (typeof archive !== "undefined") {
                
                $item.on('click', '.see-on-the-map', function() {
                    map.setView(new L.LatLng($item.data('latitude'), $item.data('longitude')), 15);
                    marker.openPopup();
                });

                var customOptions = {
                    'maxWidth': '290',
                };

                var store_title = '';
                if ( $item.find('.store-title').length ) {
                    store_title = "<h4 class='store-title'>" + $item.find('.store-title').html() + "</h4>";
                }

                var store_html = '';
                if ( $item.find('.details-store-inner').length ) {
                    store_html = "<div class='details-store-inner'>" + $item.find('.details-store-inner').html() + "</div>";
                }


                marker.bindPopup(
                    "<div class='store-item store-grid'>" + 
                        "<div class='store-information'>" + store_title + store_html + "</div>" +
                    "</div>", customOptions).openPopup();
            }

            markers.addLayer(marker);
        }

    });

    $.apusThemeExtensions.store_map = $.apusThemeCore.store_map_init;

    
})(jQuery);