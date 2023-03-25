$(function () {
    var g = {};
    $(g).bind('prototype-element.added', function (event, data) {
        addElementHandlers(data.element);
        updateFieldProposals();
    });

    /**
     * EVENT: Custom field choice options with description text
     */
    $('.custom-field input[type=\'checkbox\'][data-description]').each(function () {
        let el = $(this),
            elDiv = el.parent().parent(),
            description = eHtml(el.data('description')),
            buttonHtml = ' <a tabindex="0" role="button" class="btn btn-default btn-xs btn-round" data-custom-option="checkbox-popover">' +
                '<span class="glyphicon glyphicon-question-sign"></span></a>';

        elDiv.append(buttonHtml);
        let elButton = elDiv.find('a[data-custom-option=\'checkbox-popover\']');
        elButton.popover({
            content: description,
            trigger: 'focus'
        });
    });

    var addElementHandlers = function (element) {
        if (!element) {
            return;
        }
        element.find('[data-toggle="popover"]').popover({
            container: 'body',
            placement: 'top',
            html: true,
            trigger: 'focus'
            /*
             }).click(function (e) {
             e.preventDefault();
             $(this).popover('toggle');
             */
        });
    };

    /**
     * Add proposals for custom field values of autocomplete fields
     */
    var provideProposals = ($('[data-provide-proposals]').length > 0),
        proposals = [],
        updateFieldProposals = function () {
            $.each(proposals, function (field, fieldProposals) {
                $('[data-typeahead-source="' + field + '"]').each(function () {
                    var el = $(this);
                    el.attr('autocomplete', 'custom');
                    el.typeahead({source: fieldProposals});
                });
            })
        };

    if (provideProposals) {
        var eid = $('[data-provide-proposals]').data('provide-proposals');
        $.ajax({
            url: '/admin/event/' + eid + '/typeahead/proposals.json',
            type: 'GET',
            success: function (result) {
                if (!result || !result.proposals) {
                    return;
                }
                proposals = result.proposals;
                updateFieldProposals();
            },
            error: function () {
                $(document).trigger('add-alerts', {
                    message: 'Die Vorschläge für die Autovervollständigung konnten nicht geladen werden',
                    priority: 'error'
                });
            }
        });

    }

    /**
     * EVENT: Handle via prototype injected forms
     */
    $('.prototype-container').each(function (index) {
        var element = $(this),
            prototype,
            elementMessage;
        if (element.attr('data-prototype')) {
            prototype = element.data('prototype');
            elementMessage = element.find('.prototype-missing-message');

            element.on('click', function (e) {
                var elementTarget = $(e.target);
                if (elementTarget.parent().is('.prototype-remove')) {
                    elementTarget = elementTarget.parent();
                }
                if (elementTarget.is('.prototype-remove')) {
                    e.preventDefault();
                    var formElement = elementTarget.parent().parent().parent().parent(),
                        formGroup = formElement.closest('.prototype-container'),
                        formElementCount = formGroup.find('.prototype-element').length;

                    formElement.remove();
                    if (formElement && elementMessage && !elementMessage.is(':visible') && formElementCount < 2) {
                        elementMessage.show(300);
                    }
                }
            });

            element.data('index', element.find('.prototype-element').length);
            element.find('.prototype-add').on('click', function (e) {
                e.preventDefault();
                var index = element.data('index');
                var newForm = prototype.replace(/__name__/g, index);
                element.data('index', index + 1);

                if (elementMessage && elementMessage.is(':visible')) {
                    elementMessage.hide(300);
                }

                element.find('.prototype-elements').append(newForm);

                $(g).trigger('prototype-element.added', {'index': index, 'element': element});
            });
        }
    });
})
;
