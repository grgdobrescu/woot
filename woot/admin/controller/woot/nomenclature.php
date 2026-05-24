<?php
namespace Opencart\Admin\Controller\Extension\Woot\Woot;

// Load Woot API library (not autoloaded by OpenCart)
require_once(DIR_EXTENSION . 'woot/system/library/woot/api.php');

use Opencart\System\Library\Woot\Api as WootApi;

/**
 * Woot Nomenclature Controller
 *
 * Manages Woot nomenclature data (countries, counties, cities, locations).
 */
class Nomenclature extends \Opencart\System\Engine\Controller {
	/**
	 * Default pagination limit
	 */
	private const PAGINATION_LIMIT = 20;

	/**
	 * Display countries list with mapping
	 *
	 * @return void
	 */
	public function countries(): void {
		$this->load->language('extension/woot/woot/nomenclature');

		$this->document->setTitle($this->language->get('heading_title_countries'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title_countries'),
			'href' => $this->url->link('extension/woot/woot/nomenclature.countries', 'user_token=' . $this->session->data['user_token'])
		];

		// URLs for AJAX actions
		$data['sync_url'] = $this->url->link('extension/woot/woot/nomenclature.syncCountries', 'user_token=' . $this->session->data['user_token']);
		$data['save_mapping_url'] = $this->url->link('extension/woot/woot/nomenclature.saveCountryMapping', 'user_token=' . $this->session->data['user_token']);
		$data['auto_map_url'] = $this->url->link('extension/woot/woot/nomenclature.autoMapCountries', 'user_token=' . $this->session->data['user_token']);
		$data['counties_url'] = $this->url->link('extension/woot/woot/nomenclature.counties', 'user_token=' . $this->session->data['user_token']);

		// Pagination
		$page = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
		$limit = self::PAGINATION_LIMIT;

		$filter_data = [
			'start' => ($page - 1) * $limit,
			'limit' => $limit
		];

		// Get Woot countries
		$this->load->model('extension/woot/woot/nomenclature');
		$data['woot_countries'] = $this->model_extension_woot_woot_nomenclature->getWootCountries($filter_data);

		$country_total = $this->model_extension_woot_woot_nomenclature->getTotalWootCountries();

		// Get OpenCart countries for mapping dropdown
		$this->load->model('localisation/country');
		$data['oc_countries'] = $this->model_localisation_country->getCountries();

		// Pagination
		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $country_total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('extension/woot/woot/nomenclature.countries', 'user_token=' . $this->session->data['user_token'] . '&page={page}')
		]);

		$data['results'] = sprintf(
			$this->language->get('text_pagination'),
			$country_total ? (($page - 1) * $limit) + 1 : 0,
			((($page - 1) * $limit) > ($country_total - $limit)) ? $country_total : ((($page - 1) * $limit) + $limit),
			$country_total,
			ceil($country_total / $limit)
		);

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/woot/woot/nomenclature_countries', $data));
	}

	/**
	 * Display counties list with mapping
	 *
	 * @return void
	 */
	public function counties(): void {
		$this->load->language('extension/woot/woot/nomenclature');

		$this->document->setTitle($this->language->get('heading_title_counties'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title_countries'),
			'href' => $this->url->link('extension/woot/woot/nomenclature.countries', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title_counties'),
			'href' => $this->url->link('extension/woot/woot/nomenclature.counties', 'user_token=' . $this->session->data['user_token'])
		];

		// Get selected country filter
		$woot_country_id = isset($this->request->get['woot_country_id']) ? (int)$this->request->get['woot_country_id'] : 0;
		$data['woot_country_id'] = $woot_country_id;

		// URLs for AJAX actions
		$data['sync_url'] = $this->url->link('extension/woot/woot/nomenclature.syncCounties', 'user_token=' . $this->session->data['user_token']);
		$data['save_mapping_url'] = $this->url->link('extension/woot/woot/nomenclature.saveCountyMapping', 'user_token=' . $this->session->data['user_token']);
		$data['auto_map_url'] = $this->url->link('extension/woot/woot/nomenclature.autoMapCounties', 'user_token=' . $this->session->data['user_token']);
		$data['cities_url'] = $this->url->link('extension/woot/woot/nomenclature.cities', 'user_token=' . $this->session->data['user_token']);
		$data['countries_url'] = $this->url->link('extension/woot/woot/nomenclature.countries', 'user_token=' . $this->session->data['user_token']);

		// Pagination
		$page = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
		$limit = self::PAGINATION_LIMIT;

		$filter_data = [
			'start' => ($page - 1) * $limit,
			'limit' => $limit
		];

		// Get mapped Woot countries for filter dropdown (only countries with oc_country_id)
		$this->load->model('extension/woot/woot/nomenclature');
		$data['woot_countries'] = $this->model_extension_woot_woot_nomenclature->getMappedWootCountries();

		// Get Woot counties for selected country
		if ($woot_country_id) {
			$data['woot_counties'] = $this->model_extension_woot_woot_nomenclature->getWootCountiesByCountry($woot_country_id, $filter_data);
			$county_total = $this->model_extension_woot_woot_nomenclature->getTotalWootCountiesByCountry($woot_country_id);

			// Get the mapped OC country to get its zones
			$woot_country = $this->model_extension_woot_woot_nomenclature->getWootCountry($woot_country_id);
			if ($woot_country && $woot_country['oc_country_id']) {
				$this->load->model('localisation/zone');
				$data['oc_zones'] = $this->model_localisation_zone->getZonesByCountryId($woot_country['oc_country_id']);
			} else {
				$data['oc_zones'] = [];
			}

			// Pagination
			$data['pagination'] = $this->load->controller('common/pagination', [
				'total' => $county_total,
				'page'  => $page,
				'limit' => $limit,
				'url'   => $this->url->link('extension/woot/woot/nomenclature.counties', 'user_token=' . $this->session->data['user_token'] . '&woot_country_id=' . $woot_country_id . '&page={page}')
			]);

			$data['results'] = sprintf(
				$this->language->get('text_pagination'),
				$county_total ? (($page - 1) * $limit) + 1 : 0,
				((($page - 1) * $limit) > ($county_total - $limit)) ? $county_total : ((($page - 1) * $limit) + $limit),
				$county_total,
				ceil($county_total / $limit)
			);
		} else {
			$data['woot_counties'] = [];
			$data['oc_zones'] = [];
			$data['pagination'] = '';
			$data['results'] = '';
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/woot/woot/nomenclature_counties', $data));
	}

	/**
	 * Display cities browser (read-only)
	 *
	 * @return void
	 */
	public function cities(): void {
		$this->load->language('extension/woot/woot/nomenclature');

		$this->document->setTitle($this->language->get('heading_title_cities'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title_countries'),
			'href' => $this->url->link('extension/woot/woot/nomenclature.countries', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title_cities'),
			'href' => $this->url->link('extension/woot/woot/nomenclature.cities', 'user_token=' . $this->session->data['user_token'])
		];

		// Get selected filters
		$woot_country_id = isset($this->request->get['woot_country_id']) ? (int)$this->request->get['woot_country_id'] : 0;
		$woot_county_id = isset($this->request->get['woot_county_id']) ? (int)$this->request->get['woot_county_id'] : 0;
		$data['woot_country_id'] = $woot_country_id;
		$data['woot_county_id'] = $woot_county_id;

		// URLs for AJAX actions
		$data['sync_url'] = $this->url->link('extension/woot/woot/nomenclature.syncCities', 'user_token=' . $this->session->data['user_token']);
		$data['get_counties_url'] = $this->url->link('extension/woot/woot/nomenclature.getCountiesJson', 'user_token=' . $this->session->data['user_token']);
		$data['countries_url'] = $this->url->link('extension/woot/woot/nomenclature.countries', 'user_token=' . $this->session->data['user_token']);
		$data['counties_url'] = $this->url->link('extension/woot/woot/nomenclature.counties', 'user_token=' . $this->session->data['user_token']);

		// Pagination
		$page = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
		$limit = self::PAGINATION_LIMIT;

		$filter_data = [
			'start' => ($page - 1) * $limit,
			'limit' => $limit
		];

		// Get mapped Woot countries that have synced counties (required for cities)
		$this->load->model('extension/woot/woot/nomenclature');
		$data['woot_countries'] = $this->model_extension_woot_woot_nomenclature->getMappedCountriesWithCounties();

		if ($woot_country_id) {
			$data['woot_counties'] = $this->model_extension_woot_woot_nomenclature->getWootCountiesByCountry($woot_country_id);
		} else {
			$data['woot_counties'] = [];
		}

		// Get Woot cities for selected country (with optional county filter)
		if ($woot_country_id) {
			$data['woot_cities'] = $this->model_extension_woot_woot_nomenclature->getWootCitiesByCountry($woot_country_id, $woot_county_id ?: null, $filter_data);
			$city_total = $this->model_extension_woot_woot_nomenclature->getTotalWootCitiesByCountry($woot_country_id, $woot_county_id ?: null);

			// Build pagination URL with filters
			$pagination_url = 'user_token=' . $this->session->data['user_token'] . '&woot_country_id=' . $woot_country_id;
			if ($woot_county_id) {
				$pagination_url .= '&woot_county_id=' . $woot_county_id;
			}

			$data['pagination'] = $this->load->controller('common/pagination', [
				'total' => $city_total,
				'page'  => $page,
				'limit' => $limit,
				'url'   => $this->url->link('extension/woot/woot/nomenclature.cities', $pagination_url . '&page={page}')
			]);

			$data['results'] = sprintf(
				$this->language->get('text_pagination'),
				$city_total ? (($page - 1) * $limit) + 1 : 0,
				((($page - 1) * $limit) > ($city_total - $limit)) ? $city_total : ((($page - 1) * $limit) + $limit),
				$city_total,
				ceil($city_total / $limit)
			);
		} else {
			$data['woot_cities'] = [];
			$data['pagination'] = '';
			$data['results'] = '';
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/woot/woot/nomenclature_cities', $data));
	}

	/**
	 * Display locations browser
	 *
	 * @return void
	 */
	public function locations(): void {
		$this->load->language('extension/woot/woot/nomenclature');

		$this->document->setTitle($this->language->get('heading_title_locations'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title_countries'),
			'href' => $this->url->link('extension/woot/woot/nomenclature.countries', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title_locations'),
			'href' => $this->url->link('extension/woot/woot/nomenclature.locations', 'user_token=' . $this->session->data['user_token'])
		];

		// Get selected filters
		$woot_country_id = isset($this->request->get['woot_country_id']) ? (int)$this->request->get['woot_country_id'] : 0;
		$woot_county_id = isset($this->request->get['woot_county_id']) ? (int)$this->request->get['woot_county_id'] : 0;
		$woot_city_id = isset($this->request->get['woot_city_id']) ? (int)$this->request->get['woot_city_id'] : 0;
		$type = isset($this->request->get['type']) ? $this->request->get['type'] : '';

		$data['woot_country_id'] = $woot_country_id;
		$data['woot_county_id'] = $woot_county_id;
		$data['woot_city_id'] = $woot_city_id;
		$data['type'] = $type;

		// URLs for AJAX actions
		$data['sync_url'] = $this->url->link('extension/woot/woot/nomenclature.syncLocations', 'user_token=' . $this->session->data['user_token']);
		$data['get_counties_url'] = $this->url->link('extension/woot/woot/nomenclature.getCountiesJson', 'user_token=' . $this->session->data['user_token']);
		$data['get_cities_url'] = $this->url->link('extension/woot/woot/nomenclature.getCitiesJson', 'user_token=' . $this->session->data['user_token']);
		$data['countries_url'] = $this->url->link('extension/woot/woot/nomenclature.countries', 'user_token=' . $this->session->data['user_token']);
		$data['counties_url'] = $this->url->link('extension/woot/woot/nomenclature.counties', 'user_token=' . $this->session->data['user_token']);
		$data['cities_url'] = $this->url->link('extension/woot/woot/nomenclature.cities', 'user_token=' . $this->session->data['user_token']);

		// Pagination
		$page = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
		$limit = self::PAGINATION_LIMIT;

		// Get mapped Woot countries that have synced counties AND cities (required for locations)
		$this->load->model('extension/woot/woot/nomenclature');
		$data['woot_countries'] = $this->model_extension_woot_woot_nomenclature->getMappedCountriesWithCities();

		if ($woot_country_id) {
			$data['woot_counties'] = $this->model_extension_woot_woot_nomenclature->getWootCountiesByCountry($woot_country_id);
		} else {
			$data['woot_counties'] = [];
		}

		if ($woot_county_id) {
			$data['woot_cities'] = $this->model_extension_woot_woot_nomenclature->getWootCitiesByCounty($woot_county_id);
		} else {
			$data['woot_cities'] = [];
		}

		// Build filter for locations
		$filter = [
			'start' => ($page - 1) * $limit,
			'limit' => $limit
		];
		if ($woot_country_id) {
			$filter['woot_country_id'] = $woot_country_id;
		}
		if ($woot_county_id) {
			$filter['woot_county_id'] = $woot_county_id;
		}
		if ($woot_city_id) {
			$filter['woot_city_id'] = $woot_city_id;
		}
		if ($type) {
			$filter['type'] = $type;
		}

		// Get locations with filter
		$data['woot_locations'] = $this->model_extension_woot_woot_nomenclature->getWootLocations($filter);
		$location_total = $this->model_extension_woot_woot_nomenclature->getTotalWootLocations($filter);

		// Build pagination URL with filters
		$pagination_url = 'user_token=' . $this->session->data['user_token'];
		if ($woot_country_id) {
			$pagination_url .= '&woot_country_id=' . $woot_country_id;
		}
		if ($woot_county_id) {
			$pagination_url .= '&woot_county_id=' . $woot_county_id;
		}
		if ($woot_city_id) {
			$pagination_url .= '&woot_city_id=' . $woot_city_id;
		}
		if ($type) {
			$pagination_url .= '&type=' . $type;
		}

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $location_total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('extension/woot/woot/nomenclature.locations', $pagination_url . '&page={page}')
		]);

		$data['results'] = sprintf(
			$this->language->get('text_pagination'),
			$location_total ? (($page - 1) * $limit) + 1 : 0,
			((($page - 1) * $limit) > ($location_total - $limit)) ? $location_total : ((($page - 1) * $limit) + $limit),
			$location_total,
			ceil($location_total / $limit)
		);

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/woot/woot/nomenclature_locations', $data));
	}

	/**
	 * AJAX: Sync countries from API
	 *
	 * @return void
	 */
	public function syncCountries(): void {
		$this->load->language('extension/woot/woot/nomenclature');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/woot/woot/nomenclature')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$public_key = $this->config->get('shipping_woot_public_key');
			$secret_key = $this->config->get('shipping_woot_secret_key');

			if (!$public_key || !$secret_key) {
				$json['error'] = $this->language->get('error_not_connected');
			}
		}

		if (!$json) {
			$api = new WootApi($public_key, $secret_key);
			$countries = $api->getCountries();

			if ($countries !== false) {
				$this->load->model('extension/woot/woot/nomenclature');
				$count = $this->model_extension_woot_woot_nomenclature->saveCountries($countries);

				$json['success'] = sprintf($this->language->get('text_sync_countries_success'), $count);
				$json['count'] = $count;
			} else {
				$json['error'] = $this->language->get('error_sync_countries');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * AJAX: Sync counties for a country from API
	 *
	 * @return void
	 */
	public function syncCounties(): void {
		$this->load->language('extension/woot/woot/nomenclature');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/woot/woot/nomenclature')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$woot_country_id = isset($this->request->post['woot_country_id']) ? (int)$this->request->post['woot_country_id'] : 0;

		if (!$woot_country_id) {
			$json['error'] = $this->language->get('error_country_required');
		}

		if (!$json) {
			$public_key = $this->config->get('shipping_woot_public_key');
			$secret_key = $this->config->get('shipping_woot_secret_key');

			if (!$public_key || !$secret_key) {
				$json['error'] = $this->language->get('error_not_connected');
			}
		}

		if (!$json) {
			$api = new WootApi($public_key, $secret_key);
			$counties = $api->getCounties($woot_country_id);

			if ($counties !== false) {
				$this->load->model('extension/woot/woot/nomenclature');
				$count = $this->model_extension_woot_woot_nomenclature->saveCounties($woot_country_id, $counties);

				$json['success'] = sprintf($this->language->get('text_sync_counties_success'), $count);
				$json['count'] = $count;
			} else {
				$json['error'] = $this->language->get('error_sync_counties');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * AJAX: Sync cities for a country from API
	 *
	 * @return void
	 */
	public function syncCities(): void {
		$this->load->language('extension/woot/woot/nomenclature');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/woot/woot/nomenclature')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$woot_country_id = isset($this->request->post['woot_country_id']) ? (int)$this->request->post['woot_country_id'] : 0;

		if (!$woot_country_id) {
			$json['error'] = $this->language->get('error_country_required');
		}

		if (!$json) {
			$public_key = $this->config->get('shipping_woot_public_key');
			$secret_key = $this->config->get('shipping_woot_secret_key');

			if (!$public_key || !$secret_key) {
				$json['error'] = $this->language->get('error_not_connected');
			}
		}

		if (!$json) {
			$api = new WootApi($public_key, $secret_key);
			$cities = $api->getCities($woot_country_id);

			if ($cities !== false) {
				$this->load->model('extension/woot/woot/nomenclature');
				$count = $this->model_extension_woot_woot_nomenclature->saveCities($woot_country_id, $cities);

				$json['success'] = sprintf($this->language->get('text_sync_cities_success'), $count);
				$json['count'] = $count;
			} else {
				$json['error'] = $this->language->get('error_sync_cities');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * AJAX: Sync locations for a country from API
	 *
	 * @return void
	 */
	public function syncLocations(): void {
		$this->load->language('extension/woot/woot/nomenclature');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/woot/woot/nomenclature')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$woot_country_id = isset($this->request->post['woot_country_id']) ? (int)$this->request->post['woot_country_id'] : 0;

		if (!$woot_country_id) {
			$json['error'] = $this->language->get('error_country_required');
		}

		if (!$json) {
			$public_key = $this->config->get('shipping_woot_public_key');
			$secret_key = $this->config->get('shipping_woot_secret_key');

			if (!$public_key || !$secret_key) {
				$json['error'] = $this->language->get('error_not_connected');
			}
		}

		if (!$json) {
			$api = new WootApi($public_key, $secret_key);
			$locations = $api->getLocations($woot_country_id);

			if ($locations !== false) {
				$this->load->model('extension/woot/woot/nomenclature');
				$count = $this->model_extension_woot_woot_nomenclature->saveLocations($woot_country_id, $locations);

				$json['success'] = sprintf($this->language->get('text_sync_locations_success'), $count);
				$json['count'] = $count;
			} else {
				$json['error'] = $this->language->get('error_sync_locations');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * AJAX: Save country mapping
	 *
	 * @return void
	 */
	public function saveCountryMapping(): void {
		$this->load->language('extension/woot/woot/nomenclature');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/woot/woot/nomenclature')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$woot_country_id = isset($this->request->post['woot_country_id']) ? (int)$this->request->post['woot_country_id'] : 0;
		$oc_country_id = isset($this->request->post['oc_country_id']) ? (int)$this->request->post['oc_country_id'] : null;

		if (!$woot_country_id) {
			$json['error'] = $this->language->get('error_country_required');
		}

		if (!$json) {
			$this->load->model('extension/woot/woot/nomenclature');
			$this->model_extension_woot_woot_nomenclature->mapCountryToOpenCart($woot_country_id, $oc_country_id ?: null);

			$json['success'] = $this->language->get('text_mapping_saved');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * AJAX: Save county/zone mapping
	 *
	 * @return void
	 */
	public function saveCountyMapping(): void {
		$this->load->language('extension/woot/woot/nomenclature');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/woot/woot/nomenclature')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$woot_county_id = isset($this->request->post['woot_county_id']) ? (int)$this->request->post['woot_county_id'] : 0;
		$oc_zone_id = isset($this->request->post['oc_zone_id']) ? (int)$this->request->post['oc_zone_id'] : null;

		if (!$woot_county_id) {
			$json['error'] = $this->language->get('error_county_required');
		}

		if (!$json) {
			$this->load->model('extension/woot/woot/nomenclature');
			$this->model_extension_woot_woot_nomenclature->mapCountyToOpenCart($woot_county_id, $oc_zone_id ?: null);

			$json['success'] = $this->language->get('text_mapping_saved');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * AJAX: Auto-map countries by ISO code
	 *
	 * @return void
	 */
	public function autoMapCountries(): void {
		$this->load->language('extension/woot/woot/nomenclature');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/woot/woot/nomenclature')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('extension/woot/woot/nomenclature');
			$mapped = $this->model_extension_woot_woot_nomenclature->autoMapCountries();

			$json['success'] = sprintf($this->language->get('text_auto_map_countries_success'), $mapped);
			$json['mapped'] = $mapped;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * AJAX: Auto-map counties by code or name
	 *
	 * @return void
	 */
	public function autoMapCounties(): void {
		$this->load->language('extension/woot/woot/nomenclature');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/woot/woot/nomenclature')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$woot_country_id = isset($this->request->post['woot_country_id']) ? (int)$this->request->post['woot_country_id'] : 0;

		if (!$woot_country_id) {
			$json['error'] = $this->language->get('error_country_required');
		}

		if (!$json) {
			$this->load->model('extension/woot/woot/nomenclature');
			$mapped = $this->model_extension_woot_woot_nomenclature->autoMapCounties($woot_country_id);

			$json['success'] = sprintf($this->language->get('text_auto_map_counties_success'), $mapped);
			$json['mapped'] = $mapped;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * AJAX: Get counties for a country (JSON)
	 *
	 * @return void
	 */
	public function getCountiesJson(): void {
		$json = [];

		$woot_country_id = isset($this->request->get['woot_country_id']) ? (int)$this->request->get['woot_country_id'] : 0;

		if ($woot_country_id) {
			$this->load->model('extension/woot/woot/nomenclature');
			$json['counties'] = $this->model_extension_woot_woot_nomenclature->getWootCountiesByCountry($woot_country_id);
		} else {
			$json['counties'] = [];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * AJAX: Get cities for a county (JSON)
	 *
	 * @return void
	 */
	public function getCitiesJson(): void {
		$json = [];

		$woot_county_id = isset($this->request->get['woot_county_id']) ? (int)$this->request->get['woot_county_id'] : 0;

		if ($woot_county_id) {
			$this->load->model('extension/woot/woot/nomenclature');
			$json['cities'] = $this->model_extension_woot_woot_nomenclature->getWootCitiesByCounty($woot_county_id);
		} else {
			$json['cities'] = [];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
