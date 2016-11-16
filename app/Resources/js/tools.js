$(function () {

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

    /**
     * GLOBAL: Provides a heartbeat to keep the csrf token always up to date
     */
    var heartbeat = function () {
        var errorHandled = false;
        setInterval(function () {
            $.ajax({
                type: 'GET',
                url: '/heartbeat',
                success: function () {
                    errorHandled = false;
                },
                error: function (response) {
                    if (errorHandled) {
                        return true;
                    }
                    switch (response.status) {
                        case 503:
                            $(document).trigger('add-alerts', {
                                message: 'Die Website scheint vorrübergehend nicht erreichbar zu sein.',
                                priority: 'warning'
                            });
                            break;
                        default:
                            $(document).trigger('add-alerts', {
                                message: 'Es scheint ein Problem mit der Internetverbindung vorzuliegen.',
                                priority: 'error'
                            });
                            break;
                    }
                    errorHandled = true;
                }
            });
        }, 600000);
    }();

});