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
     * GLOBAL load user settings
     */
    var userSettings = {
        storage: $.localStorage,
        init: function () {
            var userSettingsHash = $('body').data('user-settings-hash'),
                settingStorage = this,
                storage = this.storage;
            $.alwaysUseJsonInStorage(true);
            if (userSettingsHash && userSettingsHash != storage.get('user-settings-hash')) {
                this.load();
            } else if (storage.get('user-settings-hash')) {
                storage.set('user-settings-hash', '');
                storage.set('user-settings', {});
            }

            window.setInterval(function () {
                settingStorage.store();

            }, 2000);
        },
        load: function () {
            var storage = this.storage;
            $.ajax({
                type: 'GET',
                url: '/user/settings/load',
                dataType: 'json',
                success: function (response) {
                    if (response && response.hash && response.settings) {
                        storage.set('user-settings-hash', response.hash);
                        storage.set('user-settings', response.settings);
                    }
                }
            });
        },
        store: function (synchronous) {
            var storage = this.storage,
                async = !synchronous;
            if (storage.get('user-settings-dirty')) {
                storage.set('user-settings-dirty', false);
                $.ajax({
                    type: 'POST',
                    url: '/user/settings/store',
                    data: {
                        _token: $('body').data('user-settings-token'),
                        settings: storage.get('user-settings')
                    },
                    dataType: 'json',
                    async: async,
                    success: function (response) {
                        if (response && response.hash) {
                            storage.set('user-settings-hash', response.hash);
                        }
                    }
                });
            }
        },
        has: function (key) {
            return this.storage.isSet('user-settings.' + key);
        },
        get: function (key, valueDefault) {
            if (this.storage.isSet('user-settings.' + key)) {
                return this.storage.get('user-settings.' + key);
            } else if (valueDefault) {
                return valueDefault;
            }
        },
        set: function (key, valueNew) {
            var storageOld = this.storage.get('user-settings'),
                result = this.storage.set('user-settings.' + key, valueNew),
                storageNew = this.storage.get('user-settings');
            if (JSON.stringify(storageOld) !== JSON.stringify(storageNew)) {
                this.storage.set('user-settings-dirty', true);
            }
            return result;
        }
    };
    userSettings.init();
    window.onbeforeunload = function () {
        userSettings.store(true);
    };

    /**
     * GLOBAL: Enable tooltips
     */
    $('[data-toggle="tooltip"]').tooltip({
        container: 'body'
    });

    /**
     * GLOBAL: Enable popovers
     */
    $('[data-toggle="popover"]').popover({
        container: 'body',
        placement: 'top',
        html: true,
        trigger: 'focus'
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
            dataType: 'json',
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
                dataType: 'json',
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
    var tableRemoteContent = function() {
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
                    userSettings.set('tableFilter.'+id, tableFilterList);
                }
            });
        });

    }();

    /**
     * GLOBAL: Download-button
     */
    $('.btn-download').on('click', function (e) {
        e.preventDefault();

        var button = $(this);
        button.prop('disabled', true);

        var eid = button.data('eid'),
            url = this.getAttribute('href'),
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
    });

    /**
     * ACQUISITION: Admin acquisition list table
     */
    $('#acquisitionListTable').on('click-row.bs.table', function (e, row, $element) {
        location.href = row.bid;
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
                subject: $("#event_mail_subject").val(),
                title: $("#event_mail_title").val(),
                lead: $("#event_mail_lead").val(),
                content: $("#event_mail_content").val()
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
     * ACQUISITION: When selection is changed
     */
    $('*#acquisition_fieldType').change(function () {
        if ($(this).val() == "Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType") {
            $('.form-group-choice').css('display', 'block');
        } else {
            $('.form-group-choice').css('display', 'none');
        }
    });

    /**
     * ACQUISITION: When selection options change
     */
    var updateChoiceOptions = function () {
        var choices = $(this).val().split(';'),
            list = $('*#form-choice-option-list'),
            choicesHtml = '';

        $.each(choices, function (index, value) {
            choicesHtml += '<span class="label label-primary">' + eHtml(value) + '</span> ';
        });

        list.empty();
        list.html(choicesHtml);
    };
    $('*#acquisition_fieldTypeChoiceOptions').change(updateChoiceOptions);
});