<?php
namespace Opencart\Admin\Controller\Extension\Woot\Woot;

/**
 * Woot Order Controller
 *
 * Handles Woot shipping card injection on admin order info page.
 */
class Order extends \Opencart\System\Engine\Controller {
	/**
	 * Inject Woot shipping card into order info page
	 *
	 * Event handler for: admin/view/sale/order_info/after
	 *
	 * @param string &$route
	 * @param array &$data
	 * @param string &$output
	 * @return void
	 */
	public function injectCard(string &$route, array &$data, string &$output): void {
		// Get order_id from request
		if (!isset($this->request->get['order_id'])) {
			return;
		}

		$order_id = (int)$this->request->get['order_id'];

		if (!$order_id) {
			return;
		}

		// Load model
		$this->load->model('extension/woot/woot/order');

		// Get woot order data (if exists)
		$woot_order = $this->model_extension_woot_woot_order->getWootOrder($order_id);

		// Get service info from order's shipping method
		$service_info = $this->model_extension_woot_woot_order->getWootServiceFromOrder($order_id);

		// Only show card for Woot shipping orders
		// Either has woot_order record OR shipping method is woot
		if (!$woot_order && !$service_info) {
			return;
		}

		// If no woot_order record, create empty defaults
		if (!$woot_order) {
			$woot_order = [
				'awb_number' => '',
				'awb_status' => '',
				'woot_location_id' => null
			];
		}

		// Load language
		$this->load->language('extension/woot/woot/order');

		// Prepare data for template
		$card_data = [];

		// Language strings
		$card_data['heading_title'] = $this->language->get('heading_title');
		$card_data['text_pickup_location'] = $this->language->get('text_pickup_location');
		$card_data['text_courier'] = $this->language->get('text_courier');
		$card_data['text_service'] = $this->language->get('text_service');
		$card_data['text_locker'] = $this->language->get('text_locker');
		$card_data['text_shop'] = $this->language->get('text_shop');
		$card_data['text_door_delivery'] = $this->language->get('text_door_delivery');
		$card_data['text_location_delivery'] = $this->language->get('text_location_delivery');
		$card_data['text_awb'] = $this->language->get('text_awb');
		$card_data['text_no_awb'] = $this->language->get('text_no_awb');

		$card_data['button_generate_awb'] = $this->language->get('button_generate_awb');
		$card_data['button_print_a4'] = $this->language->get('button_print_a4');
		$card_data['button_print_a6'] = $this->language->get('button_print_a6');
		$card_data['button_view_order'] = $this->language->get('button_view_order');
		$card_data['button_cancel_awb'] = $this->language->get('button_cancel_awb');
		$card_data['text_confirm_cancel'] = $this->language->get('text_confirm_cancel');

		// Woot order data
		$card_data['order_id'] = $order_id;
		$card_data['awb_number'] = $woot_order['awb_number'] ?? '';
		$card_data['awb_status'] = $woot_order['awb_status'] ?? '';
		$card_data['has_awb'] = !empty($woot_order['awb_number']);

		// Service info (already fetched above)
		$card_data['service_name'] = $service_info['service_name'] ?? '';
		$card_data['service_courier_name'] = $service_info['courier_name'] ?? '';
		$card_data['delivery_type'] = $service_info['delivery_type'] ?? 'door';

		// Location data (for pickup point deliveries)
		$card_data['has_location'] = !empty($woot_order['woot_location_id']);

		if ($card_data['has_location']) {
			$location = $this->model_extension_woot_woot_order->getLocation($woot_order['woot_location_id']);

			if ($location) {
				$card_data['location_name'] = $location['name'];
				$card_data['location_type'] = $location['type']; // 'locker' or 'shop'
				$card_data['location_address'] = $location['address'];
				$card_data['location_city'] = $location['city_name'] ?? '';
				$card_data['location_county'] = $location['county_name'] ?? '';
				// Use location's courier if available, otherwise use service courier
				$card_data['courier_name'] = $location['courier_name'] ?? $card_data['service_courier_name'];

				// Build full address
				$address_parts = array_filter([
					$location['address'],
					$location['city_name'] ?? '',
					$location['county_name'] ?? ''
				]);
				$card_data['location_full_address'] = implode(', ', $address_parts);
			} else {
				$card_data['has_location'] = false;
			}
		}

		// For door delivery, use service courier name
		if (!$card_data['has_location']) {
			$card_data['courier_name'] = $card_data['service_courier_name'];
		}

		// URLs for buttons
		$card_data['generate_awb_url'] = $this->url->link('extension/woot/woot/awb.generate', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id);
		$card_data['print_a4_url'] = $this->url->link('extension/woot/woot/awb.print', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id . '&format=a4');
		$card_data['print_a6_url'] = $this->url->link('extension/woot/woot/awb.print', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id . '&format=a6');

		// Woot platform view URL
		if (!empty($woot_order['woot_shipment_id'])) {
			$card_data['view_order_url'] = 'https://pro.woot.ro/shipments/orders/view/' . $woot_order['woot_shipment_id'];
		} else {
			$card_data['view_order_url'] = '';
		}

		// Cancel AWB URL
		$card_data['cancel_awb_url'] = $this->url->link('extension/woot/woot/awb.cancel', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $order_id);

		// Render the card template
		$card_html = $this->load->view('extension/woot/woot/order_card', $card_data);

		// Prevent double injection
		if (strpos($output, 'id="woot-shipping-card"') !== false) {
			return;
		}

		// Inject before the first card on the page
		$first_card = strpos($output, '<div class="card mb-3">');

		if ($first_card !== false) {
			$output = substr($output, 0, $first_card) . $card_html . "\n" . substr($output, $first_card);
		}
	}
}
