$(function () {
    var elementType = '*#acquisition_fieldType',
        updateType;

    /**
     * ACQUISITION: When selection is changed
     */
    updateType = function () {
        var el = $(elementType),
            val = el ? el.val() : null;
        if (el
            && (val === "Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType"
                || val === "AppBundle\\Form\\GroupType")
        ) {
            $('.form-group-choice').css('display', 'block');
        } else {
            $('.form-group-choice').css('display', 'none');
        }

        var elMultiple = $('.form-group-choice-multiple');
        if (el && val === "AppBundle\\Form\\GroupType") {
            elMultiple.css('display', 'none');
            elMultiple.find('select').val(0);
        } else {
            elMultiple.css('display', 'block');
        }

    };
    updateType();
    $(elementType).change(updateType);

    /**
     * ACQUISITION: Price formula enable/disable
     */
    var isPriceFormulaEnabledEl = $('#acquisition_isPriceFormulaEnabled'),
        updateFormulaInputDisplay = function () {
            var checked = isPriceFormulaEnabledEl.prop('checked');
            if (checked) {
                $('.form-input-formula').css('display', 'block');
            } else {
                $('.form-input-formula').css('display', 'none');
            }
        };
    updateFormulaInputDisplay();
    isPriceFormulaEnabledEl.change(updateFormulaInputDisplay);
    $('.form-acquisition-option-add').on('click', function () {
        console.log(1);
        updateFormulaInputDisplay();
    });

    $('#dialogModalRelateParticipant').on('show.bs.modal', function (event) {
        let buttonEl = $(event.relatedTarget),
            modalEl = $(this),
            title = buttonEl.data('title'),
            description = buttonEl.data('description'),
            firstName = buttonEl.data('first-name'),
            lastName = buttonEl.data('last-name'),
            tbodyEl = modalEl.find('table tbody');

        modalEl.find('.modal-title span').text(title);
        modalEl.find('.modal-body p.description').text(description);
        modalEl.find('.modal-body .first-name').text(firstName);
        modalEl.find('.modal-body .last-name').text(lastName);
        modalEl.find('#participation_assign_related_participant_bid').val(buttonEl.data('bid'));
        modalEl.find('#participation_assign_related_participant_entityClass').val(buttonEl.data('entity-class'));
        modalEl.find('#participation_assign_related_participant_entityId').val(buttonEl.data('entity-id'));
        modalEl.find('#participation_assign_related_participant_related').val(buttonEl.data('related-aid'));
        
        tbodyEl.html('<tr><td colspan="5" class="text-center loading">(Wird geladen)</td></tr>');

        $.ajax({
            type: 'POST',
            url: '../participant_proposals',
            data: {
                bid: buttonEl.data('bid'),
                entityClass: buttonEl.data('entity-class'),
                entityId: buttonEl.data('entity-id'),
            },
            success: function (response) {
                if (response && !response.success && response.message) {
                    tbodyEl.html('<td colspan="5" class="text-center">'+response.message+'</td>');
                } else if (response && response.rows && response.rows.length) {
                    var tableBody = '';
                    jQuery.each(response.rows, function (key, row) {
                        tableBody += '<tr>' +
                            '<td>' + row.firstName + '</td>' +
                            '<td>' + row.lastName + '</td>' +
                            '<td>' + row.age + '</td>' +
                            '<td>' + row.status + '</td>' +
                            '<td class="text-right">' +
                            ' <div data-aid="' + row.aid + '" class="btn btn-' + (row.selected ? 'default' : 'primary') + ' btn-xs ' + (row.selected && !row.system ? 'disabled' : '') + '">' +
                            '  <span class="glyphicon glyphicon-link" aria-hidden="true"></span> ' +
                            (row.system ? '<span class="glyphicon glyphicon-flash" "="" aria-hidden="true" title="automatisch"></span>' : '') +
                            (row.selected ? 'verknüpft' : 'verknüpfen') +
                            ' </div>' +
                            '</td>' +
                            '</tr>';
                    });

                    tbodyEl.html(tableBody);
                    $('#dialogModalRelateParticipant table .btn').on('click', function (e) {
                        console.log(e);
                        e.preventDefault();
                        var aidButtonEl = $(this);
                        if (aidButtonEl.hasClass('disabled')) {
                            return;
                        }
                        modalEl.find('#participation_assign_related_participant_related').val(aidButtonEl.data('aid'));
                        $('#dialogModalRelateParticipant form').submit();
                    });
                } else {
                    tbodyEl.html('<td colspan="5" class="text-center">(Keine passenden Teilnehmer:innen gefunden)</td>');
                }

            },
            error: function () {
                tbodyEl.html('<td colspan="5" class="text-center">(Fehler beim Laden der Vorschläge)</td>');
            },
        });
    });


    /**
     * ACQUISITION: Formula editor
     */
    var inputFormulaEl = $('.input-formula');
    $('.btn-formula-variable').on('click', function () {
        var cursorPos = inputFormulaEl.prop('selectionStart'),
            v = inputFormulaEl.val(),
            textBefore = v.substring(0, cursorPos),
            textAfter = v.substring(cursorPos, v.length);

        inputFormulaEl.val(textBefore + $(this).data('variable') + textAfter);
    });
});
