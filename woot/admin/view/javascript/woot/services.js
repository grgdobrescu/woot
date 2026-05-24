/**
 * Woot Services Management
 *
 * Handles service selection, configuration, and CRUD operations
 * for the Woot shipping admin interface.
 */
var WootServices = (function($) {
    'use strict';

    // Private variables
    var allServices = {};
    var configuredServices = {};
    var options = {
        servicesUrl: '',
        translations: {}
    };

    /**
     * Initialize the services module
     *
     * @param {Object} config Configuration object
     * @param {string} config.servicesUrl URL to fetch services
     * @param {Object} config.initialServices Pre-configured services
     * @param {Object} config.translations Language strings
     */
    function init(config) {
        options = $.extend(options, config);

        if (config.initialServices && typeof config.initialServices === 'object' && !Array.isArray(config.initialServices)) {
            configuredServices = config.initialServices;
        }

        // Set initial hidden input value
        $('#input-services').val(JSON.stringify(configuredServices));

        // Load services from API
        loadServices();

        // Bind events
        bindEvents();
    }

    /**
     * Load services from API
     */
    function loadServices() {
        $.ajax({
            url: options.servicesUrl,
            type: 'get',
            dataType: 'json',
            success: function(json) {
                $('#services-loading').addClass('d-none');

                if (json['error']) {
                    $('#services-error').removeClass('d-none').text(json['error']);
                    return;
                }

                if (json['services']) {
                    processServices(json['services']);
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                $('#services-loading').addClass('d-none');
                $('#services-error').removeClass('d-none').text(thrownError);
            }
        });
    }

    /**
     * Process services response and populate UI
     *
     * @param {Array} services Array of service objects
     */
    function processServices(services) {
        // Store all services for reference
        services.forEach(function(service) {
            allServices[service.id] = service;
        });

        // Populate selects grouped by courier
        var homeOptions = {};
        var lockerOptions = {};

        services.forEach(function(service) {
            var target = service.delivery === 'location' ? lockerOptions : homeOptions;
            if (!target[service.courier_name]) {
                target[service.courier_name] = [];
            }
            target[service.courier_name].push(service);
        });

        // Build home services select
        var homeHtml = '<option value="">' + options.translations.text_select_service + '</option>';
        for (var courier in homeOptions) {
            homeHtml += '<optgroup label="' + courier + '">';
            homeOptions[courier].forEach(function(s) {
                homeHtml += '<option value="' + s.id + '">' + s.name + '</option>';
            });
            homeHtml += '</optgroup>';
        }
        $('#select-home-services').html(homeHtml);

        // Build locker services select
        var lockerHtml = '<option value="">' + options.translations.text_select_service + '</option>';
        for (var courier in lockerOptions) {
            lockerHtml += '<optgroup label="' + courier + '">';
            lockerOptions[courier].forEach(function(s) {
                lockerHtml += '<option value="' + s.id + '">' + s.name + '</option>';
            });
            lockerHtml += '</optgroup>';
        }
        $('#select-locker-services').html(lockerHtml);

        // Load already configured services
        for (var serviceId in configuredServices) {
            if (allServices[serviceId]) {
                var service = allServices[serviceId];
                var config = configuredServices[serviceId];

                // Ensure all fields are set (for backward compatibility)
                if (!config.delivery) config.delivery = service.delivery || 'door';
                if (!config.courier_id) config.courier_id = service.courier_id || '';
                if (!config.courier_uid) config.courier_uid = service.courier_uid || '';
                if (!config.courier_name) config.courier_name = service.courier_name || '';
                if (!config.service_uid) config.service_uid = service.uid || '';
                if (!config.service_name) config.service_name = service.name || '';
                if (config.markup_percent === undefined) config.markup_percent = '';
                if (config.markup_fixed === undefined) config.markup_fixed = '';

                addServiceRow(serviceId, config);
            }
        }

        $('#services-container').removeClass('d-none');
    }

    /**
     * Bind UI events
     */
    function bindEvents() {
        // Add service from home select
        $('#select-home-services').on('change', function() {
            var serviceId = $(this).val();
            if (serviceId && !configuredServices[serviceId]) {
                var service = allServices[serviceId];
                var defaultName = service ? (service.courier_name + ' - ' + service.name) : '';
                var serviceConfig = {
                    name: defaultName,
                    price_type: 'quotation',
                    markup_percent: '',
                    markup_fixed: '',
                    price: '',
                    delivery: service ? service.delivery : 'door',
                    courier_id: service ? service.courier_id : '',
                    courier_uid: service ? (service.courier_uid || '') : '',
                    courier_name: service ? (service.courier_name || '') : '',
                    service_uid: service ? (service.uid || '') : '',
                    service_name: service ? (service.name || '') : ''
                };
                addServiceRow(serviceId, serviceConfig);
                configuredServices[serviceId] = serviceConfig;
                updateServicesInput();
            }
            $(this).val('');
        });

        // Add service from locker select
        $('#select-locker-services').on('change', function() {
            var serviceId = $(this).val();
            if (serviceId && !configuredServices[serviceId]) {
                var service = allServices[serviceId];
                var defaultName = service ? (service.courier_name + ' - ' + service.name) : '';
                var serviceConfig = {
                    name: defaultName,
                    price_type: 'quotation',
                    markup_percent: '',
                    markup_fixed: '',
                    price: '',
                    delivery: service ? service.delivery : 'location',
                    courier_id: service ? service.courier_id : '',
                    courier_uid: service ? (service.courier_uid || '') : '',
                    courier_name: service ? (service.courier_name || '') : '',
                    service_uid: service ? (service.uid || '') : '',
                    service_name: service ? (service.name || '') : ''
                };
                addServiceRow(serviceId, serviceConfig);
                configuredServices[serviceId] = serviceConfig;
                updateServicesInput();
            }
            $(this).val('');
        });

        // Handle price type change
        $(document).on('change', '.price-type-select', function() {
            var row = $(this).closest('tr');
            var serviceId = row.data('service-id');
            var priceType = $(this).val();

            if (priceType === 'quotation') {
                row.find('.markup-percent-input, .markup-fixed-input').prop('disabled', false);
                row.find('.price-input').prop('disabled', true).val('');
            } else {
                row.find('.markup-percent-input, .markup-fixed-input').prop('disabled', true).val('');
                row.find('.price-input').prop('disabled', false);
            }

            configuredServices[serviceId].price_type = priceType;
            configuredServices[serviceId].markup_percent = row.find('.markup-percent-input').val();
            configuredServices[serviceId].markup_fixed = row.find('.markup-fixed-input').val();
            configuredServices[serviceId].price = row.find('.price-input').val();
            updateServicesInput();
        });

        // Handle markup percent change
        $(document).on('input', '.markup-percent-input', function() {
            var row = $(this).closest('tr');
            var serviceId = row.data('service-id');
            configuredServices[serviceId].markup_percent = $(this).val();
            updateServicesInput();
        });

        // Handle markup fixed change
        $(document).on('input', '.markup-fixed-input', function() {
            var row = $(this).closest('tr');
            var serviceId = row.data('service-id');
            configuredServices[serviceId].markup_fixed = $(this).val();
            updateServicesInput();
        });

        // Handle name change
        $(document).on('input', '.name-input', function() {
            var row = $(this).closest('tr');
            var serviceId = row.data('service-id');
            configuredServices[serviceId].name = $(this).val();
            updateServicesInput();
        });

        // Handle price change
        $(document).on('input', '.price-input', function() {
            var row = $(this).closest('tr');
            var serviceId = row.data('service-id');
            configuredServices[serviceId].price = $(this).val();
            updateServicesInput();
        });

        // Handle remove service
        $(document).on('click', '.remove-service', function() {
            var row = $(this).closest('tr');
            var serviceId = row.data('service-id');
            delete configuredServices[serviceId];
            row.remove();
            updateServicesInput();

            if ($('#services-tbody tr:visible').length === 0) {
                $('#no-services-row').show();
            }
        });
    }

    /**
     * Add a service row to the table
     *
     * @param {string} serviceId Service ID
     * @param {Object} config Service configuration
     */
    function addServiceRow(serviceId, config) {
        var service = allServices[serviceId];
        if (!service) return;

        $('#no-services-row').hide();

        var nameValue = config.name || (service.courier_name + ' - ' + service.name);
        var pickupType = service.pickup === 'location' ? options.translations.text_locker : options.translations.text_door;
        var deliveryType = service.delivery === 'location' ? options.translations.text_locker : options.translations.text_door;
        var quotationSelected = config.price_type === 'quotation' ? ' selected' : '';
        var fixedSelected = config.price_type === 'fixed' ? ' selected' : '';
        var isQuotation = config.price_type === 'quotation';
        var markupPercentDisabled = isQuotation ? '' : ' disabled';
        var markupFixedDisabled = isQuotation ? '' : ' disabled';
        var priceDisabled = isQuotation ? ' disabled' : '';
        var markupPercentValue = config.markup_percent || '';
        var markupFixedValue = config.markup_fixed || '';
        var priceValue = config.price || '';

        var html = '<tr data-service-id="' + serviceId + '">';
        html += '<td>' + service.courier_name + '</td>';
        html += '<td>' + service.name + '</td>';
        html += '<td><input type="text" class="form-control form-control-sm name-input" value="' + nameValue + '" placeholder="' + options.translations.text_custom_name_placeholder + '"></td>';
        html += '<td>' + pickupType + '</td>';
        html += '<td>' + deliveryType + '</td>';
        html += '<td><select class="form-select form-select-sm price-type-select">';
        html += '<option value="quotation"' + quotationSelected + '>' + options.translations.text_quotation + '</option>';
        html += '<option value="fixed"' + fixedSelected + '>' + options.translations.text_fixed + '</option>';
        html += '</select></td>';
        html += '<td><input type="text" class="form-control form-control-sm markup-percent-input" value="' + markupPercentValue + '" placeholder="%"' + markupPercentDisabled + '></td>';
        html += '<td><input type="text" class="form-control form-control-sm markup-fixed-input" value="' + markupFixedValue + '" placeholder="0.00"' + markupFixedDisabled + '></td>';
        html += '<td><input type="text" class="form-control form-control-sm price-input" value="' + priceValue + '" placeholder="0.00"' + priceDisabled + '></td>';
        html += '<td><button type="button" class="btn btn-danger btn-sm remove-service"><i class="fa-solid fa-trash"></i></button></td>';
        html += '</tr>';

        $('#services-tbody').append(html);
    }

    /**
     * Update the hidden services input
     */
    function updateServicesInput() {
        $('#input-services').val(JSON.stringify(configuredServices));
    }

    /**
     * Get all services
     *
     * @returns {Object}
     */
    function getAllServices() {
        return allServices;
    }

    /**
     * Get configured services
     *
     * @returns {Object}
     */
    function getConfiguredServices() {
        return configuredServices;
    }

    // Public API
    return {
        init: init,
        getAllServices: getAllServices,
        getConfiguredServices: getConfiguredServices
    };

})(jQuery);

/**
 * Woot Connection Management
 *
 * Handles connect/disconnect functionality
 */
var WootConnection = (function($) {
    'use strict';

    var options = {
        translations: {}
    };

    /**
     * Initialize connection handlers
     *
     * @param {Object} config Configuration object
     */
    function init(config) {
        options = $.extend(options, config);
        bindEvents();
    }

    /**
     * Bind connection events
     */
    function bindEvents() {
        // Connect button
        $('#button-connect').on('click', function() {
            var element = this;
            var url = $(this).data('url');

            $.ajax({
                url: url,
                type: 'post',
                data: $('#form-connect').serialize(),
                dataType: 'json',
                beforeSend: function() {
                    $(element).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> ' + options.translations.text_connecting);
                },
                complete: function() {
                    $(element).prop('disabled', false).html('<i class="fa-solid fa-plug"></i> ' + options.translations.button_connect);
                },
                success: function(json) {
                    if (json['error']) {
                        alert(json['error']);
                    }

                    if (json['success']) {
                        location.reload();
                    }
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                }
            });
        });

        // Disconnect button
        $('#button-disconnect').on('click', function() {
            if (confirm(options.translations.text_confirm_disconnect)) {
                var element = this;
                var url = $(this).data('url');

                $.ajax({
                    url: url,
                    type: 'post',
                    dataType: 'json',
                    beforeSend: function() {
                        $(element).prop('disabled', true);
                    },
                    complete: function() {
                        $(element).prop('disabled', false);
                    },
                    success: function(json) {
                        if (json['error']) {
                            alert(json['error']);
                        }

                        if (json['success']) {
                            location.reload();
                        }
                    },
                    error: function(xhr, ajaxOptions, thrownError) {
                        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
                    }
                });
            }
        });
    }

    // Public API
    return {
        init: init
    };

})(jQuery);

/**
 * Woot Sender Address Management
 *
 * Handles sender/pickup address selection
 */
var WootSenderAddress = (function($) {
    'use strict';

    var allAddresses = {};
    var selectedAddressId = '';
    var options = {
        addressesUrl: '',
        initialAddressId: '',
        translations: {}
    };

    /**
     * Initialize the sender address module
     *
     * @param {Object} config Configuration object
     */
    function init(config) {
        options = $.extend(options, config);
        selectedAddressId = config.initialAddressId || '';

        loadAddresses();
        bindEvents();
    }

    /**
     * Load sender addresses from API
     */
    function loadAddresses() {
        $.ajax({
            url: options.addressesUrl,
            type: 'get',
            dataType: 'json',
            success: function(json) {
                $('#sender-loading').addClass('d-none');

                if (json['error']) {
                    $('#sender-error').removeClass('d-none').text(json['error']);
                    return;
                }

                if (json['addresses']) {
                    processAddresses(json['addresses']);
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                $('#sender-loading').addClass('d-none');
                $('#sender-error').removeClass('d-none').text(thrownError);
            }
        });
    }

    /**
     * Process addresses response and populate select
     *
     * @param {Array} addresses Array of address objects
     */
    function processAddresses(addresses) {
        // Store all addresses for reference
        addresses.forEach(function(address) {
            allAddresses[address.id] = address;
        });

        // Build select options
        var html = '<option value="">' + options.translations.text_select_address + '</option>';
        addresses.forEach(function(address) {
            var label = address.company_name || address.contact || '';
            if (address.city) {
                label += ' - ' + address.city;
            }
            if (address.favorite == 1) {
                label += ' ★';
            }
            var selected = address.id == selectedAddressId ? ' selected' : '';
            html += '<option value="' + address.id + '"' + selected + '>' + label + '</option>';
        });
        $('#select-sender-address').html(html);

        // Show preview if address is selected
        if (selectedAddressId && allAddresses[selectedAddressId]) {
            showAddressPreview(selectedAddressId);
        }

        $('#sender-container').removeClass('d-none');
    }

    /**
     * Bind UI events
     */
    function bindEvents() {
        $('#select-sender-address').on('change', function() {
            var addressId = $(this).val();
            selectedAddressId = addressId;
            $('#input-sender-address').val(addressId);

            if (addressId && allAddresses[addressId]) {
                showAddressPreview(addressId);
            } else {
                $('#sender-address-preview').addClass('d-none');
            }
        });
    }

    /**
     * Show address preview
     *
     * @param {string} addressId Address ID
     */
    function showAddressPreview(addressId) {
        var address = allAddresses[addressId];
        if (!address) return;

        // Company name or contact as title
        var title = address.company_name || address.contact || 'Address #' + address.id;
        $('#sender-company-name').text(title);

        // Country badge
        $('#sender-country-badge').text(address.country || '');

        // Contact
        if (address.contact && address.company_name) {
            $('#sender-contact').text(address.contact);
            $('#sender-contact-row').show();
        } else {
            $('#sender-contact-row').hide();
        }

        // Phone
        if (address.phone) {
            $('#sender-phone').text(address.phone);
            $('#sender-phone-row').show();
        } else {
            $('#sender-phone-row').hide();
        }

        // Email
        if (address.email) {
            $('#sender-email').text(address.email);
            $('#sender-email-row').show();
        } else {
            $('#sender-email-row').hide();
        }

        // Street address
        if (address.address) {
            $('#sender-address').text(address.address);
            $('#sender-address-row').show();
        } else {
            $('#sender-address-row').hide();
        }

        // Location (city, county, zipcode)
        var location = [];
        if (address.city) location.push(address.city);
        if (address.county) location.push(address.county);
        if (address.zipcode) location.push(address.zipcode);
        if (location.length > 0) {
            $('#sender-location').text(location.join(', '));
            $('#sender-location-row').show();
        } else {
            $('#sender-location-row').hide();
        }

        $('#sender-address-preview').removeClass('d-none');
    }

    /**
     * Get selected address ID
     *
     * @returns {string}
     */
    function getSelectedAddressId() {
        return selectedAddressId;
    }

    /**
     * Reload addresses from API
     */
    function reload() {
        // Reset state
        allAddresses = {};

        // Show loading, hide container and error
        $('#sender-loading').removeClass('d-none');
        $('#sender-container').addClass('d-none');
        $('#sender-error').addClass('d-none');
        $('#sender-address-preview').addClass('d-none');

        // Reload from API
        loadAddresses();
    }

    // Public API
    return {
        init: init,
        getSelectedAddressId: getSelectedAddressId,
        reload: reload
    };

})(jQuery);

/**
 * Woot Default Parcel Management
 *
 * Handles default parcel selection
 */
var WootDefaultParcel = (function($) {
    'use strict';

    var allParcels = {};
    var selectedParcelId = '';
    var options = {
        parcelsUrl: '',
        initialParcelId: '',
        translations: {}
    };

    /**
     * Initialize the default parcel module
     *
     * @param {Object} config Configuration object
     */
    function init(config) {
        options = $.extend(options, config);
        selectedParcelId = config.initialParcelId || '';

        loadParcels();
        bindEvents();
    }

    /**
     * Load parcels from API
     */
    function loadParcels() {
        $.ajax({
            url: options.parcelsUrl,
            type: 'get',
            dataType: 'json',
            success: function(json) {
                $('#parcel-loading').addClass('d-none');

                if (json['error']) {
                    $('#parcel-error').removeClass('d-none').text(json['error']);
                    return;
                }

                if (json['parcels']) {
                    processParcels(json['parcels']);
                }
            },
            error: function(xhr, ajaxOptions, thrownError) {
                $('#parcel-loading').addClass('d-none');
                $('#parcel-error').removeClass('d-none').text(thrownError);
            }
        });
    }

    /**
     * Process parcels response and populate select
     *
     * @param {Array} parcels Array of parcel objects
     */
    function processParcels(parcels) {
        // Store all parcels for reference
        parcels.forEach(function(parcel) {
            allParcels[parcel.id] = parcel;
        });

        // Build select options
        var html = '<option value="">' + options.translations.text_select_parcel + '</option>';
        parcels.forEach(function(parcel) {
            var label = parcel.name || 'Parcel #' + parcel.id;
            var typeLabel = parcel.type === 'envelope' ? options.translations.text_envelope : options.translations.text_package;
            label += ' (' + typeLabel + ')';
            if (parcel.favorite == 1) {
                label += ' ★';
            }
            var selected = parcel.id == selectedParcelId ? ' selected' : '';
            html += '<option value="' + parcel.id + '"' + selected + '>' + label + '</option>';
        });
        $('#select-default-parcel').html(html);

        // Show preview if parcel is selected
        if (selectedParcelId && allParcels[selectedParcelId]) {
            showParcelPreview(selectedParcelId);
        }

        $('#parcel-container').removeClass('d-none');
    }

    /**
     * Bind UI events
     */
    function bindEvents() {
        $('#select-default-parcel').on('change', function() {
            var parcelId = $(this).val();
            selectedParcelId = parcelId;
            $('#input-default-parcel').val(parcelId);

            if (parcelId && allParcels[parcelId]) {
                showParcelPreview(parcelId);
            } else {
                $('#parcel-preview').addClass('d-none');
            }
        });
    }

    /**
     * Show parcel preview
     *
     * @param {string} parcelId Parcel ID
     */
    function showParcelPreview(parcelId) {
        var parcel = allParcels[parcelId];
        if (!parcel) return;

        // Parcel name
        $('#parcel-name').text(parcel.name || 'Parcel #' + parcel.id);

        // Type badge
        var typeLabel = parcel.type === 'envelope' ? options.translations.text_envelope : options.translations.text_package;
        $('#parcel-type-badge').text(typeLabel);

        // Dimensions
        if (parcel.length && parcel.width && parcel.height) {
            $('#parcel-dimensions').text(parcel.length + ' x ' + parcel.width + ' x ' + parcel.height + ' cm');
            $('#parcel-dimensions-row').show();
        } else {
            $('#parcel-dimensions').text(options.translations.text_na);
            $('#parcel-dimensions-row').show();
        }

        // Weight
        if (parcel.weight) {
            $('#parcel-weight').text(parcel.weight + ' kg');
            $('#parcel-weight-row').show();
        } else {
            $('#parcel-weight').text(options.translations.text_na);
            $('#parcel-weight-row').show();
        }

        // Content
        if (parcel.content) {
            $('#parcel-content').text(parcel.content);
            $('#parcel-content-row').show();
        } else {
            $('#parcel-content-row').hide();
        }

        // Favorite
        var favoriteLabel = parcel.favorite == 1 ? options.translations.text_yes : options.translations.text_no;
        $('#parcel-favorite').text(favoriteLabel);
        $('#parcel-favorite-row').show();

        $('#parcel-preview').removeClass('d-none');
    }

    /**
     * Get selected parcel ID
     *
     * @returns {string}
     */
    function getSelectedParcelId() {
        return selectedParcelId;
    }

    /**
     * Reload parcels from API
     */
    function reload() {
        // Reset state
        allParcels = {};

        // Show loading, hide container and error
        $('#parcel-loading').removeClass('d-none');
        $('#parcel-container').addClass('d-none');
        $('#parcel-error').addClass('d-none');
        $('#parcel-preview').addClass('d-none');

        // Reload from API
        loadParcels();
    }

    // Public API
    return {
        init: init,
        getSelectedParcelId: getSelectedParcelId,
        reload: reload
    };

})(jQuery);
