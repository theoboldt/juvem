$(function () {

    /**
     * PARTICIPATION: Participation details
     */
    var paymentManagementModal = $('#dialogPriceConfiguration'),
        toPayTableEl = $('#dialogPriceConfiguration #payment tbody'),
        toPayFooterTableEl = $('#dialogPriceConfiguration #payment tfoot'),
        priceHistoryTableEl = $('#dialogPriceConfiguration #priceHistory tbody'),
        displayToPayInfo = function (data) {
            var rawRow,
                rawRows = '',
                createInfoRow = function (value, participantName) {
                    return '<tr>' +
                        '    <td class="value text-right">' + value + ' €</td>' +
                        '    <td class="participant">' + participantName + '</td>' +
                        '</tr>';
                };
            jQuery.each(data, function (key, rowData) {
                rawRow = createInfoRow(
                    rowData.value,
                    eHtml(rowData.participant_name)
                );
                rawRows += rawRow;
            });
            toPayTableEl.html(rawRows);
        },
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
                    '    <td class="small"><span class="created">' + date + '</span>, <a class="creator" href="/admin/user/' + creatorId + '">' + creatorName + '</a></td>' +
                    '</tr>';
            };

            var rawRows = '';
            if (data && data.length > 1) {
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
            var btn = $('.btn-payment-full');

            btn.data('value', value);
            btn.html('<b>' + value + ' €</b> Komplett');

            if (multiple) {
                toPayFooterTableEl.html(
                    '<tr>' +
                    '    <td class="value text-right"><b>' + value + ' €</b></td>' +
                    '    <td class="participant"><b>Summe</b> (für alle Teilnehmer)</td>' +
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
            success: function (data) {
                if (data.payment_history) {
                    displayPriceHistory(data.payment_history);
                }
                if (data.to_pay) {
                    displayToPayInfo(data.to_pay);
                }
                if (data.to_pay_all) {
                    displayPaymentFullValue(data.to_pay_all, (aids.toString().split(';').length > 1));
                }
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
                    if (result.payment_history) {
                        displayPriceHistory(result.payment_history);
                    }
                    if (result.to_pay) {
                        displayToPayInfo(result.to_pay);
                    }
                    if (result.to_pay_all) {
                        displayPaymentFullValue(result.to_pay_all, (aids.toString().split(';').length > 1));
                    }
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