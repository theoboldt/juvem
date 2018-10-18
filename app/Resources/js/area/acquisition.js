$(function () {
    var elementType = '*#acquisition_fieldType',
        updateType;

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
});
