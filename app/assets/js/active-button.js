$(function(){

    /**
     * GLOBAL: Active button
     */
    $('[data-element="activebutton"]').each(function () {
        var button = $(this);

        button.prop('disabled', true);
        var token = button.data('token'),
            entityName = button.data('entity'),
            entityId = button.data('entity-id'),
            propertyName = button.data('property'),
            enableLabel = button.data('button-enable-label'),
            enableGlyph = button.data('button-enable-glyph'),
            disableLabel = button.data('button-disable-label'),
            disableGlyph = button.data('button-disable-glyph'),
            isXs = button.hasClass('btn-xs') ? 1 : 0,
            formData = {
                _token: token,
                isXs: isXs,
                entityName: entityName,
                entityId: entityId,
                propertyName: propertyName,
                buttons: {
                    buttonEnable: {
                        label: enableLabel,
                        glyph: enableGlyph
                    },
                    buttonDisable: {
                        label: disableLabel,
                        glyph: disableGlyph
                    }
                }
            };

        $.ajax({
            url: '/admin/active/button',
            data: formData,
            success: function (response) {
                button.empty();
                if (response && response.html) {
                    button.html(response.html);
                }
            },
            complete: function () {
                button.prop('disabled', false);
                button.find('[data-toggle="tooltip"]').tooltip({
                    container: 'body'
                });
            }
        });

        button.click(function () {
            button.prop('disabled', true);
            $.ajax({
                url: '/admin/active/button',
                data: $.extend(formData, {toggle: 1}),
                success: function (response) {
                    button.empty();
                    if (response && response.html) {
                        button.html(response.html);
                    }
                    button.trigger('juvem.activeButton.success', [button, response]);
                },
                complete: function (response) {
                    button.prop('disabled', false);
                    button.find('[data-toggle="tooltip"]').tooltip({
                        container: 'body'
                    });
                    button.trigger('juvem.activeButton.complete', [button, response]);
                }
            });
        });
    });
});