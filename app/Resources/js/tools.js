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
     * GLOBAL: Download-button handler
     */
    window.handleDownloadBtnClick = function (button) {
        button.prop('disabled', true);

        var url = button.attr('href'),
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
    };

    /**
     * GLOBAL: Download-button
     */
    window.attachDownloadBtnListener = function () {
        $('.btn-download').on('click', function (e) {
            e.preventDefault();
            var button = $(this);
            handleDownloadBtnClick(button);
        });
    };
    window.attachDownloadBtnListener();

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

    var $grid = $('.grid').masonry({
        itemSelector: '.grid-item',
        columnWidth: '.grid-sizer',
        percentPosition: true
    });
    $grid.imagesLoaded().progress(function () {
        $grid.masonry('layout');
    });

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
                        $grid.masonry('layout');
                    } else {
                        el.addClass('load');
                        $grid.masonry('layout');
                        setTimeout(function () {
                            $grid.masonry('layout');
                        }, 650);
                    }
                });
        });
    });

    const sorterNumberize = function (n) {
        var nFloat,
            regex = /^.*<span class="rounded-age">\(([,.0-9]+)\)<\/span>.*$/,
            matches;

        if (regex.test(n)) {
            matches = regex.exec(n);
            n = matches[1];
        }

        nFloat = parseFloat(n.replace(/\./g, '').replace(',', '.'));
        if (isNaN(nFloat)) {
            return Number.MIN_SAFE_INTEGER ? Number.MIN_SAFE_INTEGER : -900719925474099;
        } else {
            return nFloat;
        }
    };
    /**
     * Sort numbers which use german number format
     *
     * @param a
     * @param b
     * @returns {number}
     */
    window.germanNumberFormatSorter = function (a, b) {
        'use strict';

        a = sorterNumberize(a);
        b = sorterNumberize(b);

        if (a > b) return 1;
        if (a < b) return -1;
        return 0;
    };

    const sorterDatify = function (v) {
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

    /**
     * Sort date time values which use german format
     *
     * @param a
     * @param b
     * @returns {number}
     */
    window.germanDateTimeSorter = function (a, b) {
        'use strict';
        a = sorterDatify(a);
        b = sorterDatify(b);

        if (a > b) return 1;
        if (a < b) return -1;
        return 0;
    };

});
