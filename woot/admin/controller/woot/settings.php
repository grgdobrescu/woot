<?php
namespace Opencart\Admin\Controller\Extension\Woot\Woot;

/**
 * Woot Settings Controller
 *
 * General settings for Woot module (beyond shipping-specific settings).
 */
class Settings extends \Opencart\System\Engine\Controller {
	/**
	 * Display general settings
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/woot/woot/settings');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/woot/woot/settings', 'user_token=' . $this->session->data['user_token'])
		];

		// TODO: Implement general settings (default parcel dimensions, notifications, etc.)

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/woot/woot/settings', $data));
	}

	/**
	 * Save general settings
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/woot/woot/settings');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/woot/woot/settings')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			// TODO: Save general settings
			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
