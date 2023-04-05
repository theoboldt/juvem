$(function () {
    jQuery('.btn-email-listing').each(function () {
        const btn = jQuery(this),
            url = btn.data('url'),
            refreshUrl = btn.data('refresh-url'),
            refreshToken = btn.data('refresh-token'),
            type = btn.data('type'),
            id = btn.data('id'),
            cacheKey = 'emails.list.' + type + '.id' + id,
            modalEl = jQuery('#modalEmailListing'),
            refreshButtonEl = modalEl.find('.btn-refresh'),
            tbodyEl = modalEl ? modalEl.find('table tbody') : null;

        if (!url) {
            btn.toggleClass('disabled', true);
            return;
        }

        const updateButton = function (list) {
            const btnTitle = list && list.length === 1 ? ' 1 E-Mail ' : ' ' + list.length + ' E-Mails ';
            btn.find('.hidden-xs').html(btnTitle);
        };

        btn.toggleClass('loading-text', true);
        if (commonCache.has(cacheKey)) {
            updateButton(commonCache.get(cacheKey));
        } else {
            btn.toggleClass('disabled', true);
        }

        const renderEmails = function () {
                if (btn.hasClass('disabled') || !modalEl || !tbodyEl) {
                    return;
                }
                tbodyEl.html('');
                const emails = commonCache.get(cacheKey);
                let html = '';

                if (emails && emails.length === 0) {
                    html += '<tr><td colspan="5" class="text-center">Keine passenden E-Mails gefunden.</td></tr>';
                }


                jQuery.each(emails, function (key, email) {
                    let emailAddressesFrom = eHtml(email.from.join(', '));
                    let emailAddressesTo = eHtml(email.to.join(', '));

                    html += '<tr>';
                    html += '<td>';

                    if (email.organization_sender) {
                        html += '<span class="label label-primary" title="'+emailAddressesFrom+'">Wir</span>';
                    } else {
                        html += '<small>' + emailAddressesFrom + '</small>';
                    }
                    html += ' &rarr; ';
                    if (email.organization_receiver) {
                        html += '<span class="label label-primary" title="'+emailAddressesTo+'">Uns</span>';
                    } else {
                        html += '<small>' + emailAddressesTo + '</small>';
                    }

                    html += '</td>';
                    html += '<td>' + eHtml(email.date) + '</td>';
                    html += '<td>';
                    if (email.attachment_count) {
                        html += '<span class="glyphicon glyphicon-paperclip" aria-hidden="true" title="';
                        html += (email.attachment_count === 1 ? '1 Anhang' : email.attachment_count + ' Anhänge');
                        html += '"></span> ';
                    }
                    html += eHtml(email.subject);
                    html += '</td>';

                    html += '<td>';
                    if (email.mailbox_symbol) {
                        html += '<span class="glyphicon glyphicon-' + email.mailbox_symbol + '" aria-hidden="true"></span> ';
                    }
                    html += eHtml(email.mailbox);
                    html += '</td>';

                    html += '<td>';
                    if (email.url_download_raw) {
                        html += '<a href="' + email.url_download_raw + '" target="_blank" title="E-Mail Quelltext herunterladen" class="btn btn-xs btn-default btn-download-email">' +
                            '<span class="glyphicon glyphicon-download" aria-hidden="true"></span>' +
                            '</a> ';
                    }
                    html += '</td>';

                    html += '</tr>';
                });
                tbodyEl.html(html);
                tbodyEl.toggleClass('loading-text', false);
            },
            loadMails = function (refresh) {
                refreshButtonEl.toggleClass('disabled', true);
                const config = {
                    type: refresh ? 'POST' : 'GET',
                    url: refresh ? refreshUrl : url,
                    success: function (response) {
                        btn.toggleClass('disabled', false);
                        if (response) {
                            if (response.items) {
                                commonCache.set(cacheKey, response.items);
                                updateButton(response.items);
                                renderEmails();
                            }
                            if (response.enableImapFinalRecipientWarning) {
                                modalEl.find('.imap-final-recipient-warning').html(
                                    '<div class="alert alert-warning">Aus technischen Gründen konnte keine Suche nach Nachrichten, die über nicht zugestellte E-Mails berichten, durchgeführt werden.</div>');
                            }
                            if (response.enableImapFullTextSearchError) {
                                modalEl.find('.imap-final-recipient-error').html(
                                    '<div class="alert alert-danger">Aus technischen Gründen kann nicht nach zugehörigen E-Mails gesucht werden.</div>');
                            }
                        }
                    },
                    complete: function () {
                        refreshButtonEl.toggleClass('disabled', false);
                        btn.toggleClass('disabled', false);
                        btn.toggleClass('loading-text', false);
                    }
                };
                if (refresh) {
                    config.data = {
                        _token: refreshToken
                    };
                }
                jQuery.ajax(config);
            };
        loadMails(false);

        refreshButtonEl.on('click', function () {
            if (refreshButtonEl.hasClass('disabled')) {
                return;
            }
            refreshButtonEl.toggleClass('disabled', true);
            tbodyEl.toggleClass('loading-text', true);
            loadMails(true);
        });

        btn.on('click', function () {
            renderEmails();
            modalEl.modal('show');
        });
    });
    
    /**
     * USER/NEWSLETTER Attachment
     */
    const dropzoneEl = $('#dropzone-user-attachment');
    if (dropzoneEl && dropzoneEl.length) {
        let speedEl = $('#dropzone-user-attachment-speed'),
            attachmentTbodyEl = $('#dropzone-user-attachment table tbody'),
            attachmentTfootEl = $('#dropzone-user-attachment table tfoot'),
            loadAttachmentsBtn = $('#dropzone-user-attachment-reload'),
            formEl = dropzoneEl.find('input[type=checkbox]'),
            formElsChecked = dropzoneEl.find('input[type=checkbox]:checked'),
            formFieldName = formEl && formEl.length ? formEl.attr('name') : null;
        let checkedAttachmentIds = [];

        formElsChecked.each(function () {
            checkedAttachmentIds.push(parseInt(this.value));
        });
        attachmentTfootEl.html(''); //clear form fields

        const renderAttachments = function (attachments) {
                let tbodyHtml = '';
                if (attachments) {
                    $.each(attachments, function (index, attachment) {
                        tbodyHtml += '<tr>';
                        tbodyHtml += '<td class="checkbox">';

                        if (formFieldName) {
                            tbodyHtml += '<label for="user-attachment-form-input-' + attachment.id + '">';
                            tbodyHtml += '<input type="checkbox" id="user-attachment-form-input-' + attachment.id + '" name="' + formFieldName + '" value="' + attachment.id + '" /> ';
                        }
                        tbodyHtml += eHtml(attachment.filename);
                        if (formFieldName) {
                            tbodyHtml += '</label>';
                        }
                        tbodyHtml += '</td>';
                        tbodyHtml += '<td class="text-right">' + filesize(attachment.filesize) + '</td>';
                        tbodyHtml += '<td>';

                        tbodyHtml += '<a href="' + attachment.download + '" target="_blank" title="Datei herunterladen" class="btn btn-xs btn-default">' + '<span class="glyphicon glyphicon-download" aria-hidden="true"></span>' +
                            '</a> ';

                        if (attachment.delete) {
                            tbodyHtml += '<span data-url="' + attachment.delete + '" data-token="' + attachment.delete_token + '" title="Datei löschen" class="attachment-delete btn btn-xs btn-default">' + '<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>' +
                                '</span> ';

                        }

                        tbodyHtml += '</td>';
                        tbodyHtml += '</tr>';
                    });
                }
                attachmentTbodyEl.html(tbodyHtml);

                attachmentTbodyEl.find('input[type=checkbox]').each(function () {
                    if (checkedAttachmentIds.includes(parseInt(this.value))) {
                        $(this).prop('checked', true);
                    }
                });
                attachmentTbodyEl.find('input[type=checkbox]').change(function () {
                    const el = $(this),
                        attachmentId = parseInt(this.value),
                        newValue = el.prop('checked');

                    checkedAttachmentIds = jQuery.grep(checkedAttachmentIds, function (value) {
                        return value !== attachmentId;
                    });
                    if (newValue) {
                        checkedAttachmentIds.push(attachmentId);
                    }
                });

                if (!attachments || attachments.length === 0) {
                    tbodyHtml = '<tr><td colspan="3" class="text-center">Keine Dateianhänge hochgeladen.</td></tr>';
                }

                attachmentTbodyEl.find('.attachment-delete').click(function () {
                    const el = $(this);
                    if (el.hasClass('disabled')) {
                        return;
                    }
                    el.toggleClass('disabled', true);
                    $.post({
                        url: el.data('url'),
                        data: {
                            token: el.data('token'),
                        },
                        success: function (response) {
                            if (response && response.attachments) {
                                renderAttachments(response.attachments);
                            }
                        },
                        error: function () {
                            $(document).trigger('add-alerts', {
                                message: 'Die Datei konnte nicht gelöscht werden',
                                priority: 'error'
                            });
                        }
                    });
                });
            },
            loadAttachments = function () {
                loadAttachmentsBtn.toggleClass('disabled', true);
                attachmentTbodyEl.toggleClass('loading-text', true);
                $.ajax({
                    url: dropzoneEl.data('attachment-list-url'),
                    success: function (response) {
                        if (response && response.attachments) {
                            renderAttachments(response.attachments);
                        }
                        attachmentTbodyEl.toggleClass('loading-text', false);
                        loadAttachmentsBtn.toggleClass('disabled', false);
                    },
                    error: function () {
                        attachmentTbodyEl.html(
                            '<tr><td colspan="3" class="text-center">Die Dateianhänge konnten nicht geladen werden.</td></tr>'
                        );
                        $(document).trigger('add-alerts', {
                            message: 'Die Dateianhänge konnten nicht geladen werden',
                            priority: 'error'
                        });
                        attachmentTbodyEl.toggleClass('loading-text', false);
                        loadAttachmentsBtn.toggleClass('disabled', false);
                    }
                });
            };
        loadAttachmentsBtn.click(function () {
            if (!loadAttachmentsBtn.hasClass('disabled')) {
                loadAttachments();
            }
        });
        loadAttachments();

        dropzoneEl.filedrop({
            url: dropzoneEl.data('attachment-upload-url'),
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
                            message: 'Der Browser unterstützt leider das Hochladen von Dateien via HTML5 nicht',
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
            queuefiles: 4,
            maxfiles: 10,
            maxfilesize: 10,
            uploadFinished: function (i, file, response, time) {
                attachmentTfootEl.find('#file-attachment-progress-' + i).remove();
                if (response.attachments) {
                    renderAttachments(response.attachments);
                }
                if (response.errors) {
                    $.each(response.errors, function (index, error) {
                        $(document).trigger('add-alerts', {
                            message: error,
                            priority: 'error'
                        });
                    });
                }
            },
            uploadStarted: function (i, file, len) {
                attachmentTfootEl.append(
                    '<tr id="file-attachment-progress-' + i + '">' +
                    ' <td></td>' +
                    ' <td>' +
                    '  <div class="row">' +
                    '   <div class="col-xs-12">' +
                    '    ' + eHtml(file.name) + ' <i>wird hochgeladen...</i>' +
                    '   </div>' +
                    '   <div class="col-xs-12">' +
                    '    <div class="progress">' +
                    '     <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;">' +
                    '      0%' +
                    '     </div>' +
                    '    </div>' +
                    '   </div>' +
                    ' </div>' +
                    ' </td>' +
                    ' <td>' + filesize(file.size) + '</td>' +
                    '</tr>'
                );
            },
            progressUpdated: function (i, file, progress) {
                var barEl = attachmentTfootEl.find('#file-attachment-progress-' + i + ' .progress-bar');
                if (barEl.data('aria-valuenow') != progress) {
                    barEl.data('aria-valuenow', progress);
                    barEl.css('width', progress + '%');
                    barEl.text(progress + '%');
                }
            },
            speedUpdated: function (i, file, speed) {
                speedEl.text('Dateien werden mit ' + speed.toFixed(1) + ' kb/s hochgeladen...');
            },
            afterAll: function () {
                speedEl.text('');
            }
        });
    }
});
