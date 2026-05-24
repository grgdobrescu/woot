<?php
// Heading
$_['heading_title_countries']     = 'Woot Țări';
$_['heading_title_counties']      = 'Woot Județe';
$_['heading_title_cities']        = 'Woot Localități';
$_['heading_title_locations']     = 'Woot Puncte de Ridicare';

// Text
$_['text_home']                   = 'Acasă';
$_['text_extension']              = 'Extensii';
$_['text_success']                = 'Succes: Nomenclatorul Woot a fost modificat!';
$_['text_list']                   = 'Lista Nomenclator';
$_['text_no_results']             = 'Nu s-au găsit rezultate.';
$_['text_loading']                = 'Se încarcă...';
$_['text_syncing']                = 'Se sincronizează...';
$_['text_not_synced']             = 'Nesincronizat';
$_['text_synced']                 = 'Sincronizat';
$_['text_not_mapped']             = 'Neasociat';
$_['text_mapped']                 = 'Asociat';
$_['text_select_country']         = '-- Selectați Țara --';
$_['text_select_county']          = '-- Selectați Județul --';
$_['text_all_counties']           = '-- Toate Județele --';
$_['text_select_city']            = '-- Selectați Localitatea --';
$_['text_select_type']            = '-- Toate Tipurile --';
$_['text_all']                    = 'Toate';
$_['text_shop']                   = 'Magazin';
$_['text_locker']                 = 'Easybox';
$_['text_yes']                    = 'Da';
$_['text_no']                     = 'Nu';
$_['text_sync_hint']              = 'Click pe "Sincronizare" pentru a prelua datele de la API-ul Woot.';
$_['text_mapping_hint']           = 'Asociați locațiile Woot cu țările/zonele OpenCart pentru integrarea în checkout.';
$_['text_auto_map_hint']          = 'Asocierea încearcă să potrivească după codul ISO (țări) sau codul/numele zonei (județe).';
$_['text_no_mapped_countries']    = 'Nu există țări asociate. Accesați <a href="%s">Țări</a> și sincronizați & asociați țările Woot cu țările OpenCart.';
$_['text_no_mapped_counties']     = 'Nu există țări cu județe asociate. Accesați <a href="%s">Județe</a> și sincronizați & asociați județele pentru țările asociate.';
$_['text_no_synced_cities']       = 'Nu există țări cu localități sincronizate. Accesați <a href="%s">Localități</a> și sincronizați localitățile pentru țările dvs.';
$_['text_filter']                 = 'Filtrare';

$_['text_sync_countries_success'] = 'Succes: S-au sincronizat %d țări de la API-ul Woot.';
$_['text_sync_counties_success']  = 'Succes: S-au sincronizat %d județe de la API-ul Woot.';
$_['text_sync_cities_success']    = 'Succes: S-au sincronizat %d localități de la API-ul Woot.';
$_['text_sync_locations_success'] = 'Succes: S-au sincronizat %d puncte de ridicare de la API-ul Woot.';
$_['text_mapping_saved']          = 'Succes: Maparea a fost salvată.';
$_['text_auto_map_countries_success'] = 'Succes: S-au asociat automat %d țări.';
$_['text_auto_map_counties_success']  = 'Succes: S-au asociat automat %d județe.';
$_['text_pagination']             = 'Afișare %d - %d din %d (%d Pagini)';

// Column
$_['column_woot_id']              = 'ID Woot';
$_['column_name']                 = 'Nume';
$_['column_code']                 = 'Cod';
$_['column_oc_country']           = 'Țară OpenCart';
$_['column_oc_zone']              = 'Zonă OpenCart';
$_['column_favorite']             = 'Favorit';
$_['column_eu']                   = 'UE';
$_['column_has_counties']         = 'Are Județe';
$_['column_has_cities']           = 'Are Localități';
$_['column_has_locations']        = 'Are Puncte';
$_['column_counties_count']       = 'Județe';
$_['column_cities_count']         = 'Localități';
$_['column_locations_count']      = 'Puncte';
$_['column_date_synced']          = 'Ultima Sincronizare';
$_['column_postal_code']          = 'Cod Poștal';
$_['column_type']                 = 'Tip';
$_['column_courier']              = 'Curier';
$_['column_address']              = 'Adresă';
$_['column_country']              = 'Țară';
$_['column_county']               = 'Județ';
$_['column_city']                 = 'Localitate';
$_['column_action']               = 'Acțiune';

// Button
$_['button_sync']                 = 'Sincronizare';
$_['button_sync_all']             = 'Sincronizare Tot';
$_['button_auto_map']             = 'Asociere automată';
$_['button_save_mapping']         = 'Salvare';
$_['button_filter']               = 'Filtrare';
$_['button_view_counties']        = 'Vezi Județele';
$_['button_view_cities']          = 'Vezi Localitățile';
$_['button_view_locations']       = 'Vezi Punctele';

// Entry
$_['entry_woot_country']          = 'Țară Woot';
$_['entry_woot_county']           = 'Județ Woot';
$_['entry_woot_city']             = 'Localitate Woot';
$_['entry_oc_country']            = 'Țară OpenCart';
$_['entry_oc_zone']               = 'Zonă OpenCart';
$_['entry_type']                  = 'Tip';

// Tab
$_['tab_countries']               = 'Țări';
$_['tab_counties']                = 'Județe';
$_['tab_cities']                  = 'Localități';
$_['tab_locations']               = 'Puncte de Ridicare';

// Help
$_['help_sync']                   = 'Sincronizați datele nomenclatorului de la API-ul Woot.';
$_['help_auto_map']               = 'Asociați automat intrările Woot cu echivalentele OpenCart după cod sau nume.';

// Error
$_['error_permission']            = 'Atenție: Nu aveți permisiunea de a modifica nomenclatorul Woot!';
$_['error_not_connected']         = 'Eroare: API-ul Woot nu este conectat. Configurați cheile API în setările de livrare.';
$_['error_country_required']      = 'Eroare: Țara este obligatorie.';
$_['error_county_required']       = 'Eroare: Județul este obligatoriu.';
$_['error_sync_countries']        = 'Eroare: Nu s-au putut sincroniza țările de la API.';
$_['error_sync_counties']         = 'Eroare: Nu s-au putut sincroniza județele de la API.';
$_['error_sync_cities']           = 'Eroare: Nu s-au putut sincroniza localitățile de la API.';
$_['error_sync_locations']        = 'Eroare: Nu s-au putut sincroniza punctele de ridicare de la API.';
