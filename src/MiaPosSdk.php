<?php

namespace Finergy\MiaPosSdk;

require_once 'MiaPosApiClient.php';
require_once 'MiaPosAuthClient.php';

use Finergy\MiaPosSdk\Exceptions\ValidationException;
use Finergy\MiaPosSdk\Exceptions\ClientApiException;

/**
 * Class MiaPosSdk
 *
 * Main SDK class for interacting with the Mia POS Ecomm API.
 * Provides methods to create payments, check payment statuses,
 * verify signatures, and manage result strings.
 */
class MiaPosSdk
{
    /**
     * @var MiaPosSdk|null Singleton instance of the SDK
     */
    private static $instance = null;
    private $apiClient;
    private $authClient;

    /**
     * MiaPosSdk constructor.
     *
     * Initializes the SDK with the base URL, Merchant ID, and Secret Key.
     *
     * @param string $baseUrl Base URL for the Mia POS API.
     * @param string $merchantId Merchant ID provided by Mia POS.
     * @param string $secretKey Secret Key for authentication.
     *
     * @throws ValidationException If any of the required parameters are missing.
     */
    private function __construct($baseUrl, $merchantId, $secretKey)
    {
        if (empty($baseUrl)) {
            throw new ValidationException('Base URL is required.');
        }

        if (empty($merchantId)) {
            throw new ValidationException('Merchant ID is required.');
        }

        if (empty($secretKey)) {
            throw new ValidationException('Secret Key is required.');
        }

        $this->authClient = new MiaPosAuthClient($baseUrl, $merchantId, $secretKey);
        $this->apiClient = new MiaPosApiClient($baseUrl);
    }

    /**
     * Returns a singleton instance of MiaPosSdk.
     *
     * @param string $baseUrl Base URL for the Mia POS API.
     * @param string $merchantId Merchant ID provided by Mia POS.
     * @param string $secretKey Secret Key for authentication.
     *
     * @return MiaPosSdk
     *
     * @throws ValidationException If required parameters are missing.
     */
    public static function getInstance($baseUrl, $merchantId, $secretKey)
    {
        if (self::$instance === null) {
            self::$instance = new MiaPosSdk($baseUrl, $merchantId, $secretKey);
        }
        return self::$instance;
    }

    /**
     * Creates a new payment.
     *
     * @param array $paymentData An associative array containing payment details by miaEcomm protocol
     * @return array Response from the API.
     *
     * @throws ValidationException If required parameters are missing.
     * @throws ClientApiException If there is an API error during the request.
     */
    public function createPayment($paymentData)
    {
        $requiredFields = [
            'terminalId', 'orderId', 'amount', 'currency', 'payDescription'
        ];

        $this->validateParameters($paymentData, $requiredFields);
        $token = $this->getAccessToken();
        return $this->apiClient->createPayment($token, $paymentData);
    }

    /**
     * Retrieves the status of a payment by its ID.
     *
     * @param string $paymentId The unique payment ID.
     *
     * @return array Response from the API.
     *
     * @throws ValidationException If the payment ID is empty.
     * @throws ClientApiException If there is an API error during the request.
     */
    public function getPaymentStatus($paymentId)
    {
        if (empty($paymentId)) {
            throw new ValidationException('Payment ID is required.');
        }
        $token = $this->getAccessToken();
        return $this->apiClient->getPaymentStatus($token, $paymentId);
    }

    /**
     * Verifies the signature of a payment result.
     *
     * @param string $result_str The result string to verify.
     * @param string $signature The provided signature.
     *
     * @return bool True if the signature is valid; otherwise, false.
     *
     * @throws ValidationException If required parameters are missing.
     * @throws ClientApiException If there is an API error during the request.
     */
    public function verifySignature($result_str, $signature)
    {
        if (empty($result_str)) {
            throw new ValidationException('Result string is required.');
        }

        if (empty($signature)) {
            throw new ValidationException('Signature is required.');
        }

        $token = $this->getAccessToken();
        $publicKey = $this->apiClient->getPublicKey($token);

        if (!isset($publicKey)) {
            throw new ValidationException('Public key is missing in the response.');
        }


        $publicKeyPem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split($publicKey, 64, "\n") . "-----END PUBLIC KEY-----";
        $publicKeyResource = openssl_pkey_get_public($publicKeyPem);

        if ($publicKeyResource === false) {
            throw new ValidationException("Failed to parse the public key {$publicKeyPem}");
        }

        $decodedSignature = base64_decode($signature);
        if ($decodedSignature === false) {
            throw new ValidationException("Failed to decode the signature {$signature}");
        }

        $verified = openssl_verify(
            $result_str,
            $decodedSignature,
            $publicKeyResource,
            OPENSSL_ALGO_SHA256
        );

        if (PHP_VERSION_ID < 80000) {
            openssl_free_key($publicKeyResource);
        }

        return $verified === 1;
    }

    /**
     * Forms a signature string based on result data.
     *
     * @param array $result_data A set of data received when receiving the payment status or when receiving the payment result on callbackUrl
     *
     * @return string Generated string for the data set, for signature verification.
     *
     * @throws ValidationException If the result data is invalid.
     */
    public function formSignStringByResult($result_data)
    {
        if (empty($result_data) || !is_array($result_data)) {
            throw new ValidationException('Result data must be a non-empty array.');
        }

        ksort($result_data);

        $result_str = implode(
            ';',
            array_map(function ($key, $value) {
                if ($key === 'amount') {
                    return number_format($value, 2, '.', '');
                }
                return (string)$value;
            }, array_keys($result_data), $result_data)
        );

        return $result_str;
    }

    /**
     * Validate the required parameters.
     *
     * @param array $data Input data to validate.
     * @param array $requiredFields List of required fields.
     * @throws ValidationException If a required field is missing.
     */
    private function validateParameters($data, $requiredFields)
    {
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            throw new ValidationException(
                'Missing required fields: ' . implode(', ', $missingFields)
            );
        }
    }


    private function getAccessToken()
    {
        return $this->authClient->getAccessToken();
    }
} 