<?php
namespace Opencart\Catalog\Model\Extension\Woot\Shipping;

// Load Woot API library (not autoloaded by OpenCart)
require_once(DIR_EXTENSION . 'woot/system/library/woot/api.php');

use Opencart\System\Library\Woot\Api as WootApi;

/**
 * Woot Shipping Model
 *
 * Handles shipping quote calculation for the Woot shipping extension.
 * This model is required by OpenCart's shipping extension system.
 */
class Woot extends \Opencart\System\Engine\Model {
	/**
	 * @var WootApi|null
	 */
	private ?WootApi $api = null;

	/**
	 * Get Quote
	 *
	 * Returns shipping quotes based on configured services.
	 *
	 * @param array<string, mixed> $address
	 *
	 * @return array<string, mixed>
	 */
	public function getQuote(array $address): array {
		$this->load->language('extension/woot/shipping/woot');

		// Check geo zone restriction
		$geo_zone_id = (int)$this->config->get('shipping_woot_geo_zone_id');

		if (!$geo_zone_id) {
			// No geo zone restriction - available everywhere
			$status = true;
		} else {
			// Check if address is within the configured geo zone
			// Direct query since catalog has no GeoZone model in OpenCart 4.x
			$query = $this->db->query("
				SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone`
				WHERE geo_zone_id = '" . $geo_zone_id . "'
				AND country_id = '" . (int)$address['country_id'] . "'
				AND (zone_id = '0' OR zone_id = '" . (int)$address['zone_id'] . "')
			");

			$status = $query->num_rows > 0;
		}

		$method_data = [];

		if ($status) {
			$quote_data = [];

			// Get configured services
			$configured_services = $this->config->get('shipping_woot_services');

			if (is_array($configured_services) && !empty($configured_services)) {
				// Get cart data for API quotes
				$cart_data = $this->getCartData();

				// Separate fixed price services from quotation services
				$fixed_services = [];
				$quotation_service_ids = [];

				foreach ($configured_services as $service_id => $service_config) {
					if (isset($service_config['price_type']) && $service_config['price_type'] === 'fixed') {
						$fixed_services[$service_id] = $service_config;
					} else {
						$quotation_service_ids[] = (int)$service_id;
					}
				}

				// Get API prices for all quotation services in one request
				$api_prices = [];
				if (!empty($quotation_service_ids)) {
					$api_prices = $this->getApiPrices($quotation_service_ids, $address, $cart_data);
				}

				// Check if prices include VAT (use API's total price directly)
				$price_includes_vat = (int)$this->config->get('shipping_woot_price_includes_vat');

				// Build quote data for all services
				foreach ($configured_services as $service_id => $service_config) {
					$cost = false;

					if (isset($fixed_services[$service_id])) {
						// Fixed price
						$cost = isset($service_config['price']) ? (float)$service_config['price'] : 0.0;
					} elseif (isset($api_prices[$service_id])) {
						// API quotation price - use total (VAT included) or price (net) based on setting
						if ($price_includes_vat && isset($api_prices[$service_id]['total'])) {
							$cost = (float)$api_prices[$service_id]['total'];
						} else {
							$cost = (float)$api_prices[$service_id]['price'];
						}

						// Apply percentage markup
						if (!empty($service_config['markup_percent'])) {
							$cost += $cost * (float)$service_config['markup_percent'] / 100;
						}

						// Apply fixed markup
						if (!empty($service_config['markup_fixed'])) {
							$cost += (float)$service_config['markup_fixed'];
						}
					}

					if ($cost !== false) {
						// Get delivery type from service config or API data
						$delivery_type = $service_config['delivery'] ?? 'door';

						// When VAT is included, set tax_class_id to 0 to prevent double taxation
						$tax_class_id = $price_includes_vat ? 0 : $this->config->get('shipping_woot_tax_class_id');

						$quote_data['woot_' . $service_id] = [
							'code'         => 'woot.woot_' . $service_id,
							'name'         => $this->getServiceName($service_config),
							'cost'         => $cost,
							'tax_class_id' => $tax_class_id,
							'text'         => $this->currency->format(
								$this->tax->calculate(
									$cost,
									$tax_class_id,
									$this->config->get('config_tax')
								),
								$this->session->data['currency']
							),
							'delivery_type' => $delivery_type
						];
					}
				}
			}

			// No fallback - services must be configured in admin

			$method_data = [
				'code'       => 'woot',
				'name'       => $this->language->get('heading_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_woot_sort_order'),
				'error'      => false
			];
		}

		return $method_data;
	}

	/**
	 * Get API instance
	 *
	 * @return WootApi
	 */
	protected function getApi(): WootApi {
		if ($this->api === null) {
			$public_key = $this->config->get('shipping_woot_public_key');
			$secret_key = $this->config->get('shipping_woot_secret_key');

			$this->api = new WootApi($public_key, $secret_key, $this->session->data);
		}

		return $this->api;
	}

	/**
	 * Get API Prices for Multiple Services
	 *
	 * Makes a single API call to get prices for all quotation services.
	 *
	 * @param array $service_ids Array of service IDs
	 * @param array $address OpenCart address data
	 * @param array $cart_data Cart data
	 *
	 * @return array Prices keyed by service_id
	 */
	protected function getApiPrices(array $service_ids, array $address, array $cart_data): array {
		// Get sender address ID from config
		$sender_address_id = (int)$this->config->get('shipping_woot_sender_address_id');
		if (!$sender_address_id) {
			return [];
		}

		// Build receiver address data
		$receiver = [];

		// Country is required - get from session or lookup from nomenclature
		if (!empty($this->session->data['woot_country_id'])) {
			$receiver['country_id'] = (int)$this->session->data['woot_country_id'];
		} else {
			// Lookup Woot country_id from OpenCart country_id
			$woot_country_id = $this->getWootCountryId((int)$address['country_id']);
			if ($woot_country_id) {
				$receiver['country_id'] = $woot_country_id;
			} else {
				// Can't get quote without country_id
				return [];
			}
		}

		// County
		if (!empty($this->session->data['woot_county_id'])) {
			$receiver['county_id'] = (int)$this->session->data['woot_county_id'];
		} else {
			// Lookup Woot county_id from OpenCart zone_id
			$woot_county_id = $this->getWootCountyId((int)$address['zone_id']);
			if ($woot_county_id) {
				$receiver['county_id'] = $woot_county_id;
			} else {
				// Fallback: send county name (zone name) as text
				$receiver['county'] = $address['zone'] ?? '';
			}
		}

		// City
		if (!empty($this->session->data['woot_city_id'])) {
			$receiver['city_id'] = (int)$this->session->data['woot_city_id'];
		} else {
			// Lookup Woot city_id by name and county
			$woot_county_id = $receiver['county_id'] ?? null;
			$city_name = $address['city'] ?? '';
			if ($woot_county_id && $city_name) {
				$woot_city_id = $this->getWootCityId($city_name, $woot_county_id);
				if ($woot_city_id) {
					$receiver['city_id'] = $woot_city_id;
				} else {
					// City not found in nomenclature - can't get quote
					return [];
				}
			} else {
				return [];
			}
		}

		// Build parcel from cart data
		$parcel = $this->buildParcel($cart_data);

		// Get prices from API for all services at once
		$api = $this->getApi();
		$prices = $api->getQuotes($sender_address_id, $receiver, $parcel, $service_ids);

		return is_array($prices) ? $prices : [];
	}

	/**
	 * Get Woot Country ID from OpenCart Country ID
	 *
	 * @param int $oc_country_id OpenCart country ID
	 * @return int|null Woot country ID or null if not mapped
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

		return null;
	}

	/**
	 * Get Woot County ID from OpenCart Zone ID
	 *
	 * @param int $oc_zone_id OpenCart zone ID
	 * @return int|null Woot county ID or null if not mapped
	 */
	protected function getWootCountyId(int $oc_zone_id): ?int {
		$query = $this->db->query("
			SELECT woot_county_id
			FROM `" . DB_PREFIX . "woot_county`
			WHERE oc_zone_id = '" . (int)$oc_zone_id . "'
		");

		if ($query->num_rows) {
			return (int)$query->row['woot_county_id'];
		}

		return null;
	}

	/**
	 * Get Woot City ID by name and county
	 *
	 * @param string $city_name City name to search
	 * @param int $woot_county_id Woot county ID
	 * @return int|null Woot city ID or null if not found
	 */
	protected function getWootCityId(string $city_name, int $woot_county_id): ?int {
		// Try exact match first
		$query = $this->db->query("
			SELECT woot_city_id
			FROM `" . DB_PREFIX . "woot_city`
			WHERE woot_county_id = '" . (int)$woot_county_id . "'
			AND name = '" . $this->db->escape($city_name) . "'
		");

		if ($query->num_rows) {
			return (int)$query->row['woot_city_id'];
		}

		// Try case-insensitive match
		$query = $this->db->query("
			SELECT woot_city_id
			FROM `" . DB_PREFIX . "woot_city`
			WHERE woot_county_id = '" . (int)$woot_county_id . "'
			AND LOWER(name) = LOWER('" . $this->db->escape($city_name) . "')
		");

		if ($query->num_rows) {
			return (int)$query->row['woot_city_id'];
		}

		return null;
	}

	/**
	 * Build Parcel Data
	 *
	 * Builds parcel configuration for API request using default parcel settings.
	 *
	 * @param array $cart_data Cart data with weight and products
	 *
	 * @return array Parcel data for API
	 */
	protected function buildParcel(array $cart_data): array {
		// Default parcel dimensions - TODO: fetch from stored parcel config
		$parcel = [
			'type' => 'package',
			'content' => 'goods',
			'weight' => max(0.1, (float)$cart_data['weight']),
			'length' => 20,
			'width' => 15,
			'height' => 10
		];

		return $parcel;
	}

	/**
	 * Get Service Name
	 *
	 * Returns the display name for a service from the config.
	 *
	 * @param array $service_config Service configuration with 'name' key
	 *
	 * @return string
	 */
	protected function getServiceName(array $service_config): string {
		if (!empty($service_config['name'])) {
			return $service_config['name'];
		}

		// Fallback to generic name
		return $this->language->get('text_description');
	}

	/**
	 * Get Cart Data
	 *
	 * Returns cart data for API requests.
	 *
	 * @return array<string, mixed>
	 */
	protected function getCartData(): array {
		$cart_data = [
			'products' => [],
			'weight'   => $this->cart->getWeight(),
			'total'    => $this->cart->getTotal()
		];

		foreach ($this->cart->getProducts() as $product) {
			$cart_data['products'][] = [
				'product_id' => $product['product_id'],
				'name'       => $product['name'],
				'quantity'   => $product['quantity'],
				'weight'     => $product['weight'],
				'price'      => $product['price']
			];
		}

		return $cart_data;
	}
}
