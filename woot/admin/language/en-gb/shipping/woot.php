<?php
// Heading
$_['heading_title']              = 'Woot Shipping';

// Menu
$_['text_shipping_settings']     = 'Shipping Settings';
$_['text_countries']             = 'Countries';
$_['text_counties']              = 'Counties';
$_['text_cities']                = 'Cities';
$_['text_locations']             = 'Locations';

// Text
$_['text_extension']             = 'Extensions';
$_['text_success']               = 'Success: You have modified Woot shipping settings!';
$_['text_edit']                  = 'Shipping Settings';
$_['text_connect']               = 'Connect to Woot';
$_['text_connected']             = 'Connected';
$_['text_connected_as']          = 'Connected with public key: %s';
$_['text_connecting']            = 'Connecting...';
$_['text_connect_success']       = 'Success: Connected to Woot API!';
$_['text_disconnect_success']    = 'Success: Disconnected from Woot API!';
$_['text_confirm_disconnect']    = 'Are you sure you want to disconnect?';
$_['text_shipping_settings']     = 'Shipping Settings';
$_['text_general_settings']      = 'General Settings';
$_['text_services']              = 'Available Services';
$_['text_loading_services']      = 'Loading available services...';
$_['text_select_service']        = '-- Select a service --';
$_['text_delivery_home']         = 'Delivery to Address';
$_['text_delivery_locker']       = 'Delivery to Locker / Pickup Point';
$_['text_no_services_selected']  = 'No services configured. Select a service from the dropdowns above.';
$_['text_service']               = 'Service';
$_['text_courier']               = 'Courier';
$_['text_custom_name']           = 'Display Name';
$_['text_custom_name_placeholder'] = 'Name shown in checkout';
$_['text_pickup']                = 'Pickup';
$_['text_delivery']              = 'Delivery';
$_['text_price_type']            = 'Price Type';
$_['text_markup_percent']        = 'Markup %';
$_['text_markup_fixed']          = 'Markup Fixed';
$_['text_price']                 = 'Price';
$_['text_quotation']             = 'Quotation';
$_['text_fixed']                 = 'Fixed';
$_['text_door']                  = 'Door';
$_['text_locker']                = 'Locker';
$_['text_none']                  = 'None';
$_['text_all_zones']             = 'All Zones';
$_['text_sender_address']        = 'Pickup Address';
$_['text_loading_addresses']     = 'Loading addresses...';
$_['text_select_address']        = '-- Select pickup address --';
$_['text_default_parcel']        = 'Default Parcel';
$_['text_loading_parcels']       = 'Loading parcels...';
$_['text_select_parcel']         = '-- Select default parcel --';
$_['text_envelope']              = 'Envelope';
$_['text_package']               = 'Package';
$_['text_yes']                   = 'Yes';
$_['text_no']                    = 'No';
$_['text_na']                    = 'N/A';
$_['text_shipped_settings']      = 'Shipped Notification';
$_['text_shipped_message_placeholder'] = 'Your package has been shipped with {courier_name}.
AWB: {awb}
Tracking: {tracking_url}';
$_['text_repayment_settings']    = 'Repayment (COD) Settings';

// Entry
$_['entry_public_key']           = 'Public Key';
$_['entry_secret_key']           = 'Secret Key';
$_['entry_tax_class']            = 'Tax Class';
$_['entry_price_includes_vat']   = 'Prices Include VAT';
$_['entry_geo_zone']             = 'Geo Zone';
$_['entry_status']               = 'Status';
$_['entry_sort_order']           = 'Sort Order';
$_['entry_sender_address']       = 'Default Pickup Address';
$_['entry_default_parcel']       = 'Default Parcel';
$_['entry_shipped_status']       = 'Order Status After AWB';
$_['entry_shipped_message']      = 'Notification Message';
$_['entry_repayment_methods']    = 'Payment Methods';

// Button
$_['button_connect']             = 'Connect';
$_['button_disconnect']          = 'Disconnect';
$_['button_reload']              = 'Reload';
$_['button_manage']              = 'Manage';

// Help
$_['help_public_key']            = 'Enter your Woot API public key.';
$_['help_secret_key']            = 'Enter your Woot API secret key.';
$_['help_sender_address']        = 'Select the default address for package pickup.';
$_['help_price_includes_vat']    = 'Enable if API prices already include VAT. When enabled, the tax class setting is ignored and the total price from API is used directly.';
$_['help_default_parcel']        = 'Select the default parcel configuration for shipments.';
$_['help_shipped_status']        = 'Order status to set automatically after AWB generation.';
$_['help_shipped_message']       = 'Available: {courier_name}, {awb}, {tracking_url}';
$_['help_repayment_methods']     = 'Select payment methods that require repayment (e.g., Cash on Delivery). Order total will be sent to courier.';

// Error
$_['error_permission']           = 'Warning: You do not have permission to modify Woot shipping!';
$_['error_public_key']           = 'Public Key is required!';
$_['error_secret_key']           = 'Secret Key is required!';
$_['error_connect']              = 'Error: Could not connect to Woot API. Please check your credentials.';
$_['error_services']             = 'Error: Could not load services from Woot API.';
$_['error_sender_addresses']     = 'Error: Could not load sender addresses from Woot API.';
$_['error_sender_address']       = 'Pickup Address is required!';
$_['error_parcels']              = 'Error: Could not load parcels from Woot API.';
$_['error_default_parcel']       = 'Default Parcel is required!';
$_['error_token']                = 'Error: Could not authenticate with Woot API.';
