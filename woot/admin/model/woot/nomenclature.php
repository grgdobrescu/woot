<?php
namespace Opencart\Admin\Model\Extension\Woot\Woot;

/**
 * Woot Nomenclature Model
 *
 * Handles database operations for Woot nomenclature data.
 */
class Nomenclature extends \Opencart\System\Engine\Model {
	/**
	 * Save countries from API response
	 *
	 * @param array $countries Array of country data from API
	 * @return int Number of countries saved
	 */
	public function saveCountries(array $countries): int {
		$count = 0;
		$now = date('Y-m-d H:i:s');

		foreach ($countries as $country) {
			if (!isset($country['id'])) {
				continue;
			}

			$this->db->query("
				INSERT INTO `" . DB_PREFIX . "woot_country` SET
					`woot_country_id` = '" . (int)$country['id'] . "',
					`name` = '" . $this->db->escape($country['name'] ?? '') . "',
					`code` = '" . $this->db->escape($country['code'] ?? '') . "',
					`favorite` = '" . (int)($country['favorite'] ?? 0) . "',
					`sort` = '" . (int)($country['sort'] ?? 0) . "',
					`eu` = '" . (int)($country['eu'] ?? 0) . "',
					`has_counties` = '" . (int)($country['has_counties'] ?? 0) . "',
					`has_cities` = '" . (int)($country['has_cities'] ?? 0) . "',
					`has_locations` = '" . (int)($country['has_locations'] ?? 0) . "',
					`counties_count` = '" . (int)($country['counties'] ?? 0) . "',
					`cities_count` = '" . (int)($country['cities'] ?? 0) . "',
					`locations_count` = '" . (int)($country['locations'] ?? 0) . "',
					`date_synced` = '" . $this->db->escape($now) . "'
				ON DUPLICATE KEY UPDATE
					`name` = '" . $this->db->escape($country['name'] ?? '') . "',
					`code` = '" . $this->db->escape($country['code'] ?? '') . "',
					`favorite` = '" . (int)($country['favorite'] ?? 0) . "',
					`sort` = '" . (int)($country['sort'] ?? 0) . "',
					`eu` = '" . (int)($country['eu'] ?? 0) . "',
					`has_counties` = '" . (int)($country['has_counties'] ?? 0) . "',
					`has_cities` = '" . (int)($country['has_cities'] ?? 0) . "',
					`has_locations` = '" . (int)($country['has_locations'] ?? 0) . "',
					`counties_count` = '" . (int)($country['counties'] ?? 0) . "',
					`cities_count` = '" . (int)($country['cities'] ?? 0) . "',
					`locations_count` = '" . (int)($country['locations'] ?? 0) . "',
					`date_synced` = '" . $this->db->escape($now) . "'
			");

			$count++;
		}

		return $count;
	}

	/**
	 * Save counties from API response
	 *
	 * @param int $woot_country_id Woot country ID
	 * @param array $counties Array of county data from API
	 * @return int Number of counties saved
	 */
	public function saveCounties(int $woot_country_id, array $counties): int {
		$count = 0;
		$now = date('Y-m-d H:i:s');

		foreach ($counties as $county) {
			if (!isset($county['id'])) {
				continue;
			}

			$this->db->query("
				INSERT INTO `" . DB_PREFIX . "woot_county` SET
					`woot_county_id` = '" . (int)$county['id'] . "',
					`woot_country_id` = '" . (int)$woot_country_id . "',
					`name` = '" . $this->db->escape($county['name'] ?? '') . "',
					`code` = '" . $this->db->escape($county['code'] ?? '') . "',
					`date_synced` = '" . $this->db->escape($now) . "'
				ON DUPLICATE KEY UPDATE
					`woot_country_id` = '" . (int)$woot_country_id . "',
					`name` = '" . $this->db->escape($county['name'] ?? '') . "',
					`code` = '" . $this->db->escape($county['code'] ?? '') . "',
					`date_synced` = '" . $this->db->escape($now) . "'
			");

			$count++;
		}

		return $count;
	}

	/**
	 * Save cities from API response
	 *
	 * @param int $woot_country_id Woot country ID (not used but kept for context)
	 * @param array $cities Array of city data from API
	 * @return int Number of cities saved
	 */
	public function saveCities(int $woot_country_id, array $cities): int {
		$count = 0;
		$now = date('Y-m-d H:i:s');

		foreach ($cities as $city) {
			if (!isset($city['id'])) {
				continue;
			}

			// API response format: id, name, county_id, county_name, county_code
			$woot_county_id = isset($city['county_id']) ? (int)$city['county_id'] : 0;

			$this->db->query("
				INSERT INTO `" . DB_PREFIX . "woot_city` SET
					`woot_city_id` = '" . (int)$city['id'] . "',
					`woot_county_id` = '" . $woot_county_id . "',
					`name` = '" . $this->db->escape($city['name'] ?? '') . "',
					`date_synced` = '" . $this->db->escape($now) . "'
				ON DUPLICATE KEY UPDATE
					`woot_county_id` = '" . $woot_county_id . "',
					`name` = '" . $this->db->escape($city['name'] ?? '') . "',
					`date_synced` = '" . $this->db->escape($now) . "'
			");

			$count++;
		}

		return $count;
	}

	/**
	 * Save locations from API response
	 *
	 * @param int $woot_country_id Woot country ID
	 * @param array $locations Array of location data from API
	 * @return int Number of locations saved
	 */
	public function saveLocations(int $woot_country_id, array $locations): int {
		$count = 0;
		$now = date('Y-m-d H:i:s');

		foreach ($locations as $location) {
			if (!isset($location['id'])) {
				continue;
			}

			$type = ($location['type'] ?? 'locker') === 'shop' ? 'shop' : 'locker';

			$this->db->query("
				INSERT INTO `" . DB_PREFIX . "woot_location` SET
					`woot_location_id` = '" . (int)$location['id'] . "',
					`name` = '" . $this->db->escape($location['name'] ?? '') . "',
					`type` = '" . $this->db->escape($type) . "',
					`courier_id` = " . (isset($location['courier_id']) ? "'" . (int)$location['courier_id'] . "'" : "NULL") . ",
					`courier_uid` = " . (isset($location['courier_uid']) ? "'" . $this->db->escape($location['courier_uid']) . "'" : "NULL") . ",
					`courier_name` = " . (isset($location['courier_name']) ? "'" . $this->db->escape($location['courier_name']) . "'" : "NULL") . ",
					`woot_country_id` = '" . (int)$woot_country_id . "',
					`woot_county_id` = " . (isset($location['county_id']) ? "'" . (int)$location['county_id'] . "'" : "NULL") . ",
					`woot_city_id` = " . (isset($location['city_id']) ? "'" . (int)$location['city_id'] . "'" : "NULL") . ",
					`address` = " . (isset($location['address']) ? "'" . $this->db->escape($location['address']) . "'" : "NULL") . ",
					`zipcode` = " . (isset($location['zipcode']) ? "'" . $this->db->escape($location['zipcode']) . "'" : "NULL") . ",
					`latitude` = " . (isset($location['latitude']) ? "'" . (float)$location['latitude'] . "'" : "NULL") . ",
					`longitude` = " . (isset($location['longitude']) ? "'" . (float)$location['longitude'] . "'" : "NULL") . ",
					`repayments_card` = '" . (int)($location['repayments_card'] ?? 0) . "',
					`repayments_cash` = '" . (int)($location['repayments_cash'] ?? 0) . "',
					`sender` = '" . (int)($location['sender'] ?? 0) . "',
					`receiver` = '" . (int)($location['receiver'] ?? 0) . "',
					`date_synced` = '" . $this->db->escape($now) . "'
				ON DUPLICATE KEY UPDATE
					`name` = '" . $this->db->escape($location['name'] ?? '') . "',
					`type` = '" . $this->db->escape($type) . "',
					`courier_id` = " . (isset($location['courier_id']) ? "'" . (int)$location['courier_id'] . "'" : "NULL") . ",
					`courier_uid` = " . (isset($location['courier_uid']) ? "'" . $this->db->escape($location['courier_uid']) . "'" : "NULL") . ",
					`courier_name` = " . (isset($location['courier_name']) ? "'" . $this->db->escape($location['courier_name']) . "'" : "NULL") . ",
					`woot_country_id` = '" . (int)$woot_country_id . "',
					`woot_county_id` = " . (isset($location['county_id']) ? "'" . (int)$location['county_id'] . "'" : "NULL") . ",
					`woot_city_id` = " . (isset($location['city_id']) ? "'" . (int)$location['city_id'] . "'" : "NULL") . ",
					`address` = " . (isset($location['address']) ? "'" . $this->db->escape($location['address']) . "'" : "NULL") . ",
					`zipcode` = " . (isset($location['zipcode']) ? "'" . $this->db->escape($location['zipcode']) . "'" : "NULL") . ",
					`latitude` = " . (isset($location['latitude']) ? "'" . (float)$location['latitude'] . "'" : "NULL") . ",
					`longitude` = " . (isset($location['longitude']) ? "'" . (float)$location['longitude'] . "'" : "NULL") . ",
					`repayments_card` = '" . (int)($location['repayments_card'] ?? 0) . "',
					`repayments_cash` = '" . (int)($location['repayments_cash'] ?? 0) . "',
					`sender` = '" . (int)($location['sender'] ?? 0) . "',
					`receiver` = '" . (int)($location['receiver'] ?? 0) . "',
					`date_synced` = '" . $this->db->escape($now) . "'
			");

			$count++;
		}

		return $count;
	}

	/**
	 * Get Woot countries with pagination
	 *
	 * @param array $data Filter/pagination data (start, limit)
	 * @return array
	 */
	public function getWootCountries(array $data = []): array {
		$language_id = (int)$this->config->get('config_language_id');

		$sql = "
			SELECT wc.*, ocd.name AS oc_country_name
			FROM `" . DB_PREFIX . "woot_country` wc
			LEFT JOIN `" . DB_PREFIX . "country` oc ON wc.oc_country_id = oc.country_id
			LEFT JOIN `" . DB_PREFIX . "country_description` ocd ON oc.country_id = ocd.country_id AND ocd.language_id = '" . $language_id . "'
			ORDER BY wc.favorite DESC, wc.sort ASC, wc.name ASC
		";

		if (isset($data['start']) || isset($data['limit'])) {
			$start = isset($data['start']) ? max(0, (int)$data['start']) : 0;
			$limit = isset($data['limit']) ? max(1, (int)$data['limit']) : 20;
			$sql .= " LIMIT " . $start . "," . $limit;
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * Get total count of Woot countries
	 *
	 * @return int
	 */
	public function getTotalWootCountries(): int {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "woot_country`");
		return (int)$query->row['total'];
	}

	/**
	 * Get only Woot countries that are mapped to OpenCart (oc_country_id IS NOT NULL)
	 *
	 * Used for child entity pages (counties, cities, locations) where unmapped countries
	 * cannot be used meaningfully.
	 *
	 * @return array
	 */
	public function getMappedWootCountries(): array {
		$language_id = (int)$this->config->get('config_language_id');

		$sql = "
			SELECT wc.*, ocd.name AS oc_country_name
			FROM `" . DB_PREFIX . "woot_country` wc
			INNER JOIN `" . DB_PREFIX . "country` oc ON wc.oc_country_id = oc.country_id
			LEFT JOIN `" . DB_PREFIX . "country_description` ocd ON oc.country_id = ocd.country_id AND ocd.language_id = '" . $language_id . "'
			WHERE wc.oc_country_id IS NOT NULL
			ORDER BY wc.favorite DESC, wc.sort ASC, wc.name ASC
		";

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * Get mapped Woot countries that have synced AND mapped counties
	 *
	 * Used for cities page where counties must be synced and mapped first.
	 *
	 * @return array
	 */
	public function getMappedCountriesWithCounties(): array {
		$language_id = (int)$this->config->get('config_language_id');

		$sql = "
			SELECT wc.*, ocd.name AS oc_country_name,
				(SELECT COUNT(*) FROM `" . DB_PREFIX . "woot_county` wco
					WHERE wco.woot_country_id = wc.woot_country_id AND wco.oc_zone_id IS NOT NULL) AS mapped_counties
			FROM `" . DB_PREFIX . "woot_country` wc
			INNER JOIN `" . DB_PREFIX . "country` oc ON wc.oc_country_id = oc.country_id
			LEFT JOIN `" . DB_PREFIX . "country_description` ocd ON oc.country_id = ocd.country_id AND ocd.language_id = '" . $language_id . "'
			WHERE wc.oc_country_id IS NOT NULL
			HAVING mapped_counties > 0
			ORDER BY wc.favorite DESC, wc.sort ASC, wc.name ASC
		";

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * Get mapped Woot countries that have synced/mapped counties AND synced cities
	 *
	 * Used for locations page where cities must exist first.
	 *
	 * @return array
	 */
	public function getMappedCountriesWithCities(): array {
		$language_id = (int)$this->config->get('config_language_id');

		$sql = "
			SELECT wc.*, ocd.name AS oc_country_name,
				(SELECT COUNT(*) FROM `" . DB_PREFIX . "woot_county` wco
					WHERE wco.woot_country_id = wc.woot_country_id AND wco.oc_zone_id IS NOT NULL) AS mapped_counties,
				(SELECT COUNT(*) FROM `" . DB_PREFIX . "woot_city` wci
					INNER JOIN `" . DB_PREFIX . "woot_county` wco2 ON wci.woot_county_id = wco2.woot_county_id
					WHERE wco2.woot_country_id = wc.woot_country_id AND wco2.oc_zone_id IS NOT NULL) AS synced_cities
			FROM `" . DB_PREFIX . "woot_country` wc
			INNER JOIN `" . DB_PREFIX . "country` oc ON wc.oc_country_id = oc.country_id
			LEFT JOIN `" . DB_PREFIX . "country_description` ocd ON oc.country_id = ocd.country_id AND ocd.language_id = '" . $language_id . "'
			WHERE wc.oc_country_id IS NOT NULL
			HAVING mapped_counties > 0 AND synced_cities > 0
			ORDER BY wc.favorite DESC, wc.sort ASC, wc.name ASC
		";

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * Get a single Woot country
	 *
	 * @param int $woot_country_id
	 * @return array|null
	 */
	public function getWootCountry(int $woot_country_id): ?array {
		$language_id = (int)$this->config->get('config_language_id');

		$query = $this->db->query("
			SELECT wc.*, ocd.name AS oc_country_name
			FROM `" . DB_PREFIX . "woot_country` wc
			LEFT JOIN `" . DB_PREFIX . "country` oc ON wc.oc_country_id = oc.country_id
			LEFT JOIN `" . DB_PREFIX . "country_description` ocd ON oc.country_id = ocd.country_id AND ocd.language_id = '" . $language_id . "'
			WHERE wc.woot_country_id = '" . (int)$woot_country_id . "'
		");

		return $query->num_rows ? $query->row : null;
	}

	/**
	 * Get Woot counties for a country with pagination
	 *
	 * @param int $woot_country_id
	 * @param array $data Filter/pagination data (start, limit)
	 * @return array
	 */
	public function getWootCountiesByCountry(int $woot_country_id, array $data = []): array {
		$language_id = (int)$this->config->get('config_language_id');

		$sql = "
			SELECT wco.*, zd.name AS oc_zone_name
			FROM `" . DB_PREFIX . "woot_county` wco
			LEFT JOIN `" . DB_PREFIX . "zone` z ON wco.oc_zone_id = z.zone_id
			LEFT JOIN `" . DB_PREFIX . "zone_description` zd ON z.zone_id = zd.zone_id AND zd.language_id = '" . $language_id . "'
			WHERE wco.woot_country_id = '" . (int)$woot_country_id . "'
			ORDER BY wco.name ASC
		";

		if (isset($data['start']) || isset($data['limit'])) {
			$start = isset($data['start']) ? max(0, (int)$data['start']) : 0;
			$limit = isset($data['limit']) ? max(1, (int)$data['limit']) : 20;
			$sql .= " LIMIT " . $start . "," . $limit;
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * Get total count of Woot counties for a country
	 *
	 * @param int $woot_country_id
	 * @return int
	 */
	public function getTotalWootCountiesByCountry(int $woot_country_id): int {
		$query = $this->db->query("
			SELECT COUNT(*) AS total
			FROM `" . DB_PREFIX . "woot_county`
			WHERE woot_country_id = '" . (int)$woot_country_id . "'
		");
		return (int)$query->row['total'];
	}

	/**
	 * Get a single Woot county
	 *
	 * @param int $woot_county_id
	 * @return array|null
	 */
	public function getWootCounty(int $woot_county_id): ?array {
		$language_id = (int)$this->config->get('config_language_id');

		$query = $this->db->query("
			SELECT wco.*, zd.name AS oc_zone_name
			FROM `" . DB_PREFIX . "woot_county` wco
			LEFT JOIN `" . DB_PREFIX . "zone` z ON wco.oc_zone_id = z.zone_id
			LEFT JOIN `" . DB_PREFIX . "zone_description` zd ON z.zone_id = zd.zone_id AND zd.language_id = '" . $language_id . "'
			WHERE wco.woot_county_id = '" . (int)$woot_county_id . "'
		");

		return $query->num_rows ? $query->row : null;
	}

	/**
	 * Get Woot cities for a county
	 *
	 * @param int $woot_county_id
	 * @return array
	 */
	public function getWootCitiesByCounty(int $woot_county_id): array {
		$query = $this->db->query("
			SELECT wci.*, wco.name AS county_name, wco.code AS county_code
			FROM `" . DB_PREFIX . "woot_city` wci
			LEFT JOIN `" . DB_PREFIX . "woot_county` wco ON wci.woot_county_id = wco.woot_county_id
			WHERE wci.woot_county_id = '" . (int)$woot_county_id . "'
			ORDER BY wci.name ASC
		");

		return $query->rows;
	}

	/**
	 * Get Woot cities for a country (with optional county filter) with pagination
	 *
	 * @param int $woot_country_id
	 * @param int|null $woot_county_id Optional county filter
	 * @param array $data Filter/pagination data (start, limit)
	 * @return array
	 */
	public function getWootCitiesByCountry(int $woot_country_id, ?int $woot_county_id = null, array $data = []): array {
		$sql = "
			SELECT wci.*, wco.name AS county_name, wco.code AS county_code
			FROM `" . DB_PREFIX . "woot_city` wci
			LEFT JOIN `" . DB_PREFIX . "woot_county` wco ON wci.woot_county_id = wco.woot_county_id
			WHERE wco.woot_country_id = '" . (int)$woot_country_id . "'
		";

		if ($woot_county_id) {
			$sql .= " AND wci.woot_county_id = '" . (int)$woot_county_id . "'";
		}

		$sql .= " ORDER BY wci.name ASC";

		if (isset($data['start']) || isset($data['limit'])) {
			$start = isset($data['start']) ? max(0, (int)$data['start']) : 0;
			$limit = isset($data['limit']) ? max(1, (int)$data['limit']) : 20;
			$sql .= " LIMIT " . $start . "," . $limit;
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * Get total count of Woot cities for a country (with optional county filter)
	 *
	 * @param int $woot_country_id
	 * @param int|null $woot_county_id Optional county filter
	 * @return int
	 */
	public function getTotalWootCitiesByCountry(int $woot_country_id, ?int $woot_county_id = null): int {
		$sql = "
			SELECT COUNT(*) AS total
			FROM `" . DB_PREFIX . "woot_city` wci
			LEFT JOIN `" . DB_PREFIX . "woot_county` wco ON wci.woot_county_id = wco.woot_county_id
			WHERE wco.woot_country_id = '" . (int)$woot_country_id . "'
		";

		if ($woot_county_id) {
			$sql .= " AND wci.woot_county_id = '" . (int)$woot_county_id . "'";
		}

		$query = $this->db->query($sql);
		return (int)$query->row['total'];
	}

	/**
	 * Get a single Woot city
	 *
	 * @param int $woot_city_id
	 * @return array|null
	 */
	public function getWootCity(int $woot_city_id): ?array {
		$query = $this->db->query("
			SELECT *
			FROM `" . DB_PREFIX . "woot_city`
			WHERE woot_city_id = '" . (int)$woot_city_id . "'
		");

		return $query->num_rows ? $query->row : null;
	}

	/**
	 * Get Woot locations with optional filters and pagination
	 *
	 * @param array $filter Optional filters (woot_country_id, woot_county_id, woot_city_id, type, start, limit)
	 * @return array
	 */
	public function getWootLocations(array $filter = []): array {
		$sql = "
			SELECT wl.*,
				wc.name AS country_name,
				wco.name AS county_name,
				wci.name AS city_name
			FROM `" . DB_PREFIX . "woot_location` wl
			LEFT JOIN `" . DB_PREFIX . "woot_country` wc ON wl.woot_country_id = wc.woot_country_id
			LEFT JOIN `" . DB_PREFIX . "woot_county` wco ON wl.woot_county_id = wco.woot_county_id
			LEFT JOIN `" . DB_PREFIX . "woot_city` wci ON wl.woot_city_id = wci.woot_city_id
			WHERE 1
		";

		if (!empty($filter['woot_country_id'])) {
			$sql .= " AND wl.woot_country_id = '" . (int)$filter['woot_country_id'] . "'";
		}

		if (!empty($filter['woot_county_id'])) {
			$sql .= " AND wl.woot_county_id = '" . (int)$filter['woot_county_id'] . "'";
		}

		if (!empty($filter['woot_city_id'])) {
			$sql .= " AND wl.woot_city_id = '" . (int)$filter['woot_city_id'] . "'";
		}

		if (!empty($filter['type'])) {
			$sql .= " AND wl.type = '" . $this->db->escape($filter['type']) . "'";
		}

		$sql .= " ORDER BY wl.name ASC";

		if (isset($filter['start']) || isset($filter['limit'])) {
			$start = isset($filter['start']) ? max(0, (int)$filter['start']) : 0;
			$limit = isset($filter['limit']) ? max(1, (int)$filter['limit']) : 20;
			$sql .= " LIMIT " . $start . "," . $limit;
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * Get total count of Woot locations with optional filters
	 *
	 * @param array $filter Optional filters (woot_country_id, woot_county_id, woot_city_id, type)
	 * @return int
	 */
	public function getTotalWootLocations(array $filter = []): int {
		$sql = "
			SELECT COUNT(*) AS total
			FROM `" . DB_PREFIX . "woot_location` wl
			WHERE 1
		";

		if (!empty($filter['woot_country_id'])) {
			$sql .= " AND wl.woot_country_id = '" . (int)$filter['woot_country_id'] . "'";
		}

		if (!empty($filter['woot_county_id'])) {
			$sql .= " AND wl.woot_county_id = '" . (int)$filter['woot_county_id'] . "'";
		}

		if (!empty($filter['woot_city_id'])) {
			$sql .= " AND wl.woot_city_id = '" . (int)$filter['woot_city_id'] . "'";
		}

		if (!empty($filter['type'])) {
			$sql .= " AND wl.type = '" . $this->db->escape($filter['type']) . "'";
		}

		$query = $this->db->query($sql);
		return (int)$query->row['total'];
	}

	/**
	 * Map Woot country to OpenCart country
	 *
	 * @param int $woot_country_id
	 * @param int|null $oc_country_id
	 * @return void
	 */
	public function mapCountryToOpenCart(int $woot_country_id, ?int $oc_country_id): void {
		$this->db->query("
			UPDATE `" . DB_PREFIX . "woot_country` SET
				`oc_country_id` = " . ($oc_country_id ? "'" . (int)$oc_country_id . "'" : "NULL") . "
			WHERE `woot_country_id` = '" . (int)$woot_country_id . "'
		");
	}

	/**
	 * Map Woot county to OpenCart zone
	 *
	 * @param int $woot_county_id
	 * @param int|null $oc_zone_id
	 * @return void
	 */
	public function mapCountyToOpenCart(int $woot_county_id, ?int $oc_zone_id): void {
		$this->db->query("
			UPDATE `" . DB_PREFIX . "woot_county` SET
				`oc_zone_id` = " . ($oc_zone_id ? "'" . (int)$oc_zone_id . "'" : "NULL") . "
			WHERE `woot_county_id` = '" . (int)$woot_county_id . "'
		");
	}

	/**
	 * Auto-map countries by ISO code
	 *
	 * @return int Number of countries mapped
	 */
	public function autoMapCountries(): int {
		// Match by ISO code (Woot code -> OC iso_code_2)
		// Use COLLATE to handle different collations between tables
		$this->db->query("
			UPDATE `" . DB_PREFIX . "woot_country` wc
			INNER JOIN `" . DB_PREFIX . "country` oc ON UPPER(wc.code) COLLATE utf8mb4_unicode_ci = UPPER(oc.iso_code_2) COLLATE utf8mb4_unicode_ci
			SET wc.oc_country_id = oc.country_id
			WHERE wc.oc_country_id IS NULL
		");

		return $this->db->countAffected();
	}

	/**
	 * Auto-map counties by code or name for a country
	 *
	 * @param int $woot_country_id
	 * @return int Number of counties mapped
	 */
	public function autoMapCounties(int $woot_country_id): int {
		// Get the mapped OC country
		$woot_country = $this->getWootCountry($woot_country_id);

		if (!$woot_country || !$woot_country['oc_country_id']) {
			return 0;
		}

		$oc_country_id = (int)$woot_country['oc_country_id'];
		$language_id = (int)$this->config->get('config_language_id');
		$mapped = 0;

		// First try: Match by code (Woot code -> OC zone.code)
		// Use COLLATE to handle different collations between tables
		$this->db->query("
			UPDATE `" . DB_PREFIX . "woot_county` wco
			INNER JOIN `" . DB_PREFIX . "zone` z ON UPPER(wco.code) COLLATE utf8mb4_unicode_ci = UPPER(z.code) COLLATE utf8mb4_unicode_ci AND z.country_id = '" . $oc_country_id . "'
			SET wco.oc_zone_id = z.zone_id
			WHERE wco.woot_country_id = '" . (int)$woot_country_id . "'
				AND wco.oc_zone_id IS NULL
		");

		$mapped += $this->db->countAffected();

		// Second try: Match by name (exact match, case-insensitive) using zone_description
		// Use COLLATE to handle different collations between tables
		$this->db->query("
			UPDATE `" . DB_PREFIX . "woot_county` wco
			INNER JOIN `" . DB_PREFIX . "zone_description` zd ON LOWER(wco.name) COLLATE utf8mb4_unicode_ci = LOWER(zd.name) COLLATE utf8mb4_unicode_ci AND zd.language_id = '" . $language_id . "'
			INNER JOIN `" . DB_PREFIX . "zone` z ON zd.zone_id = z.zone_id AND z.country_id = '" . $oc_country_id . "'
			SET wco.oc_zone_id = z.zone_id
			WHERE wco.woot_country_id = '" . (int)$woot_country_id . "'
				AND wco.oc_zone_id IS NULL
		");

		$mapped += $this->db->countAffected();

		return $mapped;
	}

	/**
	 * Get sync status (counts)
	 *
	 * @return array
	 */
	public function getSyncStatus(): array {
		$countries = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "woot_country`");
		$counties = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "woot_county`");
		$cities = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "woot_city`");
		$locations = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "woot_location`");

		$mapped_countries = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "woot_country` WHERE oc_country_id IS NOT NULL");
		$mapped_counties = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "woot_county` WHERE oc_zone_id IS NOT NULL");

		return [
			'countries'        => (int)$countries->row['total'],
			'counties'         => (int)$counties->row['total'],
			'cities'           => (int)$cities->row['total'],
			'locations'        => (int)$locations->row['total'],
			'mapped_countries' => (int)$mapped_countries->row['total'],
			'mapped_counties'  => (int)$mapped_counties->row['total'],
		];
	}
}
