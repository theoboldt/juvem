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
        formatCurrencyNumber = function(value) {
            if (value === null) {
                return '';
            }
            return jQuery.number( value, 2, ',', '.') + '&nbsp;€ ';
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
        },
        displayToPayInfo = function (data) {
            var rawRow,
                rawRows = '',
                createInfoRow = function (toPayValue, priceValue, participantAid, participantName) {
                    var cellValue,
                        participantPriceHtml = '';
                    if (toPayValue === null) {
                        cellValue = '<i title="Kein Preis festgelegt">keiner</i>';
                        participantPriceHtml = '<i>(keiner festgelegt)</i>';
                    } else {
                        cellValue = formatCurrencyNumber(toPayValue);
                        participantPriceHtml = formatCurrencyNumber(priceValue);

                        if (toPayValue > 0) {
                            participantPriceHtml += '(noch zu zahlen: ' + formatCurrencyNumber(toPayValue);
                        } else if (toPayValue < 0) {
                            participantPriceHtml += '(zu viel bezahlt: ' + formatCurrencyNumber(toPayValue * -1);
                        } else {
                            participantPriceHtml += '(bezahlt)';
                        }
                    }
                    $('#participant-price-' + participantAid.toString()).html(participantPriceHtml);

                    return '<tr>' +
                        '    <td class="value text-right">' + cellValue + '</td>' +
                        '    <td class="participant">' + participantName + '</td>' +
                        '</tr>';
                };
            jQuery.each(data, function (key, rowData) {
                rawRow = createInfoRow(
                    rowData.to_pay_value_raw,
                    rowData.price_value_raw,
                    parseInt(rowData.participant_aid),
                    eHtml(rowData.participant_name)
                );
                rawRows += rawRow;
            });
            toPayTableEl.html(rawRows);
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
                    '    <td class="value">' + formatCurrencyNumber(value) + '/td>' +
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
                btn.css('display', 'none');
            } else {
                valueText = formatCurrencyNumber(value);
                btn.data('value', value);
                btn.html('<b>' + valueText + '</b> Komplett');
                btn.css('display', 'block');
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
                        case 'AppBundle\\Manager\\Payment\\PriceSummand\\FilloutChoiceSummand':
                            symbolTitle = 'Formel durch Auswahl';
                            description = 'Feld <i>' + eHtml(rowData.attribute_name) + '</i>,<br> Option ' + '<i>' + eHtml(rowData.choice_name) + '</i>';
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
                    '    <td class="value">' + formatCurrencyNumber(price_tag_sum)+'</td>' +
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

});
