$(function(){

    /**
     * ACQUISITION: When selection is changed
     */
    $('*#acquisition_fieldType').change(function () {
        if ($(this).val() == "Symfony\\Component\\Form\\Extension\\Core\\Type\\ChoiceType") {
            $('.form-group-choice').css('display', 'block');
        } else {
            $('.form-group-choice').css('display', 'none');
        }
    });

    /**
     * ACQUISITION: When selection options change
     */
    var updateChoiceOptions = function () {
        var choices = $(this).val().split(';'),
            list = $('*#form-choice-option-list'),
            choicesHtml = '';

        $.each(choices, function (index, value) {
            choicesHtml += '<span class="label label-primary">' + eHtml(value) + '</span> ';
        });

        list.empty();
        list.html(choicesHtml);
    };
    $('*#acquisition_fieldTypeChoiceOptions').change(updateChoiceOptions);

});