$(function () {
    var attendanceList = $('#attendanceList'),
        elAutoRefresh = $('#autoRefresh'),
        listId = attendanceList.data('list-id'),
        autoRefreshInterval = false,
        reloadBlocked = true,
        updateInProgress = false,
        queuedUpdates = [];

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
    }
});
