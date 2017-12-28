$(function () {
    var btnOpenSendModal = $('#openDialogSend');

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
    updateMailPreviewAndDisableSend = function () {
        updateMailPreview();
        btnOpenSendModal.toggleClass('disabled', true).prop(
            'title',
            'Bevor der Newsletter versandt werden kann, müssen alle Änderungen gespeichert werden.'
        );
    };
    $('*#mail-form-newsletter-newsletter .btn-update-preview').click(updateMailPreviewAndDisableSend);
    $('*#mail-form-newsletter input, *#mail-form-newsletter textarea, *#mail-form-newsletter select').change(updateMailPreviewAndDisableSend);

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
            url: '/admin/newsletter/affected-recipient-count',
            data: {
                _token: $('*#dialogSend').data('token'),
                ageRangeBegin: $('*#newsletter_mail_ageRangeBegin').val(),
                ageRangeEnd: $('*#newsletter_mail_ageRangeEnd').val(),
                events: $('*#newsletter_mail_events').val() || []
            },
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

    /**
     * NEWSLETTER: Send modal
     */
    var btnSend = $('#sendMessageButton'),
        updatePotentialRecipients = function () {
            var lid = btnSend.data('lid'),
                listColEl = $('#new-recipient-list-description'),
                recpientEl = $('#new-recipient-list'),
                alertEl = $('.alert-no-recipients'),
                updateAlertElVisibility = function (visible) {
                    if (visible) {
                        alertEl.css('display', 'block');
                    } else {
                        alertEl.css('display', 'none');
                    }
                };
            updateAlertElVisibility(false);
            listColEl.attr('class', 'loading-text');
            btnSend.toggleClass('disabled', true);

            $.ajax({
                url: '/admin/newsletter/affected-recipient-list',
                data: {
                    _token: $('*#dialogSend').data('token'),
                    lid: lid,
                    ageRangeBegin: $('*#newsletter_mail_ageRangeBegin').val(),
                    ageRangeEnd: $('*#newsletter_mail_ageRangeEnd').val(),
                    events: $('*#newsletter_mail_events').val() || []
                },
                success: function (response) {
                    recpientEl.html("");
                    if (response.length) {
                        btnSend.toggleClass('disabled', false);
                        updateAlertElVisibility(false);
                        $.each(response, function (key, value) {
                            recpientEl.append('<li>' + eHtml(value) + '</li>');
                        });
                    } else {
                        updateAlertElVisibility(true);
                    }
                },
                complete: function () {
                    listColEl.attr('class', '');
                }
            });
        };
    $('#dialogSend').on('show.bs.modal', updatePotentialRecipients);

    /**
     * NEWSLETTER: Ensure that modal is not opening if related button is disabled
     */
    btnOpenSendModal.click(function () {
        if ($(this).hasClass('disabled')) {
            return false;
        }
    });

    /**
     * NEWSLETTER: Send newsletter
     */
    btnSend.click(function () {
        var lid = $(this).data('lid');
        btnSend.prop('disabled', true);

        $.ajax({
            url: '/admin/newsletter/send',
            data: {
                _token: $('*#dialogSend').data('token'),
                lid: lid
            },
            success: function () {
                location.reload(true);
            },
            fail: function () {
                btnSend.prop('disabled', false);
                updatePotentialRecipients();
            }
        });
    });


    /**
     * NEWSLETTER: Send test newsletter
     */
    var btnSendTest = $('#sendTestMessageButton');
    $('#sendTestMessageButton').click(function () {
        btnSendTest.prop('disabled', true);

        $.ajax({
            url: '/admin/newsletter/send_test',
            data: {
                _token: $('*#dialogSendTest').data('token'),
                email: $('*#newsletter_test_email').val(),
                subject: $('*#newsletter_mail_subject').val(),
                title: $('*#newsletter_mail_title').val(),
                lead: $('*#newsletter_mail_lead').val(),
                content: $('*#newsletter_mail_content').val()
            },
            success: function () {
                btnSendTest.prop('disabled', false);
                $('#dialogSendTest').modal('hide');
                $(document).trigger('add-alerts', {
                    message: 'Die Test-E-Mail wurde versandt',
                    priority: 'success'
                });
            },
            fail: function () {
                $(document).trigger('add-alerts', {
                    message: 'Beim versenden der E-Mail ist ein Fehler aufgetreten',
                    priority: 'error'
                });
            }
        });
    });

});