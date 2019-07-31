$(function () {
    var attendanceList = $('#attendanceList'),
        listId = attendanceList.data('list-id'),
        autoRefreshInterval = false,
        reloadBlocked = true;

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
                            var choiceId = column.choice_id;
                            if (!choiceId) {
                                choiceId = 0;
                            }
                            $("#choice_" + aid + "_" + columnId + "_" + choiceId).trigger('click', ['caused-by-refresh']);
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
        $('#autoRefresh').click(function () {
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
        $('.column label').click(function (event, cause) {
            if (cause === 'caused-by-refresh') {
                return;
            }
            var el = $(this),
                elInput = el.find('input'),
                elRow = el.parents('tr'),
                aid = elRow.data('aid'),
                columnId = elInput.data('column-id'),
                choiceId = elInput.data('choice-id');

            el.toggleClass('preview', true);

            $.ajax({
                type: 'POST',
                url: listId + '/fillout.json',
                data: {
                    _token: attendanceList.data('token'),
                    updates: [{
                        aid: aid,
                        columnId: columnId,
                        choiceId: choiceId,
                        comment: null
                    }],
                },
                datatype: 'json',
                success: function () {
                debugger
                },
                error: function (response) {
                    el.toggleClass('btn-alert', true);
                    $(document).trigger('add-alerts', {
                        message: 'Die Daten des Teilnehmers konnten nicht aktualisiert werden. Möglicherweise ist die Internetverbindung unterbrochen worden.',
                        priority: 'error'
                    });
                },
                complete: function (response) {
                    el.toggleClass('preview', false);
                    //elementLabel.removeClass('disabled');
                }
            });

        });


    }
});
