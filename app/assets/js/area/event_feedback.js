$(function () {
    /**
     * EVENT FEEDBACK DETAILS: Show/Hide counter thesis
     */
    $(".feedback-question .question .btn-toggle-counter-thesis").click(function (event) {
        event.preventDefault();
        const btnEl = $(this),
            distributionEl = btnEl.parent().parent().parent().parent().parent().parent();

        if (!distributionEl) {
            return;
        }
        if (distributionEl.hasClass('counter-thesis-visible')) {
            distributionEl.toggleClass('counter-thesis-visible', false);
        } else {
            distributionEl.toggleClass('counter-thesis-visible', true);
        }
    });

    let form = $('#feedback_questionnaire_fillout'),
        silentSubmitFeedbackFormRequested = false,
        silentSubmitFeedbackFormActive = false;
    if (form) {
        let silentSubmitFeedbackForm = function () {
            if (silentSubmitFeedbackFormActive) {
                silentSubmitFeedbackFormRequested = true;
                return;
            }
            silentSubmitFeedbackFormRequested = false;
            silentSubmitFeedbackFormActive = true;

            let xhr = new XMLHttpRequest();
            let data = new FormData(form.get(0));
            xhr.open('POST', window.location.href + '?empty_response=1', true);
            xhr.onreadystatechange = function (response) {
                silentSubmitFeedbackFormActive = false;
                if (silentSubmitFeedbackFormRequested) {
                    silentSubmitFeedbackForm();
                }
            };
            xhr.send(data);
        };
        $('#feedback_questionnaire_fillout input[type=radio]').change(function () {
            silentSubmitFeedbackForm();
        });
    }

});
