$(function () {
    var elementType = '*#acquisition_fieldType',
        elementChoices = '*#acquisition_fieldTypeChoiceOptions',
        updateType,
        updateChoiceOptions;

    /**
     * ACQUISITION: When selection is changed
     */
    updateType = function () {
        if ($(elementType) && $(elementType).val() == "Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType") {
            $('.form-group-choice').css('display', 'block');
        } else {
            $('.form-group-choice').css('display', 'none');
        }
    };
    updateType();
    $(elementType).change(updateType);

    /**
     * ACQUISITION: When selection options change
     */
    updateChoiceOptions = function () {
        if ($(elementChoices).val()) {
            var choices = $(elementChoices).val().split(';'),
                list = $('*#form-choice-option-list'),
                choicesHtml = '';

            $.each(choices, function (index, value) {
                choicesHtml += '<span class="label label-primary">' + eHtml(value) + '</span> ';
            });

            list.empty();
            list.html(choicesHtml);
        }
    };
    $(elementChoices).change(updateChoiceOptions);
    updateChoiceOptions();

});