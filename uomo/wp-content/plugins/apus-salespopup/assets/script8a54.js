jQuery(document).ready(function($){
	"use strict";
    
    var min_time = parseInt(apus_salespopup_opts['datas']['min_time']);
    var max_time = parseInt(apus_salespopup_opts['datas']['max_time']);
    
    function apus_salespopup_show_sales_popup_random() {

    	var datas = apus_salespopup_opts['datas'];

        if ( datas['enable_sales_popup'] != 'on' ) {
            return;
        }
        
        if ($('.apus-salespopup-wrapper').is('.active')) {
            return;
        }

        var multiple_address = datas['multiple_address'].length;
        var all_products = datas['products'].length;
        
        var total_buy_times = datas['buy_times'].length;
        if (total_buy_times <= 0 || multiple_address <= 0 || all_products <= 0) {
            return;
        }
        
        var buy_time_index = apus_salespopup_random_num(0, total_buy_times - 1);
        var buy_time_min = datas['buy_times'][buy_time_index]['min'];
        var buy_time_max = datas['buy_times'][buy_time_index]['max'];
        var buy_time_unit_key = datas['buy_times'][buy_time_index]['unit'];


        buy_time_min = parseInt(buy_time_min);
        buy_time_max = parseInt(buy_time_max);
        
        if (buy_time_max < buy_time_min) {
            buy_time_max = buy_time_min;
        }
        
        if (buy_time_max == 0) {
            buy_time_max = datas['buy_times'][0]['min'];
            if (buy_time_max < buy_time_min || buy_time_max <= 0) {
                buy_time_max = 59;
            }
        }
        
        var purchased_time = apus_salespopup_random_num(buy_time_min, buy_time_max);
        if (purchased_time != 1) {
            buy_time_unit_key = buy_time_unit_key + 's';
        }
        var time_unit = apus_salespopup_opts['text'][buy_time_unit_key];
        
        if (!$('body .apus-salespopup-wrapper').length) {
            $('body').append('<div class="apus-salespopup-wrapper"></div>');
        }

        var address_index = apus_salespopup_random_num(0, multiple_address - 1);
        var product_index = apus_salespopup_random_num(0, all_products - 1);

        var product = datas['products'][product_index];
        var address = datas['multiple_address'][address_index];
        var product_name = '<h4 class="product-name"><a href="' + product['url'] + '">' + product['product_name'] + '</a></h4>';
        var popup_text = datas['popup_text'].replace('{address}', address).replace('{product_name}', product_name).replace('{purchased_time}', purchased_time).replace('{time_unit}', time_unit);
        var popup_thumb = '<a class="thumb" href="' + product['url'] + '"><img src="' + product['img'] + '" /></a>'
        var html = '<div class="inner">' + popup_thumb + ' <div class="inner-right">' + popup_text + '</div> <span class="close"><i class="ti-close"></i></span></div>';
        
        $('.apus-salespopup-wrapper').html(html).addClass('active').fadeIn().delay(9000).fadeOut(function () {
            $('.apus-salespopup-wrapper').removeClass('active');
        });
    }
    
    $(document).on('click', '.apus-salespopup-wrapper .close', function (e) {
        $('.apus-salespopup-wrapper').removeClass('active').css({
            'display': 'none'
        });
        e.preventDefault();
    });
    
    var count_run = 0;
    (function apus_salespopup_randomize() {
        count_run++;
        var rand = apus_salespopup_random_num(min_time, max_time);
        setTimeout(function () {
            
            apus_salespopup_show_sales_popup_random();

            apus_salespopup_randomize();
        }, rand);
    }());
    
    function apus_salespopup_random_num(min_num, max_num) {
        return Math.floor(Math.random() * (max_num - min_num + 1) + min_num);
    }

});