$(function () {
    var g = {};
    $(g).bind('prototype-element.added', function (event, data) {
        ensureLactoseFreeAnnotations();
        addElementHandlers(data.element);
        updateFieldProposals();
    });

    /**
     * EVENT: Participate lactose-free food option force hint
     */
    var ensureLactoseFreeAnnotations = function () {
        var lactoseFreeOption,
            foodOptionFields = $('.food-options').first();

        if (foodOptionFields.length > 0) {
            lactoseFreeOption = foodOptionFields.data('food-lactose-option');

            $('.food-options div').each(function () {
                var popover,
                    button,
                    element = $(this),
                    input = element.find('input');

                if (input.val() == lactoseFreeOption && !element.find('button').length) {
                    element.append(' <button type="button" class="btn btn-default btn-xs btn-round" tabindex="-1">' +
                        '<span class="glyphicon glyphicon-question-sign"></span>' +
                        '</button>');
                    button = element.find('button');

                    popover = button.popover({
                        content: 'Bitte geben Sie im Feld für <i>Medizinische Hinweise</i> detailierte Informationen zur Ausprägung der Unverträglichkeit an. Müssen konsequent laktosefreie Produkte verwendet werden (bspw. auch bei Schokoriegeln) oder ist es ausreichend auf laktosearme Produkte zu achten? Verwendet der Teilnehmer Laktase-Tabletten?',
                        html: true,
                        trigger: 'manual'
                    });

                    element.find('button').on('click', function (e) {
                        e.preventDefault();
                        popover.popover('show');
                        return false;
                    });

                    popover.on('shown.bs.popover', function () {
                        $('body').one('click', function () {
                            if (button.next('div.popover:visible').length) {
                                popover.popover('hide');
                            }
                        });
                    });

                    element.find('label input').on('click', function (e) {
                        if ($(this).prop('checked') && !button.next('div.popover:visible').length) {
                            popover.popover('show');
                        }
                    });
                }
            });
        }
    };
    ensureLactoseFreeAnnotations();

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
     * Add proposals for fillouts of autocomplete fields
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
