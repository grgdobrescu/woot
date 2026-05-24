<?php
namespace Opencart\System\Library\Woot;

/**
 * Woot API Client
 *
 * Handles all communication with the Woot API.
 * Used by both admin and catalog sides.
 */
class Api {
	/**
	 * API Base URL
	 */
	private const API_URL = 'https://ws.woot.ro/latest';

	/**
	 * @var string|null
	 */
	private ?string $publicKey = null;

	/**
	 * @var string|null
	 */
	private ?string $secretKey = null;

	/**
	 * @var string|null Cached token
	 */
	private ?string $token = null;

	/**
	 * @var int Token expiry timestamp
	 */
	private int $tokenExpires = 0;

	/**
	 * @var array Session reference for token caching
	 */
	private array $session = [];

	/**
	 * Constructor
	 *
	 * @param string|null $publicKey
	 * @param string|null $secretKey
	 * @param array $session Reference to session data for token caching
	 */
	public function __construct(?string $publicKey = null, ?string $secretKey = null, array &$session = []) {
		$this->publicKey = $publicKey;
		$this->secretKey = $secretKey;
		$this->session = &$session;

		// Load cached token from session
		if (isset($session['woot_token']) && isset($session['woot_token_expires'])) {
			if ($session['woot_token_expires'] > time()) {
				$this->token = $session['woot_token'];
				$this->tokenExpires = $session['woot_token_expires'];
			}
		}
	}

	/**
	 * Set API credentials
	 *
	 * @param string $publicKey
	 * @param string $secretKey
	 * @return void
	 */
	public function setCredentials(string $publicKey, string $secretKey): void {
		$this->publicKey = $publicKey;
		$this->secretKey = $secretKey;
	}

	/**
	 * Test API connection with provided credentials
	 *
	 * @param string $publicKey
	 * @param string $secretKey
	 * @return array{success: bool, token?: string, error?: string}
	 */
	public function testConnection(string $publicKey, string $secretKey): array {
		$url = self::API_URL . '/account/authorize';

		$data = [
			'public_key' => $publicKey,
			'secret_key' => $secretKey
		];

		$response = $this->request($url, $data);

		if ($response['success'] && !empty($response['data']['success']) && isset($response['data']['token'])) {
			return [
				'success' => true,
				'token'   => $response['data']['token']
			];
		}

		return [
			'success' => false,
			'error'   => $response['error'] ?? 'Connection failed'
		];
	}

	/**
	 * Authenticate and get bearer token
	 *
	 * @return string|false Bearer token or false on failure
	 */
	public function authenticate(): string|false {
		// Return cached token if still valid
		if ($this->token && $this->tokenExpires > time()) {
			return $this->token;
		}

		if (!$this->publicKey || !$this->secretKey) {
			return false;
		}

		$url = self::API_URL . '/account/authorize';

		$data = [
			'public_key' => $this->publicKey,
			'secret_key' => $this->secretKey
		];

		$response = $this->request($url, $data);

		if ($response['success'] && !empty($response['data']['success']) && isset($response['data']['token'])) {
			$expire = isset($response['data']['expire']) ? (int)$response['data']['expire'] : 3600;

			$this->token = $response['data']['token'];
			$this->tokenExpires = time() + $expire;

			// Cache in session
			$this->session['woot_token'] = $this->token;
			$this->session['woot_token_expires'] = $this->tokenExpires;

			return $this->token;
		}

		return false;
	}

	/**
	 * Get available shipping services
	 *
	 * @return array|false Array of services or false on failure
	 */
	public function getServices(): array|false {
		$token = $this->authenticate();

		if (!$token) {
			return false;
		}

		$url = self::API_URL . '/general/services';

		$response = $this->request($url, [], 'GET', $token);

		if ($response['success'] && is_array($response['data'])) {
			return $response['data'];
		}

		return false;
	}

	/**
	 * Get sender addresses
	 *
	 * @param int $page Page number
	 * @param int $limit Items per page
	 *
	 * @return array|false Array of sender addresses or false on failure
	 */
	public function getSenderAddresses(int $page = 1, int $limit = 100): array|false {
		$token = $this->authenticate();

		if (!$token) {
			return false;
		}

		$url = self::API_URL . '/addresses/sender?page=' . $page . '&limit=' . $limit;

		$response = $this->request($url, [], 'GET', $token);

		if ($response['success'] && is_array($response['data'])) {
			return $response['data'];
		}

		return false;
	}

	/**
	 * Get parcels
	 *
	 * @param int $page Page number
	 * @param int $limit Items per page
	 *
	 * @return array|false Array of parcels or false on failure
	 */
	public function getParcels(int $page = 1, int $limit = 100): array|false {
		$token = $this->authenticate();

		if (!$token) {
			return false;
		}

		$url = self::API_URL . '/account/parcels?page=' . $page . '&limit=' . $limit;

		$response = $this->request($url, [], 'GET', $token);

		if ($response['success'] && is_array($response['data'])) {
			return $response['data'];
		}

		return false;
	}

	/**
	 * Get shipping quotes from API for multiple services
	 *
	 * Uses /orders/prices endpoint to calculate shipping costs.
	 *
	 * @param int $senderAddressId Sender address ID from Woot
	 * @param array $receiver Receiver address data (city_id or city name, county_id, country_id)
	 * @param array $parcel Default parcel configuration
	 * @param array $serviceIds Array of service IDs to get prices for
	 * @return array|false Array of prices keyed by service_id or false on failure
	 */
	public function getQuotes(int $senderAddressId, array $receiver, array $parcel, array $serviceIds): array|false {
		$token = $this->authenticate();

		if (!$token) {
			return false;
		}

		$url = self::API_URL . '/orders/prices';

		// Build receiver address
		$receiverData = [];
		if (!empty($receiver['city_id'])) {
			$receiverData['city_id'] = (int)$receiver['city_id'];
		} elseif (!empty($receiver['city'])) {
			$receiverData['city'] = $receiver['city'];
		}
		if (!empty($receiver['county_id'])) {
			$receiverData['county_id'] = (int)$receiver['county_id'];
		} elseif (!empty($receiver['county_name'])) {
			$receiverData['county'] = $receiver['county_name'];
		}
		if (!empty($receiver['country_id'])) {
			$receiverData['country_id'] = (int)$receiver['country_id'];
		}

		$data = [
			'service_ids' => array_map('intval', $serviceIds),
			'sender' => [
				'address_id' => $senderAddressId
			],
			'receiver' => $receiverData,
			'parcels' => [
				$parcel
			]
		];

		$response = $this->request($url, $data, 'POST', $token);

		if ($response['success'] && !empty($response['data'])) {
			// Response is an array of prices directly
			$prices = $response['data'];

			// Return prices keyed by service_id
			$result = [];
			foreach ($prices as $price) {
				if (isset($price['service_id'])) {
					$result[$price['service_id']] = $price;
				}
			}
			return $result;
		}

		return false;
	}

	/**
	 * Make HTTP request to API
	 *
	 * @param string $url API endpoint URL
	 * @param array $data Request data
	 * @param string $method HTTP method (GET or POST)
	 * @param string|null $token Bearer token for authenticated requests
	 * @return array{success: bool, data?: array, error?: string, http_code?: int}
	 */
	protected function request(string $url, array $data = [], string $method = 'POST', ?string $token = null): array {
		$curl = curl_init();

		$headers = [
			'Content-Type: application/json',
			'Accept: application/json'
		];

		if ($token) {
			$headers[] = 'Authorization: Bearer ' . $token;
		}

		$options = [
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_SSL_VERIFYPEER => true
		];

		$requestBody = null;
		if ($method === 'POST') {
			$options[CURLOPT_POST] = true;
			$requestBody = json_encode($data);
			$options[CURLOPT_POSTFIELDS] = $requestBody;
		} elseif ($method === 'GET') {
			$options[CURLOPT_HTTPGET] = true;
		} elseif ($method === 'DELETE') {
			$options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
			if (!empty($data)) {
				$requestBody = json_encode($data);
				$options[CURLOPT_POSTFIELDS] = $requestBody;
			}
		}

		curl_setopt_array($curl, $options);

		$response = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$error = curl_error($curl);

		curl_close($curl);

		// Log request and response for order-related endpoints
		$logType = $this->getLogType($url);
		if ($logType) {
			$this->log($logType, $url, $method, $requestBody, $response, $httpCode);
		}

		if ($error) {
			return [
				'success'   => false,
				'error'     => $error,
				'http_code' => $httpCode
			];
		}

		if ($httpCode >= 200 && $httpCode < 300) {
			$decoded = json_decode($response, true);

			return [
				'success'   => true,
				'data'      => is_array($decoded) ? $decoded : [],
				'http_code' => $httpCode
			];
		}

		return [
			'success'   => false,
			'error'     => 'HTTP ' . $httpCode,
			'http_code' => $httpCode,
			'response'  => $response
		];
	}

	/**
	 * Determine log type based on URL
	 *
	 * @param string $url API endpoint URL
	 * @return string|null Log type ('quotation' or 'awb') or null if not loggable
	 */
	protected function getLogType(string $url): ?string {
		// /orders/prices -> quotation
		if (strpos($url, '/orders/prices') !== false) {
			return 'quotation';
		}

		// /orders (create, cancel) or /orders/{id}/awb -> awb
		if (strpos($url, '/orders') !== false) {
			return 'awb';
		}

		return null;
	}

	/**
	 * Log API request/response to daily rotating file
	 *
	 * @param string $type Log type ('quotation' or 'awb')
	 * @param string $url API endpoint URL
	 * @param string $method HTTP method
	 * @param string|null $request Raw request body
	 * @param string|false $response Raw response body
	 * @param int $httpCode HTTP status code
	 * @return void
	 */
	protected function log(string $type, string $url, string $method, ?string $request, string|false $response, int $httpCode): void {
		// Build log file path: storage/logs/woot-{type}-YYYY-MM-DD.log
		$logDir = defined('DIR_STORAGE') ? DIR_STORAGE . 'logs/' : '';
		if (empty($logDir) || !is_dir($logDir)) {
			return;
		}

		$logFile = $logDir . 'woot-' . $type . '-' . date('Y-m-d') . '.log';

		$logEntry = str_repeat('=', 80) . "\n";
		$logEntry .= "[" . date('Y-m-d H:i:s') . "] " . $method . " " . $url . "\n";
		$logEntry .= "HTTP " . $httpCode . "\n";
		$logEntry .= str_repeat('-', 40) . " REQUEST " . str_repeat('-', 31) . "\n";
		$logEntry .= ($request ?: '(empty)') . "\n";
		$logEntry .= str_repeat('-', 40) . " RESPONSE " . str_repeat('-', 30) . "\n";
		$logEntry .= ($response !== false ? $response : '(empty)') . "\n\n";

		file_put_contents($logFile, $logEntry, FILE_APPEND);
	}

	/**
	 * Get countries from API
	 *
	 * @return array|false Array of countries or false on failure
	 */
	public function getCountries(): array|false {
		$token = $this->authenticate();

		if (!$token) {
			return false;
		}

		$url = self::API_URL . '/general/countries';

		$response = $this->request($url, [], 'GET', $token);

		if ($response['success'] && is_array($response['data'])) {
			return $response['data'];
		}

		return false;
	}

	/**
	 * Get counties for a country from API
	 *
	 * @param int $countryId Woot country ID
	 * @return array|false Array of counties or false on failure
	 */
	public function getCounties(int $countryId): array|false {
		$token = $this->authenticate();

		if (!$token) {
			return false;
		}

		$url = self::API_URL . '/general/counties?country_id=' . $countryId;

		$response = $this->request($url, [], 'GET', $token);

		if ($response['success'] && is_array($response['data'])) {
			return $response['data'];
		}

		return false;
	}

	/**
	 * Get cities for a country from API
	 *
	 * @param int $countryId Woot country ID
	 * @return array|false Array of cities or false on failure
	 */
	public function getCities(int $countryId): array|false {
		$token = $this->authenticate();

		if (!$token) {
			return false;
		}

		$url = self::API_URL . '/general/cities?country_id=' . $countryId;

		$response = $this->request($url, [], 'GET', $token);

		if ($response['success'] && is_array($response['data'])) {
			return $response['data'];
		}

		return false;
	}

	/**
	 * Get pickup locations for a country from API
	 *
	 * @param int $countryId Woot country ID
	 * @return array|false Array of locations or false on failure
	 */
	public function getLocations(int $countryId): array|false {
		$token = $this->authenticate();

		if (!$token) {
			return false;
		}

		$url = self::API_URL . '/general/locations?country_id=' . $countryId;

		$response = $this->request($url, [], 'GET', $token);

		if ($response['success'] && is_array($response['data'])) {
			return $response['data'];
		}

		return false;
	}

	/**
	 * Create order/shipment and get AWB
	 *
	 * @param int $serviceId Service ID
	 * @param array $sender Sender data (address_id or full address)
	 * @param array $receiver Receiver data (address_id or full address)
	 * @param array $parcels Array of parcel data
	 * @param array $options Optional: repayment, insurance, options (opd, sat, rdc, pxc)
	 * @return array{success: bool, order_id?: int, awb_number?: string, error?: string}
	 */
	public function createOrder(int $serviceId, array $sender, array $receiver, array $parcels, array $options = []): array {
		$token = $this->authenticate();

		if (!$token) {
			return [
				'success' => false,
				'error' => 'Authentication failed'
			];
		}

		$url = self::API_URL . '/orders';

		$data = [
			'service_id' => $serviceId,
			'sender' => $sender,
			'receiver' => $receiver,
			'parcels' => $parcels,
			'source' => 'opencart'
		];

		// Add optional fields
		if (isset($options['repayment'])) {
			$data['repayment'] = (float)$options['repayment'];
		}
		if (isset($options['insurance'])) {
			$data['insurance'] = (float)$options['insurance'];
		}
		if (isset($options['options'])) {
			$data['options'] = $options['options'];
		}

		$response = $this->request($url, $data, 'POST', $token);

		if ($response['success'] && !empty($response['data']['success'])) {
			return [
				'success' => true,
				'order_id' => $response['data']['order_id'] ?? null,
				'awb_number' => $response['data']['awb_number'] ?? $response['data']['awb'] ?? null
			];
		}

		// Build detailed error message from API response
		$error = 'Failed to create order';

		// Parse response body (for HTTP 4xx errors, body is in 'response' key as string)
		$errorData = $response['data'] ?? [];
		if (empty($errorData) && isset($response['response'])) {
			$errorData = json_decode($response['response'], true) ?? [];
		}

		if (isset($errorData['error'])) {
			// API returns errors as {"error": {"field": "message"}}
			if (is_array($errorData['error'])) {
				$errors = [];
				foreach ($errorData['error'] as $field => $msg) {
					$errors[] = $field . ': ' . $msg;
				}
				$error = implode('; ', $errors);
			} else {
				$error = $errorData['error'];
			}
		} elseif (isset($errorData['message'])) {
			$error = $errorData['message'];
		} elseif (isset($response['error'])) {
			$error = $response['error'];
		}

		return [
			'success' => false,
			'error' => $error
		];
	}

	/**
	 * Get AWB label PDF
	 *
	 * @param int $orderId Woot order/shipment ID
	 * @param string $format Label format (a4 or a6)
	 * @return array{success: bool, pdf?: string, error?: string}
	 */
	public function getAwbLabel(int $orderId, string $format = 'a4'): array {
		$token = $this->authenticate();

		if (!$token) {
			return [
				'success' => false,
				'error' => 'Authentication failed'
			];
		}

		$url = self::API_URL . '/orders/' . $orderId . '/awb?format=' . strtoupper($format);

		// Use regular request since response is JSON with base64 PDF
		$response = $this->request($url, [], 'GET', $token);

		if ($response['success'] && !empty($response['data']['success']) && !empty($response['data']['pdf'])) {
			// Decode base64 PDF
			$pdfData = base64_decode($response['data']['pdf']);
			return [
				'success' => true,
				'pdf' => $pdfData
			];
		}

		$error = 'Failed to get AWB label';
		if (isset($response['data']['error'])) {
			$error = is_array($response['data']['error']) ? json_encode($response['data']['error']) : $response['data']['error'];
		} elseif (isset($response['error'])) {
			$error = $response['error'];
		}

		return [
			'success' => false,
			'error' => $error
		];
	}

	/**
	 * Make raw HTTP request (for binary responses like PDF)
	 *
	 * @param string $url
	 * @param string|null $token
	 * @return array
	 */
	protected function requestRaw(string $url, ?string $token = null): array {
		$curl = curl_init();

		$headers = [
			'Accept: application/pdf'
		];

		if ($token) {
			$headers[] = 'Authorization: Bearer ' . $token;
		}

		$options = [
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_HTTPGET        => true
		];

		curl_setopt_array($curl, $options);

		$response = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
		$error = curl_error($curl);

		curl_close($curl);

		// Log request and response (AWB label requests)
		$logType = $this->getLogType($url);
		if ($logType) {
			// For binary PDF responses, log content type instead of raw binary
			$responseLog = $response;
			if ($contentType && strpos($contentType, 'application/pdf') !== false) {
				$responseLog = '(PDF binary data, ' . strlen($response) . ' bytes)';
			}
			$this->log($logType, $url, 'GET', null, $responseLog, $httpCode);
		}

		if ($error) {
			return [
				'success' => false,
				'error' => $error
			];
		}

		if ($httpCode >= 200 && $httpCode < 300) {
			return [
				'success' => true,
				'body' => $response,
				'content_type' => $contentType
			];
		}

		// Try to parse error from JSON response
		$errorMsg = 'HTTP ' . $httpCode;
		$decoded = json_decode($response, true);
		if ($decoded && isset($decoded['error'])) {
			if (is_array($decoded['error'])) {
				$errors = [];
				foreach ($decoded['error'] as $field => $msg) {
					$errors[] = "$field: $msg";
				}
				$errorMsg = implode('; ', $errors);
			} else {
				$errorMsg = $decoded['error'];
			}
		} elseif ($decoded && isset($decoded['message'])) {
			$errorMsg = $decoded['message'];
		}

		return [
			'success' => false,
			'error' => $errorMsg
		];
	}

	/**
	 * Cancel order/shipment
	 *
	 * @param int $orderId Woot order/shipment ID
	 * @param int $reasonId Cancellation reason ID (default 1)
	 * @return array{success: bool, error?: string}
	 */
	public function cancelOrder(int $orderId, int $reasonId = 1): array {
		$token = $this->authenticate();

		if (!$token) {
			return [
				'success' => false,
				'error' => 'Authentication failed'
			];
		}

		$url = self::API_URL . '/orders/' . $orderId;

		$data = [
			'reason_id' => $reasonId
		];

		$response = $this->request($url, $data, 'DELETE', $token);

		if ($response['success']) {
			return ['success' => true];
		}

		$error = 'Failed to cancel order';
		if (isset($response['data']['error'])) {
			$error = is_array($response['data']['error']) ? json_encode($response['data']['error']) : $response['data']['error'];
		} elseif (isset($response['data']['message'])) {
			$error = $response['data']['message'];
		} elseif (isset($response['error'])) {
			$error = $response['error'];
		}

		return [
			'success' => false,
			'error' => $error
		];
	}

	/**
	 * Get API URL constant
	 *
	 * @return string
	 */
	public static function getApiUrl(): string {
		return self::API_URL;
	}
}
