$(function () {
    /**
     * Do export
     *
     * @param button
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
                inputTitle.val('Teilnehmer');
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
                    'config[participation][salution]',
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
                    'config[participation][salution]',
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
        const regex = /config\[(participant|participation)\]\[acquisitionFields\]\[acq_field_(\d+)\]\[enabled\]/;

        inputs.each(function () {
            const input = $(this),
                name = input.attr('name');
            var newValue = false;

            if (input.attr('type') === 'checkbox') {
                if (regex.test(name)) {
                    const result = regex.exec(name),
                        fieldArea = result[1],
                        fieldId = result[2],
                        baseFieldName = '.form-file-download select[name="config[' + fieldArea + '][acquisitionFields][acq_field_' + fieldId + ']',
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
            button = form.find('button');

        if (button.hasClass('disabled')) {
            return;
        }
        $(".form-file-download .btn-generator").toggleClass('disabled', true);

        $.ajax({
            url: form.attr('action'),
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            dataType: 'binary',
            data: form.serialize(),
            xhrFields: {
                responseType: 'blob'
            },
            success: function (data, status, jqXHR) {
                var disposition = jqXHR.getResponseHeader('Content-Disposition'),
                    regex = /filename="(.*)"/,
                    a = document.createElement('a'),
                    url = window.URL.createObjectURL(data);

                a.href = url;
                a.download = regex.exec(disposition)[1];

                a.click();
                window.URL.revokeObjectURL(url);
            },
            complete: function (response, status) {
                $(".form-file-download .btn-generator").toggleClass('disabled', false);

                if (status !== 'success') {
                    $(document).trigger('add-alerts', {
                        message: 'Der Download konnte nicht erstellt werden',
                        priority: 'error'
                    });
                }
            }
        });
    });

});
