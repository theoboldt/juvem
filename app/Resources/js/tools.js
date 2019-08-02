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

    /**
     * The "mean" is the "average" you're used to, where you add up all the numbers
     * and then divide by the number of numbers.
     *
     * For example, the "mean" of [3, 5, 4, 4, 1, 1, 2, 3] is 2.875.
     *
     * @param {Array} numbers An array of numbers.
     * @return {Number} The calculated average (or mean) value from the specified
     *     numbers.
     */
    window.eMean = function (numbers) {
        var total = 0, i;
        for (i = 0; i < numbers.length; i += 1) {
            total += numbers[i];
        }
        return total / numbers.length;
    };

    /**
     * The "median" is the "middle" value in the list of numbers.
     *
     * @param {Array} numbers An array of numbers.
     * @return {Number} The calculated median value from the specified numbers.
     */
    window.eMedian = function (numbers) {
        // median of [3, 5, 4, 4, 1, 1, 2, 3] = 3
        var median = 0, numsLen = numbers.length;
        numbers.sort();

        if (
            numsLen % 2 === 0 // is even
        ) {
            // average of two middle numbers
            median = (numbers[numsLen / 2 - 1] + numbers[numsLen / 2]) / 2;
        } else { // is odd
            // middle number only
            median = numbers[(numsLen - 1) / 2];
        }

        return median;
    };

    /**
     * The "mode" is the number that is repeated most often.
     *
     * For example, the "mode" of [3, 5, 4, 4, 1, 1, 2, 3] is [1, 3, 4].
     *
     * @param {Array} numbers An array of numbers.
     * @return {Array} The mode of the specified numbers.
     */
    window.eMode = function (numbers) {
        // as result can be bimodal or multi-modal,
        // the returned result is provided as an array
        // mode of [3, 5, 4, 4, 1, 1, 2, 3] = [1, 3, 4]
        var modes = [], count = [], i, number, maxIndex = 0;

        for (i = 0; i < numbers.length; i += 1) {
            number = numbers[i];
            count[number] = (count[number] || 0) + 1;
            if (count[number] > maxIndex) {
                maxIndex = count[number];
            }
        }

        for (i in count)
            if (count.hasOwnProperty(i)) {
                if (count[i] === maxIndex) {
                    modes.push(Number(i));
                }
            }

        return modes;
    };

    /**
     * The "range" of a list a numbers is the difference between the largest and
     * smallest values.
     *
     * For example, the "range" of [3, 5, 4, 4, 1, 1, 2, 3] is [1, 5].
     *
     * @param {Array} numbers An array of numbers.
     * @return {Array} The range of the specified numbers.
     */
    window.eRange = function (numbers) {
        if (numbers.length === 1) {
            return [numbers[0], numbers[0]];
        }
        numbers.sort();
        return [numbers[0], numbers[numbers.length - 1]];
    };

});
