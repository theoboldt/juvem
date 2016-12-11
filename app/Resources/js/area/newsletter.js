$(function () {
    /**
     * NEWSLETTER: Email preview
     */
    var updateMailPreview = function () {
        var updateButton = $('*#mail-form-newsletter .btn-update-preview');
        updateButton.prop('disabled', true);
        var content = {
                subject: $("#newsletter_mail_subject").val(),
                title: $("#newsletter_mail_title").val(),
                lead: $("#newsletter_mail_lead").val(),
                content: $("#newsletter_mail_content").val()
            },
            preview = $('*#mail-template iframe').contents();

        var replacePlaceholders = function (value) {
            if (!value) {
                return '';
            }
            return eHtml(value);
        };

        $.each(content, function (key, value) {
            value = replacePlaceholders(value);
            switch (key) {
                case 'content':
                    value = value.replace(/\n\n/g, '</p><p>');
                    break;
                case 'subject':
                    if (value == '') {
                        value = '<em>Kein Betreff</em>';
                    }
                    break;
            }

            if (key == 'subject') {
                $('*#mail-template-iframe-panel .panel-heading').html(value);
            } else {
                preview.find('#mail-part-' + key).html(value);
            }
        });
        updateButton.prop('disabled', false);
    };
    $('*#mail-form-newsletter-newsletter .btn-update-preview').click(updateMailPreview);
    $('*#mail-form-newsletter input, *#mail-form-newsletter textarea').change(updateMailPreview);

    if ($("#newsletter_mail_subject").val() != '' && $("#newsletter_mail_title").val() != ''
        && $("#newsletter_mail_lead").val() != '' && $("#newsletter_mail_content").val() != ''
    ) {
        //if there is something inserted in the fields
        $('iframe').on('load', function () {
            updateMailPreview();
        });
    }

    /**
     * NEWSLETTER: Recipient count
     */
    var updateRecipientCount = function () {
        var textField = $('#affectedSubscription');
        textField.attr('class', 'loading-text');

        $.ajax({
            type: 'POST',
            url: '/admin/newsletter/affected-recipient-count',
            data: {
                ageRangeBegin: $('*#newsletter_mail_ageRangeBegin').val(),
                ageRangeEnd: $('*#newsletter_mail_ageRangeEnd').val(),
                events: $('*#newsletter_mail_events').val() || []
            },
            dataType: 'json',
            success: function (response) {
                var text;
                if (response.count == 1) {
                    text = '1 Person wird diesen Newsletter erhalten.';
                } else {
                    text = response.count + ' Personen werden diesen Newsletter erhalten.';
                }
                textField.text(text);
            },
            complete: function () {
                textField.attr('class', '');
            }
        });
    };
    $('*#newsletter_mail_ageRangeBegin, *#newsletter_mail_ageRangeEnd, *#newsletter_mail_events').change(updateRecipientCount);

});