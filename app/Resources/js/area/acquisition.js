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
});
