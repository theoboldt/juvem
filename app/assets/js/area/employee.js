$(function () {
    $('#import-employee-actions .btn-apply-to-all').click(function () {
        var el = $(this),
            bid = el.data('bid'),
            action = el.data('action');

        switch (action) {
            case 'select':
                var optionId = el.data('id');
                $('.custom-field-field-' + bid + ' select').each(function () {
                    $(this).find('option[value="' + optionId + '"]').prop('selected', true);
                });
                $('.custom-field-field-' + bid + ' input[type="radio"]').each(function () {
                    $(this).prop('checked', false);
                });
                $('.custom-field-field-' + bid + ' input[value="' + optionId + '"]').each(function () {
                    $(this).prop('checked', true);
                });
                break;
            case 'clear':
                $('.custom-field-field-' + bid + ' input').each(function () {
                    $(this).val('');
                });
                break;
        }
    });
})
;
