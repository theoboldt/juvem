$(function () {

    /**
     * PARTICIPATION: Participation details
     */
    var paymentManagementModal = $('#dialogPriceConfiguration'),
        toPayTableEl = $('#dialogPriceConfiguration #payment tbody'),
        toPayFooterTableEl = $('#dialogPriceConfiguration #payment tfoot'),
        priceTagTableEl = $('#dialogPriceConfiguration #price tbody'),
        priceTagFooterTableEl = $('#dialogPriceConfiguration #price tfoot'),
        priceHistoryTableEl = $('#dialogPriceConfiguration #priceHistory tbody'),
        invoiceTableEl = $('#dialogPriceConfiguration #invoiceList tbody'),
        formatCurrencyNumber = function (value) {
            if (value === null) {
                return '';
            }
            return jQuery.number(value, 2, ',', '.') + '&nbsp;€ ';
        },
        handlePaymentControllerResponse = function (result, aids) {
            var multiple = (aids.toString().split(';').length > 1);
            if (result.payment_history) {
                displayPriceHistory(result.payment_history);
            }
            if (result.to_pay_list) {
                displayToPayInfo(result.to_pay_list);
            }
            if (result.to_pay_sum) {
                displayPaymentFullValue(result.to_pay_sum, multiple);
            }
            if (result.price_tag_list) {
                displayPriceTag(result.price_tag_list, multiple, result.price_tag_sum);
            }
            if (result.invoice_list) {
                displayInvoiceList(result.invoice_list);
            }
        },
        displayToPayInfo = function (data) {
            var rawRow,
                rawRows = '',
                createInfoRow = function (toPayValue, participantPriceHtml, participantAid, participantName) {
                    var cellValue;
                    if (toPayValue === null) {
                        cellValue = '<i title="Kein Preis festgelegt">keiner</i>';
                    } else {
                        cellValue = formatCurrencyNumber(toPayValue);
                    }
                    $('#participant-price-' + participantAid.toString()).html(participantPriceHtml);

                    return '<tr>' +
                        '    <td class="value text-right">' + cellValue + '</td>' +
                        '    <td class="participant">' + participantName + '</td>' +
                        '</tr>';
                };
            jQuery.each(data, function (key, rowData) {
                rawRow = createInfoRow(
                    rowData.to_pay_value,
                    rowData.price_html,
                    parseInt(rowData.participant_aid),
                    eHtml(rowData.participant_name)
                );
                rawRows += rawRow;
            });
            toPayTableEl.html(rawRows);
        },
        displayInvoiceList = function (data) {
            var rowsHtml = '',
                rowHtml;
            if (data && data.length) {
                jQuery.each(data, function (key, rowData) {
                    var creatorHtml,
                        downloadBtnHtml = '';
                    if (rowData.created_by && rowData.created_by.id) {
                        creatorHtml = '<a class="creator" href="/admin/user/' + rowData.created_by.id + '">' + rowData.created_by.fullname + '</a>';
                    } else {
                        creatorHtml = rowData.created_by.fullname;
                    }
                    if (rowData.download_url) {
                        downloadBtnHtml = '<a href="' + rowData.download_url + '" target="_blank" class="btn btn-default btn-xs btn-download"><span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> Download</a>';
                    }

                    rowHtml = '<tr>' +
                        '    <td class="symbol" title="Rechnung">' +
                        '       <span class="glyphicon glyphicon-page" aria-hidden="true"></span>' +
                        '   </td>' +
                        '    <td class="value">' + formatCurrencyNumber((rowData.sum / 100)) + '</td>' +
                        '    <td>' + rowData.invoice_number + '</td>' +
                        '    <td class="small"><span class="created">' + rowData.created_at + '</span>, ' + creatorHtml + '</td>' +
                        '    <td>' + downloadBtnHtml + '</td>' +
                        '</tr>';

                    rowsHtml += rowHtml;
                });
            } else {
                rowsHtml = '<td colspan="5" class="text-center">(Keine Rechnungen vorhanden)</td>';
            }
            invoiceTableEl.html(rowsHtml);
            attachDownloadBtnListener();
        },
        displayPriceHistory = function (data) {
            var createPriceRow = function (type, value, description, date, creatorId, creatorName, participant) {
                var glyph,
                    symbolTitle,
                    creatorHtml;

                switch (type) {
                    case 'price_payment':
                        glyph = 'log-in';
                        symbolTitle = 'Zahlung erfasst';
                        break;
                    case 'price_set':
                        glyph = 'pencil';
                        symbolTitle = 'Grundpreis festgelegt';
                        break;
                }

                if (creatorId) {
                    creatorHtml = '<a class="creator" href="/admin/user/' + creatorId + '">' + creatorName + '</a>';
                } else {
                    creatorHtml = creatorName;
                }

                return '<tr class="' + type + '">' +
                    '    <td class="symbol" title="' + symbolTitle + '">' +
                    '       <span class="glyphicon glyphicon-' + glyph + '" aria-hidden="true"></span>' +
                    '   </td>' +
                    '    <td class="participant">' + participant + '</td>' +
                    '    <td class="value">' + formatCurrencyNumber(value) + '</td>' +
                    '    <td class="description">' + description + '</td>' +
                    '    <td class="small"><span class="created">' + date + '</span>, ' + creatorHtml + '</td>' +
                    '</tr>';
            };

            var rawRows = '';
            if (data && data.length) {
                var rawRow;
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
            } else {
                rawRows = '<td colspan="5" class="text-center">(Kein Vorgang erfasst)</td>';
            }
            priceHistoryTableEl.html(rawRows);
        },
        displayPaymentFullValue = function (value, multiple) {
            var btn = $('.btn-payment-full'),
                valueText;

            if (value === null) {
                valueText = '<i title="Kein Preis festgelegt">keiner</i>';
                btn.css('visibility', 'hidden');
            } else {
                valueText = formatCurrencyNumber(value);
                btn.data('value', value);
                btn.html('<b>' + valueText + '</b> Komplett');
                btn.css('visibility', 'visible');
            }

            toPayFooterTableEl.html(
                '<tr class="total">' +
                '    <td class="value text-right"><b>' + valueText + '</b></td>' +
                '    <td class="participant">' +
                '       <b>Summe</b>' + (multiple ? ' (für alle Teilnehmer)' : '') +
                '   </td>' +
                '</tr>'
            );
        },
        displayPriceTag = function (rows, multiple, price_tag_sum) {
            if (rows.length) {
                var htmlRows = [];
                jQuery.each(rows, function (key, rowData) {
                    var description = '',
                        symbolTitle = '?',
                        glyphParticipant,
                        glyphParticipantTitle,
                        glyph = 'check';

                    switch (rowData.type) {
                        case 'AppBundle\\Manager\\Payment\\PriceSummand\\BasePriceSummand':
                            glyph = 'tag';
                            symbolTitle = 'Grundpreis';
                            description = 'Grundpreis';
                            break;
                        case 'AppBundle\\Manager\\Payment\\PriceSummand\\FilloutSummand':
                            glyph = 'edit';
                            symbolTitle = 'Formel mit Eingabewert';
                            description = 'Feld <i>' + eHtml(rowData.attribute_name) + '</i>';
                            break;
                    }
                    if (rowData.is_participation_summand) {
                        glyphParticipant = 'file';
                        glyphParticipantTitle = 'Summand durch Anmeldung';
                    } else {
                        glyphParticipant = 'user';
                        glyphParticipantTitle = 'Summand durch Teilnehmer';
                    }

                    htmlRows.push(
                        '<tr>' +
                        '    <td class="symbol" title="' + symbolTitle + '">' +
                        '       <span class="glyphicon glyphicon-' + glyph + '" aria-hidden="true"></span>' +
                        '   </td>' +
                        '    <td class="participant">' + rowData.participant_name + '</td>' +
                        '    <td class="value">' + formatCurrencyNumber(rowData.value) + '</td>' +
                        '    <td>' +
                        '<span class="glyphicon glyphicon-' + glyphParticipant + '" aria-hidden="true" title="' + glyphParticipantTitle + '"></span> ' +
                        '</td>',
                        '    <td class="description">' + description + '</td>' +
                        '</tr>'
                    );
                });
                priceTagTableEl.html(htmlRows.join(''));
                priceTagFooterTableEl.html(
                    '<tr class="total">' +
                    '    <td class="symbol" title="Gesamtpreis">' +
                    '   </td>' +
                    '    <td class="participant"></td>' +
                    '    <td class="value">' + formatCurrencyNumber(price_tag_sum) + '</td>' +
                    '    <td></td>' +
                    '    <td class="description">' +
                    '       <b>Summe</b>' + (multiple ? ' (für alle Teilnehmer)' : '') +
                    '</td>' +
                    '</tr>'
                );
            } else {
                priceTagTableEl.html(
                    '<tr>' +
                    '<td colspan="5" class="text-center">(Keine Preisinformationen erfasst)</td>' +
                    '</tr>'
                );
            }
        };
    $('#dialogPriceConfiguration').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget),
            aids = button.data('aids'),
            modal = $(this),
            inputEls = modal.find('input');
        paymentManagementModal.toggleClass('loading', true);
        toPayFooterTableEl.html('');
        modal.find('.modal-title span').text(button.data('title'));
        modal.data('aids', aids);

        $.each(inputEls, function (i, el) {
            $(el).val('');
        });


        $.ajax({
            url: '/admin/event/participant/price/history',
            data: {
                aids: aids
            },
            success: function (result) {
                handlePaymentControllerResponse(result, aids);
            },
            error: function () {
                $(document).trigger('add-alerts', {
                    message: 'Preishistorie konnte nicht geladen werden',
                    priority: 'error'
                });
            },
            complete: function () {
                paymentManagementModal.toggleClass('loading', false);
            }
        });
    });
    $('#dialogPriceConfiguration #payment .btn-predefined').on('click', function (e) {
        e.preventDefault();
        var button = $(this);

        if (button.data('value')) {
            $('#paymentValue').val(button.data('value'));
        }
        if (button.data('description')) {
            $('#paymentDescription').val(button.data('description'));
        }
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
        paymentManagementModal.toggleClass('loading', true);

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
                if (result) {
                    handlePaymentControllerResponse(result, aids);
                    $('#priceHistoryLink').tab('show');
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
                paymentManagementModal.toggleClass('loading', false);
                button.toggleClass('disabled', false);
            }
        });

    });

    $('#createInvoiceBtn').on('click', function (e) {
        e.preventDefault();
        var button = $(this);
        button.toggleClass('disabled', true);

        $.ajax({
            url: '/admin/event/participation/invoice/create',
            data: {
                _token: button.data('token'),
                pid: button.data('pid')
            },
            success: function (result) {
                if (result && result.invoice_list) {
                    displayInvoiceList(result.invoice_list);
                }
            },
            error: function () {
                $(document).trigger('add-alerts', {
                    message: 'Die Rechnung konnte nicht erstellt werden',
                    priority: 'error'
                });
            },
            complete: function () {
                button.toggleClass('disabled', false);
            }
        });
    });

});
