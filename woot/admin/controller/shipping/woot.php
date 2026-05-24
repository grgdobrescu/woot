<?php
namespace Opencart\Admin\Controller\Extension\Woot\Shipping;

// Load Woot API library (not autoloaded by OpenCart)
require_once(DIR_EXTENSION . 'woot/system/library/woot/api.php');

use Opencart\System\Library\Woot\Api as WootApi;

class Woot extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/woot/shipping/woot');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/woot/shipping/woot', 'user_token=' . $this->session->data['user_token'])
		];

		$data['save'] = $this->url->link('extension/woot/shipping/woot.save', 'user_token=' . $this->session->data['user_token']);
		$data['connect'] = $this->url->link('extension/woot/shipping/woot.connect', 'user_token=' . $this->session->data['user_token']);
		$data['disconnect'] = $this->url->link('extension/woot/shipping/woot.disconnect', 'user_token=' . $this->session->data['user_token']);
		$data['services_url'] = $this->url->link('extension/woot/shipping/woot.services', 'user_token=' . $this->session->data['user_token']);
		$data['sender_addresses_url'] = $this->url->link('extension/woot/shipping/woot.senderAddresses', 'user_token=' . $this->session->data['user_token']);
		$data['parcels_url'] = $this->url->link('extension/woot/shipping/woot.parcels', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping');

		// API Settings
		$data['shipping_woot_public_key'] = $this->config->get('shipping_woot_public_key');
		$data['shipping_woot_secret_key'] = $this->config->get('shipping_woot_secret_key');
		$data['shipping_woot_connected'] = $this->config->get('shipping_woot_connected');

		// Selected Services (must be object/associative array, not indexed array)
		$data['shipping_woot_services'] = $this->config->get('shipping_woot_services');
		if (!is_array($data['shipping_woot_services']) || empty($data['shipping_woot_services'])) {
			$data['shipping_woot_services'] = new \stdClass();
		}

		// Default Sender Address (pickup address)
		$data['shipping_woot_sender_address_id'] = $this->config->get('shipping_woot_sender_address_id');

		// Default Parcel
		$data['shipping_woot_default_parcel_id'] = $this->config->get('shipping_woot_default_parcel_id');

		// Tax Class
		$this->load->model('localisation/tax_class');

		$data['shipping_woot_tax_class_id'] = (int)$this->config->get('shipping_woot_tax_class_id');
		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		// Price Includes VAT setting
		$data['shipping_woot_price_includes_vat'] = (int)$this->config->get('shipping_woot_price_includes_vat');

		// Geo Zone
		$this->load->model('localisation/geo_zone');

		$data['shipping_woot_geo_zone_id'] = $this->config->get('shipping_woot_geo_zone_id');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		// Status & Sort Order
		$data['shipping_woot_status'] = $this->config->get('shipping_woot_status');
		$data['shipping_woot_sort_order'] = $this->config->get('shipping_woot_sort_order');

		// Shipped Notification Settings
		$data['shipping_woot_shipped_status_id'] = $this->config->get('shipping_woot_shipped_status_id');
		$data['shipping_woot_shipped_message'] = $this->config->get('shipping_woot_shipped_message');

		// Order Statuses for dropdown
		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		// Repayment Settings
		$data['shipping_woot_repayment_methods'] = $this->config->get('shipping_woot_repayment_methods') ?? [];

		// Get available payment methods
		$this->load->model('setting/extension');
		$extensions = $this->model_setting_extension->getExtensionsByType('payment');

		// Save current heading_title before loading payment languages
		$saved_heading_title = $this->language->get('heading_title');

		$data['payment_methods'] = [];
		foreach ($extensions as $extension) {
			$this->load->language('extension/' . $extension['extension'] . '/payment/' . $extension['code']);
			// OpenCart 4 stores payment code as "extension.code" in orders
			$full_code = $extension['code'] . '.' . $extension['code'];
			$data['payment_methods'][] = [
				'code' => $full_code,
				'name' => $this->language->get('heading_title')
			];
		}

		// Reload our language file to restore all strings
		$this->load->language('extension/woot/shipping/woot');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/woot/shipping/woot', $data));
	}

	/**
	 * Connect - Test API credentials and save if valid
	 *
	 * @return void
	 */
	public function connect(): void {
		$this->load->language('extension/woot/shipping/woot');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/woot/shipping/woot')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$public_key = $this->request->post['shipping_woot_public_key'] ?? '';
		$secret_key = $this->request->post['shipping_woot_secret_key'] ?? '';

		if (!$public_key) {
			$json['error'] = $this->language->get('error_public_key');
		}

		if (!$secret_key) {
			$json['error'] = $this->language->get('error_secret_key');
		}

		if (!$json) {
			// Test API connection using library
			$api = new WootApi();
			$result = $api->testConnection($public_key, $secret_key);

			if ($result['success']) {
				$this->load->model('setting/setting');

				$this->model_setting_setting->editSetting('shipping_woot', [
					'shipping_woot_public_key' => $public_key,
					'shipping_woot_secret_key' => $secret_key,
					'shipping_woot_connected'  => 1
				]);

				$json['success'] = $this->language->get('text_connect_success');
			} else {
				$json['error'] = $result['error'] ?? $this->language->get('error_connect');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Disconnect - Clear API credentials
	 *
	 * @return void
	 */
	public function disconnect(): void {
		$this->load->language('extension/woot/shipping/woot');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/woot/shipping/woot')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$this->model_setting_setting->editSetting('shipping_woot', [
				'shipping_woot_public_key' => '',
				'shipping_woot_secret_key' => '',
				'shipping_woot_connected'  => 0,
				'shipping_woot_status'     => 0
			]);

			$json['success'] = $this->language->get('text_disconnect_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Get Services from API
	 *
	 * @return void
	 */
	public function services(): void {
		$this->load->language('extension/woot/shipping/woot');

		$json = [];

		if (!$this->user->hasPermission('access', 'extension/woot/shipping/woot')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$public_key = $this->config->get('shipping_woot_public_key');
			$secret_key = $this->config->get('shipping_woot_secret_key');

			$api = new WootApi($public_key, $secret_key);
			$services = $api->getServices();

			if ($services !== false) {
				$json['services'] = $services;
			} else {
				$json['error'] = $this->language->get('error_services');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Get Sender Addresses from API
	 *
	 * @return void
	 */
	public function senderAddresses(): void {
		$this->load->language('extension/woot/shipping/woot');

		$json = [];

		if (!$this->user->hasPermission('access', 'extension/woot/shipping/woot')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$public_key = $this->config->get('shipping_woot_public_key');
			$secret_key = $this->config->get('shipping_woot_secret_key');

			$api = new WootApi($public_key, $secret_key);
			$addresses = $api->getSenderAddresses();

			if ($addresses !== false && isset($addresses['list'])) {
				$json['addresses'] = $addresses['list'];
			} elseif ($addresses !== false) {
				$json['addresses'] = $addresses;
			} else {
				$json['error'] = $this->language->get('error_sender_addresses');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Get Parcels from API
	 *
	 * @return void
	 */
	public function parcels(): void {
		$this->load->language('extension/woot/shipping/woot');

		$json = [];

		if (!$this->user->hasPermission('access', 'extension/woot/shipping/woot')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$public_key = $this->config->get('shipping_woot_public_key');
			$secret_key = $this->config->get('shipping_woot_secret_key');

			$api = new WootApi($public_key, $secret_key);
			$parcels = $api->getParcels();

			if ($parcels !== false && isset($parcels['list'])) {
				$json['parcels'] = $parcels['list'];
			} elseif ($parcels !== false) {
				$json['parcels'] = $parcels;
			} else {
				$json['error'] = $this->language->get('error_parcels');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/woot/shipping/woot');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/woot/shipping/woot')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!isset($this->request->post['shipping_woot_sender_address_id']) || $this->request->post['shipping_woot_sender_address_id'] === '') {
			$json['error'] = $this->language->get('error_sender_address');
		}

		if (!isset($this->request->post['shipping_woot_default_parcel_id']) || $this->request->post['shipping_woot_default_parcel_id'] === '') {
			$json['error'] = $this->language->get('error_default_parcel');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			// Preserve connection settings
			$this->request->post['shipping_woot_public_key'] = $this->config->get('shipping_woot_public_key');
			$this->request->post['shipping_woot_secret_key'] = $this->config->get('shipping_woot_secret_key');
			$this->request->post['shipping_woot_connected'] = $this->config->get('shipping_woot_connected');

			// Decode services JSON (object with service configs)
			// Note: OpenCart HTML-encodes POST data, so we need to decode it first
			if (isset($this->request->post['shipping_woot_services'])) {
				$servicesRaw = html_entity_decode($this->request->post['shipping_woot_services']);
				$services = json_decode($servicesRaw, true);
				$this->request->post['shipping_woot_services'] = is_array($services) ? $services : [];
			} else {
				$this->request->post['shipping_woot_services'] = [];
			}

			$this->model_setting_setting->editSetting('shipping_woot', $this->request->post);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Install
	 *
	 * @return void
	 */
	public function install(): void {
		// Get collation from existing OpenCart table to ensure compatibility
		$collation_query = $this->db->query("SHOW TABLE STATUS WHERE Name = '" . DB_PREFIX . "country'");
		$collation = 'utf8mb4_unicode_ci'; // Default fallback

		if ($collation_query->num_rows && isset($collation_query->row['Collation'])) {
			$collation = $collation_query->row['Collation'];
		}

		// Extract charset from collation (e.g., utf8mb4_unicode_ci -> utf8mb4)
		$charset = explode('_', $collation)[0];

		// Create nomenclature tables with same collation as OpenCart
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "woot_country` (
				`woot_country_id` INT(11) NOT NULL,
				`name` VARCHAR(128) NOT NULL,
				`code` VARCHAR(3) NOT NULL,
				`favorite` TINYINT(1) NOT NULL DEFAULT 0,
				`sort` INT(11) NOT NULL DEFAULT 0,
				`eu` TINYINT(1) NOT NULL DEFAULT 0,
				`has_counties` TINYINT(1) NOT NULL DEFAULT 0,
				`has_cities` TINYINT(1) NOT NULL DEFAULT 0,
				`has_locations` TINYINT(1) NOT NULL DEFAULT 0,
				`counties_count` INT(11) NOT NULL DEFAULT 0,
				`cities_count` INT(11) NOT NULL DEFAULT 0,
				`locations_count` INT(11) NOT NULL DEFAULT 0,
				`oc_country_id` INT(11) DEFAULT NULL,
				`date_synced` DATETIME NOT NULL,
				PRIMARY KEY (`woot_country_id`),
				KEY `oc_country_id` (`oc_country_id`),
				KEY `code` (`code`)
			) ENGINE=InnoDB DEFAULT CHARSET=" . $charset . " COLLATE=" . $collation . "
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "woot_county` (
				`woot_county_id` INT(11) NOT NULL,
				`woot_country_id` INT(11) NOT NULL,
				`name` VARCHAR(128) NOT NULL,
				`code` VARCHAR(10) NOT NULL,
				`oc_zone_id` INT(11) DEFAULT NULL,
				`date_synced` DATETIME NOT NULL,
				PRIMARY KEY (`woot_county_id`),
				KEY `woot_country_id` (`woot_country_id`),
				KEY `oc_zone_id` (`oc_zone_id`),
				KEY `code` (`code`)
			) ENGINE=InnoDB DEFAULT CHARSET=" . $charset . " COLLATE=" . $collation . "
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "woot_city` (
				`woot_city_id` INT(11) NOT NULL,
				`woot_county_id` INT(11) NOT NULL,
				`name` VARCHAR(128) NOT NULL,
				`date_synced` DATETIME NOT NULL,
				PRIMARY KEY (`woot_city_id`),
				KEY `woot_county_id` (`woot_county_id`),
				KEY `name` (`name`)
			) ENGINE=InnoDB DEFAULT CHARSET=" . $charset . " COLLATE=" . $collation . "
		");

		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "woot_location` (
				`woot_location_id` INT(11) NOT NULL,
				`name` VARCHAR(255) NOT NULL,
				`type` ENUM('shop', 'locker') NOT NULL DEFAULT 'locker',
				`courier_id` INT(11) DEFAULT NULL,
				`courier_uid` VARCHAR(64) DEFAULT NULL,
				`courier_name` VARCHAR(128) DEFAULT NULL,
				`woot_country_id` INT(11) NOT NULL,
				`woot_county_id` INT(11) DEFAULT NULL,
				`woot_city_id` INT(11) DEFAULT NULL,
				`address` VARCHAR(255) DEFAULT NULL,
				`zipcode` VARCHAR(20) DEFAULT NULL,
				`latitude` DECIMAL(10, 8) DEFAULT NULL,
				`longitude` DECIMAL(11, 8) DEFAULT NULL,
				`repayments_card` TINYINT(1) NOT NULL DEFAULT 0,
				`repayments_cash` TINYINT(1) NOT NULL DEFAULT 0,
				`sender` TINYINT(1) NOT NULL DEFAULT 0,
				`receiver` TINYINT(1) NOT NULL DEFAULT 0,
				`date_synced` DATETIME NOT NULL,
				PRIMARY KEY (`woot_location_id`),
				KEY `woot_country_id` (`woot_country_id`),
				KEY `woot_county_id` (`woot_county_id`),
				KEY `woot_city_id` (`woot_city_id`),
				KEY `type` (`type`)
			) ENGINE=InnoDB DEFAULT CHARSET=" . $charset . " COLLATE=" . $collation . "
		");

		// Create woot_order table to store woot-specific order data
		// Stores:
		// - woot_location_id: pickup point location (for locker/shop delivery)
		// - courier/service info: for tracking URLs and email display
		// - awb: tracking number, status, and woot shipment ID
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "woot_order` (
				`woot_order_id` INT(11) NOT NULL AUTO_INCREMENT,
				`order_id` INT(11) NOT NULL,
				`woot_location_id` INT(11) DEFAULT NULL,
				`courier_id` INT(11) DEFAULT NULL,
				`courier_uid` VARCHAR(64) DEFAULT NULL,
				`courier_name` VARCHAR(128) DEFAULT NULL,
				`service_id` INT(11) DEFAULT NULL,
				`service_uid` VARCHAR(64) DEFAULT NULL,
				`service_name` VARCHAR(128) DEFAULT NULL,
				`woot_shipment_id` INT(11) DEFAULT NULL,
				`awb_number` VARCHAR(64) DEFAULT NULL,
				`awb_status` VARCHAR(64) DEFAULT NULL,
				`date_added` DATETIME NOT NULL,
				`date_modified` DATETIME NOT NULL,
				PRIMARY KEY (`woot_order_id`),
				UNIQUE KEY `order_id` (`order_id`),
				KEY `woot_location_id` (`woot_location_id`),
				KEY `awb_number` (`awb_number`)
			) ENGINE=InnoDB DEFAULT CHARSET=" . $charset . " COLLATE=" . $collation . "
		");

		// Register events
		$this->load->model('setting/event');

		// Event: Inject city dropdown into checkout register form (guest/new registration)
		$this->model_setting_event->addEvent([
			'code'        => 'woot_checkout_register_city',
			'description' => 'Woot: Inject city dropdown into checkout register form',
			'trigger'     => 'catalog/view/checkout/register/after',
			'action'      => 'extension/woot/woot/checkout.injectCityDropdown',
			'status'      => 1,
			'sort_order'  => 0
		]);

		// Event: Inject city dropdown into shipping address form (logged-in users)
		$this->model_setting_event->addEvent([
			'code'        => 'woot_shipping_address_city',
			'description' => 'Woot: Inject city dropdown into checkout shipping address',
			'trigger'     => 'catalog/view/checkout/shipping_address/after',
			'action'      => 'extension/woot/woot/checkout.injectCityDropdown',
			'status'      => 1,
			'sort_order'  => 0
		]);

		// Event: Capture city selection on shipping address save
		$this->model_setting_event->addEvent([
			'code'        => 'woot_shipping_address_save',
			'description' => 'Woot: Capture city selection when shipping address is saved',
			'trigger'     => 'catalog/controller/checkout/shipping_address.save/after',
			'action'      => 'extension/woot/woot/checkout.captureCity',
			'status'      => 1,
			'sort_order'  => 0
		]);

		// Event: Inject location picker into shipping method selection
		$this->model_setting_event->addEvent([
			'code'        => 'woot_shipping_method_location',
			'description' => 'Woot: Inject location picker for delivery-to-location services',
			'trigger'     => 'catalog/view/checkout/shipping_method/after',
			'action'      => 'extension/woot/woot/checkout.injectLocationPicker',
			'status'      => 1,
			'sort_order'  => 0
		]);

		// Event: Validate location after shipping method is saved (undo if invalid)
		$this->model_setting_event->addEvent([
			'code'        => 'woot_shipping_method_validate',
			'description' => 'Woot: Validate location selection after shipping method is saved',
			'trigger'     => 'catalog/controller/checkout/shipping_method.save/after',
			'action'      => 'extension/woot/woot/checkout.validateShippingMethod',
			'status'      => 1,
			'sort_order'  => 0
		]);

		// Event: Validate location on confirm page view
		$this->model_setting_event->addEvent([
			'code'        => 'woot_order_confirm_validate',
			'description' => 'Woot: Validate location on order confirmation page',
			'trigger'     => 'catalog/view/checkout/confirm/after',
			'action'      => 'extension/woot/woot/checkout.validateOrderConfirm',
			'status'      => 1,
			'sort_order'  => 0
		]);

		// Event: Save woot order data (location, city) when order is placed
		$this->model_setting_event->addEvent([
			'code'        => 'woot_order_add',
			'description' => 'Woot: Save location and city data when order is created',
			'trigger'     => 'catalog/model/checkout/order.addOrder/after',
			'action'      => 'extension/woot/woot/checkout.saveOrderWoot',
			'status'      => 1,
			'sort_order'  => 0
		]);

		// Event: Add Woot PRO menu to admin sidebar
		$this->model_setting_event->addEvent([
			'code'        => 'woot_admin_menu',
			'description' => 'Woot: Add Woot PRO menu to admin sidebar',
			'trigger'     => 'admin/view/common/column_left/before',
			'action'      => 'extension/woot/startup/woot',
			'status'      => 1,
			'sort_order'  => 0
		]);

		// Event: Display Woot shipping card on order info page
		$this->model_setting_event->addEvent([
			'code'        => 'woot_order_info_card',
			'description' => 'Woot: Display shipping card on order info page',
			'trigger'     => 'admin/view/sale/order_info/after',
			'action'      => 'extension/woot/woot/order.injectCard',
			'status'      => 1,
			'sort_order'  => 0
		]);

		// Add user permissions for new controllers
		$this->load->model('user/user_group');

		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/woot/woot/settings');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/woot/woot/settings');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/woot/woot/nomenclature');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/woot/woot/nomenclature');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/woot/startup/woot');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/woot/startup/woot');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/woot/woot/order');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/woot/woot/order');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/woot/woot/awb');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/woot/woot/awb');
	}

	/**
	 * Uninstall
	 *
	 * @return void
	 */
	public function uninstall(): void {
		// Drop nomenclature tables
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "woot_location`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "woot_city`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "woot_county`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "woot_country`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "woot_order`");

		// Remove events
		$this->load->model('setting/event');

		$this->model_setting_event->deleteEventByCode('woot_checkout_register_city');
		$this->model_setting_event->deleteEventByCode('woot_shipping_address_city');
		$this->model_setting_event->deleteEventByCode('woot_shipping_address_save');
		$this->model_setting_event->deleteEventByCode('woot_shipping_method_location');
		$this->model_setting_event->deleteEventByCode('woot_shipping_method_validate');
		$this->model_setting_event->deleteEventByCode('woot_order_confirm_validate');
		$this->model_setting_event->deleteEventByCode('woot_order_add');
		$this->model_setting_event->deleteEventByCode('woot_admin_menu');
		$this->model_setting_event->deleteEventByCode('woot_order_info_card');
	}
}
