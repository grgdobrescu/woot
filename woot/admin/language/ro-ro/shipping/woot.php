<?php
// Heading
$_['heading_title']              = 'Woot Shipping';

// Menu
$_['text_shipping_settings']     = 'Setări livrare';
$_['text_countries']             = 'Țări';
$_['text_counties']              = 'Județe';
$_['text_cities']                = 'Localități';
$_['text_locations']             = 'Puncte de livrare';

// Text
$_['text_extension']             = 'Extensii';
$_['text_success']               = 'Succes: Setările Woot au fost modificate!';
$_['text_edit']                  = 'Setări livrare';
$_['text_connect']               = 'Conectare la Woot';
$_['text_connected']             = 'Conectat';
$_['text_connected_as']          = 'Conectat cu cheia publică: %s';
$_['text_connecting']            = 'Se conectează...';
$_['text_connect_success']       = 'Succes: Conectat la API-ul Woot!';
$_['text_disconnect_success']    = 'Succes: Deconectat de la API-ul Woot!';
$_['text_confirm_disconnect']    = 'Sigur doriți să vă deconectați?';
$_['text_shipping_settings']     = 'Setări livrare';
$_['text_general_settings']      = 'Setări generale';
$_['text_services']              = 'Servicii disponibile';
$_['text_loading_services']      = 'Se încarcă serviciile disponibile...';
$_['text_select_service']        = '-- Selectați un serviciu --';
$_['text_delivery_home']         = 'Livrare la adresă';
$_['text_delivery_locker']       = 'Livrare la locker / Punct de livrare';
$_['text_no_services_selected']  = 'Niciun serviciu configurat. Selectați un serviciu din listele de mai sus.';
$_['text_service']               = 'Serviciu';
$_['text_courier']               = 'Curier';
$_['text_custom_name']           = 'Nume afișat';
$_['text_custom_name_placeholder'] = 'Numele afișat în checkout';
$_['text_pickup']                = 'Ridicare';
$_['text_delivery']              = 'Livrare';
$_['text_price_type']            = 'Tip preț';
$_['text_markup_percent']        = 'Adaos %';
$_['text_markup_fixed']          = 'Adaos fix';
$_['text_price']                 = 'Preț';
$_['text_quotation']             = 'Cotație';
$_['text_fixed']                 = 'Fix';
$_['text_door']                  = 'Ușă';
$_['text_locker']                = 'Easybox';
$_['text_none']                  = 'Niciunul';
$_['text_all_zones']             = 'Toate zonele';
$_['text_sender_address']        = 'Adresă de ridicare';
$_['text_loading_addresses']     = 'Se încarcă adresele...';
$_['text_select_address']        = '-- Selectați adresa de ridicare --';
$_['text_default_parcel']        = 'Colet favorit';
$_['text_loading_parcels']       = 'Se încarcă coletele...';
$_['text_select_parcel']         = '-- Selectați coletul favorit --';
$_['text_envelope']              = 'Plic';
$_['text_package']               = 'Colet';
$_['text_yes']                   = 'Da';
$_['text_no']                    = 'Nu';
$_['text_na']                    = 'N/A';
$_['text_shipped_settings']      = 'Notificare Expediere';
$_['text_shipped_message_placeholder'] = 'Coletul a fost expediat cu {courier_name}.
AWB: {awb}
Urmarire: {tracking_url}';
$_['text_repayment_settings']    = 'Setari Ramburs';

// Entry
$_['entry_public_key']           = 'Cheie publică';
$_['entry_secret_key']           = 'Cheie secretă';
$_['entry_tax_class']            = 'Clasă Taxă';
$_['entry_price_includes_vat']   = 'Prețurile includ TVA';
$_['entry_geo_zone']             = 'Zonă geografică';
$_['entry_status']               = 'Status';
$_['entry_sort_order']           = 'Ordine Sortare';
$_['entry_sender_address']       = 'Adresă favorită';
$_['entry_default_parcel']       = 'Colet favorit';
$_['entry_shipped_status']       = 'Status Comanda dupa AWB';
$_['entry_shipped_message']      = 'Mesaj Notificare';
$_['entry_repayment_methods']    = 'Metode de plata';

// Button
$_['button_connect']             = 'Conectare';
$_['button_disconnect']          = 'Deconectare';
$_['button_reload']              = 'Reîncarcă';
$_['button_manage']              = 'Gestionează';

// Help
$_['help_public_key']            = 'Introduceți cheia publică API Woot.';
$_['help_secret_key']            = 'Introduceți cheia secretă API Woot.';
$_['help_sender_address']        = 'Adresa folosită pentru expeditor la generarea AWB.';
$_['help_price_includes_vat']    = 'Activați dacă prețurile API includ deja TVA. Când este activat, setarea clasei de taxă este ignorată și prețul total din API este folosit direct.';
$_['help_default_parcel']        = 'Coletul folosit atunci când nu există dimeniunile pe produs la generarea AWB.';
$_['help_shipped_status']        = 'Statusul comenzii setat automat dupa generarea AWB.';
$_['help_shipped_message']       = 'Disponibile: {courier_name}, {awb}, {tracking_url}';
$_['help_repayment_methods']     = 'Selectati metodele de plata care necesita ramburs (ex: Plata la livrare). Totalul comenzii va fi trimis la curier.';

// Error
$_['error_permission']           = 'Atenție: Nu aveți permisiunea de a modifica setările Woot!';
$_['error_public_key']           = 'Cheia Publică este obligatorie!';
$_['error_secret_key']           = 'Cheia Secretă este obligatorie!';
$_['error_connect']              = 'Eroare: Nu s-a putut conecta la API-ul Woot. Verificați credențialele.';
$_['error_services']             = 'Eroare: Nu s-au putut încărca serviciile de la API-ul Woot.';
$_['error_sender_addresses']     = 'Eroare: Nu s-au putut încărca adresele de expeditor de la API-ul Woot.';
$_['error_sender_address']       = 'Adresa de Ridicare este obligatorie!';
$_['error_parcels']              = 'Eroare: Nu s-au putut încărca coletele de la API-ul Woot.';
$_['error_default_parcel']       = 'Coletul Implicit este obligatoriu!';
$_['error_token']                = 'Eroare: Nu s-a putut autentifica la API-ul Woot.';
