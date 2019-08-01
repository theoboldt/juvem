$(function () {
    var attendanceList = $('#attendanceList'),
        elAutoRefresh = $('#autoRefresh'),
        listId = attendanceList.data('list-id'),
        autoRefreshInterval = false,
        reloadBlocked = true,
        updateInProgress = false,
        queuedUpdates = [],
        filterGroups = [],
        filterColumns = [],
        elIndicator = $('.attendance-list-toolbar .indicator-fetch');

    if (attendanceList.length) {
        $('td.column label').on('click', function () {
            $(this).tooltip('destroy');
        });

        /**
         * Auto refresh
         **/
        const autoRefresh = function () {
            if (!reloadBlocked) {
                reloadBlocked = true;
            }
            reloadBlocked = true;
            elIndicator.toggleClass('loading', true);
            $.ajax({
                type: 'GET',
                url: listId + '/fillout.json',
                datatype: 'json',
                success: function (response) {
                    $.each(response.participants, function (aid, participant) {
                        $.each(participant.columns, function (columnId, column) {
                            var choiceId = column.choice_id,
                                baseId = "#choice_" + aid + "_" + columnId + "_",
                                elBtn,
                                elCommentBtn;
                            if (!choiceId) {
                                choiceId = 0;
                            }
                            elBtn = $(baseId + choiceId);
                            elCommentBtn = $(baseId + 'comment');
                            elCommentBtn.toggleClass('active', column.comment !== null);
                            elCommentBtn.data('comment', column.comment);
                            elBtn.trigger('click', ['caused-by-refresh']);
                        });

                    });
                    reloadBlocked = false;
                },
                error: function () {
                    var autoRefreshInput = $('#autoRefresh input');
                    if (autoRefreshInput.prop('checked')) {
                        autoRefreshInput.prop('checked', false);
                        $('#autoRefresh label').removeClass('active');
                    }
                    clearInterval(autoRefreshInterval);
                    $(document).trigger('add-alerts', {
                        message: 'Die Daten der Anwesenheitsliste konnten nicht geladen werden. Möglicherweise ist die Internetverbindung unterbrochen worden.',
                        priority: 'error'
                    });
                    reloadBlocked = false;
                },
                complete: function (response) {
                    elIndicator.toggleClass('loading', false);
                }
            });
        };
        autoRefresh();
        elAutoRefresh.click(function () {
            var checkbox = $(this).find('input'),
                oldValue = checkbox.prop('checked'),
                newValue = !oldValue;

            if (newValue) {
                autoRefresh();
                autoRefreshInterval = setInterval(autoRefresh, 10000);
            } else if (autoRefreshInterval !== false) {
                clearInterval(autoRefreshInterval);
            }
        });

        $('.btn-column-all').click(function () {
            var el = $(this),
                columnId = el.data('column-id'),
                choiceId = el.data('choice-id');

            $.each($('#attendanceList tbody tr'), function (key, element) {
                var el = $(element);
                if (!el.hasClass('hidden')) {
                    var inputEl = $("#choice_" + el.data('aid') + "_" + columnId + "_" + choiceId);
                    inputEl.parents('label').click();
                }
            });
            el.focus();
        });

        const scheduleQueueFlush = function () {
            clearInterval(autoRefreshInterval);
            setTimeout(function () {
                if (updateInProgress || !queuedUpdates.length) {
                    return;
                }
                updateInProgress = true;
                var updatesToProcess = queuedUpdates,
                    updates = [];
                queuedUpdates = []; //empty queue
                elIndicator.toggleClass('loading', true);

                $.each(updatesToProcess, function (key, el) {
                    var elInput = el.find('input'),
                        elRow = el.parents('tr'),
                        aid = elRow.data('aid'),
                        columnId = elInput.data('column-id'),
                        choiceId = elInput.data('choice-id');

                    updates.push({
                        aid: aid,
                        columnId: columnId,
                        choiceId: choiceId
                    });
                    el.toggleClass('preview', true);
                });

                $.ajax({
                    type: 'POST',
                    url: listId + '/fillout.json',
                    data: {
                        _token: attendanceList.data('token'),
                        updates: updates,
                    },
                    datatype: 'json',
                    success: function () {
                        filterRows();
                    },
                    error: function (response) {
                        $.each(updatesToProcess, function (key, el) {
                            el.toggleClass('btn-alert', true);
                        });
                        $(document).trigger('add-alerts', {
                            message: 'Die Daten des Teilnehmers konnten nicht aktualisiert werden. Möglicherweise ist die Internetverbindung unterbrochen worden.',
                            priority: 'error'
                        });
                    },
                    complete: function (response) {
                        $.each(updatesToProcess, function (key, el) {
                            el.toggleClass('preview', false);
                        });
                        if (elAutoRefresh.find('input').prop('checked')) {
                            autoRefreshInterval = setInterval(autoRefresh, 10000);
                        }
                        updateInProgress = false;
                        scheduleQueueFlush();
                        elIndicator.toggleClass('loading', false);
                    }
                });

            }, 100);
        };

        $('.column label').click(function (event, cause) {
            if (cause === 'caused-by-refresh' || cause === 'caused-by-batch') {
                return;
            }
            const el = $(this);
            queuedUpdates.push(el);
            el.toggleClass('preview', true);
            scheduleQueueFlush();
        });

        $('#modalComment').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget),
                elInput = $('#modalCommentContent'),
                columnId = btn.parents('td').data('column-id'),
                aid = btn.parents('tr').data('aid');
            elInput.val(btn.data('comment'));
            elInput.data('aid', aid);
            elInput.data('column-id', columnId);
            elInput.focus();

            $('#modalComment .btn-primary').click(function () {
                var elInput = $('#modalCommentContent'),
                    newComment = elInput.val();
                $('#modalComment').modal('hide');
                btn.toggleClass('active', newComment);
                btn.data('comment', newComment);

                $.ajax({
                    type: 'POST',
                    url: listId + '/comment.json',
                    data: {
                        _token: elInput.data('token'),
                        aid: elInput.data('aid'),
                        columnId: elInput.data('column-id'),
                        comment: newComment,
                    },
                    datatype: 'json',
                    success: function (response) {
                        if (response && response.message) {
                            btn.toggleClass('active', false);
                            $(document).trigger('add-alerts', {
                                message: response.message,
                                priority: 'warning'
                            });
                        }
                    },
                    error: function (response) {
                        btn.toggleClass('btn-alert', true);
                        $(document).trigger('add-alerts', {
                            message: 'Die Daten des Teilnehmers konnten nicht aktualisiert werden. Möglicherweise ist die Internetverbindung unterbrochen worden.',
                            priority: 'error'
                        });
                    },
                    complete: function (response) {
                        btn.toggleClass('preview', false);
                    }
                });

                $('#modalComment .btn-primary').off();
            });
        });

        $('#attendance-filters label').on('change', function () {
            filterGroups = {};
            filterColumns = {};

            $('#attendance-filters label').each(function () {
                var el = $(this),
                    elInput = el.find('input'),
                    elValue = elInput.val(),
                    elChecked = elInput.prop('checked'),
                    elFilter = elInput.parents('.filter');
                if (!elChecked || elValue === 'all') {
                    return;
                }
                if (elFilter.hasClass('filter-column')) {
                    var columnId = elFilter.data('column-id');
                    filterColumns[columnId] = elValue;
                } else if (elFilter.hasClass('filter-group')) {
                    var bid = elFilter.data('bid');
                    filterGroups[bid] = elValue;
                }
            });
            filterRows();
        });

        const filterRows = function () {
            $('#attendanceList tbody tr').each(function () {
                var el = $(this);
                el.toggleClass('hidden', false);
                $.each(filterGroups, function (groupId, expectedChoiceId) {
                    var givenChoice = el.data('group-' + groupId);
                    if (givenChoice != expectedChoiceId) {
                        el.toggleClass('hidden', true);
                    }
                });
                $.each(filterColumns, function (columnId, expectedChoiceId) {
                    var elColumn = el.find('td[data-column-id="' + columnId + '"]'),
                        elChoice = elColumn.find('input[data-choice-id="' + expectedChoiceId + '"]');

                    if (!elChoice.parent().hasClass('active')) {
                        el.toggleClass('hidden', true);
                    }
                });
            });
        };

        $('#modalExport .btn-primary').on('click', function (e) {
            e.preventDefault();
            var elBtn = $(this),
                bid = $('#modalExport input[name=exportGroupBy]:checked').val(),
                url = listId + '/export' + (bid ? '/' + bid : '');

            elBtn.attr('href', url);

            handleDownloadBtnClick(elBtn);
            $('#modalExport').modal('hide');
        });
    }

    var modalExportMultiple = $('#modalExportMultiple');
    modalExportMultiple.on('show.bs.modal', function () {
        var modalEl = $('#modalExportMultiple'),
            lists = $('#eventAttendanceListTable').bootstrapTable('getAllSelections'),
            listEl = modalEl.find('ul'),
            listIds = [];

        listEl.html('');
        $.each(lists, function (key, list) {
            listIds.push(list.tid);
            listEl.append('<li>' + eHtml(list.title) + '</li>');
        });
        $('#modalExportMultiple .btn-primary').on('click', function (e) {
            e.preventDefault();
            var elBtn = $(this),
                bid = $('#modalExportMultiple input[name=exportGroupBy]:checked').val(),
                url = 'attendance/export-multiple/' + (bid ? bid : '0') + '/';

            url += listIds.join(',');

            elBtn.attr('href', url);

            handleDownloadBtnClick(elBtn);
            modalExportMultiple.modal('hide');
        });
    });

});
