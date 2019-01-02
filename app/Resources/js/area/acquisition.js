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


    $('#dialogModalRelateParticipant').on('show.bs.modal', function (event) {
        var buttonEl = $(event.relatedTarget),
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
        modalEl.find('#participation_assign_related_participant_oid').val(buttonEl.data('oid'));
        modalEl.find('#participation_assign_related_participant_related').val(buttonEl.data('aid'));

        tbodyEl.html('<tr><td colspan="4" class="text-center loading">(Wird geladen)</td></tr>');

        $.get({
            url: '../participant_proposal/' + buttonEl.data('oid'),
            success: function (response) {
                if (response && response.rows && response.rows.length) {
                    var tableBody = '';
                    jQuery.each(response.rows, function (key, row) {

                        tableBody += '<tr>' +
                            '<td>' + row.firstName + '</td>' +
                            '<td>' + row.lastName + '</td>' +
                            '<td>' + row.age + '</td>' +
                            '<td>' + row.status + '</td>' +
                            '<td class="text-right"><div data-aid="' + row.aid + '" class="btn btn-primary btn-xs"><span class="glyphicon glyphicon-link" aria-hidden="true"></span> verknüpfen</div></td>' +
                            '</tr>';
                    });

                    tbodyEl.html(tableBody);
                    $('#dialogModalRelateParticipant table .btn').on('click', function (e) {
                        console.log(e);
                        e.preventDefault();
                        var aidButtonEl = $(this);
                        modalEl.find('#participation_assign_related_participant_related').val(aidButtonEl.data('aid'));
                        $('#dialogModalRelateParticipant form').submit();
                    });
                } else {
                    tbodyEl.html('<td colspan="5" class="text-center">(Keine passenden Teilnehmer gefunden)</td>');
                }

            },
            error: function () {
                tbodyEl.html('<td colspan="5" class="text-center">(Fehler beim laden der Vorschläge)</td>');
            },
        });
    })

});
