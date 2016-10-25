$(function () {

    /**
     * GLOBAL: Open transmitted url in main window or in new tab if meta key is pressed
     *
     * @param url   Url to visit
     */
    var openInNewTabOnMetaKey = function (url) {
        if (window.event.metaKey) {
            window.open(url, '_blank');
        } else {
            location.href = url;
        }
    };

    /**
     * GLOBAL: Bootstrap table on page which provides filters
     */
    var tableRemoteContent = function () {
        $('.table-remote-content').each(function () {
            var table = $(this),
                tableFilterList = {},
                id = table.attr('id'),
                subId = table.data('sub-id'),
                tableSettingsIdentifier;

            if (subId) {
                tableSettingsIdentifier = 'tableFilter.' + id + '.' + subId + '.';
            } else {
                tableSettingsIdentifier = 'tableFilter.' + id + '.';
            }

            $('#bootstrap-table-toolbar .dropup[data-property]').each(function () {
                var dropup = $(this),
                    options = dropup.find('ul li a'),
                    property = dropup.data('property'),
                    indexDefault = dropup.data('default'),
                    settingIdentifier = tableSettingsIdentifier + property,
                    indexTarget = userSettings.get(settingIdentifier, indexDefault);

                if (!$.isNumeric(indexTarget) || options.length <= indexTarget) {
                    indexTarget = 0;
                }

                options.each(function (indexCurrent, option) {
                    if (indexCurrent == indexTarget) {
                        var text = $(option).text(),
                            filter = $(option).data('filter');

                        dropup.find('button .description').text(text);
                        tableFilterList[property] = filter;
                    }
                });

            });

            //apply filters
            table.bootstrapTable('filterBy', tableFilterList);

            //add filter handler
            $('#bootstrap-table-toolbar li a').on('click', function (e) {
                e.preventDefault();
                var option = $(this).get(0),
                    dropup = $(this).parent().parent().parent(),
                    property = dropup.data('property'),
                    filter = $(this).data('filter'),
                    text = $(this).text(),
                    index;

                $(this).parent().parent().find('li a').each(function (indexCurrent, optionCurrent) {
                    if (option === optionCurrent) {
                        index = indexCurrent;
                        return false;
                    }
                });
                if (index === undefined) {
                    return true;
                }

                tableFilterList[property] = filter;
                dropup.find('button .description').text(text);
                table.bootstrapTable('filterBy', tableFilterList);
                userSettings.set(tableSettingsIdentifier + property, index);
            });
        });
    }();


    /**
     * ACQUISITION: Admin acquisition list table
     */
    $('#acquisitionListTable').on('click-row.bs.table', function (e, row, $element) {
        openInNewTabOnMetaKey(row.bid);
    });

    /**
     * EVENT: Admin event list table
     */
    $('#eventListTable').on('click-row.bs.table', function (e, row, $element) {
        openInNewTabOnMetaKey(row.eid);
    });

    /**
     * EVENT: Admin event participants list table
     */
    $('#participantsListTable').on('click-row.bs.table', function (e, row, $element) {
        openInNewTabOnMetaKey('participation/' + row.pid);
    });

    /**
     * USER: User list
     */
    $('#userListTable').on('click-row.bs.table', function (e, row, $element) {
        openInNewTabOnMetaKey(row.uid);
    });

    /**
     * USER: A users participants list table
     */
    $('#userParticipantsListTable').on('click-row.bs.table', function (e, row, $element) {
        openInNewTabOnMetaKey('../event/' + row.eid + '/participation/' + row.pid);
    });

    /**
     * NEWSLETTER: A newsletter subscription
     */
    $('#newsletterSubscriptionTable').on('click-row.bs.table', function (e, row, $element) {
        openInNewTabOnMetaKey('subscription/' + row.rid );
    });

});