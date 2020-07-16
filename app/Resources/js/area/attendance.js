$(function () {
    var attendanceList = $('#attendanceList'),
        elContainer = $('#attendanceFilloutPage'),
        elEditableMode = $('#toggleEditableMode'),
        elAutoRefresh = $('#autoRefresh'),
        listId = attendanceList.data('list-id'),
        autoRefreshInterval = false,
        reloadBlocked = true,
        updateInProgress = false,
        queuedUpdates = [],
        queuedUndos = [],
        queuedRedos = [],
        filterGroups = [],
        filterColumns = [],
        elIndicator = $('.attendance-list-toolbar .indicator-fetch'),
        btnUndo = $('#btnUndo'),
        btnRedo = $('#btnRedo');

    if (attendanceList.length) {
        $('td.column label').on('click', function () {
            $(this).tooltip('destroy');
        });

        const handleParticipantsResponse = function (response) {
            if (response.participants) {
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
                filterRows();
            }
        };

        /**
         * Auto refresh
         **/
        const refreshView = function () {
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
                    handleParticipantsResponse(response);
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
        refreshView();
        elAutoRefresh.click(function () {
            var checkbox = $(this).find('input'),
                oldValue = checkbox.prop('checked'),
                newValue = !oldValue;

            if (newValue) {
                refreshView();
                autoRefreshInterval = setInterval(refreshView, 10000);
            } else if (autoRefreshInterval !== false) {
                clearInterval(autoRefreshInterval);
            }
        });
        elEditableMode.click(function () {
            var checkbox = $(this).find('input'),
                oldValue = checkbox.prop('checked'),
                newValue = !oldValue;

            elContainer.toggleClass('locked', !newValue);
        });
        elEditableMode.tooltip();
        $('.btn-column-all').click(function (event) {
            if (elContainer.hasClass('locked')) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return;  //do nothing if not editable
            }
            var el = $(this),
                columnId = el.data('column-id'),
                choiceId = el.data('choice-id');
            el.tooltip('destroy');
            el.toggleClass('btn-primary', true);
            attendanceList.toggleClass('disabled', true);
            setTimeout(function () {
                $.each($('#attendanceList tbody tr'), function (key, element) {
                    var el = $(element);
                    if (!el.hasClass('filtered-hidden')) {
                        var inputEl = $("#choice_" + el.data('aid') + "_" + columnId + "_" + choiceId);
                        inputEl.parents('label').click();
                    }
                });
                attendanceList.toggleClass('disabled', false);
                el.toggleClass('btn-primary', false);
                el.focus();
            }, 100);
        });

        const updateUndoRedo = function () {
            btnUndo.toggleClass('disabled', !queuedUndos.length);
            btnRedo.toggleClass('disabled', !queuedRedos.length);
        };

        btnUndo.on('click', function (event) {
            if (!elContainer.hasClass('locked') && queuedUndos.length) {
                const elId = queuedUndos.pop(),
                    el = $('#' + elId),
                    elOld = findCheckedOrDefault(elId);
                queuedRedos.push(elOld.attr('id'));
                el.closest('label').trigger('click', ['caused-by-undo']);
            } else {
                event.preventDefault();
            }
        });

        btnRedo.on('click', function (event) {
            if (!elContainer.hasClass('locked') && queuedRedos.length) {
                const elId = queuedRedos.pop(),
                    el = $('#' + elId),
                    elOld = findCheckedOrDefault(elId);
                queuedUndos.push(elOld.attr('id'));
                el.closest('label').trigger('click', ['caused-by-redo']);
            } else {
                event.preventDefault();
            }
        });

        const findCheckedOrDefault = function (elId) {
            var el = $('#' + elId),
                elBtnGroup = el.closest('.btn-group'),
                elOld = elBtnGroup.find("input[type='radio']:checked");
            if (!elOld || !elOld.length) {
                elOld = elBtnGroup.find("input[data-choice-id='0']");
            }
            return elOld;
        }

        const flushQueueImmediately = function (callAfterComplete) {
            if (updateInProgress || !queuedUpdates.length) {
                return;
            }
            updateInProgress = true;
            var updatesToProcess = queuedUpdates,
                updates = {};
            queuedUpdates = []; //empty queue
            elIndicator.toggleClass('loading', true);

            $.each(updatesToProcess, function (key, elId) {
                var elInput = $('#' + elId),
                    elLabel = elInput.closest('label'),
                    elRow = elInput.parents('tr'),
                    aid = elRow.data('aid'),
                    columnId = elInput.data('column-id'),
                    choiceId = elInput.data('choice-id');

                if (!updates[columnId]) {
                    updates[columnId] = {};
                }
                if (!updates[columnId][choiceId]) {
                    updates[columnId][choiceId] = [];
                }
                updates[columnId][choiceId].push(aid);
                elLabel.toggleClass('preview', true);
            });

            $.ajax({
                type: 'POST',
                url: listId + '/fillout.json',
                data: {
                    _token: attendanceList.data('token'),
                    updates: updates,
                },
                datatype: 'json',
                success: function (response) {
                    handleParticipantsResponse(response);
                },
                error: function (response) {
                    $.each(updatesToProcess, function (key, elId) {
                        const elLabel = $('#' + elId).closest('label');
                        elLabel.toggleClass('btn-alert', true);
                    });
                    $(document).trigger('add-alerts', {
                        message: 'Die Daten des Teilnehmers konnten nicht aktualisiert werden. Möglicherweise ist die Internetverbindung unterbrochen worden.',
                        priority: 'error'
                    });
                },
                complete: function (response) {
                    $.each(updatesToProcess, function (key, elId) {
                        const elLabel = $('#' + elId).closest('label');
                        elLabel.toggleClass('preview', false);
                    });
                    if (elAutoRefresh.find('input').prop('checked')) {
                        autoRefreshInterval = setInterval(refreshView, 10000);
                    }
                    updateInProgress = false;
                    scheduleQueueFlush();
                    elIndicator.toggleClass('loading', false);
                    if (callAfterComplete) {
                        callAfterComplete();
                    }
                }
            });
        };
        const scheduleQueueFlush = function () {
            clearInterval(autoRefreshInterval);
            setTimeout(flushQueueImmediately, 100);
        };

        $('.column label').click(function (event, cause) {
            if (cause === 'caused-by-refresh' || cause === 'caused-by-batch') {
                return;
            }
            if (elContainer.hasClass('locked')) {
                elEditableMode.tooltip('show');

                event.preventDefault();
                event.stopImmediatePropagation();
                return;  //do nothing if not editable
            }
            const elNew = $(this),
                elNewId = elNew.find('input').attr('id'),
            elBtnGroup = elNew.closest('.btn-group');
            queuedUpdates.push(elNewId);

            if (cause === 'caused-by-undo' || cause === 'caused-by-redo') {
                elContainer.toggleClass('locked', true);
            } else {
                queuedRedos = [];
                var elOld = findCheckedOrDefault(elNewId),
                    elOldId = elOld.attr('id');

                if (elNewId !== elOldId)
                queuedUndos.push(elOldId);
            }
            elNew.toggleClass('preview', true);
            updateUndoRedo();
            elBtnGroup.find('input').prop('checked', false);
            elNew.find('input').prop('checked', true);
            if (cause === 'caused-by-undo' || cause === 'caused-by-redo') {
                flushQueueImmediately(function() {
                    elContainer.toggleClass('locked', false);
                });
            } else {
                scheduleQueueFlush();
            }
        });

        $('#modalComment').on('show.bs.modal', function (event) {
            var btn = $(event.relatedTarget),
                elInput = $('#modalCommentContent'),
                columnId = btn.parents('td').data('column-id'),
                aid = btn.parents('tr').data('aid');
            if (btn.hasClass('disabled')
                || (elContainer.hasClass('locked') && !btn.hasClass('active'))
            ) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return false;
            }

            elInput.val(btn.data('comment'));
            elInput.data('aid', aid);
            elInput.data('column-id', columnId);
            elInput.focus();

            $('#modalComment .btn-primary').click(function (event) {
                if (elContainer.hasClass('locked')) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    return;  //do nothing if not editable
                }
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
                el.toggleClass('filtered-hidden', false);
                $.each(filterGroups, function (groupId, expectedChoiceId) {
                    var givenChoice = el.data('group-' + groupId);
                    if (givenChoice != expectedChoiceId) {
                        el.toggleClass('filtered-hidden', true);
                    }
                });
                $.each(filterColumns, function (columnId, expectedChoiceId) {
                    var elColumn = el.find('td[data-column-id="' + columnId + '"]'),
                        elChoice = elColumn.find('input[data-choice-id="' + expectedChoiceId + '"]');

                    if (!elChoice.parent().hasClass('active')) {
                        el.toggleClass('filtered-hidden', true);
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
            elBtn.off();
            modalExportMultiple.modal('hide');
        });
    });

});
