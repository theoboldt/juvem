$(function () {
    $('#calculationPersonCount').on('input', function () {
        var valueRaw = $(this).val().replace(',', '.'),
            personCount = parseFloat(valueRaw);

        jQuery('.calculationAmountSum').each(function () {
            var el = $(this),
                perPersonValue = parseFloat(el.data('per-person')),
                sum = perPersonValue * personCount,
                html = '?';
            if (!isNaN(personCount) || personCount < 0) {
                html = sum.toFixed(3).replace('.', ',');
            }
            el.html(html);
        });

    });
});
