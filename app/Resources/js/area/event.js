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
            inputDescriptionFormVisible,
            description;

        switch (action) {
            case 'confirm':
                description = 'Sollen die Anmeldungen der folgenden Teilnehmer bestätigt werden? Dabei werden auch die entsprechenden E-Mails verschickt.';
                inputDescriptionFormVisible = false;
                break;
            case 'paid':
                description = 'Sollen für die folgenden Teilnehmer Zahlungseingang vermerkt werden?';
                inputDescriptionFormVisible = true;
                break;
        }

        modalEl.find('#participantsActionText').text(description);
        modalEl.find('#inputDescriptionForm').css('display', inputDescriptionFormVisible ? 'block' : 'none');
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
                    message: modalEl.find('#inputDescriptionForm input').val(),
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


    /**
     * EVENT: Handle comment form call
     */
    var modalInvoiceCreate = $('#dialogModalInvoiceCreate'),
        modalInvoiceCreateVisible = false;
    modalInvoiceCreate.on('show.bs.modal', function (e) {
        modalInvoiceCreateVisible = true;
        createButton.toggleClass('disabled', false);
        $('#dialogModalInvoiceForm').css('display', 'block');
        $('#dialogModalInvoiceProgress').css('display', 'none');
    });
    modalInvoiceCreate.on('hide.bs.modal', function (e) {
        modalInvoiceCreateVisible = false;
    });
    var createButton = $('#dialogModalInvoiceCreateButton');

    createButton.on('click', function (e) {
        e.preventDefault();
        if (createButton.hasClass('disabled')) {
            return;
        }

        var selectedValue,
            selected = $("#dialogModalInvoiceCreate input[type='radio']:checked"),
            eid = $("#dialogModalInvoiceCreate input[name='eid']").val(),
            token = $("#dialogModalInvoiceCreate input[name='_token']").val(),
            progressBarEl = $('#dialogModalInvoiceProgress .progress-bar');
        progressBarEl.css('width', '0%');
        progressBarEl.css('min-width', '0px');
        progressBarEl.text('');
        if (selected.length > 0) {
            selectedValue = selected.val();
        } else {
            return;
        }
        createButton.toggleClass('disabled', true);

        $('#dialogModalInvoiceForm').css('display', 'none');
        $('#dialogModalInvoiceProgress').css('display', 'block');

        var performInvoiceCreationRequest = function (pid, token, onComplete) {
                if (!modalInvoiceCreateVisible) {
                    return;
                }
                $.ajax({
                    url: '/admin/event/participation/invoice/create',
                    data: {
                        _token: token,
                        pid: pid
                    },
                    error: function () {
                        $(document).trigger('add-alerts', {
                            message: 'Die Rechnung für die Anmeldung #' + pid + ' konnte nicht erstellt werden',
                            priority: 'error'
                        });
                    },
                    complete: function (response) {
                        onComplete(response);
                    }
                });
            },
            finishInvoiceCreation = function (abort, participationsDone) {
                createButton.toggleClass('disabled', false);
                modalInvoiceCreate.modal('hide');
                $('#invoiceListTable').bootstrapTable('refresh');
                $(document).trigger('add-alerts', {
                    message: 'Rechnungen für ' + parseInt(participationsDone) + ' Teilnehmer erstellt',
                    priority: abort ? 'error' : 'success'
                });
            };

        $.ajax({
            url: '/admin/event/' + eid + '/invoice/participations',
            data: {
                _token: token,
                filter: selectedValue
            },
            success: function (response) {
                if (response && response.participations) {

                    var participations = response.participations,
                        participationsTotal = participations.length,
                        participationsDone = participationsTotal - participations.length,
                        updateProgressBar = function (participationsDone) {
                            progressBarEl.css('width', (((participationsDone + 1) / (participationsTotal + 1)) * 100) + '%');
                            progressBarEl.text(participationsDone + '/' + participationsTotal);
                        };
                    progressBarEl.css('min-width', '50px');
                    updateProgressBar(participationsDone);

                    if (participationsTotal === 1) {
                        finishInvoiceCreation(false);
                        return;
                    }
                    var participation = participations.pop(), //extract first element
                        onComplete = function onComplete(rawResponse) {
                            updateProgressBar(participationsTotal - participations.length);
                            if (rawResponse.status !== 200 && !modalInvoiceCreateVisible) {
                                finishInvoiceCreation(true, participationsDone);
                            } else if (participations.length) {
                                participation = participations.pop();
                                performInvoiceCreationRequest(participation.pid, participation.token, onComplete);
                            } else {
                                finishInvoiceCreation(false, participationsDone);
                            }
                        };
                    performInvoiceCreationRequest(participation.pid, participation.token, onComplete);
                }
            },
            error: function () {
                $(document).trigger('add-alerts', {
                    message: 'Die qualifizierten Anmeldungen konnten nicht ermittelt werden',
                    priority: 'error'
                });
                finishInvoiceCreation(true);
            }
        });
    });

    $('#prepareThmubnails').click(function () {
        var btn = $(this),
            progressContainerEl = $('#cache-progress');
        if (btn.hasClass('disabled')) {
            return;
        }
        progressContainerEl.html('<div class="progress"><div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="0" style="width: 0%;min-width:90px;">Preparing...</div></div>');

        btn.toggleClass('disabled', true);
        $.ajax({
            type: 'GET',
            url: '/admin/event/' + btn.data('eid') + '/gallery/image_urls.json',
            success: function (response) {
                if (!response.urls || !response.urls.length) {
                    progressContainerEl.html('');
                    return;
                }
                var urls = response.urls,
                    urlsTotal = urls.length;
                barEl = progressContainerEl.find('.progress-bar');
                barEl.data('aria-valuemax', urlsTotal);
                barEl.text('0/' + urlsTotal);

                var popImage = function () {
                    if (!urls.length) {
                        progressContainerEl.html('');
                        btn.toggleClass('disabled', false);
                        return;
                    }
                    var url = urls.shift();
                    $.ajax({
                        type: 'GET',
                        dataType: 'text',
                        url: url,
                        complete: function () {
                            var urlsDone = urlsTotal - urls.length;
                            barEl.data('aria-valuenow', urlsDone);
                            barEl.text(urlsDone + '/' + urlsTotal);
                            barEl.css('width', (urlsDone / urlsTotal * 100) + '%');
                            popImage();
                        }
                    });
                };
                popImage();
            },
            error: function () {
                progressContainerEl.html('');
                $(document).trigger('add-alerts', {
                    message: 'Die Bilder-Liste konnte nicht geladen werden',
                    priority: 'error'
                });
            },
            complete: function (response) {
                btn.toggleClass('disabled');
            }
        });
    });

    jQuery('.accordion').each(function () {
        var el = jQuery(this),
            elId = el.attr('id'),
            accordionItemKey = userSettings.get('accordion-' + elId, null),
            accordionItemEl;

        if (accordionItemKey) {
            accordionItemEl = jQuery('#' + accordionItemKey);
            accordionItemEl.collapse('show');
        }
        el.on('hide.bs.collapse', function (e) {
            userSettings.set('accordion-' + elId, null);
        });
        el.on('shown.bs.collapse', function (e) {
            var accordionId = jQuery(this).attr('id'),
            itemEl = jQuery(e.target),
            itemId = itemEl.attr('id');

            userSettings.set('accordion-' + accordionId, itemId);
        })
    });

    const paymentSummaryEl = jQuery('#infoEventPaymentSummary');
    if (paymentSummaryEl.length) {
        const loadPaymentSummary = function () {
            const btnReload = paymentSummaryEl.find('button');
            paymentSummaryEl.toggleClass('loading', true);
            btnReload.toggleClass('disabled', true);
            $.ajax({
                type: 'GET',
                url: '/admin/event/' + paymentSummaryEl.data('eid') + '/payment_summary.json',
                success: function (response) {
                    paymentSummaryEl.toggleClass('loading', false);
                    btnReload.toggleClass('disabled', false);
                    var html = '';
                    if (response.success) {
                        html += '<div class="progress">';
                        html += '  <div class="progress-bar" role="progressbar" data-toggle="tooltip" data-title="Umsatz durch Preise: '
                            + response.paid_volume.euros + '&nbsp;€" aria-valuenow="'
                            + response.paid_volume.euros + '&nbsp;€'
                            + '" aria-valuemin="0" aria-valuemax="' + response.expected_volume.euros + '" style="width: ' + response.bars.paid + '%;"><span>' + Math.round(response.paid_volume.cents / 100) + '&nbsp;€</span></div>';

                        if (response.bars.additional) {
                            html += '  <div class="progress-bar bar-alt" role="progressbar" data-toggle="tooltip" data-title="Zusätzlicher Umsatz durch Überzahlung: '
                                + response.additional_volume.euros + '&nbsp;€" aria-valuenow="'
                                + response.additional_volume.euros + '&nbsp;€'
                                + '" aria-valuemin="0" aria-valuemax="' + response.expected_volume.euros + '" style="width: ' + response.bars.additional + '%;"><span>' + Math.round(response.additional_volume.cents / 100) + '&nbsp;€</span></div>';
                        }
                        if (response.bars.missing > 10) {
                            html += '  <div class="progress-bar bar-transparent" role="progressbar" data-toggle="tooltip" data-title="Offene Zahlungen: '
                                + response.missing_volume.euros + '&nbsp;€" aria-valuenow="'
                                + response.missing_volume.euros + '&nbsp;€'
                                + '" aria-valuemin="0" aria-valuemax="' + response.expected_volume.euros + '" style="width: ' + response.bars.missing + '%;"><span>' + Math.round(response.missing_volume.cents / 100) + '&nbsp;€</span></div>';
                        }

                        html += '</div>';

                        html += '<p>';
                        html += 'Es wird ein Umsatz <abbr title="in Höhe von">i.H.v.</abbr> von <b>' + response.expected_volume.euros + '&nbsp;€</b> erwartet. ';
                        if (response.additional_volume.cents) {
                            html += 'Dabei sind bereits <b>' + response.additional_volume.euros + '&nbsp;€</b> Überzahlung berücksichtigt. ';
                        }
                        if (response.missing_volume.cents === 0) {
                            html += 'Es sind keine Zahlungen mehr offen. ';
                        } else {
                            html += 'Es werden noch Zahlungen <abbr title="in Höhe von">i.H.v.</abbr> <b>' + response .missing_volume.euros + '&nbsp;€</b> offen. ';
                        }
                        html += '</p>';

                    } else {
                        if (response.message.content) {
                            html = '<div class="alert alert-'
                                + eHtml(response.message.severity)
                                + '" role="alert">' + eHtml(response.message.content) + '</div>';
                        }
                    }

                    paymentSummaryEl.find('div').html(html);
                    paymentSummaryEl.find('[data-toggle="tooltip"]').tooltip({placement: 'top'});
                    paymentSummaryEl.attr('class', 'load');
                }
            });
        };
        loadPaymentSummary();
        paymentSummaryEl.find('button').on('click', loadPaymentSummary);

    }


});
