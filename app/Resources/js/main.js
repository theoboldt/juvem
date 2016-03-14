jQuery(document).ready(function () {

    /**
     * GLOBAL Escape html
     */
    var eHtml = function (value) {
        return $('<i></i>').text(value).html();
    };

    /*
     $('#nav').affix({
     offset: {
     top: $('#nav').offset().top
     }
     });
     */

    /**
     * GLOBAL: Enable tooltips and popovers
     */
    $('[data-toggle="tooltip"]').tooltip({
        container: 'body'
    });

    /**
     * GLOBAL: Active button
     */
    $('[data-element="activebutton"]').each(function () {
        var button = $(this);

        button.prop('disabled', true);
        var token = button.data('token'),
            entityName = button.data('entity'),
            entityId = button.data('entity-id'),
            propertyName = button.data('property'),
            enableLabel = button.data('button-enable-label'),
            enableGlyph = button.data('button-enable-glyph'),
            disableLabel = button.data('button-disable-label'),
            disableGlyph = button.data('button-disable-glyph'),
            formData = {
                _token: token,
                entityName: entityName,
                entityId: entityId,
                propertyName: propertyName,
                buttons: {
                    buttonEnable: {
                        label: enableLabel,
                        glyph: enableGlyph
                    },
                    buttonDisable: {
                        label: disableLabel,
                        glyph: disableGlyph
                    }
                }
            };

        $.ajax({
            type: 'POST',
            url: '/admin/active/button',
            data: formData,
            datatype: 'json',
            success: function (response) {
                button.empty();
                if (response && response.html) {
                    button.html(response.html);
                }
            },
            error: function (response) {
                $(document).trigger('add-alerts', {
                    message: 'Die gewünschte Aktion wurde nicht korrekt ausgeführt',
                    priority: 'error'
                });
            },
            complete: function (response) {
                button.prop('disabled', false);
            }
        });

        button.click(function () {
            button.prop('disabled', true);
            $.ajax({
                type: 'POST',
                url: '/admin/active/button',
                data: $.extend(formData, {toggle: 1}),
                datatype: 'json',
                success: function (response) {
                    button.empty();
                    if (response && response.html) {
                        button.html(response.html);
                    }
                    button.trigger('juvem.activeButton.success', [button, response]);
                },
                error: function (response) {
                    $(document).trigger('add-alerts', {
                        message: 'Die gewünschte Aktion wurde nicht korrekt ausgeführt',
                        priority: 'error'
                    });
                    button.trigger('juvem.activeButton.error', [button, response]);
                },
                complete: function (response) {
                    button.prop('disabled', false);
                    button.trigger('juvem.activeButton.complete', [button, response]);
                }
            });
        });
    });

    /**
     * GLOBAL: Bootstrap table on page which provides filters
     */
    var table = $('.table-remote-content'),
        tableFilterList = {};

    //load initial filters
    $('#bootstrap-table-toolbar .dropup[data-filter]').each(function (index) {
        var property = $(this).data('property'),
            filter = $(this).data('filter');

        tableFilterList[property] = filter;
    });
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
    });

    /**
     * EVENT: Admin event list table
     */
    $('#eventListTable').on('click-row.bs.table', function (e, row, $element) {
        location.href = row.eid;
    });

    /**
     * EVENT: Admin event participants list table
     */
    $('#participantsListTable').on('click-row.bs.table', function (e, row, $element) {
        location.href = 'participation/' + row.pid;
    });

    /**
     * USER: User list
     */
    $('#userListTable').on('click-row.bs.table', function (e, row, $element) {
        location.href = row.uid;
    });

    /**
     * USER: A users participants list table
     */
    $('#userParticipantsListTable').on('click-row.bs.table', function (e, row, $element) {
        location.href = '../event/' + row.eid + '/participation/' + row.pid;
    });

    /**
     * EVENT: Handle via prototype injected forms
     */
    $('.prototype-container').each(function (index) {
        var element = $(this),
            prototype;
        if (element.attr('data-prototype')) {
            prototype = element.data('prototype');

            var addElementHandlers = function () {
                element.find('.prototype-remove').on('click', function (e) {
                    e.preventDefault();
                    $(this).parent().parent().parent().parent().remove();
                });
                element.find('[data-toggle="popover"]').popover({
                    container: 'body',
                    placement: 'top',
                    html: true,
                    trigger: 'focus'
                    /*
                     }).click(function (e) {
                     e.preventDefault();
                     $(this).popover('toggle');
                     */
                });
            };

            element.data('index', element.find('.prototype-element').length);
            element.find('.prototype-add').on('click', function (e) {
                e.preventDefault();
                var index = element.data('index');
                var newForm = prototype.replace(/__name__/g, index);
                element.data('index', index + 1);

                element.find('.prototype-elements').append(newForm);
                addElementHandlers();
            });
            addElementHandlers();
        }
    });

    /**
     * EVENT: Events participants email preview
     */
    var updateMailPreview = function () {
        var updateButton = $('*#mail-form .btn-update-preview');
        updateButton.prop('disabled', true);
        var content = {
                subject: $("input[name='app_bundle_event_mail[subject]']").val(),
                title: $("input[name='app_bundle_event_mail[title]']").val(),
                lead: $("input[name='app_bundle_event_mail[lead]']").val(),
                content: $("textarea[name='app_bundle_event_mail[content]']").val()
            },
            preview = $('*#mail-template iframe').contents(),
            exampleSalution = 'Frau',
            exampleLastName = 'Müller',
            exampleEventTitle = $('#mail-form').data('event-title');

        var replacePlaceholders = function (value) {
            if (!value) {
                return '';
            }
            value = value.replace(/\{PARTICIPATION_SALUTION\}/g, exampleSalution);
            value = value.replace(/\{PARTICIPATION_NAME_LAST\}/g, exampleLastName);
            value = value.replace(/\{EVENT_TITLE\}/g, exampleEventTitle);

            return eHtml(value);
        };

        $.each(content, function (key, value) {
            value = replacePlaceholders(value);

            switch (key) {
                case 'content':
                    value = value.replace(/\n\n/g, '</p><p>');
                    break;
                case 'subject':
                    if (value == '') {
                        value = '<em>Kein Betreff</em>';
                    }
                    break;
            }

            if (key == 'subject') {
                $('*#mail-template-iframe-panel .panel-heading').html(value);
            } else {
                preview.find('#mail-part-' + key).html(value);
            }
        });
        updateButton.prop('disabled', false);
    };
    $('*#mail-form .btn-update-preview').click(updateMailPreview);
    $('*#mail-form input, *#mail-form textarea').change(updateMailPreview);

    /**
     * EVENT: Events participants email preview
     */
    $('#exportParticipants').on('click', function (e) {
        e.preventDefault();

        var button = $(this),
            eid = button.data('eid'),
            url = '/admin/event/' + parseInt(eid, 10) + '/participants/export';
        button.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: url,
            data: {},
            datatype: 'json',
            success: function (response) {
                console.log('exorted');
                window.open(response.url);
            },
            error: function (response) {
                $(document).trigger('add-alerts', {
                    message: 'Der Export konnte nicht erstellt werden',
                    priority: 'error'
                });
            },
            complete: function (response) {
                button.prop('disabled', false);
            }
        });
    });
});