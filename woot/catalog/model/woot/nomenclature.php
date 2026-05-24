<?php
namespace Opencart\Catalog\Model\Extension\Woot\Woot;

/**
 * Woot Nomenclature Model (Catalog)
 *
 * Handles city/nomenclature lookups for checkout.
 */
class Nomenclature extends \Opencart\System\Engine\Model {
	/**
	 * Check if Woot shipping is enabled
	 *
	 * @return bool
	 */
	public function isWootEnabled(): bool {
		return (bool)$this->config->get('shipping_woot_status');
	}

	/**
	 * Get cities by OpenCart zone_id
	 *
	 * Looks up the Woot county mapped to this zone and returns its cities.
	 *
	 * @param int $zone_id OpenCart zone ID
	 * @return array
	 */
	public function getCitiesByZone(int $zone_id): array {
		// First find the Woot county mapped to this zone
		$query = $this->db->query("
			SELECT wco.woot_county_id
			FROM `" . DB_PREFIX . "woot_county` wco
			WHERE wco.oc_zone_id = '" . (int)$zone_id . "'
		");

		if (!$query->num_rows) {
			return [];
		}

		$woot_county_id = (int)$query->row['woot_county_id'];

		// Get cities for this county
		$query = $this->db->query("
			SELECT woot_city_id, name
			FROM `" . DB_PREFIX . "woot_city`
			WHERE woot_county_id = '" . $woot_county_id . "'
			ORDER BY name ASC
		");

		return $query->rows;
	}

	/**
	 * Search cities by name within a zone
	 *
	 * @param int $zone_id OpenCart zone ID
	 * @param string $query Search query
	 * @param int $limit Max results
	 * @return array
	 */
	public function searchCities(int $zone_id, string $query, int $limit = 50): array {
		// First find the Woot county mapped to this zone
		$countyQuery = $this->db->query("
			SELECT wco.woot_county_id
			FROM `" . DB_PREFIX . "woot_county` wco
			WHERE wco.oc_zone_id = '" . (int)$zone_id . "'
		");

		if (!$countyQuery->num_rows) {
			return [];
		}

		$woot_county_id = (int)$countyQuery->row['woot_county_id'];

		// Search cities
		$sql = "
			SELECT woot_city_id, name
			FROM `" . DB_PREFIX . "woot_city`
			WHERE woot_county_id = '" . $woot_county_id . "'
		";

		if ($query) {
			$sql .= " AND name LIKE '%" . $this->db->escape($query) . "%'";
		}

		$sql .= " ORDER BY name ASC LIMIT " . (int)$limit;

		$result = $this->db->query($sql);

		return $result->rows;
	}

	/**
	 * Get city by ID
	 *
	 * @param int $woot_city_id
	 * @return array|null
	 */
	public function getCityById(int $woot_city_id): ?array {
		$query = $this->db->query("
			SELECT wc.*, wco.name AS county_name, wcn.name AS country_name
			FROM `" . DB_PREFIX . "woot_city` wc
			LEFT JOIN `" . DB_PREFIX . "woot_county` wco ON wc.woot_county_id = wco.woot_county_id
			LEFT JOIN `" . DB_PREFIX . "woot_country` wcn ON wco.woot_country_id = wcn.woot_country_id
			WHERE wc.woot_city_id = '" . (int)$woot_city_id . "'
		");

		return $query->num_rows ? $query->row : null;
	}

	/**
	 * Check if a zone has Woot cities mapped
	 *
	 * @param int $zone_id OpenCart zone ID
	 * @return bool
	 */
	public function hasWootCities(int $zone_id): bool {
		$query = $this->db->query("
			SELECT COUNT(*) AS total
			FROM `" . DB_PREFIX . "woot_county` wco
			INNER JOIN `" . DB_PREFIX . "woot_city` wc ON wco.woot_county_id = wc.woot_county_id
			WHERE wco.oc_zone_id = '" . (int)$zone_id . "'
		");

		return (int)$query->row['total'] > 0;
	}

	/**
	 * Get Woot county by OpenCart zone_id
	 *
	 * @param int $zone_id OpenCart zone ID
	 * @return array|null
	 */
	public function getWootCountyByZone(int $zone_id): ?array {
		$query = $this->db->query("
			SELECT wco.*
			FROM `" . DB_PREFIX . "woot_county` wco
			WHERE wco.oc_zone_id = '" . (int)$zone_id . "'
		");

		return $query->num_rows ? $query->row : null;
	}

	/**
	 * Get Woot country by OpenCart country_id
	 *
	 * @param int $country_id OpenCart country ID
	 * @return array|null
	 */
	public function getWootCountryByOcId(int $country_id): ?array {
		$query = $this->db->query("
			SELECT wc.*
			FROM `" . DB_PREFIX . "woot_country` wc
			WHERE wc.oc_country_id = '" . (int)$country_id . "'
		");

		return $query->num_rows ? $query->row : null;
	}
}
