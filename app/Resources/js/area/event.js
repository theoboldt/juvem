$(function () {
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
            exampleSalutation = 'Frau',
            exampleLastName = 'Müller',
            exampleEventTitle = $('#mail-form').data('event-title');

        var replacePlaceholders = function (value) {
            if (!value) {
                return '';
            }
            value = value.replace(/\{PARTICIPATION_SALUTATION\}/g, exampleSalutation);
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
     * EVENT Gallery upload
     */
    var dropzoneEl = $('#dropzone'),
        progressEl = $('#upload-progress .row'),
        galleryId = $('.dropzone-gallery .gallery').data('gallery-id-prefix');
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
                if (response.template) {
                    $('#dropzone-gallery .gallery').append(response.template);
                    $('#gallery-image-wrap-0-' + response.iid + ' a').on('click', handleImageClick);
                    galleryRenderer.renderAllGalleries();
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
            imageEl.html('<img src="' + el.attr('href') + '" class="img-responsive">');
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

    $('#specificFilterRefresh').on('click', function (e) {
        var specificAgeValue = $('#specificAge').val(),
            specificDateValue = $('#specificDate').val(),
            tableRefreshButton = $('[name=refresh]'),
            button = $(this),
            token = button.data('token'),
            eid = parseInt(button.data('eid'));

        if (button.hasClass('disabled')) {
            return;
        }
        button.toggleClass('disabled', true);
        tableRefreshButton.toggleClass('disabled', true);

        $.ajax({
            url: '/admin/event/' + eid + '/update-specific-age',
            data: {
                _token: token,
                specificAge: specificAgeValue,
                specificDate: specificDateValue
            },
            success: function () {
                $('#participantsSpecificAgeListTable').bootstrapTable('refresh');
            },
            error: function () {
                $(document).trigger('add-alerts', {
                    message: 'Die Angaben für die Liste konnten nicht verarbeitet werden',
                    priority: 'error'
                });
            },
            complete: function () {
                button.toggleClass('disabled', false);
                tableRefreshButton.toggleClass('disabled', false);
            }
        });
    });


});
