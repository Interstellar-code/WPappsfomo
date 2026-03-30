//CHARTS
var table_charts_rehub = function() {
    if(jQuery('.table_view_charts').length > 0){
        jQuery('.table_view_charts').each(function(index){
            var rowcount = jQuery(this).find('.top_chart_row_found').data('rowcount');
            for (var rowcountindex = 0; rowcountindex < rowcount; rowcountindex++) {
                 //Equal height for each row
                 var heightArray = jQuery(this).find('li.row_chart_'+ rowcountindex +'').map( function(){
                    return  jQuery(this).height();
                 }).get();
                 var maxHeight = Math.max.apply( Math, heightArray);
                 jQuery(this).find('li.row_chart_'+ rowcountindex +'').height(maxHeight);

                 //Find differences
                 var recomparecolvalue;
                 jQuery(this).find('.top_chart_wrap li.row_chart_'+ rowcountindex +'').each(function(n) {
                    if (jQuery(this).html() != recomparecolvalue && n > 0) {
                       jQuery(this).closest('.table_view_charts').find('li.row_chart_'+ rowcountindex +'').addClass('row-is-different');
                    }
                    else {
                       recomparecolvalue = jQuery(this).html();
                    }
                 });
            }
            var carheight = jQuery(this).find('.top_chart_first').height();
            jQuery(this).find('.caroufredsel_wrapper').height(carheight+2);
        });
    }
}

jQuery(document).ready(function($) {
	"use strict";

    $('.table_view_charts').on('click', '.re-compare-show-diff', function(e){
        if ($(this).is(':checked')){
            $(this).closest('.table_view_charts').find('li[class^="row_chart"]').filter(':not(.heading_row_chart)').filter(':not(.row-is-different)').addClass('low-opacity');
        } 
        else {
            $(this).closest('.table_view_charts').find('li[class^="row_chart"]').filter(':not(.heading_row_chart)').filter(':not(.row-is-different)').removeClass('low-opacity');
        }     
    }); 

});

jQuery(document).ready(function($) {
    table_charts_rehub();
   if(jQuery(".table_view_charts").length > 0){
        jQuery(".table_view_charts").each(function() {
            jQuery(this).removeClass('loading');
        });
    };

});