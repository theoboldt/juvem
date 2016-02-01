jQuery(document).ready(function () {

    /**
     * Enable tooltips and popvers
     */
    $('[data-toggle="tooltip"]').tooltip({
        container: 'body'
    });
    $('[data-toggle="popover"]').popover();

    /**
     * Admin event list table
     */
    $('#eventListTable').on('click-row.bs.table', function (e, row, $element) {
        location.href = row.eid;
    });

    /**
     * Admin event particioation list table
     */
    $('#participantsListTable').on('click-row.bs.table', function (e, row, $element) {
        location.href = 'participation/' + row.pid;
    });

    /**
     * Handle via prototype injected forms
     */
    $('.prototype-container').each(function (index) {
        var element = $(this),
            prototype;
        if (element.attr('data-prototype')) {
            prototype = element.data('prototype');

            var addElementHandlers = function () {
                element.find('.prototype-remove').on('click', function (e) {
                    e.preventDefault();
                    $(this).parent().parent().parent().parent().remove();
                });
                element.find('[data-toggle="popover"]').popover({
                    container: 'body',
                    placement: 'top',
                    title: 'Hinweis'
                });
            };

            element.data('index', element.find(':input').length);
            element.find('.prototype-add').on('click', function (e) {
                e.preventDefault();
                var index = element.data('index');
                var newForm = prototype.replace(/__name__/g, index);
                element.data('index', index + 1);

                element.find('.prototype-elements').append(newForm);
                addElementHandlers();
            });
            addElementHandlers();
        }
    });

});