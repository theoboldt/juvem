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
                id = table.attr('id');
            if (id && userSettings.has('tableFilter.' + id)) {
                //if settings for table stored in user settings, apply
                tableFilterList = userSettings.get('tableFilter.' + id);
                //set status of those fields to current selection
                $.each(tableFilterList, function (property, value) {
                    var dropup = $('#bootstrap-table-toolbar .dropup[data-property=' + property + ']'),
                        text = '(unbekannte Auswahl)';

                    dropup.find('ul li a').each(function () {
                        var optionElement = $(this),
                            optionSetting = optionElement.data('filter');

                        if (($.isArray(optionSetting) && $.isArray(value)
                            && $(value).not(optionSetting).length === 0 && $(optionSetting).not(value).length === 0)
                            || (optionSetting == value)
                        ) {
                            text = optionElement.text();
                            return false;
                        }
                    });
                    dropup.find('button .description').text(text);
                });
            } else {
                //check filter fields for initial settings; works currently only for one single table per page
                $('#bootstrap-table-toolbar .dropup[data-filter]').each(function () {
                    var filterElement = $(this),
                        property = filterElement.data('property'),
                        filter = filterElement.data('filter');
                    tableFilterList[property] = filter;
                });
            }
            //apply filters
            table.bootstrapTable('filterBy', tableFilterList);

            //add filter handler
            $('#bootstrap-table-toolbar li a').on('click', function (e) {
                var dropup = $(this).parent().parent().parent(),
                    property = dropup.data('property'),
                    filter = $(this).data('filter'),
                    text = $(this).text();
                e.preventDefault();

                tableFilterList[property] = filter;
                dropup.find('button .description').text(text);

                table.bootstrapTable('filterBy', tableFilterList);
                if (id) {
                    userSettings.set('tableFilter.' + id, tableFilterList);
                }
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

});