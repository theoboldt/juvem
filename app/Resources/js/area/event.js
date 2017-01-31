$(function () {

    /**
     * EVENT: Handle via prototype injected forms
     */
    $('.prototype-container').each(function (index) {
        var element = $(this),
            prototype,
            elementMessage;
        if (element.attr('data-prototype')) {
            prototype = element.data('prototype');
            elementMessage = element.find('.prototype-missing-message');

            element.on('click', function (e) {
                var elementTarget = $(e.target);
                if (elementTarget.parent().is('.prototype-remove')) {
                    elementTarget = elementTarget.parent();
                }
                if (elementTarget.is('.prototype-remove')) {
                    e.preventDefault();
                    var formElement = elementTarget.parent().parent().parent().parent(),
                        formGroup = formElement.parent().parent(),
                        formElementCount = formGroup.find('.prototype-element').length;

                    formElement.remove();
                    if (formElement && elementMessage && !elementMessage.is(':visible') && formElementCount < 2) {
                        elementMessage.show(300);
                    }
                }
            });

            var addElementHandlers = function () {
                element.find('[data-toggle="popover"]').popover({
                    container: 'body',
                    placement: 'top',
                    html: true,
                    trigger: 'focus'
                    /*
                     }).click(function (e) {
                     e.preventDefault();
                     $(this).popover('toggle');
                     */
                });
            };

            element.data('index', element.find('.prototype-element').length);
            element.find('.prototype-add').on('click', function (e) {
                e.preventDefault();
                var index = element.data('index');
                var newForm = prototype.replace(/__name__/g, index);
                element.data('index', index + 1);

                if (elementMessage && elementMessage.is(':visible')) {
                    elementMessage.hide(300);
                }

                element.find('.prototype-elements').append(newForm);
            });
            addElementHandlers();
        }
    });

    /**
     * EVENT: Events participants email preview
     */
    var updateMailPreview = function () {
        var updateButton = $('*#mail-form .btn-update-preview');
        updateButton.prop('disabled', true);
        var content = {
                subject: $("#event_mail_subject").val(),
                title: $("#event_mail_title").val(),
                lead: $("#event_mail_lead").val(),
                content: $("#event_mail_content").val()
            },
            preview = $('*#mail-template iframe').contents(),
            exampleSalution = 'Frau',
            exampleLastName = 'Müller',
            exampleEventTitle = $('#mail-form').data('event-title');

        var replacePlaceholders = function (value) {
            if (!value) {
                return '';
            }
            value = value.replace(/\{PARTICIPATION_SALUTION\}/g, exampleSalution);
            value = value.replace(/\{PARTICIPATION_NAME_LAST\}/g, exampleLastName);
            value = value.replace(/\{EVENT_TITLE\}/g, exampleEventTitle);

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
    $('*#mail-form .btn-update-preview').click(updateMailPreview);
    $('*#mail-form input, *#mail-form textarea').change(updateMailPreview);


    /**
     * EVENT: Handle comment form call
     */
    $('#dialogModalComment').on('show.bs.modal', function (event) {
        var modal = $(this),
            button = $(event.relatedTarget),
            cid = button.data('cid'),
            content = cid ? button.data('content') : null,
            meta = cid ? button.parent().parent().find('small').html() : '',
            relatedId = button.data('related-id'),
            relatedClass = button.data('comment-class'),
            selectorPartClassAndId = '[data-comment-class="' + relatedClass.replace(/\\/g, '\\\\') + '"][data-related-id="' + relatedId + '"]',
            commentsEl = $('.comments' + selectorPartClassAndId),
            countEl = $('.comment-count' + selectorPartClassAndId);

        if (cid) {
            modal.find('#dialogModalCommentLabel').text('Anmerkung bearbeiten');
            modal.find('input[type=submit]').val('Änderungen speichern');
        } else {
            modal.find('#dialogModalCommentLabel').text('Anmerkungen hinzufügen');
            modal.find('input[type=submit]').val('Anmerkungen hinzufügen');
        }

        modal.find('p.meta').html(meta);
        modal.find('#modalCommentContent').val(content);

        $('#dialogModalCommentButton').unbind('click').click(function () {
            button.toggleClass('disabled', true);
            commentsEl.toggleClass('loading-text', true);
            $.ajax({
                type: 'POST',
                url: '/admin/comment/update',
                data: {
                    _token: $('#modalCommentToken').val(),
                    cid: cid,
                    relatedClass: relatedClass,
                    relatedId: relatedId,
                    content: $('#modalCommentContent').val()
                },
                dataType: 'json',
                success: function (response) {
                    commentsEl.empty();
                    if (response && response.comments) {
                        commentsEl.html(response.comments);
                    }
                    if (response && response.count) {
                        countEl.text(response.count);
                    }
                },
                error: function () {
                    $(document).trigger('add-alerts', {
                        message: 'Die Anmerkung konnte nicht gespeichert werden',
                        priority: 'error'
                    });
                },
                complete: function () {
                    button.toggleClass('disabled', false);
                    commentsEl.toggleClass('loading-text', false);
                }
            });

            modal.modal('hide');
            return false;
        });
    });


});