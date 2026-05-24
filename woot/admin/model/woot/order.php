<?php
namespace Opencart\Admin\Model\Extension\Woot\Woot;

/**
 * Woot Order Model
 *
 * Handles Woot order data operations for admin.
 */
class Order extends \Opencart\System\Engine\Model {
	/**
	 * Get Woot order data by order ID
	 *
	 * Returns woot_order record with location details (if any)
	 * Note: courier_id, courier_uid, courier_name, service_id, service_uid, service_name
	 * are now stored directly in woot_order table.
	 *
	 * @param int $order_id Order ID
	 * @return array|null
	 */
	public function getWootOrder(int $order_id): ?array {
		$query = $this->db->query("
			SELECT wo.*,
				wl.name AS location_name,
				wl.type AS location_type,
				wl.address AS location_address,
				wl.courier_name AS location_courier_name,
				wl.courier_id AS location_courier_id,
				wci.name AS city_name,
				wco.name AS county_name
			FROM `" . DB_PREFIX . "woot_order` wo
			LEFT JOIN `" . DB_PREFIX . "woot_location` wl ON (wo.woot_location_id = wl.woot_location_id)
			LEFT JOIN `" . DB_PREFIX . "woot_city` wci ON (wl.woot_city_id = wci.woot_city_id)
			LEFT JOIN `" . DB_PREFIX . "woot_county` wco ON (wl.woot_county_id = wco.woot_county_id)
			WHERE wo.order_id = '" . (int)$order_id . "'
		");

		if ($query->num_rows) {
			return $query->row;
		}

		return null;
	}

	/**
	 * Get location by ID
	 *
	 * @param int $location_id Location ID
	 * @return array|null
	 */
	public function getLocation(int $location_id): ?array {
		$query = $this->db->query("
			SELECT wl.*,
				wci.name AS city_name,
				wco.name AS county_name,
				wcou.name AS country_name
			FROM `" . DB_PREFIX . "woot_location` wl
			LEFT JOIN `" . DB_PREFIX . "woot_city` wci ON (wl.woot_city_id = wci.woot_city_id)
			LEFT JOIN `" . DB_PREFIX . "woot_county` wco ON (wl.woot_county_id = wco.woot_county_id)
			LEFT JOIN `" . DB_PREFIX . "woot_country` wcou ON (wl.woot_country_id = wcou.woot_country_id)
			WHERE wl.woot_location_id = '" . (int)$location_id . "'
		");

		if ($query->num_rows) {
			return $query->row;
		}

		return null;
	}

	/**
	 * Check if order uses Woot shipping
	 *
	 * @param int $order_id Order ID
	 * @return bool
	 */
	public function isWootOrder(int $order_id): bool {
		$query = $this->db->query("
			SELECT order_id
			FROM `" . DB_PREFIX . "woot_order`
			WHERE order_id = '" . (int)$order_id . "'
		");

		return $query->num_rows > 0;
	}

	/**
	 * Update AWB for order
	 *
	 * @param int $order_id Order ID
	 * @param string $awb_number AWB number
	 * @param int|null $woot_order_id Woot platform order ID
	 * @param string $awb_status AWB status
	 * @return void
	 */
	public function updateAwb(int $order_id, string $awb_number, ?int $woot_order_id = null, string $awb_status = ''): void {
		// Check if woot_order record exists
		$query = $this->db->query("SELECT order_id FROM `" . DB_PREFIX . "woot_order` WHERE order_id = '" . (int)$order_id . "'");

		if ($query->num_rows) {
			// Update existing record
			$sql = "UPDATE `" . DB_PREFIX . "woot_order`
				SET awb_number = '" . $this->db->escape($awb_number) . "',
					awb_status = '" . $this->db->escape($awb_status) . "',
					date_modified = NOW()";

			if ($woot_order_id) {
				$sql .= ", woot_shipment_id = '" . (int)$woot_order_id . "'";
			}

			$sql .= " WHERE order_id = '" . (int)$order_id . "'";

			$this->db->query($sql);
		} else {
			// Insert new record if it doesn't exist
			$this->db->query("
				INSERT INTO `" . DB_PREFIX . "woot_order` SET
					order_id = '" . (int)$order_id . "',
					awb_number = '" . $this->db->escape($awb_number) . "',
					awb_status = '" . $this->db->escape($awb_status) . "',
					woot_shipment_id = " . ($woot_order_id ? "'" . (int)$woot_order_id . "'" : "NULL") . ",
					date_added = NOW(),
					date_modified = NOW()
			");
		}
	}

	/**
	 * Clear AWB for order
	 *
	 * @param int $order_id Order ID
	 * @return void
	 */
	public function clearAwb(int $order_id): void {
		$this->db->query("
			UPDATE `" . DB_PREFIX . "woot_order`
			SET awb_number = NULL,
				awb_status = NULL,
				woot_shipment_id = NULL,
				date_modified = NOW()
			WHERE order_id = '" . (int)$order_id . "'
		");
	}

	/**
	 * Get order shipping method info
	 *
	 * @param int $order_id Order ID
	 * @return array|null
	 */
	public function getOrderShippingMethod(int $order_id): ?array {
		$query = $this->db->query("
			SELECT shipping_method
			FROM `" . DB_PREFIX . "order`
			WHERE order_id = '" . (int)$order_id . "'
		");

		if ($query->num_rows && !empty($query->row['shipping_method'])) {
			$shipping_method = json_decode($query->row['shipping_method'], true);
			return is_array($shipping_method) ? $shipping_method : null;
		}

		return null;
	}

	/**
	 * Get Woot service config from order's shipping method
	 *
	 * First checks woot_order table for stored values, then falls back to config lookup.
	 *
	 * @param int $order_id Order ID
	 * @return array|null Service config with courier and service info
	 */
	public function getWootServiceFromOrder(int $order_id): ?array {
		// First, try to get stored values from woot_order table
		$woot_order = $this->getWootOrder($order_id);

		if ($woot_order && !empty($woot_order['service_id'])) {
			// Get delivery type from config (not stored in woot_order)
			$configured_services = $this->config->get('shipping_woot_services');
			$delivery_type = 'door';
			if (is_array($configured_services) && isset($configured_services[$woot_order['service_id']])) {
				$delivery_type = $configured_services[$woot_order['service_id']]['delivery'] ?? 'door';
			}

			return [
				'service_id'    => $woot_order['service_id'],
				'service_uid'   => $woot_order['service_uid'] ?? '',
				'service_name'  => $woot_order['service_name'] ?? 'Woot Shipping',
				'courier_id'    => $woot_order['courier_id'] ?? '',
				'courier_uid'   => $woot_order['courier_uid'] ?? '',
				'courier_name'  => $woot_order['courier_name'] ?? '',
				'delivery_type' => $delivery_type
			];
		}

		// Fallback: look up from shipping method code and config
		$shipping_method = $this->getOrderShippingMethod($order_id);

		if (!$shipping_method || empty($shipping_method['code'])) {
			return null;
		}

		// Check if this is a Woot shipping method
		$code = $shipping_method['code'];
		if (strpos($code, 'woot.woot_') !== 0) {
			return null;
		}

		// Extract service_id from code (woot.woot_123 -> 123)
		$service_id = str_replace('woot.woot_', '', $code);

		// Get configured services from settings
		$configured_services = $this->config->get('shipping_woot_services');

		if (!is_array($configured_services) || !isset($configured_services[$service_id])) {
			// Service not found in config, return basic info from shipping method
			return [
				'service_id'    => $service_id,
				'service_uid'   => '',
				'service_name'  => $shipping_method['name'] ?? 'Woot Shipping',
				'courier_id'    => '',
				'courier_uid'   => '',
				'courier_name'  => '',
				'delivery_type' => 'door'
			];
		}

		$service_config = $configured_services[$service_id];

		return [
			'service_id'    => $service_id,
			'service_uid'   => $service_config['service_uid'] ?? '',
			'service_name'  => $service_config['name'] ?? $shipping_method['name'] ?? 'Woot Shipping',
			'courier_id'    => $service_config['courier_id'] ?? '',
			'courier_uid'   => $service_config['courier_uid'] ?? '',
			'courier_name'  => $service_config['courier_name'] ?? '',
			'delivery_type' => $service_config['delivery'] ?? 'door'
		];
	}
}
