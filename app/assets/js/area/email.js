$(function () {
    jQuery('.btn-email-listing').each(function () {
        const btn = jQuery(this),
            url = btn.data('url'),
            refreshUrl = btn.data('refresh-url'),
            refreshToken = btn.data('refresh-token'),
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
            const btnTitle = list && list.length === 1 ? ' 1 E-Mail ' : ' ' + list.length + ' E-Mails ';
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

                if (emails && emails.length === 0) {
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

                    html += '<td>';
                    if (email.url_download_raw) {
                        html += '<a href="' + email.url_download_raw + '" target="_blank" title="E-Mail Quelltext herunterladen" class="btn btn-xs btn-default btn-download-email">' +
                            '<span class="glyphicon glyphicon-download" aria-hidden="true"></span>' +
                            '</a> ';
                    }
                    html += '</td>';

                    html += '</tr>';
                });
                tbodyEl.html(html);
                tbodyEl.toggleClass('loading-text', false);
            },
            loadMails = function (refresh) {
                refreshButtonEl.toggleClass('disabled', true);
                const config = {
                    type: refresh ? 'POST' : 'GET',
                    url: refresh ? refreshUrl : url,
                    success: function (response) {
                        btn.toggleClass('disabled', false);
                        if (response) {
                            if (response.items) {
                                commonCache.set(cacheKey, response.items);
                                updateButton(response.items);
                                renderEmails();
                            }
                            if (response.enableImapFinalRecipientWarning) {
                                modalEl.find('.imap-final-recipient-warning').html(
                                    '<div class="alert alert-warning">Aus technischen Gründen konnte keine Suche nach Nachrichten, die über nicht zugestellte E-Mails berichten, durchgeführt werden.</div>');
                            }
                            if (response.enableImapFullTextSearchError) {
                                modalEl.find('.imap-final-recipient-error').html(
                                    '<div class="alert alert-error">Aus technischen Gründen kann nicht nach zugehörigen E-Mails gesucht werden.</div>');
                            }
                        }
                    },
                    complete: function () {
                        refreshButtonEl.toggleClass('disabled', false);
                        btn.toggleClass('disabled', false);
                        btn.toggleClass('loading-text', false);
                    }
                };
                if (refresh) {
                    config.data = {
                        _token: refreshToken
                    };
                }
                jQuery.ajax(config);
            };
        loadMails(false);

        refreshButtonEl.on('click', function () {
            if (refreshButtonEl.hasClass('disabled')) {
                return;
            }
            refreshButtonEl.toggleClass('disabled', true);
            tbodyEl.toggleClass('loading-text', true);
            loadMails(true);
        });

        btn.on('click', function () {
            renderEmails();
            modalEl.modal('show');
        });
    });
});
