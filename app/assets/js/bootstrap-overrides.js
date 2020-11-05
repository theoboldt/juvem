$(function(){

    /*
     $('#nav').affix({
     offset: {
     top: $('#nav').offset().top
     }
     });
     */

    /**
     * GLOBAL: Enable tooltips
     */
    $('[data-toggle="tooltip"]').tooltip({
        container: 'body'
    });

    /**
     * GLOBAL: Enable popovers
     */
    $('[data-toggle="popover"]').popover({
        container: 'body',
        placement: 'top',
        html: true,
        trigger: 'focus'
    });
});