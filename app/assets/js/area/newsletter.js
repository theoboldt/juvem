$(function () {
    var btnOpenSendModal = $('#openDialogSend'),
        updateButton = $('*#mail-form-newsletter .btn-update-preview'),
        updateScheduled = false;

    /**
     * NEWSLETTER: Email preview
     */
    const updateMailPreview = function () {
            if (updateScheduled === false) {
                updateScheduled = true;

                updateButton.prop('disabled', true);
                setTimeout(function () {
                    updateScheduled = false;

                    var form = $('#mail-form-newsletter form'),
                        action = form.attr('action'),
                        target = form.attr('target');

                    form.attr('action', '/admin/newsletter/create_preview');
                    form.attr('target', 'mail-template-iframe');

                    form.submit();

                    form.attr('action', action ? action : '');
                    form.attr('target', target ? target : '');
                    updateButton.prop('disabled', false);

                    $('*#mail-template-iframe-panel .panel-heading').html(eHtml($("#newsletter_mail_subject").val()));
                }, 500);
            }
        },
        disableSend = function () {
            btnOpenSendModal.toggleClass('disabled', true).prop(
                'title',
                'Bevor der Newsletter versandt werden kann, müssen alle Änderungen gespeichert werden.'
            );
        };
    $('*#mail-form-newsletter-newsletter .btn-update-preview').click(updateMailPreview);
    $('*#mail-form-newsletter input.preview, *#mail-form-newsletter textarea.preview').bind('input propertychange', updateMailPreview);
    $('*#mail-form-newsletter input, *#mail-form-newsletter textarea, *#mail-form-newsletter select').bind('input propertychange', disableSend);

    updateButton.click(function () {
        updateMailPreview();
    });

    /**
     * NEWSLETTER: Recipient count
     */
    const updateRecipientCount = function () {
        const textField = $('#affectedSubscription');
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
    const btnSend = $('#sendMessageButton'),
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
            success: function (response) {
                btnSendTest.prop('disabled', false);
                $('#dialogSendTest').modal('hide');
                if (response && response.sentCount !== undefined) {
                    if (response.sentCount) {
                        $(document).trigger('add-alerts', {
                            message: 'Die Test-E-Mail wurde versandt',
                            priority: 'success'
                        });

                    } else {
                        $(document).trigger('add-alerts', {
                            message: 'Die Test-E-Mail scheint nicht versandt worden zu sein. Möglicherweise liegt ein Problem mit dem Mail-Dienstleister vor.',
                            priority: 'warning'
                        });
                    }
                }
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
