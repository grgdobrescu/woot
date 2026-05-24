<?php
namespace Opencart\Catalog\Controller\Extension\Woot\Woot;

/**
 * Woot Checkout Controller
 *
 * Handles event-driven integration with checkout for city selection.
 */
class Checkout extends \Opencart\System\Engine\Controller {
	/**
	 * Event handler: Inject city dropdown script into shipping address template
	 *
	 * Triggered by: catalog/view/checkout/shipping_address/after
	 *
	 * @param string $route
	 * @param array $args
	 * @param mixed $output
	 * @return void
	 */
	public function injectCityDropdown(string &$route, array &$args, mixed &$output): void {
		// Debug: always inject a marker to see if event fires
		$output .= '<!-- WOOT CITY EVENT FIRED -->';

		// Only inject if Woot shipping is enabled
		if (!$this->config->get('shipping_woot_status')) {
			$output .= '<!-- WOOT SHIPPING NOT ENABLED -->';
			return;
		}

		// Load language
		$this->load->language('extension/woot/woot/city');

		// Prepare data for the script template
		$data['get_cities_url'] = $this->url->link('extension/woot/woot/city.getCities', 'language=' . $this->config->get('config_language'));
		$data['has_woot_cities_url'] = $this->url->link('extension/woot/woot/city.hasWootCities', 'language=' . $this->config->get('config_language'));

		// Language strings for JavaScript
		$data['text_select_city'] = $this->language->get('text_select_city');
		$data['text_loading'] = $this->language->get('text_loading');
		$data['text_no_cities'] = $this->language->get('text_no_cities');

		// Get current session woot_city_id if set
		$data['woot_city_id'] = isset($this->session->data['woot_city_id']) ? (int)$this->session->data['woot_city_id'] : 0;

		// Render the script template
		$script = $this->load->view('extension/woot/woot/checkout_city_script', $data);

		// Append script to output
		$output .= $script;
	}

	/**
	 * Event handler: Inject location picker into shipping method template
	 *
	 * Triggered by: catalog/view/checkout/shipping_method/after
	 *
	 * @param string $route
	 * @param array $args
	 * @param mixed $output
	 * @return void
	 */
	public function injectLocationPicker(string &$route, array &$args, mixed &$output): void {
		// Only inject if Woot shipping is enabled
		if (!$this->config->get('shipping_woot_status')) {
			return;
		}

		// Load language
		$this->load->language('extension/woot/woot/location');

		// Prepare data for the template
		$data['locations_map_url'] = 'https://pro.woot.ro/locations.html';
		$data['save_location_url'] = $this->url->link('extension/woot/woot/checkout.saveLocation', 'language=' . $this->config->get('config_language'));

		// Language strings
		$data['text_select_location'] = $this->language->get('text_select_location');
		$data['button_select_location'] = $this->language->get('button_select_location');
		$data['button_change'] = $this->language->get('button_change');
		$data['button_close'] = $this->language->get('button_close');
		$data['text_location_required'] = $this->language->get('text_location_required');

		// Get existing location from session
		$data['woot_location_id'] = $this->session->data['woot_location']['id'] ?? '';
		$data['woot_location_name'] = $this->session->data['woot_location']['name'] ?? '';
		$data['woot_location_address'] = $this->session->data['woot_location']['address'] ?? '';
		$data['woot_location_city'] = $this->session->data['woot_location']['city'] ?? '';
		$data['woot_location_county'] = $this->session->data['woot_location']['county'] ?? '';
		$data['woot_location_courier'] = $this->session->data['woot_location']['courier'] ?? '';

		// Render the location picker template
		$location_picker = $this->load->view('extension/woot/woot/location_picker', $data);

		// Append to output
		$output .= $location_picker;

		// Also inject shipping methods data with delivery_type
		$output .= $this->getShippingMethodsScript();
	}

	/**
	 * Generate script with shipping methods data including delivery_type and courier_id
	 *
	 * @return string
	 */
	protected function getShippingMethodsScript(): string {
		$methods_data = [];

		// Get configured services from settings
		$configured_services = $this->config->get('shipping_woot_services');

		if (is_array($configured_services)) {
			foreach ($configured_services as $service_id => $service_config) {
				$methods_data['woot_' . $service_id] = [
					'delivery_type' => $service_config['delivery'] ?? 'door',
					'courier_id' => $service_config['courier_id'] ?? ''
				];
			}
		}

		return '<script>window.wootShippingMethods = ' . json_encode($methods_data) . ';</script>';
	}

	/**
	 * Save selected location to session
	 *
	 * @return void
	 */
	public function saveLocation(): void {
		$json = [];

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			$this->session->data['woot_location'] = [
				'id'      => $this->request->post['location_id'] ?? '',
				'name'    => $this->request->post['location_name'] ?? '',
				'address' => $this->request->post['location_address'] ?? '',
				'city'    => $this->request->post['location_city'] ?? '',
				'county'  => $this->request->post['location_county'] ?? '',
				'courier' => $this->request->post['location_courier'] ?? ''
			];

			$json['success'] = true;
		} else {
			$json['error'] = 'Invalid request';
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Check if current shipping method requires location selection
	 *
	 * @return bool
	 */
	protected function isLocationRequired(): bool {
		if (!isset($this->session->data['shipping_method']['code'])) {
			return false;
		}

		$code = $this->session->data['shipping_method']['code'];

		// Check if it's a woot shipping method
		if (strpos($code, 'woot.woot_') !== 0) {
			return false;
		}

		// Extract service ID
		$service_id = str_replace('woot.woot_', '', $code);

		// Get configured services
		$configured_services = $this->config->get('shipping_woot_services');

		if (is_array($configured_services) && isset($configured_services[$service_id])) {
			$delivery_type = $configured_services[$service_id]['delivery'] ?? 'door';
			return $delivery_type === 'location';
		}

		return false;
	}

	/**
	 * Check if a location has been selected
	 *
	 * @return bool
	 */
	protected function hasLocationSelected(): bool {
		return !empty($this->session->data['woot_location']['id']);
	}

	/**
	 * Event handler: Save woot order data when order is created
	 *
	 * Triggered by: catalog/model/checkout/order.addOrder/after
	 *
	 * @param string $route
	 * @param array $args
	 * @param mixed $output The order_id returned by addOrder
	 * @return void
	 */
	public function saveOrderWoot(string &$route, array &$args, mixed &$output): void {
		// Only save if Woot shipping is enabled
		if (!$this->config->get('shipping_woot_status')) {
			return;
		}

		// $output contains the order_id returned by addOrder
		$order_id = (int)$output;

		if (!$order_id) {
			return;
		}

		// Get shipping method code from session
		$shipping_code = '';
		if (isset($this->session->data['shipping_method']['code'])) {
			$shipping_code = $this->session->data['shipping_method']['code'];
		}

		// Only save for woot shipping methods
		if (strpos($shipping_code, 'woot.woot_') !== 0) {
			return;
		}

		// Extract service_id from code (woot.woot_123 -> 123)
		$service_id = str_replace('woot.woot_', '', $shipping_code);

		// Get service config from settings
		$services = $this->config->get('shipping_woot_services');
		$service_config = is_array($services) && isset($services[$service_id]) ? $services[$service_id] : [];

		// Get location ID from session (for pickup point orders)
		$woot_location_id = $this->session->data['woot_location']['id'] ?? null;

		// Insert woot order data with courier/service info
		$this->db->query("
			INSERT INTO `" . DB_PREFIX . "woot_order` SET
				`order_id` = '" . (int)$order_id . "',
				`woot_location_id` = " . ($woot_location_id ? "'" . (int)$woot_location_id . "'" : "NULL") . ",
				`courier_id` = " . (!empty($service_config['courier_id']) ? "'" . (int)$service_config['courier_id'] . "'" : "NULL") . ",
				`courier_uid` = " . (!empty($service_config['courier_uid']) ? "'" . $this->db->escape($service_config['courier_uid']) . "'" : "NULL") . ",
				`courier_name` = " . (!empty($service_config['courier_name']) ? "'" . $this->db->escape($service_config['courier_name']) . "'" : "NULL") . ",
				`service_id` = '" . (int)$service_id . "',
				`service_uid` = " . (!empty($service_config['service_uid']) ? "'" . $this->db->escape($service_config['service_uid']) . "'" : "NULL") . ",
				`service_name` = " . (!empty($service_config['service_name']) ? "'" . $this->db->escape($service_config['service_name']) . "'" : "NULL") . ",
				`date_added` = NOW(),
				`date_modified` = NOW()
		");

		// Clear location session data after order is placed
		unset($this->session->data['woot_location']);
		unset($this->session->data['woot_city_id']);
		unset($this->session->data['woot_county_id']);
		unset($this->session->data['woot_country_id']);
	}

	/**
	 * Event handler: Validate location selection AFTER shipping method is saved
	 * If validation fails, undo the save and return error
	 *
	 * Triggered by: catalog/controller/checkout/shipping_method.save/after
	 *
	 * @param string $route
	 * @param array $args
	 * @param mixed $output
	 * @return void
	 */
	public function validateShippingMethod(string &$route, array &$args, mixed &$output): void {
		// Only validate if Woot shipping is enabled
		if (!$this->config->get('shipping_woot_status')) {
			return;
		}

		// Check if output is valid JSON string
		if (empty($output) || !is_string($output)) {
			return;
		}

		// Decode current response
		$json = json_decode($output, true);

		// If there's already an error, don't process
		if (!is_array($json) || isset($json['error'])) {
			return;
		}

		// Only validate if save was successful
		if (!isset($json['success'])) {
			return;
		}

		// Check if location is required but not selected
		if ($this->isLocationRequired() && !$this->hasLocationSelected()) {
			$this->load->language('extension/woot/woot/location');

			// Undo the shipping method save
			unset($this->session->data['shipping_method']);

			// Return error
			$json = ['error' => $this->language->get('text_location_required')];
			$output = json_encode($json);
		}
	}

	/**
	 * Event handler: Validate location before order confirmation
	 *
	 * Triggered by: catalog/controller/checkout/confirm.confirm/before
	 *
	 * @param string $route
	 * @param array $args
	 * @return void
	 */
	/**
	 * Event handler: Validate location after confirm page is loaded
	 * This prevents showing confirm button if location is not selected
	 *
	 * Triggered by: catalog/view/checkout/confirm/after
	 *
	 * @param string $route
	 * @param array $args
	 * @param mixed $output
	 * @return void
	 */
	public function validateOrderConfirm(string &$route, array &$args, mixed &$output): void {
		// Only validate if Woot shipping is enabled
		if (!$this->config->get('shipping_woot_status')) {
			return;
		}

		// Check if location is required but not selected
		if ($this->isLocationRequired() && !$this->hasLocationSelected()) {
			$this->load->language('extension/woot/woot/location');

			// Replace the confirm content with an error message
			$output = '<div class="alert alert-warning"><i class="fa-solid fa-exclamation-triangle"></i> '
				. $this->language->get('text_location_required')
				. '</div>';
		}
	}

	/**
	 * Event handler: Capture city selection when shipping address is saved
	 *
	 * Triggered by: catalog/controller/checkout/shipping_address.save/after
	 *
	 * @param string $route
	 * @param array $args
	 * @param mixed $output
	 * @return void
	 */
	public function captureCity(string &$route, array &$args, mixed &$output): void {
		// Only capture if Woot shipping is enabled
		if (!$this->config->get('shipping_woot_status')) {
			return;
		}

		// Check if woot_city_id was submitted
		if (isset($this->request->post['woot_city_id']) && $this->request->post['woot_city_id']) {
			$woot_city_id = (int)$this->request->post['woot_city_id'];

			// Store in session
			$this->session->data['woot_city_id'] = $woot_city_id;

			// Also get county and country IDs for the order
			$this->load->model('extension/woot/woot/nomenclature');
			$city = $this->model_extension_woot_woot_nomenclature->getCityById($woot_city_id);

			if ($city) {
				$this->session->data['woot_county_id'] = (int)$city['woot_county_id'];

				// Get country ID from county
				$county = $this->db->query("
					SELECT woot_country_id
					FROM `" . DB_PREFIX . "woot_county`
					WHERE woot_county_id = '" . (int)$city['woot_county_id'] . "'
				");

				if ($county->num_rows) {
					$this->session->data['woot_country_id'] = (int)$county->row['woot_country_id'];
				}
			}
		} else {
			// Clear session data if not using Woot city
			unset($this->session->data['woot_city_id']);
			unset($this->session->data['woot_county_id']);
			unset($this->session->data['woot_country_id']);
		}
	}

}
