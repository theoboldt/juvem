$(function () {
    jQuery('.btn-email-listing').each(function () {
        const btn = jQuery(this),
            url = btn.data('url'),
            type = btn.data('type'),
            id = btn.data('id'),
            cacheKey = 'emails.list.' + type + '.id' + id;

        if (!url) {
            btn.toggleClass('disabled', true);
            return;
        }

        const updateButton = function (list) {
            const btnTitle = list.length === 1 ? ' 1 E-Mail ' : ' ' + list.length + ' E-Mails ';
            btn.find('.hidden-xs').html(btnTitle);
        };

        btn.toggleClass('loading-text', true);
        if (commonCache.has(cacheKey)) {
            updateButton(commonCache.get(cacheKey));
        } else {
            btn.toggleClass('disabled', true);
        }

        jQuery.ajax({
            type: 'GET',
            url: url,
            success: function (response) {
                btn.toggleClass('loading-text', false);
                btn.toggleClass('disabled', false);

                if (response.items) {
                    commonCache.set(cacheKey, response.items);
                    updateButton(response.items);
                }
            }
        });

        btn.on('click', function () {
            const modalEl = jQuery('#modalEmailListing'),
                tbodyEl = modalEl ? modalEl.find('table tbody') : null;
            if (btn.hasClass('disabled') || !modalEl || !tbodyEl) {
                return;
            }
            tbodyEl.html('');
            const emails = commonCache.get(cacheKey);
            let html = '';

            jQuery.each(emails, function (key, email) {
                html += '<tr>';
                html += '<td><small>' + eHtml(email.from.join(', ')) + '</small></td>';
                html += '<td><small>' + eHtml(email.to.join(', ')) + '</small></td>';
                html += '<td>' + eHtml(email.date) + '</td>';
                html += '<td>';
                if (email.attachment_count) {
                    html += '<span class="glyphicon glyphicon-paperclip" aria-hidden="true" title="';
                    html += (email.attachment_count === 1 ? '1 Anhang' : email.attachment_count + ' Anhänge');
                    html += '"></span> ';
                }
                html += eHtml(email.subject);
                html += '</td>';
                html += '<td>' + eHtml(email.mailbox) + '</td>';
                html += '</tr>';
            });
            tbodyEl.html(html);

            modalEl.modal('show');
        });
    });
});
