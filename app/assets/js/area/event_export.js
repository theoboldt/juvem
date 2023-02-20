$(function () {
    /**
     * Do export
     *
     * @param button
     * @param preventSubmit
     */
    var prefillAndExport = function (button, preventSubmit) {
        const template = button.data('template'),
            inputs = $('.form-file-download .column-configuration input'),
            inputTitle = $('input[name="config[title]"]'),
            selectAge = $('.form-file-download select[name="config[participant][ageAtEvent]"]'),
            selectPhone = $('.form-file-download select[name="config[participation][phoneNumber]"]');
        var includeFields = [],
            includeAcquisitionFields = {
                participant: {
                    display: null,
                    optionValue: null
                },
                participation: {
                    display: null,
                    optionValue: null
                }
            };
        if (button.hasClass('disabled')) {
            return;
        }

        switch (template) {
            case 'participants':
                includeFields = [
                    'config[participant][aid]',
                    'config[participant][nameFirst]',
                    'config[participant][nameLast]',
                    'config[participant][birthday]',
                    'config[participant][gender]',
                    'config[participant][foodVegetarian]',
                    'config[participant][foodLactoseFree]',
                    'config[participant][foodLactoseNoPork]',
                    'config[participant][infoMedical]',
                    'config[participant][infoGeneral]',
                    'config[participant][price]',
                    'config[participation][pid]'
                ];
                selectAge.val('completed');
                selectPhone.val('comma');
                includeAcquisitionFields.participant.display = 'selectedAnswer';
                includeAcquisitionFields.participant.optionValue = 'managementTitle';
                inputTitle.val('Teilnehmende');
                break;
            case 'food':
                includeFields = [
                    'config[participant][nameFirst]',
                    'config[participant][nameLast]',
                    'config[participant][foodVegetarian]',
                    'config[participant][foodLactoseFree]',
                    'config[participant][foodLactoseNoPork]',
                    'config[participant][infoMedical]',
                    'config[participant][infoGeneral]',
                ];
                selectAge.val('none');
                selectPhone.val('comma');
                inputTitle.val('Ernährung');
                break;
            case 'phone_list':
                includeFields = [
                    'config[participant][nameFirst]',
                    'config[participant][nameLast]',
                    'config[participation][nameLast]',
                ];
                selectAge.val('none');
                selectPhone.val('comma_description_wrap');
                inputTitle.val('Telefonliste');
                break;
            case 'letter':
                includeFields = [
                    'config[participant][aid]',
                    'config[participant][nameFirst]',
                    'config[participant][nameLast]',
                    'config[participant][birthday]',
                    'config[participant][gender]',
                    'config[participant][foodVegetarian]',
                    'config[participant][foodLactoseFree]',
                    'config[participant][foodLactoseNoPork]',
                    'config[participant][infoMedical]',
                    'config[participant][infoGeneral]',
                    'config[participant][price]',
                    'config[participation][pid]',
                    'config[participation][salutation]',
                    'config[participation][nameFirst]',
                    'config[participation][nameLast]',
                    'config[participation][email]',
                    'config[participation][addressStreet]',
                    'config[participation][addressCity]',
                    'config[participation][addressZip]',
                    'config[additional_sheet][participation]'
                ];
                selectAge.val('completed');
                selectPhone.val('comma_description');
                includeAcquisitionFields.participant.display = 'separateColumns';
                includeAcquisitionFields.participant.optionValue = 'managementTitle';
                includeAcquisitionFields.participation.display = 'separateColumns';
                includeAcquisitionFields.participation.optionValue = 'managementTitle';
                inputTitle.val('Serienbrief');
                break;
            case 'nx':
                includeFields = [
                    'config[participant][aid]',
                    'config[participant][nameFirst]',
                    'config[participant][nameLast]',
                    'config[participant][birthday]',
                    'config[participant][gender]',
                    'config[participant][foodVegetarian]',
                    'config[participant][foodLactoseFree]',
                    'config[participant][foodLactoseNoPork]',
                    'config[participant][infoMedical]',
                    'config[participant][infoGeneral]',
                    'config[participant][price]',
                    'config[participation][pid]',
                    'config[participation][salutation]',
                    'config[participation][nameFirst]',
                    'config[participation][nameLast]',
                    'config[participation][email]',
                    'config[participation][addressStreet]',
                    'config[participation][addressCity]',
                    'config[participation][addressZip]',
                ];
                selectAge.val('none');
                selectPhone.val('none');
                break;
            default:
                selectAge.val('none');
                selectPhone.val('none');
                inputTitle.val('');
                break;
        }
        const regex = /config\[(participant|participation)\]\[acquisitionFields\]\[custom_field_(\d+)\]\[enabled\]/;

        inputs.each(function () {
            const input = $(this),
                name = input.attr('name');
            var newValue = false;

            if (input.attr('type') === 'checkbox') {
                if (regex.test(name)) {
                    const result = regex.exec(name),
                        fieldArea = result[1],
                        fieldId = result[2],
                        baseFieldName = '.form-file-download select[name="config[' + fieldArea + '][acquisitionFields][custom_field_' + fieldId + ']',
                        hasField = includeAcquisitionFields[fieldArea] !== null,
                        valueDisplay = hasField && includeAcquisitionFields[fieldArea]['display'] ? includeAcquisitionFields[fieldArea]['display'] : null,
                        selectDisplay = $(baseFieldName + '[display]"]'),
                        valueOptionValue = hasField && includeAcquisitionFields[fieldArea]['optionValue'] ? includeAcquisitionFields[fieldArea]['optionValue'] : null,
                        selectOptionValue = $(baseFieldName + '[optionValue]"]');

                    if (valueDisplay !== null) {
                        newValue = true;
                        if (selectDisplay.length) {
                            var options = [];
                            selectDisplay.find('option').each(function () {
                                var el = $(this);
                                options.push(el.attr('value'));
                            });
                            if ($.inArray(valueDisplay, options) !== -1) {
                                selectDisplay.val(valueDisplay);
                            } else {
                                selectDisplay.val('commaSeparated');
                            }
                        }
                        if (selectOptionValue && selectOptionValue.length) {
                            selectOptionValue.val(valueOptionValue);
                        }
                    }
                } else {
                    if ($.inArray(name, includeFields) !== -1) {
                        newValue = true;
                    }
                }
                input.prop('checked', newValue);
            }
        });

        if (!preventSubmit && template !== 'reset') {
            $(".form-file-download").submit();
        }
    };

    /**
     * Check if automatically directed here on load
     */
    if (location.hash) {
        const button = $('button[data-template="' + location.hash.substring(1) + '"]');
        if (button.length) {
            prefillAndExport(button, true);
        }
    }

    /**
     * EVENT EXPORT: Prefill form by templates
     */
    $(".form-file-download .templates button").click(function () {
        prefillAndExport($(this), false);
    });

    /**
     * GLOBAL: Export configurator download
     */
    $(".form-file-download").submit(function (event) {
        event.preventDefault();
        var form = $(this),
            buttonSubmit =$(".form-file-download .btn-generator");

        if (buttonSubmit.hasClass('disabled')) {
            return;
        }
        buttonSubmit.toggleClass('disabled', true);

        $.ajax({
            url: form.data('new-action'),
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            data: form.serialize(),
            success: function (result) {
                var iframe = $("<iframe/>").attr({
                        src: result.download_url,
                        style: "display:none"

                    }).appendTo(form);

                iframe.on('load', function () {
                    setTimeout(function () {
                        iframe.remove();
                    }, 1000)
                });

            },
            complete: function (response, status) {
                buttonSubmit.toggleClass('disabled', false);

                if (status !== 'success') {
                    $(document).trigger('add-alerts', {
                        message: 'Der Download konnte nicht erstellt werden',
                        priority: 'error'
                    });
                }
            }
        });
    });


    /**
     * EVENT EXPORT TEMPLATES: Prepare buttons
     */
    $("#templates .export-template .btn-toolbar a").click(function (event) {
        event.preventDefault();
        var button = $(this),
            action = button.data('action'),
            templateEl = button.closest('.export-template'),
            configuration = templateEl.data('configuration'),
            configurationFlat;

        const flattenObject = (obj, prefix = '') =>
            Object.keys(obj).reduce((acc, k) => {
                const pre = prefix.length ? prefix + '][' : '';
                if (typeof obj[k] === 'object') Object.assign(acc, flattenObject(obj[k], pre + k));
                else acc[pre + k] = obj[k];
                return acc;
            }, {});
        configurationFlat = flattenObject(configuration);

        if (action === 'apply-process' || action === 'apply-only') {
            $.each(configurationFlat, function (name, value) {
                var el = $('[name="config[' + name + ']"]');
                if (el.is('input') || el.is('select')) {
                    if (el.attr('type') === 'checkbox') {
                        el.prop('checked', value);
                    } else if (el.attr('type') === 'radio') {
                        el.each(function () {
                            var elOption = $(this);
                            elOption.prop('checked', elOption.attr('value') === value);
                        });
                    } else {
                        el.val(value);
                    }
                } else {
                    console.log(el); //debugging, such elements are not expected
                }
            });
        }
        if (action === 'apply-process') {
            $(".form-file-download").submit();
        }
        if (action === 'delete') {
            let modal = $('#dialogDeleteTemplate'),
                id = templateEl.data('id'),
                title = templateEl.find('h3').text();

            modal.find('.modal-title i').text(title);
            modal.find('.modal-body i').text(title);

            modal.find('input[name="form[delete]"]').val(id);
        }
        if (action === 'edit-meta') {
            let modal = $('#dialogEditTemplate'),
                id = templateEl.data('id'),
                title = templateEl.find('h3').text(),
                description = templateEl.find('.panel-body > p.description').text();

            modal.find('.modal-title i').text(title);
            modal.find('input[name="form[edit]"]').val(id);
            modal.find('input[name="form[title]"]').val(title);
            modal.find('textarea[name="form[description]"]').val(description);
        }
    });

    /**
     * EVENT EXPORT TEMPLATES: Prepare buttons
     */
    $("#templates .btn-form-redirect").click(function (event) {
        event.preventDefault();
        var button = $(this);

        var submitEl = $(".form-file-download");
        submitEl.off();
        submitEl.attr('action', button.data('form-action'));
        submitEl.submit();
    });

});
