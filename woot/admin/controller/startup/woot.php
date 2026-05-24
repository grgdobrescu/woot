<?php
namespace Opencart\Admin\Controller\Extension\Woot\Startup;

/**
 * Woot Startup Controller
 *
 * Adds Woot PRO menu to admin sidebar.
 */
class Woot extends \Opencart\System\Engine\Controller {
	/**
	 * Add Woot PRO menu to admin sidebar
	 *
	 * @param string $route
	 * @param array $args
	 * @param mixed $output
	 * @return void
	 */
	public function index(string &$route, array &$args, mixed &$output): void {
		if (!$this->user->hasPermission('access', 'extension/woot/woot/nomenclature')) {
			return;
		}

		$this->load->language('extension/woot/shipping/woot');

		// Find position to insert menu (after Extensions or at end)
		$position = count($args['menus']);
		foreach ($args['menus'] as $key => $menu) {
			if ($menu['id'] == 'menu-extension') {
				$position = $key + 1;
				break;
			}
		}

		// Build Woot PRO menu
		$woot_menu = [
			'id'       => 'menu-woot',
			'icon'     => 'fa-solid fa-truck-fast',
			'name'     => 'Woot PRO',
			'href'     => '',
			'children' => [
				[
					'name'     => $this->language->get('text_shipping_settings'),
					'href'     => $this->url->link('extension/woot/shipping/woot', 'user_token=' . $this->session->data['user_token']),
					'children' => []
				],
				[
					'name'     => $this->language->get('text_countries'),
					'href'     => $this->url->link('extension/woot/woot/nomenclature.countries', 'user_token=' . $this->session->data['user_token']),
					'children' => []
				],
				[
					'name'     => $this->language->get('text_counties'),
					'href'     => $this->url->link('extension/woot/woot/nomenclature.counties', 'user_token=' . $this->session->data['user_token']),
					'children' => []
				],
				[
					'name'     => $this->language->get('text_cities'),
					'href'     => $this->url->link('extension/woot/woot/nomenclature.cities', 'user_token=' . $this->session->data['user_token']),
					'children' => []
				],
				[
					'name'     => $this->language->get('text_locations'),
					'href'     => $this->url->link('extension/woot/woot/nomenclature.locations', 'user_token=' . $this->session->data['user_token']),
					'children' => []
				]
			]
		];

		// Insert menu at position
		array_splice($args['menus'], $position, 0, [$woot_menu]);
	}
}
