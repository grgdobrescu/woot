<?php
namespace Opencart\Admin\Controller\Extension\Woot\Woot;

require_once(DIR_EXTENSION . 'woot/system/library/woot/api.php');

use Opencart\System\Library\Woot\Api as WootApi;

/**
 * Woot AWB Controller
 *
 * Manages AWB (Air Waybill) generation, printing, and tracking.
 */
class Awb extends \Opencart\System\Engine\Controller {
	/**
	 * Generate AWB via API
	 *
	 * @return void
	 */
	public function generate(): void {
		$this->load->language('extension/woot/woot/order');

		$json = [];

		// Check permissions
		if (!$this->user->hasPermission('modify', 'extension/woot/woot/order')) {
			$json['error'] = $this->language->get('error_permission');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		// Get order ID
		$order_id = isset($this->request->get['order_id']) ? (int)$this->request->get['order_id'] : 0;

		if (!$order_id) {
			$json['error'] = $this->language->get('error_order');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		// Load models
		$this->load->model('sale/order');
		$this->load->model('extension/woot/woot/order');

		// Get OpenCart order
		$order = $this->model_sale_order->getOrder($order_id);

		if (!$order) {
			$json['error'] = $this->language->get('error_order');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		// Get Woot order data
		$woot_order = $this->model_extension_woot_woot_order->getWootOrder($order_id);

		// Check if AWB already exists
		if ($woot_order && !empty($woot_order['awb_number'])) {
			$json['error'] = 'AWB already exists: ' . $woot_order['awb_number'];
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		// Get service info from shipping method
		$service_info = $this->model_extension_woot_woot_order->getWootServiceFromOrder($order_id);

		if (!$service_info || empty($service_info['service_id'])) {
			$json['error'] = $this->language->get('error_not_woot_order');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		// Get API credentials
		$public_key = $this->config->get('shipping_woot_public_key');
		$secret_key = $this->config->get('shipping_woot_secret_key');
		$sender_address_id = (int)$this->config->get('shipping_woot_sender_address_id');

		if (!$public_key || !$secret_key || !$sender_address_id) {
			$json['error'] = 'Woot API not configured';
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		// Build sender data
		$sender = [
			'address_id' => $sender_address_id
		];

		// Build receiver data
		$receiver = $this->buildReceiverData($order, $woot_order);

		// Build parcels data
		$parcels = $this->buildParcelsData($order_id);

		// Build options (repayment for configured payment methods)
		$options = [];

		// Get configured repayment payment methods
		$repayment_methods = $this->config->get('shipping_woot_repayment_methods') ?? [];

		// Get payment code from order (OpenCart 4 stores as JSON)
		$payment_code = '';
		if (isset($order['payment_method']) && is_array($order['payment_method'])) {
			$payment_code = $order['payment_method']['code'] ?? '';
		}

		// If payment method requires repayment, send order total
		if ($payment_code && in_array($payment_code, $repayment_methods)) {
			$options['repayment'] = (float)$order['total'];
		}

		// Create order via API
		$api = new WootApi($public_key, $secret_key);
		$result = $api->createOrder(
			(int)$service_info['service_id'],
			$sender,
			$receiver,
			$parcels,
			$options
		);

		if ($result['success'] && !empty($result['awb_number'])) {
			// Save AWB and Woot order_id to woot_order table
			$woot_order_id = isset($result['order_id']) ? (int)$result['order_id'] : null;
			$this->model_extension_woot_woot_order->updateAwb($order_id, $result['awb_number'], $woot_order_id);

			// Update order status to "Shipped" and notify customer (if configured)
			$shipped_status_id = (int)$this->config->get('shipping_woot_shipped_status_id');
			if ($shipped_status_id) {
				// Build tracking URL
				$courier_uid = $service_info['courier_uid'] ?? '';
				$tracking_url = 'https://awb.woot.ro/urmarire-colet-' . $courier_uid . '/' . $result['awb_number'];

				// Get custom message from settings or use default
				$message_template = $this->config->get('shipping_woot_shipped_message');
				if (empty($message_template)) {
					$message_template = "Coletul a fost expediat cu {courier_name}.\nAWB: {awb}\nUrmarire: {tracking_url}";
				}

				// Replace placeholders
				$comment = str_replace(
					['{courier_name}', '{awb}', '{tracking_url}'],
					[$service_info['courier_name'] ?? '', $result['awb_number'], $tracking_url],
					$message_template
				);

				$this->addOrderHistory($order_id, $shipped_status_id, $comment, true);
			}

			$json['success'] = true;
			$json['awb_number'] = $result['awb_number'];
			$json['woot_order_id'] = $woot_order_id;
		} else {
			$json['error'] = $result['error'] ?? 'Failed to generate AWB';
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Print AWB label
	 *
	 * @return void
	 */
	public function print(): void {
		$order_id = isset($this->request->get['order_id']) ? (int)$this->request->get['order_id'] : 0;
		$format = isset($this->request->get['format']) ? $this->request->get['format'] : 'a4';

		// Validate format
		if (!in_array($format, ['a4', 'a6'])) {
			$format = 'a4';
		}

		if (!$order_id) {
			die('Order ID required');
		}

		// Get woot_shipment_id from woot_order
		$this->load->model('extension/woot/woot/order');
		$woot_order = $this->model_extension_woot_woot_order->getWootOrder($order_id);

		if (!$woot_order || empty($woot_order['woot_shipment_id'])) {
			die('No AWB found for this order');
		}

		// Get API credentials
		$public_key = $this->config->get('shipping_woot_public_key');
		$secret_key = $this->config->get('shipping_woot_secret_key');

		if (!$public_key || !$secret_key) {
			die('Woot API not configured');
		}

		// Get PDF from API
		$api = new WootApi($public_key, $secret_key);
		$result = $api->getAwbLabel((int)$woot_order['woot_shipment_id'], $format);

		if ($result['success'] && !empty($result['pdf'])) {
			// Output PDF
			header('Content-Type: application/pdf');
			header('Content-Disposition: inline; filename="AWB-' . $woot_order['awb_number'] . '-' . strtoupper($format) . '.pdf"');
			header('Content-Length: ' . strlen($result['pdf']));
			echo $result['pdf'];
			exit;
		} else {
			die('Error: ' . ($result['error'] ?? 'Failed to get AWB label'));
		}
	}

	/**
	 * Cancel AWB (cancel on Woot platform and clear from local database)
	 *
	 * @return void
	 */
	public function cancel(): void {
		$this->load->language('extension/woot/woot/order');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/woot/woot/order')) {
			$json['error'] = $this->language->get('error_permission');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		$order_id = isset($this->request->get['order_id']) ? (int)$this->request->get['order_id'] : 0;

		if (!$order_id) {
			$json['error'] = $this->language->get('error_order');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		$this->load->model('extension/woot/woot/order');

		// Get Woot order data
		$woot_order = $this->model_extension_woot_woot_order->getWootOrder($order_id);

		if (!$woot_order || empty($woot_order['woot_shipment_id'])) {
			$json['error'] = $this->language->get('error_no_awb');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		// Get API credentials
		$public_key = $this->config->get('shipping_woot_public_key');
		$secret_key = $this->config->get('shipping_woot_secret_key');

		if (!$public_key || !$secret_key) {
			$json['error'] = 'Woot API not configured';
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		// Cancel on Woot platform
		$api = new WootApi($public_key, $secret_key);
		$result = $api->cancelOrder((int)$woot_order['woot_shipment_id']);

		if ($result['success']) {
			// Clear local AWB data
			$this->model_extension_woot_woot_order->clearAwb($order_id);
			$json['success'] = true;
		} else {
			$json['error'] = $result['error'] ?? $this->language->get('error_cancel_failed');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Build receiver data from order
	 *
	 * @param array $order OpenCart order data
	 * @param array|null $woot_order Woot order data
	 * @return array
	 */
	protected function buildReceiverData(array $order, ?array $woot_order): array {
		$name = trim($order['shipping_firstname'] . ' ' . $order['shipping_lastname']);

		$receiver = [
			'name' => $name,
			'contact' => $name,
			'phone' => !empty($order['telephone']) ? $order['telephone'] : '0700000000',
			'email' => $order['email'] ?? ''
		];

		// Company is a flag (0 or 1)
		if (!empty($order['shipping_company'])) {
			$receiver['company'] = 1;
			$receiver['company_name'] = $order['shipping_company'];
		} else {
			$receiver['company'] = 0;
		}

		// Get Woot country_id (required)
		$woot_country_id = null;
		if ($woot_order && !empty($woot_order['woot_country_id'])) {
			$woot_country_id = (int)$woot_order['woot_country_id'];
		} else {
			// Lookup from OpenCart country_id
			$woot_country_id = $this->getWootCountryId((int)$order['shipping_country_id']);
		}

		// If location delivery, use location_id
		if ($woot_order && !empty($woot_order['woot_location_id'])) {
			$receiver['location_id'] = (int)$woot_order['woot_location_id'];

			// Get location details for address, city_id, county_id, country_id, postal_code
			$location = $this->model_extension_woot_woot_order->getLocation((int)$woot_order['woot_location_id']);
			if ($location) {
				if (!empty($location['address'])) {
					$receiver['address'] = $location['address'];
				}
				if (!empty($location['woot_city_id'])) {
					$receiver['city_id'] = (int)$location['woot_city_id'];
				}
				if (!empty($location['woot_county_id'])) {
					$receiver['county_id'] = (int)$location['woot_county_id'];
				}
				if (!empty($location['woot_country_id'])) {
					$receiver['country_id'] = (int)$location['woot_country_id'];
				}
				if (!empty($location['zipcode'])) {
					$receiver['zipcode'] = $location['zipcode'];
				}
			}
		} else {
			// Door delivery - build full address
			$receiver['address'] = $order['shipping_address_1'];
			if (!empty($order['shipping_address_2'])) {
				$receiver['address'] .= ', ' . $order['shipping_address_2'];
			}

			// Get Woot city_id (required)
			if ($woot_order && !empty($woot_order['woot_city_id'])) {
				$receiver['city_id'] = (int)$woot_order['woot_city_id'];
			} else {
				// Lookup city_id from woot_city table
				$city_id = $this->getWootCityId($order['shipping_city']);
				if ($city_id) {
					$receiver['city_id'] = $city_id;
				}
			}

			// Try to get Woot county_id
			if ($woot_order && !empty($woot_order['woot_county_id'])) {
				$receiver['county_id'] = (int)$woot_order['woot_county_id'];
			}

			// Always include country_id
			if ($woot_country_id) {
				$receiver['country_id'] = $woot_country_id;
			}

			if (!empty($order['shipping_postcode'])) {
				$receiver['zipcode'] = $order['shipping_postcode'];
			}
		}

		return $receiver;
	}

	/**
	 * Get Woot country_id from OpenCart country_id
	 *
	 * @param int $oc_country_id
	 * @return int|null
	 */
	protected function getWootCountryId(int $oc_country_id): ?int {
		$query = $this->db->query("
			SELECT woot_country_id
			FROM `" . DB_PREFIX . "woot_country`
			WHERE oc_country_id = '" . (int)$oc_country_id . "'
		");

		if ($query->num_rows) {
			return (int)$query->row['woot_country_id'];
		}

		// Default to Romania (usually ID 1)
		return 1;
	}

	/**
	 * Build parcels data from order products
	 *
	 * @param int $order_id Order ID
	 * @return array
	 */
	protected function buildParcelsData(int $order_id): array {
		$this->load->model('sale/order');

		$products = $this->model_sale_order->getProducts($order_id);

		// Calculate total weight
		$total_weight = 0;
		foreach ($products as $product) {
			$query = $this->db->query("SELECT weight FROM " . DB_PREFIX . "product WHERE product_id = '" . (int)$product['product_id'] . "'");
			if ($query->num_rows) {
				$weight = (float)$query->row['weight'] * (int)$product['quantity'];
				$total_weight += $weight;
			}
		}

		// Minimum weight
		if ($total_weight < 0.1) {
			$total_weight = 0.1;
		}

		// Default parcel dimensions
		return [
			[
				'type' => 'package',
				'content' => 'goods',
				'weight' => $total_weight,
				'length' => 20,
				'width' => 15,
				'height' => 10
			]
		];
	}

	/**
	 * Get Woot city_id by city name
	 *
	 * @param string $city_name
	 * @return int|null
	 */
	protected function getWootCityId(string $city_name): ?int {
		$query = $this->db->query("
			SELECT woot_city_id
			FROM `" . DB_PREFIX . "woot_city`
			WHERE name LIKE '%" . $this->db->escape($city_name) . "%'
			LIMIT 1
		");

		if ($query->num_rows) {
			return (int)$query->row['woot_city_id'];
		}

		return null;
	}

	/**
	 * Add order history entry and update order status
	 *
	 * @param int $order_id Order ID
	 * @param int $order_status_id Order status ID
	 * @param string $comment Comment
	 * @param bool $notify Notify customer
	 * @return void
	 */
	protected function addOrderHistory(int $order_id, int $order_status_id, string $comment = '', bool $notify = false): void {
		// Update order status
		$this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");

		// Add history entry
		$this->db->query("INSERT INTO `" . DB_PREFIX . "order_history` SET
			order_id = '" . (int)$order_id . "',
			order_status_id = '" . (int)$order_status_id . "',
			notify = '" . (int)$notify . "',
			comment = '" . $this->db->escape($comment) . "',
			date_added = NOW()");
	}

}
