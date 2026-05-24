<?php
namespace Opencart\Admin\Model\Extension\Woot\Woot;

/**
 * Woot Service Model
 *
 * Handles service data operations - caching, mapping, configuration.
 */
class Service extends \Opencart\System\Engine\Model {
	/**
	 * Get configured services
	 *
	 * @return array
	 */
	public function getConfiguredServices(): array {
		$services = $this->config->get('shipping_woot_services');

		if (!is_array($services)) {
			return [];
		}

		return $services;
	}

	/**
	 * Get service by ID
	 *
	 * @param string $serviceId Service ID
	 * @return array|null
	 */
	public function getService(string $serviceId): ?array {
		$services = $this->getConfiguredServices();

		return $services[$serviceId] ?? null;
	}

	/**
	 * Check if service is configured
	 *
	 * @param string $serviceId Service ID
	 * @return bool
	 */
	public function isServiceConfigured(string $serviceId): bool {
		return $this->getService($serviceId) !== null;
	}

	/**
	 * Get services by delivery type
	 *
	 * @param string $deliveryType Delivery type (door, locker)
	 * @return array
	 */
	public function getServicesByDeliveryType(string $deliveryType): array {
		$services = $this->getConfiguredServices();
		$filtered = [];

		foreach ($services as $id => $service) {
			if (isset($service['deliveryType']) && $service['deliveryType'] === $deliveryType) {
				$filtered[$id] = $service;
			}
		}

		return $filtered;
	}
}
