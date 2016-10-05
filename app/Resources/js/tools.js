$(function(){

    /**
     * GLOBAL Escape html
     */
    window.eHtml = function (value) {
        return $('<i></i>').text(value).html();
    };

    /**
     * GLOBAL: Download-button
     */
    $('.btn-download').on('click', function (e) {
        e.preventDefault();

        var button = $(this);
        button.prop('disabled', true);

        var eid = button.data('eid'),
            url = this.getAttribute('href'),
            iframe = $("<iframe/>").attr({
                src: url,
                style: "display:none"

            }).appendTo(button);

        iframe.on('load', function () {
            button.prop('disabled', false);
            setTimeout(function () {
                iframe.remove();
            }, 1000)
        });
    });

});