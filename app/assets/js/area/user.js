$(function () {

    /**
     * USER Attachment
     */
    const dropzoneEl = $('#dropzone-user-attachment');
    if (dropzoneEl) {
        const speedEl = $('#dropzone-user-attachment-speed'),
            attachmentTbodyEl = $('#dropzone-user-attachment table tbody'),
            attachmentTfootEl = $('#dropzone-user-attachment table tfoot'),
            loadAttachmentsBtn = $('#dropzone-user-attachment-reload'),
            renderAttachments = function (attachments) {
                let tbodyHtml = '';
                if (attachments) {
                    $.each(attachments, function (index, attachment) {
                        tbodyHtml += '<tr>';
                        tbodyHtml += '<td></td>';
                        tbodyHtml += '<td>' + eHtml(attachment.filename) + '</td>';
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
                if (!attachments || attachments.length === 0) {
                        tbodyHtml = '<tr><td colspan="4" class="text-center">Keine Dateianhänge hochgeladen.</td></tr>';
                }
                
                attachmentTbodyEl.html(tbodyHtml);
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
                            '<tr><td colspan="4" class="text-center">Die Dateianhänge konnten nicht geladen werden.</td></tr>'
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
