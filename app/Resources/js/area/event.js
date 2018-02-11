$(function () {
    /**
     * EVENT: Participate lactose-free food option force hint
     */
    var ensureLactoseFreeAnnotations = function () {
        var lactoseFreeOption,
            foodOptionFields = $('.food-options').first();

        if (foodOptionFields.length > 0) {
            lactoseFreeOption = foodOptionFields.data('food-lactose-option');

            $('.food-options div').each(function () {
                var popover,
                    button,
                    element = $(this),
                    input = element.find('input');

                if (input.val() == lactoseFreeOption && !element.find('button').length) {
                    element.append(' <button type="button" class="btn btn-default btn-xs btn-round">' +
                        '<span class="glyphicon glyphicon-question-sign"></span>' +
                        '</button>');
                    button = element.find('button');

                    popover = button.popover({
                        content: 'Bitte geben Sie im Feld für <i>Medizinische Hinweise</i> detailierte Informationen zur Ausprägung der Unverträglichkeit an. Müssen konsequent laktosefreie Produkte verwendet werden (bspw. auch bei Schokoriegeln) oder ist es ausreichend auf laktosearme Produkte zu achten? Verwendet der Teilnehmer Laktase-Tabletten?',
                        html: true,
                        trigger: 'manual'
                    });

                    element.find('button').on('click', function (e) {
                        e.preventDefault();
                        popover.popover('show');
                        return false;
                    });

                    popover.on('shown.bs.popover', function () {
                        $('body').one('click', function () {
                            if (button.next('div.popover:visible').length) {
                                popover.popover('hide');
                            }
                        });
                    });

                    element.find('label input').on('click', function (e) {
                        if ($(this).prop('checked') && !button.next('div.popover:visible').length) {
                            popover.popover('show');
                        }
                    });
                }
            });
        }
    };
    ensureLactoseFreeAnnotations();

    /**
     * EVENT: Handle via prototype injected forms
     */
    $('.prototype-container').each(function (index) {
        var element = $(this),
            prototype,
            elementMessage;
        if (element.attr('data-prototype')) {
            prototype = element.data('prototype');
            elementMessage = element.find('.prototype-missing-message');

            element.on('click', function (e) {
                var elementTarget = $(e.target);
                if (elementTarget.parent().is('.prototype-remove')) {
                    elementTarget = elementTarget.parent();
                }
                if (elementTarget.is('.prototype-remove')) {
                    e.preventDefault();
                    var formElement = elementTarget.parent().parent().parent().parent(),
                        formGroup = formElement.parent().parent(),
                        formElementCount = formGroup.find('.prototype-element').length;

                    formElement.remove();
                    if (formElement && elementMessage && !elementMessage.is(':visible') && formElementCount < 2) {
                        elementMessage.show(300);
                    }
                }
            });

            var addElementHandlers = function () {
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

                if (elementMessage && elementMessage.is(':visible')) {
                    elementMessage.hide(300);
                }

                element.find('.prototype-elements').append(newForm);

                ensureLactoseFreeAnnotations();
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
     * EVENT: Handle comment form call
     */
    $('#dialogModalComment').on('show.bs.modal', function (event) {
        var modal = $(this),
            button = $(event.relatedTarget),
            cid = button.data('cid'),
            content = cid ? button.data('content') : null,
            meta = cid ? button.parent().parent().find('small').html() : '',
            relatedId = button.data('related-id'),
            relatedClass = button.data('comment-class'),
            selectorPartClassAndId = '[data-comment-class="' + relatedClass.replace(/\\/g, '\\\\') + '"][data-related-id="' + relatedId + '"]',
            commentsEl = $('.comments' + selectorPartClassAndId),
            countEl = $('.comment-count' + selectorPartClassAndId);

        if (cid) {
            modal.find('#dialogModalCommentLabel').text('Anmerkung bearbeiten');
            modal.find('input[type=submit]').val('Änderungen speichern');
        } else {
            modal.find('#dialogModalCommentLabel').text('Anmerkungen hinzufügen');
            modal.find('input[type=submit]').val('Anmerkungen hinzufügen');
        }

        modal.find('p.meta').html(meta);
        modal.find('#modalCommentContent').val(content);

        $('#dialogModalCommentButton').unbind('click').click(function () {
            button.toggleClass('disabled', true);
            commentsEl.toggleClass('loading-text', true);
            $.ajax({
                url: '/admin/comment/update',
                data: {
                    _token: $('#modalCommentToken').val(),
                    cid: cid,
                    relatedClass: relatedClass,
                    relatedId: relatedId,
                    content: $('#modalCommentContent').val()
                },
                success: function (response) {
                    commentsEl.empty();
                    if (response && response.comments) {
                        commentsEl.html(response.comments);
                    }
                    if (response && response.count) {
                        countEl.text(response.count);
                    }
                },
                error: function () {
                    $(document).trigger('add-alerts', {
                        message: 'Die Anmerkung konnte nicht gespeichert werden',
                        priority: 'error'
                    });
                },
                complete: function () {
                    button.toggleClass('disabled', false);
                    commentsEl.toggleClass('loading-text', false);
                }
            });

            modal.modal('hide');
            return false;
        });
    });


    /**
     * EVENT: Apply confirm/paid status to several participants
     */
    $('#dialogModalParticipantsAction').on('show.bs.modal', function (event) {
        var modalEl = $(this),
            buttonEl = $(event.relatedTarget),
            listEl = modalEl.find('#participantsActionList'),
            action = buttonEl.data('action'),
            participants = $('#participantsListTable').bootstrapTable('getAllSelections'),
            participantIds = [],
            description;

        switch (action) {
            case 'confirm':
                description = 'Sollen die Anmeldungen der folgenden Teilnehmer bestätigt werden? Dabei werden auch die entsprechenden E-Mails verschickt.';
                break;
            case 'paid':
                description = 'Sollen für die folgenden Teilnehmer Zahlungseingang vermerkt werden?';
                break;
        }

        modalEl.find('#participantsActionText').text(description);
        listEl.html('');
        $.each(participants, function (key, participant) {
            participantIds.push(participant.aid);
            listEl.append('<li>' + eHtml(participant.nameFirst) + ' ' + eHtml(participant.nameLast) + '</li>');
        });

        $('#dialogModalCommentButton').unbind('click').click(function () {
            buttonEl.toggleClass('disabled', true);
            $.ajax({
                url: '/admin/event/participantschange',
                data: {
                    _token: modalEl.find('input[name=_token]').val(),
                    eid: modalEl.find('input[name=eid]').val(),
                    action: action,
                    participants: participantIds
                },
                success: function () {
                    $('#participantsListTable').bootstrapTable('refresh');
                },
                error: function () {
                    $(document).trigger('add-alerts', {
                        message: 'Die Änderungen konnten nicht gespeichert werden',
                        priority: 'error'
                    });
                },
                complete: function () {
                    buttonEl.toggleClass('disabled', false);
                }
            });

            modalEl.modal('hide');
            return false;
        });
    });

    /**
     * PARTICIPATION: Participation details
     */
    var priceHistoryTableEl = $('#dialogPriceConfiguration #priceHistory tbody'),
        displayPriceHistory = function (data) {
        var createPriceRow = function (type, value, description, date, creatorId, creatorName, participant) {
            var glyph;

            switch (type) {
                case 'price_payment':
                    glyph = 'log-in';
                    break;
                case 'price_set':
                    glyph = 'pencil';
                    break;
            }

            return '<tr class="' + type + '">' +
            '    <td class="symbol"><span class="glyphicon glyphicon-' + glyph + '" aria-hidden="true"></span></td>' +
            '    <td class="participant">' + participant + '</td>' +
            '    <td class="value">' + value + ' €</td>' +
            '    <td class="description">' + description + '</td>' +
            '    <td class="small"><span class="created">' + date + '</span>, <a class="creator" href="/admin/user/'+creatorId+'">' + creatorName + '</a></td>' +
            '</tr>';
        };

        var rawRow,
            rawRows = '';
        jQuery.each(data, function (key, rowData) {
            rawRow = createPriceRow(
                rowData.type,
                rowData.value,
                eHtml(rowData.description),
                rowData.created_at,
                rowData.created_by_uid,
                eHtml(rowData.created_by_name),
                eHtml(rowData.participant_name)
            );
            rawRows += rawRow;
        });
        priceHistoryTableEl.html(rawRows);
    };
    $('#dialogPriceConfiguration').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget),
            aids = button.data('aids'),
            modal = $(this);
        priceHistoryTableEl.toggleClass('loading-text', true);
        modal.find('.modal-title span').text(button.data('title'));
        modal.data('aids', aids);
        $.ajax({
            url: '/admin/event/participant/price/history',
            data: {
                aids: aids
            },
            success: function (data) {
                if (data.payment_history) {
                    displayPriceHistory(data.payment_history);
                }
            },
            error: function () {
                $(document).trigger('add-alerts', {
                    message: 'Preishistorie konnte nicht geladen werden',
                    priority: 'error'
                });
            },
            complete: function () {
                priceHistoryTableEl.toggleClass('loading-text', false);
            }
        });
    });
    $('#dialogPriceConfiguration #price .btn-predefined').on('click', function (e) {
        e.preventDefault();
        var button = $(this);

        if (button.data('value')) {
            $('#newPriceValue').val(button.data('value'));
        }
        if (button.data('description')) {
            $('#newPriceDescription').val(button.data('description'));
        }
    });
    $('#dialogPriceConfiguration .btn-primary').on('click', function (e) {
        e.preventDefault();
        var button = $(this),
            modal = $('#dialogPriceConfiguration'),
            action = button.data('action'),
            aids = modal.data('aids'),
            value,
            description;
        button.toggleClass('disabled', true);
        priceHistoryTableEl.toggleClass('loading-text', true);

        switch (action) {
            case 'paymentReceived':
                value = modal.find('#paymentValue').val();
                description = modal.find('#paymentDescription').val();
                break;
            case 'newPrice':
                value = modal.find('#newPriceValue').val();
                description = modal.find('#newPriceDescription').val();
                break;
        }

        $.ajax({
            url: '/admin/event/participant/price',
            data: {
                _token: modal.data('token'),
                action: action,
                aids: aids,
                value: value,
                description: description
            },
            success: function (result) {
                if (result && result.payment_history) {
                    displayPriceHistory(result.payment_history);
                }
            },
            error: function () {
                var message;
                switch (action) {
                    case 'newPrice':
                        message = 'Der Preis konnte nicht festgelegt werden';
                        break;
                    default:
                        message = 'Der Bezahlvorgang konnte nicht verarbeitet werden';
                        break;
                }
                $(document).trigger('add-alerts', {
                    message: message,
                    priority: 'error'
                });
            },
            complete: function () {
                priceHistoryTableEl.toggleClass('loading-text', false);
                button.toggleClass('disabled', false);
            }
        });

    });


    /**
     * GLOBAL: Export configurator download
     */
    $('.export-generator-create').on('click', function (e) {
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

    var dropzoneEl = $('#dropzone'),
        progressEl = $('#upload-progress .row');
    if (dropzoneEl) {
        var speedEl = $('#galleryUploadSpeed');
        dropzoneEl.filedrop({
            url: dropzoneEl.data('upload-target'),
            paramname: 'f',
            fallback_id: 'notused',
            fallback_dropzoneClick: false,
            data: {
                token: dropzoneEl.data('token')
            },
            error: function (err, file) {
                switch (err) {
                    case 'BrowserNotSupported':
                        $(document).trigger('add-alerts', {
                            message: 'Der Browser unterstützt leider den Uplaod der Bilder via HTML5 nicht',
                            priority: 'error'
                        });
                        break;
                    case 'TooManyFiles':
                        $(document).trigger('add-alerts', {
                            message: 'Es wurden zu viele Dateien auf einmal zum Hochladen übermittelt',
                            priority: 'error'
                        });
                        break;
                    case 'FileTooLarge':
                        $(document).trigger('add-alerts', {
                            message: 'Die Datei <i>' + eHtml(file.name) + '</i> überschreitet die maximal zulässige Dateigröße',
                            priority: 'error'
                        });
                        break;
                    case 'FileTypeNotAllowed':
                        $(document).trigger('add-alerts', {
                            message: 'Die Datei <i>' + eHtml(file.name) + '</i> hat einen für den Upload ungeeigneten Dateityp',
                            priority: 'error'
                        });
                        break;
                    case 'FileExtensionNotAllowed':
                        $(document).trigger('add-alerts', {
                            message: 'Die Datei <i>' + eHtml(file.name) + '</i> hat eine für den Upload ungeeigneten Dateierweiterung',
                            priority: 'error'
                        });
                        break;
                    default:
                        break;
                }
            },
            allowedfiletypes: ['image/jpeg', 'image/png', 'image/gif'],
            allowedfileextensions: ['.jpg', '.jpeg', '.png', '.gif'],
            queuefiles: 4,
            maxfiles: 100,
            maxfilesize: 10,
            uploadFinished: function (i, file, response, time) {
                progressEl.find('#file-upload-progress-' + i).remove();
                if (response.iid) {
                    var html = '<div class="image col-xs-6 col-sm-4 col-md-3 col-lg-2" id="galleryImage-' + response.iid + '">' +
                        ' <div class="gallery-image-wrap">' +
                        '  <a href="/../event/' + response.eid + '/gallery/' + response.iid + '/original" data-eid="' + response.eid + '" data-iid="' + response.iid + '" >' +
                        '  <img src="/../event/' + response.eid + '/gallery/' + response.iid + '/thumbnail" class="img-responsive" />' +
                        '  <span><i>' + eHtml(file.name) + '</i></span></a>' +
                        ' </div>' +
                        '</div>';
                    $('#dropzone-gallery').append(html);
                    $('#galleryImage-' + response.iid + ' a').on('click', handleImageClick);
                }
            },
            uploadStarted: function (i, file, len) {
                progressEl.append(
                    '<div class="col-xs-2" id="file-upload-progress-' + i + '">' +
                    ' <div class="progress">' +
                    '  <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">' +
                    '   0%' +
                    '  </div>' +
                    ' </div>' +
                    '</div>'
                );
            },
            progressUpdated: function (i, file, progress) {
                var barEl = progressEl.find('#file-upload-progress-' + i + ' .progress-bar');
                if (barEl.data('aria-valuenow') != progress) {
                    barEl.data('aria-valuenow', progress);
                    barEl.css('width', progress + '%');
                    barEl.text(progress + '%');
                }
            },
            speedUpdated: function (i, file, speed) {
                speedEl.text(speed.toFixed(1) + ' kb/s');
            },
            afterAll: function () {
                speedEl.text('');
            }
        });

        var modalEl = $('#galleryImageDetails'),
            titleEl = $('#galleryImageTitle'),
            imageEl = $('#galleryImageImage'),
            deleteEl = $('#galleryImageDelete'),
            saveEl = $('#galleryImageSave'),
            handleImageClick;

        handleImageClick = function (e) {
            var el = $(this),
                iid = el.data('iid');
            e.preventDefault();
            titleEl.val(el.data('title'));
            modalEl.modal('show');
            imageEl.html('<img src="/../event/' + el.data('eid') + '/gallery/' + iid + '/detail" class="img-responsive">');
            deleteEl.data('iid', iid);
        };
        $('#dropzone-gallery a').on('click', handleImageClick);

        deleteEl.on('click', function (e) {
            var iid = deleteEl.data('iid');
            deleteEl.toggleClass('disabled', true);
            $.ajax({
                url: '/admin/event/gallery/image/delete',
                data: {
                    _token: deleteEl.data('token'),
                    iid: iid
                },
                success: function () {
                    $('#galleryImage-' + iid).remove();
                    modalEl.modal('hide');
                },
                error: function () {
                    $(document).trigger('add-alerts', {
                        message: 'Das Bild konnte nicht gelöscht werden',
                        priority: 'error'
                    });
                },
                complete: function () {
                    deleteEl.toggleClass('disabled', false);
                }
            });
        });
        saveEl.on('click', function (e) {
            var iid = deleteEl.data('iid'),
                title = $('#galleryImageTitle').val();
            saveEl.toggleClass('disabled', true);
            $.ajax({
                url: '/admin/event/gallery/image/save',
                data: {
                    _token: saveEl.data('token'),
                    title: title,
                    iid: iid
                },
                success: function () {
                    $('#galleryImage-' + iid + ' span').text(title);
                    modalEl.modal('hide');
                },
                error: function () {
                    $(document).trigger('add-alerts', {
                        message: 'Das Bild konnte nicht gespeichert werden',
                        priority: 'error'
                    });
                },
                complete: function () {
                    saveEl.toggleClass('disabled', false);
                }
            });
        });
    }

    if ($('#media-gallery')) {
        $(document).on('click', '[data-toggle="lightbox"]', function (event) {
            event.preventDefault();
            $(this).ekkoLightbox();
        });
    }
});