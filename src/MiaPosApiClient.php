<?php

namespace Finergy\MiaPosSdk;

use Finergy\MiaPosSdk\Exceptions\ClientApiException;

/**
 * Class MiaPosApiClient
 *
 * Handles API requests to the Mia POS Ecomm API.
 * Provides methods for creating payments, checking payment status,
 * and retrieving the public key.
 */
class MiaPosApiClient
{
    private $baseUrl;

    public function __construct($baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Creates a new payment.
     *
     * Sends a POST request to the Mia POS API to create a payment.
     *
     * @param string $token Access token for authorization.
     * @param array $paymentData An associative array containing payment details by miaEcomm protocol
     *
     * @return array Response from the API containing payment details.
     * @throws ClientApiException If the API request fails or returns an error.
     */
    public function createPayment($token, $paymentData)
    {
        $url = $this->baseUrl . '/ecomm/api/v1/pay';
        return $this->sendRequest('POST', $url, $paymentData, $token);
    }

    /**
     * Retrieves the status of a payment.
     *
     * Sends a GET request to the Mia POS API to retrieve the payment status by its ID.
     *
     * @param string $token Access token for authorization.
     * @param string $paymentId Unique identifier of the payment.
     * @return array Response from the API containing the payment status.
     *
     * @throws ClientApiException If the API request fails or returns an error.
     */
    public function getPaymentStatus($token, $paymentId)
    {
        $url = $this->baseUrl . '/ecomm/api/v1/payment/' . $paymentId;
        return $this->sendRequest('GET', $url, [], $token);
    }

    /**
     * Retrieves the public key from the Mia POS API.
     *
     * Sends a GET request to retrieve the public key for signature verification.
     *
     * @param string $token Access token for authorization.
     * @return string The public key returned by the API.
     *
     * @throws ClientApiException If the public key is not found or the API request fails.
     */
    public function getPublicKey($token)
    {
        $url = $this->baseUrl . '/ecomm/api/v1/public-key';
        $response = $this->sendRequest('GET', $url, [], $token);

        if (isset($response['publicKey'])) {
            return $response['publicKey'];
        }

        throw new ClientApiException('Public key not found in the response');
    }

    /**
     * Sends an HTTP request to the Mia POS API.
     *
     * Uses cURL to send the request with proper headers and handles errors,
     * such as network issues, HTTP errors, and invalid JSON responses.
     *
     * @param string $method HTTP method (e.g., 'POST', 'GET').
     * @param string $url The API endpoint URL.
     * @param array $data The request payload (for POST requests).
     * @param string|null $token Access token for authorization (optional).
     *
     * @return array The decoded JSON response from the API.
     *
     * @throws ClientApiException If a network error, HTTP error, or JSON decoding failure occurs.
     */
    private function sendRequest($method, $url, $data = [], $token = null)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);

        $headers = ['Content-Type: application/json'];

        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        if ($method === 'POST' && !empty($data)) {
            $payload = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $errorMessage = "Mia POS client url {$url}, method {$method} cURL error: " . curl_error($ch);
            curl_close($ch);
            throw new ClientApiException($errorMessage);
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode >= 400) {
            throw new ClientApiException("Mia POS client url {$url}, method {$method} HTTP Error: {$statusCode}, Response: {$response}", $statusCode);
        }

        $decodedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ClientApiException("Mia POS client url {$url}, method {$method} failed to decode JSON response: " . json_last_error_msg());
        }

        return $decodedResponse;
    }
}
