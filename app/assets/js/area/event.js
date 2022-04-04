$(function () {
    var updateButton = $('*#mail-form .btn-update-preview'),
        eventId = $('*#mail-form').data('eid'),
        updateScheduled = false;

    /**
     * EMAIL: Email preview
     */
    const updateMailPreview = function () {
            if (updateScheduled === false) {
                updateScheduled = true;

                updateButton.prop('disabled', true);
                setTimeout(function () {
                    updateScheduled = false;

                    var form = $('#mail-form form'),
                        action = form.attr('action'),
                        target = form.attr('target');

                    form.attr('action', '/admin/event/'+eventId+'/mail_preview');
                    form.attr('target', 'mail-template-iframe');

                    form.submit();

                    form.attr('action', action ? action : '');
                    form.attr('target', target ? target : '');
                    updateButton.prop('disabled', false);
                }, 500);
            }
        };
    updateButton.click(updateMailPreview);
    $('*#mail-form input, *#mail-form textarea').bind('input propertychange', updateMailPreview);
    updateButton.click(function () {
        updateMailPreview();
    });

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
            participants = $('#participantsListTable').bootstrapTable('getSelections'),
            participantIds = [],
            inputDescriptionFormVisible,
            description;

        switch (action) {
            case 'confirm':
                description = 'Sollen die Anmeldungen der folgenden Teilnehmer:innen bestätigt werden? Dabei werden auch die entsprechenden E-Mails verschickt.';
                inputDescriptionFormVisible = false;
                break;
            case 'paid':
                description = 'Sollen für die folgenden Teilnehmer:innen Zahlungseingang vermerkt werden?';
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
                    message: 'Rechnungen für ' + parseInt(participationsDone) + ' Teilnehmer:innen erstellt',
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
                            progressBarEl.css('width', (((participationsDone + 1) / (participationsTotal)) * 100) + '%');
                            progressBarEl.text((participationsDone+1) + '/' + participationsTotal);
                        };
                    progressBarEl.css('min-width', '50px');
                    updateProgressBar(participationsDone);

                    if (participationsTotal === 1) {
                        finishInvoiceCreation(false);
                        return;
                    }
                    if (participations.length) {
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
                            html += 'Es sind noch Zahlungen <abbr title="in Höhe von">i.H.v.</abbr> <b>' + response.missing_volume.euros + '&nbsp;€</b> offen. ';
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

    var weatherCurrentEl = $('#weather-current');
    if (weatherCurrentEl.length) {
        window.ensureOwfontLoad();

        const createTemperatureHtml = function (temperatureAware) {
                return '<span ' +
                    'data-title="Gefühlte Temperatur: ' +
                    String(temperatureAware.temperature_feels_like).replace('.', ',') +
                    ' °C"' +
                    ' style="color:#222;">' + String(temperatureAware.temperature).replace('.', ',') + '°C</span>';
            },
            createWeatherHtml = function (weatherAware, temperatureAware, htmlTemperature, size, useText, defaultCls) {
                var html = '';
                if (!defaultCls) {
                    defaultCls = 'owf owf-pull-left owf-border';
                }

                html += '<div class="w';
                if (weatherAware.length > 1) {
                    html += ' fm';
                }
                if (weatherAware.length === 2) {
                    html += ' f2';
                }
                html += '">';

                if (weatherAware.length === 1) {
                    html += '<i class="owf-' + weatherAware[0].id + ' owf-' + size + 'x ' + defaultCls + '" data-title="' + weatherAware[0].description + '"></i>';
                    html += '</div>';
                    html += '<div class="t">' + htmlTemperature + '</div>';
                    if (useText) {
                        html += weatherAware[0].description;
                    }
                } else {
                    var htmlWeatherDescriptions = [];
                    $.each(weatherAware, function (key, weather) {
                        html += '<i class="owf-' + weather.id + ' owf-' + size + 'x ' + defaultCls + '" data-title="' + weather.description + '"></i>';
                        htmlWeatherDescriptions.push(weather.description);
                    });
                    if (useText) {
                        html += htmlWeatherDescriptions.join(', ') + '; ';
                    }
                    html += '</div>';
                    html += ' <div class="t">' + htmlTemperature + '</div>';
                }
                if (temperatureAware.rain_px && temperatureAware.rain_probability) {
                    html += ' <div class="r" style="opacity:' + temperatureAware.rain_probability + ';height: ' + temperatureAware.rain_px + 'px;" ' +
                        'data-title="zu ';
                    html += parseInt(temperatureAware.rain_probability * 100);
                    html += '% ' + temperatureAware.rain_mm + ' mm/3h Niederschlag"></div>';
                }

                return html;
            };

        $.ajax({
            url: weatherCurrentEl.data('source'),
            success: function (response) {
                if (response && response.current && response.current.weather && response.current.weather.length) {
                    var htmlTemperature = createTemperatureHtml(response.current);

                    var html = '<label ';
                    if (response.current.created_at) {
                        html += 'data-title="Aktuelle Wetterdaten vom ' + response.current.created_at + '"';
                    }
                    html += 'class="control-label">Wetter (aktuell)</label>' +
                        '<p>';

                    html += createWeatherHtml(response.current.weather, response.current, htmlTemperature, 2, true);

                    html += ' </p>';

                    weatherCurrentEl.html(html);
                    $('#weather-current [data-title]').tooltip({
                        container: 'body'
                    });
                }
                if (response && response.forecast_available) {
                    var htmlRows = '',
                        headerWritten = false;

                    $.each(response.forecast, function (date, dateData) {
                        if (!headerWritten) {
                            htmlRows += '<thead>';
                            htmlRows += '<tr>';
                            htmlRows += '<td class="e">&nbsp;</td>';

                            const columnCount = Object.keys(dateData.times).length + 1,
                                columnWidth = (1 / columnCount) * 100;

                            $.each(dateData.times, function (time, climatic) {
                                htmlRows += '<td style="width:' + columnWidth + '%;">' +
                                    '<div>' + time + '</div>' +
                                    '</td>';
                            });
                            htmlRows += '</tr>';
                            htmlRows += '</thead>';
                            htmlRows += '<tbody>';
                            headerWritten = true;
                        }

                        htmlRows += '<tr>';
                        htmlRows += '<td class="d">' +
                            '<div>' +
                            ' <div class="d" data-title="';
                        switch (dateData.weekday) {
                            case 1:
                                htmlRows += 'Montag';
                                break;
                            case 2:
                                htmlRows += 'Dienstag';
                                break;
                            case 3:
                                htmlRows += 'Mittwoch';
                                break;
                            case 4:
                                htmlRows += 'Donnerstag';
                                break;
                            case 5:
                                htmlRows += 'Freitag';
                                break;
                            case 6:
                                htmlRows += 'Samstag';
                                break;
                            case 7:
                                htmlRows += 'Sonntag';
                                break;
                            default:
                                htmlRows += dateData.weekday;
                                break;
                        }

                        htmlRows += '">' + dateData.day + '</div>' +
                            ' <div class="m">' + dateData.month + '</div>' +
                            '</div>' +
                            '</td>';

                        $.each(dateData.times, function (time, climatic) {
                            if (climatic.forecast.temperature === 0 || climatic.forecast.temperature) {
                                var htmlTemperature = createTemperatureHtml(climatic.forecast);

                                htmlRows += '<td>';
                                htmlRows += '<div class="deg' + climatic.forecast.temperature + '">';
                                htmlRows += createWeatherHtml(
                                    climatic.forecast.weather,
                                    climatic.forecast,
                                    htmlTemperature,
                                    climatic.forecast.weather.length > 1 ? 2 : 3,
                                    false,
                                    'owf'
                                );

                                htmlRows += '</div>';
                                htmlRows += '</td>';
                            } else {
                                htmlRows += '<td class="e"></td>';
                            }
                        });

                        htmlRows += '</tr>';
                    });
                    htmlRows += '</tbody>';

                    $('#weather-forecast').html('<label class="control-label">Wettervorhersage</label><table>' + htmlRows + '</table>');
                    $('#weather-forecast [data-title]').tooltip({
                        container: 'body'
                    });
                }
            },
        });
    }

    $('#modalChangeTracking').on('show.bs.modal', function (event) {
        var buttonEl = $(event.relatedTarget),
            modalEl = $(this);
        modalEl.find('h4 small').html('');
        modalEl.find('table tbody').html('<tr><td colspan="5" class="loading-text text-center">(Änderungsverlauf wird geladen)</td></tr>');

        $.ajax({
            url: buttonEl.data('list-url'),
            success: function (response) {
                if (response && response.changes) {
                    var html = '',
                        htmlRow,
                        operation,
                        glyph,
                        singleRelatedClass = true,
                        previousRelatedClass = '';

                    const classNameLabel = function (className) {
                        switch (className) {
                            case 'AppBundle\\Entity\\Participation':
                                return 'Anmeldung';
                            case 'AppBundle\\Entity\\Participant':
                                return 'Teilnehmer:in';
                            case 'AppBundle\\Entity\\PhoneNumber':
                                return 'Telefonnummer';
                            case 'AppBundle\\Entity\\AcquisitionAttribute\\Fillout':
                                return 'Feldantwort';
                            case 'AppBundle\\Entity\\Employee':
                                return 'Mitarbeiter:in';
                            default:
                                return '<i>' + className + '</i>';
                        }
                    };

                    if (response.title) {
                        modalEl.find('h4 small').html(eHtml(response.title));
                    }
                    $.each(response.changes, function (index, change) {
                        htmlRow = '<tr>';

                        if (previousRelatedClass !== '' && previousRelatedClass !== change.related_class) {
                            singleRelatedClass = false;
                        }
                        console.log(previousRelatedClass);
                        console.log(change.related_class);
                        previousRelatedClass = change.related_class;

                        switch (change.operation) {
                            case 'create':
                                operation = 'Erstellen';
                                glyph = 'plus';
                                break;
                            case 'update':
                                operation = 'Bearbeiten';
                                glyph = 'pencil';
                                break;
                            case 'delete':
                                operation = 'Löschen';
                                glyph = 'remove';
                                break;
                            case 'trash':
                                operation = 'Papierkorb';
                                glyph = 'trash';
                                break;
                            case 'restore':
                                operation = 'Wiederherstellen';
                                glyph = 'repeat';
                                break;
                            default:
                                operation = change.operation;
                                glyph = 'question-sign';
                                break;
                        }

                        htmlRow += '<td class="small">' + change.occurrence_date + '</td>';
                        htmlRow += '<td class="small col-class">' + classNameLabel(change.related_class) + '</td>';
                        htmlRow += '<td>' +
                            '<span class="glyphicon glyphicon-' + glyph + '" aria-hidden="true" title="' + operation + '"></span>' +
                            '</td>';

                        htmlRow += '<td class="small">';
                        if (change.responsible_user) {
                            if (change.responsible_user && change.responsible_user.id) {
                                htmlRow += '<a href="/admin/user/' + change.responsible_user.id + '">' + change.responsible_user.fullname + '</a>';
                            } else {
                                htmlRow += change.responsible_user.fullname;
                            }
                        } else {
                            htmlRow += '<i class="empty no-one"></i>';
                        }
                        htmlRow += '</td>';

                        htmlRow += '<td>';

                        var formatValue = function (value) {
                            if (value === true) {
                                return 'ja';
                            } else if (value === false) {
                                return 'nein';
                            } else if (value === '') {
                                return '<i class="empty empty-empty"></i>';
                            } else if (value === null || value === undefined) {
                                return '<i class="empty empty-none"></i>';
                            } else {
                                return '<code>' + eHtml(value) + '</code>';
                            }
                        };

                        if ((change.attribute_changes && change.attribute_changes.length)
                            || (change.collection_changes && change.collection_changes.length)
                        ) {
                            htmlRow += '<ul>    ';
                            if (change.attribute_changes.length) {
                                $.each(change.attribute_changes, function (index, attributeChange) {
                                    htmlRow += '<li> <label>' + eHtml(attributeChange.attribute) + '</label>: ';

                                    htmlRow += '<span class="before">';
                                    htmlRow += formatValue(attributeChange.before);
                                    htmlRow += '</span>';
                                    htmlRow += ' &rarr; ';
                                    htmlRow += '<span class="after">';
                                    htmlRow += formatValue(attributeChange.after);
                                    htmlRow += '</span>';

                                    htmlRow += '</li>';
                                });
                            }

                            if (change.collection_changes.length) {
                                $.each(change.collection_changes, function (index, collectionChange) {
                                    htmlRow += '<li> <label>' + eHtml(collectionChange.attribute) + '</label>: ';
                                    htmlRow += '<code>' + collectionChange.value + '</code> ';
                                    switch (collectionChange.operation) {
                                        case 'insert':
                                            htmlRow += 'hinzugefügt';
                                            break;
                                        case 'delete':
                                            htmlRow += 'entfernt';
                                            break;
                                    }
                                    htmlRow += '</li>';
                                });
                            }

                            htmlRow += '</ul>';
                        } else {
                            switch (change.operation) {
                                case 'create':
                                    htmlRow += 'Erstellt';
                                    break;
                                case 'delete':
                                    htmlRow += 'Gelöscht';
                                    break;
                                case 'trash':
                                    htmlRow += 'In den Papierkorb gelegt';
                                    break;
                                case 'restore':
                                    htmlRow += 'Aus dem Papierkorb wiederhergestellt';
                                    break;
                            }
                        }
                        htmlRow += '</td>';

                        htmlRow += '</tr>';
                        html += htmlRow;
                    });

                    modalEl.find('table tbody').html(html);
                    modalEl.find('table').toggleClass('single-class', singleRelatedClass);
                }
            }
        });
    });

    var participantsLocationDistributionEl = $('#participantsLocationDistribution');
    if (participantsLocationDistributionEl) {
        var tbody = participantsLocationDistributionEl.find('table tbody');
        participantsLocationDistributionEl.find('.btn').click(function () {
            var el = $(this);
            if (el.hasClass('disabled')) {
                return;
            }
            for (var i = 0; i <= 5; ++i) {
                participantsLocationDistributionEl.find('table').toggleClass('level' + i, el.find('input').attr('id') === 'level' + i);
            }
        });
        $.ajax({
            type: 'GET',
            url: participantsLocationDistributionEl.data('url'),
            success: function (response) {
                if (!response.distribution) {
                    tbody.html(
                        '<tr>' +
                        ' <td colspan="3">' +
                        '  <i>Die Daten konnten nicht geladen werden.</i>' +
                        ' </td>' +
                        '</tr>'
                    );
                    return;
                }
                participantsLocationDistributionEl.find('.btn').removeClass('disabled');

                const allBelowUnknown = function (items) {
                    var result = true;
                    $.each(items, function (key, item) {
                        if (item.n !== 'Unbekannt') {
                            result = false;
                        }
                        if (item.c && item.c.length) {
                            result = allBelowUnknown(item.c);
                        }
                        if (!result) {
                            return false;
                        }
                    });
                    return result;
                }

                const flatten = function (items, level) {
                    var result = '';

                    $.each(items, function (key, item) {
                        var name = eHtml(item.n);

                        if (name === 'Unbekannt') {
                            name = '<i>Unbekannt</i>';
                        }
                        result += '<tr class="level' + level + '">';
                        result += '<td>' + name + '</td>';
                        result += '<td class="number">' + item.o + '</td>';

                        result += '<td>';
                        if (response.total) {
                            var percentage = ((item.o / response.total) * 100);
                            result += ' <div class="progress" style="margin-bottom: 0; min-width: 200px;">';
                            result += '  <div class="progress-bar" role="progressbar" aria-valuenow="' + item.o + '" aria-valuemin="0" aria-valuemax="' + response.total + '" style="width: ' + percentage + '%;">';
                            result += ((percentage > 5) ? item.o : '');
                            result += '</div>';
                        }
                        result += '</td>';

                        if (item.c && item.c.length && !allBelowUnknown(item.c)) {
                            result += flatten(item.c, level + 1);
                        }
                    });

                    return result;
                };

                var html = flatten(response.distribution, 0, response.total);
                tbody.html(html);
            },
            error: function () {
                tbody.html('');
            }
        });
    }

    const cloudFileListingEl = jQuery('#cloudFileListing');
    if (cloudFileListingEl.length) {
        const loadCloudFiles = function () {
            const btnReload = cloudFileListingEl.find('button');
            cloudFileListingEl.toggleClass('loading', true);
            btnReload.toggleClass('disabled', true);
            $.ajax({
                type: 'GET',
                url: '/admin/event/' + cloudFileListingEl.data('eid') + '/cloud/files.json',
                success: function (response) {
                    cloudFileListingEl.toggleClass('loading', false);
                    btnReload.toggleClass('disabled', false);
                    var targetEl = cloudFileListingEl.find('div');
                    if (response.files) {
                        if (response.files.length) {
                            var html = '<table class="table table-striped table-condensed">';
                            html += '<thead><tr><td>Datei</td><td class="text-right" style="min-width: 70px;">Größe</td><td><abbr title="Letzte Änderung" style="min-width: 70px;">Datum</abbr></td></tr></thead>';
                            html += '<tbody>';
                            $.each(response.files, function (key, file) {
                                html += '<tr>';
                                html += '<td>'+eHtml(file.filename)+'</td>';
                                html += '<td class="text-right">'+filesize(file.filesize)+'</td>';
                                html += '<td>'+file.last_modified+'</td>';
                                html += '<td class="text-right">';
                                html += '<a href="' + file.download + '" class="btn btn-default btn-xs" title="Herunterladen" target="_blank"><span class="glyphicon glyphicon-download" aria-hidden="true"></span></a>';
                                html += '</td>';
                                
                                html += '</tr>';
                            });
                            html += '<tbody>';
                            html += '</table>';

                            targetEl.html(html);
                        } else {
                            targetEl.html('<p>Keine Dateien in der Cloud gespeichert.</p>');
                        }
                    } else {
                        if (response && response.message && response.message.content) {
                            html = '<div class="alert alert-' + eHtml(response.message.severity)
                                + '" role="alert">' + eHtml(response.message.content) + '</div>';
                        }
                    }

                    cloudFileListingEl.find('div').html(html);
                    cloudFileListingEl.find('[data-toggle="tooltip"]').tooltip({placement: 'top'});
                    cloudFileListingEl.attr('class', 'load');
                }
            });
        };
        loadCloudFiles();
        cloudFileListingEl.find('button').on('click', loadCloudFiles);
    }

    jQuery('.event-participant-distribution').each(function () {
        const el = jQuery(this),
            eid = el.data('eid');
        if (!eid) {
            return;
        }
        $.ajax({
            type: 'GET',
            url: '/admin/event/' + eid + '/participant-distribution.json',
            success: function (response) {
                el.removeClass('loading');
                el.addClass('load');
                const renderDistribution = function () {
                    if (!response.participants.length) {
                        return;
                    }

                    var html = '',
                        filters = [],
                        filteredParticipants,
                        genderDistribution = {},
                        ageDistribution = {},
                        ageDistributionMax = 0,
                        birthdayAtEvent = 0;

                    el.find('.filter').each(function () {
                        const filterEl = jQuery(this),
                            filter = filterEl.data('filter'),
                            active = filterEl.hasClass('active');
                        if (filter && active) {
                            filters.push(filter);
                        }
                    });
                    filteredParticipants = response.participants.filter(function (participant) {
                        if (filters.length) {
                            var include = false;
                            filters.forEach(function (filter) {
                                if (participant[filter]) {
                                    include = true;
                                }
                            });
                            return include;
                        } else {
                            return true;
                        }
                    });
                    filteredParticipants.forEach(function (participant) {
                        if (!genderDistribution[participant.gender]) {
                            genderDistribution[participant.gender] = 0;
                        }
                        ++genderDistribution[participant.gender];
                        if (!ageDistribution[participant.years_of_life]) {
                            ageDistribution[participant.years_of_life] = 0;
                        }
                        ++ageDistribution[participant.years_of_life];
                        if (participant.is_birthday_at_event) {
                            ++birthdayAtEvent;
                        }
                    });
                    jQuery.each(ageDistribution, function (age, count) {
                        if (count > ageDistributionMax) {
                            ageDistributionMax = count;
                        }
                    });

                    if (filteredParticipants.length) {
                        html += ' <div class="progress">';
                        jQuery.each(genderDistribution, function (gender, count) {
                            const barClasses = ['progress-bar'];
                            let tooltip = count + '/' + filteredParticipants.length;
                            switch (gender) {
                                case 'männlich':
                                    barClasses.push('progress-bar-gender-1');
                                    tooltip += ' Teilnehmern sind männlich';
                                    break;
                                case 'weiblich':
                                    barClasses.push('progress-bar-gender-2');
                                    tooltip += ' Teilnehmerinnen sind weiblich';
                                    break;
                                default:
                                    barClasses.push('progress-bar-gender-0');
                                    tooltip += ' Teilnehmer:innen sind weiblich';
                                    break;
                            }
                            html += '<div' +
                                ' class="' + barClasses.join(' ') + '"' +
                                ' data-toggle="tooltip"' +
                                ' title="' + tooltip + '"' +
                                ' role="progressbar"' +
                                ' aria-valuemin="0"' +
                                ' aria-valuenow="' + count + '"' +
                                ' aria-valuemax="' + filteredParticipants.length + '"' +
                                ' style="width:' + ((count / filteredParticipants.length) * 100) + '%"' +
                                '>';
                            html += '  <span class="sr-only">' + tooltip + '</span>';
                            html += '  <span>' + count + '</span>';
                            html += ' </div>';
                        });
                        html += ' </div>';

                        el.find('.distribution-gender').html(html);

                        html = '';
                        jQuery.each(ageDistribution, function (age, count) {
                            html += ' <div class="row">' +
                                '  <div class="col-xs-2 col-sm-3 text-right">' +
                                '   ' + age + ' <abbr title="vollendete Lebensjahre">J.</abbr>' +
                                '  </div>' +
                                '  <div class="col-xs-10 col-sm-9">' +
                                '   <div class="progress">' +
                                '     <div class="progress-bar" role="progressbar" aria-valuemin="0" ' +
                                ' data-toggle="tooltip"' +
                                ' role="progressbar"' +
                                ' aria-valuemin="0"' +
                                ' aria-valuenow="' + count + '"' +
                                ' aria-valuemax="' + ageDistributionMax + '"' +
                                ' style="width:' + ((count / ageDistributionMax) * 100) + '%"' +
                                ' title="' + count + '/' + filteredParticipants.length + ' Teilnehmer:innen haben zu Beginn der Veranstaltung das ' + age + '.&nbsp;Lebensjahr vollendet"' +
                                '     >' +
                                '      <span class="sr-only">' + count + '/' + filteredParticipants.length + ' Teilnehmer:innen haben zu Beginn der Veranstaltung das ' + age + '.&nbsp;Lebensjahr vollendet</span>' +
                                '      <span title="Anzahl">' +
                                '       <abbr title="Teilnehmer:innen">' + count + '</abbr>' +
                                '      </span>' +
                                '   </div>' +
                                '   </div>' +
                                '  </div>' +
                                ' </div>';
                        });
                        el.find('.distribution-age').html(html);

                        html = '';
                        html += '<small>';
                        if (birthdayAtEvent === 0) {
                            html = 'Niemand hat während der Veranstaltung Geburtstag.';
                        } else {
                            html += '<span class="glyphicon glyphicon-gift" aria-hidden="true"></span> ';
                            if (birthdayAtEvent === 1) {
                                html += '1 Teilnehmer:in hat während der Veranstaltung Geburtstag.';
                            } else {
                                html += birthdayAtEvent + ' Teilnehmer:innen haben während der Veranstaltung Geburtstag.';
                            }
                        }
                        html += '</small>';
                        el.find('.distribution-birthday').html(html);

                    } else {
                        html = '<div class="row">' +
                            ' <div class="col-xs-12">' +
                            '  Niemand erfüllt die gewünschten Filterkriterien.' +
                            ' </div>' +
                            '</div>';
                        el.find('.distribution-gender').html(html);
                        el.find('.distribution-age').html('');
                        el.find('.distribution-birthday').html('');
                    }

                    el.find('[data-toggle="tooltip"]').tooltip({
                        container: 'body'
                    });
                };

                var html = '';
                const createButtonHtml = function (filter, active, disabled, label, tooltip) {
                    var html = '',
                        classes = ['btn', 'btn-xs', 'btn-default'];
                    if (active) {
                        classes.push('active');
                    }
                    if ((response.has_confirmed && !response.has_unconfirmed && !response.has_withdrawn_rejected && !response.has_deleted)
                        || (!response.has_confirmed && response.has_unconfirmed && !response.has_withdrawn_rejected && !response.has_deleted)
                        || (!response.has_confirmed && !response.has_unconfirmed && response.has_withdrawn_rejected && !response.has_deleted)
                        || (!response.has_confirmed && !response.has_unconfirmed && !response.has_withdrawn_rejected && response.has_deleted)
                    ) {
                        disabled = true;
                    }
                    if (disabled) {
                        classes.push('disabled');
                    }

                    html += '  <div class="btn-group" role="group">';
                    html += '   <button type="button" class="' + classes.join(' ') + ' filter" data-filter="' + filter + '" autocomplete="off" title="' + eHtml(tooltip) + '">';
                    html += '  ' + label;
                    html += '   </button>';
                    html += '  </div>';
                    return html;
                }


                if (response.participants.length) {
                    html += '<div class="col-xs-12" style="margin-bottom: 5px;">';
                    html += ' <div class="btn-group btn-group-justified" role="group" data-toggle="buttons" aria-label="Teilnehmer:innen Filter für die Verteilung">'

                    html += createButtonHtml('is_unconfirmed', !response.has_confirmed, !response.has_unconfirmed, 'Unbestätigt', 'Unbestätigte Teilnehmer:innen berücksichtigen');
                    html += createButtonHtml('is_confirmed', response.has_confirmed, !response.has_confirmed, 'Bestätigt', 'Bestätigte Teilnehmer:innen berücksichtigen');
                    html += createButtonHtml('is_withdrawn_rejected', !response.has_confirmed, !response.has_withdrawn_rejected, 'Abgelehnt/Zur.', 'Abgelehnte oder zurückgezogene Teilnehmer:innen berücksichtigen');
                    html += createButtonHtml('is_deleted', !response.has_confirmed, !response.has_deleted, 'Gelöscht', 'Gelöschte Teilnehmer:innen berücksichtigen');

                    html += ' </div>';
                    html += '</div>';

                    html += '<div class="col-xs-12 distribution-gender" style="margin-bottom: 5px;">';
                    html += '</div>';

                    html += '<div class="col-xs-12 distribution-age" style="margin-bottom: 5px;">';
                    html += '</div>';

                    html += '<div class="col-xs-12 distribution-birthday" style="margin-bottom: 5px;">';
                    html += '</div>';
                } else {
                    html = '<div class="col-xs-12">' +
                        '  Im Moment sind keine Teilnehmer:innen angemeldet.' +
                        '</div>';

                }


                el.append(html);
                renderDistribution();

                el.find('button.filter').click(function () {
                    const btn = jQuery(this);
                    if (!btn.hasClass('disabled')) {
                        btn.toggleClass('active');
                        renderDistribution();
                    }
                });
            }
        });


    });
});
