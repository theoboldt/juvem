jQuery(document).ready(function () {
    $('.contains-prototype').each(function (index) {
        var element = $(this),
            prototype;
        if (element.attr('data-prototype')) {
            prototype = element.data('prototype');
            //debugger
        }


    });

});