$(function () {
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
                    element.append(' <button type="button" class="btn btn-default btn-xs btn-round">' +
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

            var addElementHandlers = function () {
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

                ensureLactoseFreeAnnotations();
            });
            addElementHandlers();
        }
    });
});
