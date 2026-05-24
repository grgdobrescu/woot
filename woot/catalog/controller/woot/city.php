<?php
namespace Opencart\Catalog\Controller\Extension\Woot\Woot;

/**
 * Woot City Controller
 *
 * Handles city selection AJAX requests for checkout.
 */
class City extends \Opencart\System\Engine\Controller {
	/**
	 * Get cities for a zone (AJAX)
	 *
	 * Returns cities for the given OpenCart zone_id, using the Woot county mapping.
	 *
	 * @return void
	 */
	public function getCities(): void {
		$json = [];

		$zone_id = isset($this->request->get['zone_id']) ? (int)$this->request->get['zone_id'] : 0;

		if (!$zone_id) {
			$json['cities'] = [];
		} else {
			$this->load->model('extension/woot/woot/nomenclature');
			$cities = $this->model_extension_woot_woot_nomenclature->getCitiesByZone($zone_id);
			$json['cities'] = $cities;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Search cities by name (AJAX)
	 *
	 * For large city lists, allows searching by partial name.
	 *
	 * @return void
	 */
	public function searchCities(): void {
		$json = [];

		$zone_id = isset($this->request->get['zone_id']) ? (int)$this->request->get['zone_id'] : 0;
		$query = isset($this->request->get['query']) ? trim($this->request->get['query']) : '';

		if (!$zone_id) {
			$json['cities'] = [];
		} else {
			$this->load->model('extension/woot/woot/nomenclature');
			$cities = $this->model_extension_woot_woot_nomenclature->searchCities($zone_id, $query);
			$json['cities'] = $cities;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Get city by ID (AJAX)
	 *
	 * @return void
	 */
	public function getCity(): void {
		$json = [];

		$woot_city_id = isset($this->request->get['woot_city_id']) ? (int)$this->request->get['woot_city_id'] : 0;

		if (!$woot_city_id) {
			$json['city'] = null;
		} else {
			$this->load->model('extension/woot/woot/nomenclature');
			$city = $this->model_extension_woot_woot_nomenclature->getCityById($woot_city_id);
			$json['city'] = $city;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Check if zone has Woot cities mapped (AJAX)
	 *
	 * Used to determine whether to show city dropdown or text input.
	 *
	 * @return void
	 */
	public function hasWootCities(): void {
		$json = [];

		$zone_id = isset($this->request->get['zone_id']) ? (int)$this->request->get['zone_id'] : 0;

		if (!$zone_id) {
			$json['has_cities'] = false;
		} else {
			$this->load->model('extension/woot/woot/nomenclature');
			$json['has_cities'] = $this->model_extension_woot_woot_nomenclature->hasWootCities($zone_id);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
