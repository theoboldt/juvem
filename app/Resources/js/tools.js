$(function () {

    /**
     * GLOBAL request configuration
     */
    $.ajaxSetup({
        type: 'POST',
        dataType: 'json',
        error: function (jqXHR) {
            var message = 'Die gewünschte Aktion wurde nicht korrekt ausgeführt',
                priority = 'error';

            if (this.dataType === 'json'
                && jqXHR.responseJSON
                && jqXHR.responseJSON.message
                && jqXHR.responseJSON.message.content
                && jqXHR.responseJSON.message.type
            ) {
                message = jqXHR.responseJSON.message.content;
                priority = jqXHR.responseJSON.message.type;
            }
            $(document).trigger('add-alerts', {
                message: message,
                priority: priority
            });
        }
    });

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
                                message: 'Der Anwendungs-Server ist vorrübergehend nicht erreichbar.',
                                priority: 'warning'
                            });
                            break;
                        default:
                            $(document).trigger('add-alerts', {
                                message: 'Es scheint ein Problem mit der Internetverbindung vorzuliegen: Es kann keine Verbindung zum Anwendungs-Server aufgebaut werden.',
                                priority: 'error'
                            });
                            break;
                    }
                    errorHandled = true;
                }
            });
        }, 600000);
    }();

    /**
     * GLOBAL: Lazy load images
     */
    $(document).ready(function () {
        $('.lazy-load-image').each(function () {
            var el = $(this),
                src = el.data('src'),
                alt = el.data('alt'),
                title = el.data('title'),
                width = el.data('width'),
                height = el.data('height'),
                timeBeginLoad = new Date().getTime(),
                settings;

            settings = {
                src: src,
                alt: alt ? alt : ''
            };
            if (title) {
                settings.title = title;
            }
            if (width) {
                settings.width = width;
            }
            if (height) {
                settings.height = height;
            }
            $("<img/>").attr(settings).appendTo(el)
                .on('load', function () {
                    var timeEndLoad = new Date().getTime();
                    if (timeEndLoad - timeBeginLoad < 800) {
                        el.addClass('load-direct');
                    } else {
                        el.addClass('load');
                    }
                });
        });
    });
});

/**
 * Sort numbers which use german number format
 *
 * @param a
 * @param b
 * @returns {number}
 */
function germanNumberFormatSorter(a, b) {
    'use strict';
    var numberize = function (n) {
        return parseFloat(n.replace(/\./g, '').replace(',', '.'));
    };
    a = numberize(a);
    b = numberize(b);

    if (a > b) return 1;
    if (a < b) return -1;
    return 0;
}


/**
 * Sort date time values which use german format
 *
 * @param a
 * @param b
 * @returns {number}
 */
function germanDateTimeSorter(a, b) {
    'use strict';
    var date = function (v) {
        var parts;
        parts = v.match(/^(\d{2})\.(\d{2})\.(\d{2,4})$/);
        if (parts) {
            if (parts[3].length == 2) {
                parts[3] = '20' + parts[3];
            }
            return new Date(parts[3], parts[2], parts[1]);
        } else {
            parts = v.match(/^(\d{2})\.(\d{2})\.(\d{2,4}) (\d{2}):(\d{2})$/);
            if (parts) {
                if (parts[3].length == 2) {
                    parts[3] = '20' + parts[3];
                }
                return new Date(parts[3], parts[2], parts[1], parts[4], parts[5]);
            }
            return new Date(1980);
        }
    };
    a = date(a);
    b = date(b);

    if (a > b) return 1;
    if (a < b) return -1;
    return 0;
}