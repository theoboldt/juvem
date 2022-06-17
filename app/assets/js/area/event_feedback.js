$(function () {


    /**
     * EVENT FEEDBACK DETAILS: Show/Hide counter thesis
     */
    $(".feedback-question .question .btn-toggle-counter-thesis").click(function (event) {
        event.preventDefault();
        var btnEl = $(this),
            distributionEl = btnEl.parent().parent().parent().parent().parent().parent();

        if (distributionEl) {
            return;
        }
        if (distributionEl.hasClass('counter-thesis-visible')) {
            distributionEl.toggleClass('counter-thesis-visible', false);
        } else {
            distributionEl.toggleClass('counter-thesis-visible', true);
        }
    });
});
