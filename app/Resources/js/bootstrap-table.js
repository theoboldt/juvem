$(function () {
    var metaKeyPressed = null;

    document.addEventListener("keydown", function (event) {
        if (event.metaKey || event.ctrlKey) {
            metaKeyPressed = event.keyCode;
        }
    });
    document.addEventListener("keyup", function (event) {
        if (event.keyCode === metaKeyPressed || event.metaKey || event.ctrlKey) {
            metaKeyPressed = null;
        }
    });

    /**
     * GLOBAL: Open transmitted url in main window or in new tab if meta key is pressed
     *
     * @param url   Url to visit
     */
    var openInNewTabOnMetaKey = function (url) {
        if (metaKeyPressed !== null || (window.event && (window.event.metaKey || window.event.ctrlKey))) {
            window.open(url, '_blank');
        } else {
            location.href = url;
        }
    };

    var tableEnableQueryParam = function (table, params) {
        var options = table.bootstrapTable('getOptions'),
            changes = false,
            newQueryParams = (options.queryParams && Object.keys(options.queryParams).length > 0) ? options.queryParams : {};

        if (!$.isArray(params)) {
            params = [params];
        }

        $.each(params, function (index, param) {
            if (!newQueryParams[param]) {
                newQueryParams[param] = 1;
                changes = true;
            }
        });

        if (changes) {
            table.bootstrapTable('refreshOptions', {
                queryParams: newQueryParams
            });
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
                queryParams = [],
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
                    if (indexCurrent === indexTarget) {
                        var optionEl = $(option),
                            text = optionEl.text(),
                            filter = optionEl.data('filter'),
                            requireParam = optionEl.data('require-query-param');

                        dropup.find('button .description').text(text);
                        tableFilterList[property] = filter;
                        if (requireParam) {
                            queryParams.push(requireParam);
                        }
                    }
                });
            });

            //apply filters
            if (queryParams.length) {
                tableEnableQueryParam(table, queryParams);
            }
            table.bootstrapTable('filterBy', tableFilterList);

            //add filter handler
            $('#bootstrap-table-toolbar li a').on('click', function (e) {
                e.preventDefault();
                var optionEl = $(this),
                    option = optionEl.get(0),
                    dropup = optionEl.parent().parent().parent(),
                    requireParam = optionEl.data('require-query-param'),
                    property = dropup.data('property'),
                    filter = optionEl.data('filter'),
                    text = optionEl.text(),
                    index;

                optionEl.parent().parent().find('li a').each(function (indexCurrent, optionCurrent) {
                    if (option === optionCurrent) {
                        index = indexCurrent;
                        return false;
                    }
                });
                if (index === undefined) {
                    return true;
                }

                //apply filters
                if (requireParam) {
                    tableEnableQueryParam(table, requireParam);
                }

                tableFilterList[property] = filter;
                dropup.find('button .description').text(text);
                table.bootstrapTable('filterBy', tableFilterList);
                userSettings.set(tableSettingsIdentifier + property, index);
            });
        });
    }();

    $('table.table-remote-content').each(function () {
        var table = $(this),
            tableToolbar = $('#bootstrap-table-toolbar'),
            id = table.attr('id'),
            url = table.data('fetch-url');

        if (!url) {
            return; //this table does not support cached fetch
        }
        if (!useSecureCache()) {
            table.bootstrapTable('refreshOptions', {
                url: url
            });
            return; //load the classic way
        }
        //transfer
        var fetchDataAndCache = function () {
            tableCache.remove(id, url);
            tableToolbar.find('.indicator-fetch').remove();
            tableToolbar.append('<span class="indicator-fetch loading" data-placement="top" title="Die Tabelle zeigt veraltete Daten. Aktuellere Daten werden jetzt vom Server geladen...">' +
                '<i class="glyphicon glyphicon-transfer"></i> Lade...' +
                '</span>');
            tableToolbar.find('.indicator-fetch').tooltip();

            $.ajax({
                type: "GET",
                async: true,
                url: url,
                success: function (result, t, response) {
                    var etag = response.getResponseHeader('ETag');
                    tableCache.set(id, url, {data: result, etag: etag});
                    table.bootstrapTable('load', result);
                    tableToolbar.find('.indicator-fetch').remove();
                }
            });
        };

        if (tableCache.has(id, url)) {
            var tableCacheEntry = tableCache.get(id, url);
            tableToolbar.append('<span class="indicator-fetch checking" data-placement="top" title="Die Tabelle zeigt auf dem Computer zwischengespeicherte Daten. Es wird überprüft, ob auf dem Server aktuellere Daten vorhanden sind.">' +
                '<i class="glyphicon glyphicon-refresh"></i> Prüfe...' +
                '</span>');
            tableToolbar.find('.indicator-fetch').tooltip();

            table.bootstrapTable('load', tableCacheEntry.data);
            $.ajax({
                type: "HEAD",
                async: true,
                url: url,
                success: function (c, t, response) {
                    tableToolbar.find('.indicator-fetch').remove();
                    if (tableCacheEntry.etag !== response.getResponseHeader('ETag')) {
                        tableCache.remove(id, url);
                        fetchDataAndCache();
                    }
                }
            });
        } else {
            fetchDataAndCache();
        }
        table.on('refresh.bs.table', function (e) {
            fetchDataAndCache();
        });
    });

    /**
     * PARTICIPANT LIST: Unhide price/to pay column
     */
    var participantsTable = $('#participantsListTable');
    participantsTable.on('column-switch.bs.table', function (e, dataIndex) {
        if ((dataIndex !== 'payment_price' && dataIndex !== 'payment_to_pay')) {
            return;
        }
        tableEnableQueryParam(participantsTable, 'payment');
    });

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
     * NEWSLETTER: subscriptions
     */
    $('#newsletterSubscriptionTable').on('click-row.bs.table', function (e, row, $element) {
        openInNewTabOnMetaKey('../subscription/' + row.rid);
    });

    /**
     * NEWSLETTER: newsletters
     */
    $('#newsletterNewsletterTable').on('click-row.bs.table', function (e, row, $element) {
        openInNewTabOnMetaKey(row.lid + '/edit');
    });

    /**
     * NEWSLETTER: newsletters
     */
    $('#flashListTable').on('click-row.bs.table', function (e, row, $element) {
        openInNewTabOnMetaKey(row.fid);
    });

    /**
     * USER: A users participants list table
     */
    $('#eventAttendanceListTable').on('click-row.bs.table', function (e, row, $element) {
        openInNewTabOnMetaKey('attendance/' + row.tid);
    });

    /**
     * EMPLOYEE: A event's employee table
     */
    $('#employeeListTable').on('click-row.bs.table', function (e, row, $element) {
        openInNewTabOnMetaKey(row.gid);
    });

    /**
     * GROUP: List of an event's groups
     */
    $('#eventGroupsTable').on('click-row.bs.table', function (e, row, $element) {
        openInNewTabOnMetaKey('groups/' + row.bid);
    });

    /**
     * GROUP: List of choices of an event's group
     */
    $('#eventGroupChoicesTable').on('click-row.bs.table', function (e, row, $element) {
        openInNewTabOnMetaKey(row.bid+'/group/' + row.id);
    });

    /**
     * GROUP EMPLOYEE: List of employees assigned to a group option
     */
    $('#groupEmployeesTable').on('click-row.bs.table', function (e, row, $element) {
        openInNewTabOnMetaKey('../../../employee/'+row.gid);
    });

    /**
     * GROUP PARTICIPATION: List of participations assigned to a group option
     */
    $('#groupParticipationsTable').on('click-row.bs.table', function (e, row, $element) {
        openInNewTabOnMetaKey('../../../participation/' + row.pid);
    });

    /**
     * GROUP PARTICIPANT: List of participants assigned to a group option
     */
    $('#groupParticipantsTable').on('click-row.bs.table', function (e, row, $element) {
        openInNewTabOnMetaKey('../../../participation/' + row.pid);
    });
});
