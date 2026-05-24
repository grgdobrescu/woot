<?php
namespace Opencart\Catalog\Controller\Extension\Woot\Woot;

// Load Woot API library (not autoloaded by OpenCart)
require_once(DIR_EXTENSION . 'woot/system/library/woot/api.php');

use Opencart\System\Library\Woot\Api as WootApi;

/**
 * Woot Locker Controller
 *
 * Handles locker/pickup point selection in checkout.
 */
class Locker extends \Opencart\System\Engine\Controller {
	/**
	 * Display locker selection modal
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/woot/woot/locker');

		$data['heading_title'] = $this->language->get('heading_title');

		// TODO: Implement locker selection modal

		$this->response->setOutput($this->load->view('extension/woot/woot/locker', $data));
	}

	/**
	 * Get lockers by location
	 *
	 * @return void
	 */
	public function getLockers(): void {
		$this->load->language('extension/woot/woot/locker');

		$json = [];

		$city = $this->request->get['city'] ?? '';
		$county = $this->request->get['county'] ?? '';
		$courier_id = $this->request->get['courier_id'] ?? '';

		if (!$city) {
			$json['error'] = $this->language->get('error_city');
		}

		if (!$json) {
			$public_key = $this->config->get('shipping_woot_public_key');
			$secret_key = $this->config->get('shipping_woot_secret_key');

			$api = new WootApi($public_key, $secret_key, $this->session->data);

			// TODO: Implement locker search via API
			$json['lockers'] = [];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Search lockers by address/query
	 *
	 * @return void
	 */
	public function search(): void {
		$this->load->language('extension/woot/woot/locker');

		$json = [];

		$query = $this->request->get['query'] ?? '';

		if (strlen($query) < 3) {
			$json['error'] = $this->language->get('error_query_length');
		}

		if (!$json) {
			$public_key = $this->config->get('shipping_woot_public_key');
			$secret_key = $this->config->get('shipping_woot_secret_key');

			$api = new WootApi($public_key, $secret_key, $this->session->data);

			// TODO: Implement locker search via API
			$json['lockers'] = [];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Get locker details
	 *
	 * @return void
	 */
	public function info(): void {
		$json = [];

		$locker_id = $this->request->get['locker_id'] ?? '';

		if (!$locker_id) {
			$json['error'] = 'Locker ID required';
		}

		if (!$json) {
			$public_key = $this->config->get('shipping_woot_public_key');
			$secret_key = $this->config->get('shipping_woot_secret_key');

			$api = new WootApi($public_key, $secret_key, $this->session->data);

			// TODO: Get locker details from API
			$json['locker'] = null;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
