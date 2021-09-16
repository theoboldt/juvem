$(function () {
    jQuery('.btn-email-listing').each(function () {
        const btn = jQuery(this),
            url = btn.data('url'),
            type = btn.data('type'),
            id = btn.data('id'),
            cacheKey = 'emails.list.' + type + '.id' + id,
            modalEl = jQuery('#modalEmailListing'),
            refreshButtonEl = modalEl.find('.btn-refresh'),
            tbodyEl = modalEl ? modalEl.find('table tbody') : null;

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

        const renderEmails = function () {
                if (btn.hasClass('disabled') || !modalEl || !tbodyEl) {
                    return;
                }
                tbodyEl.html('');
                const emails = commonCache.get(cacheKey);
                let html = '';

                if (emails.length === 0) {
                    html += '<tr><td colspan="5" class="text-center">Keine passenden E-Mails gefunden.</td></tr>';
                }


                jQuery.each(emails, function (key, email) {
                    let emailAddressesFrom = eHtml(email.from.join(', '));
                    let emailAddressesTo = eHtml(email.to.join(', '));

                    html += '<tr>';
                    html += '<td>';

                    if (email.organization_sender) {
                        html += '<span class="label label-primary" title="'+emailAddressesFrom+'">Wir</span>';
                    } else {
                        html += '<small>' + emailAddressesFrom + '</small>';
                    }
                    html += ' &rarr; ';
                    if (email.organization_receiver) {
                        html += '<span class="label label-primary" title="'+emailAddressesTo+'">Uns</span>';
                    } else {
                        html += '<small>' + emailAddressesTo + '</small>';
                    }

                    html += '</td>';
                    html += '<td>' + eHtml(email.date) + '</td>';
                    html += '<td>';
                    if (email.attachment_count) {
                        html += '<span class="glyphicon glyphicon-paperclip" aria-hidden="true" title="';
                        html += (email.attachment_count === 1 ? '1 Anhang' : email.attachment_count + ' Anhänge');
                        html += '"></span> ';
                    }
                    html += eHtml(email.subject);
                    html += '</td>';

                    html += '<td>';
                    if (email.mailbox_symbol) {
                        html += '<span class="glyphicon glyphicon-' + email.mailbox_symbol + '" aria-hidden="true"></span> ';
                    }
                    html += eHtml(email.mailbox);
                    html += '</td>';

                    html += '</tr>';
                });
                tbodyEl.html(html);
                tbodyEl.toggleClass('loading-text', false);
            },
            loadMails = function () {
                refreshButtonEl.toggleClass('disabled', true);
                jQuery.ajax({
                    type: 'GET',
                    url: url,
                    success: function (response) {
                        btn.toggleClass('disabled', false);
                        if (response.items) {
                            commonCache.set(cacheKey, response.items);
                            updateButton(response.items);
                            renderEmails();
                        }
                    },
                    complete: function () {
                        refreshButtonEl.toggleClass('disabled', false);
                        btn.toggleClass('disabled', false);
                        btn.toggleClass('loading-text', false);
                    }
                });
            };
        loadMails();

        refreshButtonEl.on('click', function () {
            if (refreshButtonEl.hasClass('disabled')) {
                return;
            }
            refreshButtonEl.toggleClass('disabled', true);
            tbodyEl.toggleClass('loading-text', true);
            loadMails();
        });

        btn.on('click', function () {
            renderEmails();
            modalEl.modal('show');
        });
    });
});
