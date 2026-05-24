<?php
namespace Opencart\System\Library\Woot;

/**
 * Woot Helper
 *
 * Common helper functions for the Woot shipping module.
 */
class Helper {
	/**
	 * Format address for display
	 *
	 * @param array $address Address data
	 * @return string Formatted address
	 */
	public static function formatAddress(array $address): string {
		$parts = [];

		if (!empty($address['street'])) {
			$parts[] = $address['street'];
		}

		if (!empty($address['number'])) {
			$parts[] = $address['number'];
		}

		if (!empty($address['city'])) {
			$parts[] = $address['city'];
		}

		if (!empty($address['county'])) {
			$parts[] = $address['county'];
		}

		if (!empty($address['postalCode'])) {
			$parts[] = $address['postalCode'];
		}

		if (!empty($address['country'])) {
			$parts[] = $address['country'];
		}

		return implode(', ', $parts);
	}

	/**
	 * Format weight to grams
	 *
	 * @param float $weight Weight value
	 * @param string $unit Weight unit (kg, g, lb, oz)
	 * @return int Weight in grams
	 */
	public static function toGrams(float $weight, string $unit = 'kg'): int {
		switch (strtolower($unit)) {
			case 'g':
				return (int)$weight;
			case 'kg':
				return (int)($weight * 1000);
			case 'lb':
				return (int)($weight * 453.592);
			case 'oz':
				return (int)($weight * 28.3495);
			default:
				return (int)($weight * 1000);
		}
	}

	/**
	 * Format dimensions to cm
	 *
	 * @param float $value Dimension value
	 * @param string $unit Dimension unit (cm, m, mm, in)
	 * @return float Dimension in cm
	 */
	public static function toCm(float $value, string $unit = 'cm'): float {
		switch (strtolower($unit)) {
			case 'cm':
				return $value;
			case 'm':
				return $value * 100;
			case 'mm':
				return $value / 10;
			case 'in':
				return $value * 2.54;
			default:
				return $value;
		}
	}

	/**
	 * Get service type label
	 *
	 * @param string $pickupType Pickup type (door, locker)
	 * @param string $deliveryType Delivery type (door, locker)
	 * @return string Service type description
	 */
	public static function getServiceTypeLabel(string $pickupType, string $deliveryType): string {
		$pickup = ucfirst($pickupType);
		$delivery = ucfirst($deliveryType);

		return "{$pickup} to {$delivery}";
	}

	/**
	 * Sanitize phone number for API
	 *
	 * @param string $phone Phone number
	 * @param string $countryCode Default country code
	 * @return string Sanitized phone number
	 */
	public static function sanitizePhone(string $phone, string $countryCode = 'RO'): string {
		// Remove all non-numeric characters except +
		$phone = preg_replace('/[^0-9+]/', '', $phone);

		// Add country code if not present
		if (strpos($phone, '+') !== 0) {
			if ($countryCode === 'RO' && strpos($phone, '0') === 0) {
				$phone = '+4' . $phone;
			}
		}

		return $phone;
	}

	/**
	 * Generate unique reference for AWB
	 *
	 * @param int $orderId Order ID
	 * @param string $prefix Optional prefix
	 * @return string Unique reference
	 */
	public static function generateReference(int $orderId, string $prefix = 'OC'): string {
		return $prefix . '-' . $orderId . '-' . strtoupper(substr(md5(time()), 0, 6));
	}
}
