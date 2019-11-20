$(function () {
    $('#import-employee-actions .btn-apply-to-all').click(function () {
        var el = $(this),
            bid = el.data('bid'),
            action = el.data('action');

        switch (action) {
            case 'select':
                var optionId = el.data('id');
                $('.acq_field_' + bid + ' select').each(function () {
                    $(this).find('option[value="' + optionId + '"]').prop('selected', true);
                });
                $('.acq_field_' + bid + ' input[type="radio"]').each(function () {
                    $(this).prop('checked', false);
                });
                $('.acq_field_' + bid + ' input[value="' + optionId + '"]').each(function () {
                    $(this).prop('checked', true);
                });
                break;
            case 'clear':
                $('.acq_field_' + bid + ' input').each(function () {
                    $(this).val('');
                });
                break;
        }
    });
})
;
