<?php

namespace Finergy\MiaPosSdk;

use Exception;
use Finergy\MiaPosSdk\Exceptions\ClientApiException;

/**
 * Class MiaPosAuthClient
 *
 * Handles authentication with the Mia POS Ecomm API.
 * Provides methods to generate, refresh, and retrieve access tokens.
 */
class MiaPosAuthClient
{
    private $baseUrl;
    private $merchantId;
    private $secretKey;
    private $accessToken;
    private $refreshToken;
    private $accessExpireTime;

    public function __construct($baseUrl, $merchantId, $secretKey)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->merchantId = $merchantId;
        $this->secretKey = $secretKey;
    }

    /**
     * Retrieves the current access token.
     *
     * If the current access token is valid, it will return the cached token.
     * Otherwise, it will attempt to refresh the token or generate a new one.
     *
     * @return string The valid access token.
     *
     * @throws ClientApiException If the token cannot be generated or refreshed.
     */
    public function getAccessToken()
    {

        if ($this->accessToken && !$this->isTokenExpired()) {
            return $this->accessToken;
        }

        if ($this->refreshToken) {
            try {
                return $this->refreshAccessToken();
            } catch (Exception $e) {
                error_log('Mia pos refresh token failed: ' . $e->getMessage());
            }
        }

        return $this->generateNewTokens();
    }

    /**
     * Generates a new access token using the merchant credentials.
     *
     * Sends a request to the Mia POS API to obtain a new access and refresh token pair.
     *
     * @return string The newly generated access token.
     *
     * @throws ClientApiException If the API request fails or no access token is returned.
     */
    private function generateNewTokens()
    {
        $url = $this->baseUrl . '/ecomm/api/v1/token';
        $data = [
            'merchantId' => $this->merchantId,
            'secretKey' => $this->secretKey,
        ];

        $response = $this->sendRequest('POST', $url, $data);
        $this->parseResponseToken($response);

        if (!$this->accessToken) {
            throw new ClientApiException("Failed to retrieve access token by merchantId {$this->merchantId}. accessToken is missing from the response");
        }

        return $this->accessToken;
    }

    /**
     * Refreshes the current access token using the refresh token.
     *
     * Sends a request to the Mia POS API to refresh the access token.
     *
     * @return string The refreshed access token.
     *
     * @throws ClientApiException If the API request fails or no access token is returned.
     */
    private function refreshAccessToken()
    {
        $url = $this->baseUrl . '/ecomm/api/v1/token/refresh';
        $data = [
            'refreshToken' => $this->refreshToken,
        ];

        $response = $this->sendRequest('POST', $url, $data);

        $this->parseResponseToken($response);

        if (!$this->accessToken) {
            throw new ClientApiException("Failed to refresh access token by merchantId {$this->merchantId}. accessToken is missing from the response");
        }

        return $this->accessToken;
    }

    /**
     * Checks whether the current access token has expired.
     *
     * @return bool True if the token is expired; otherwise, false.
     */
    private function isTokenExpired()
    {
        return !$this->accessExpireTime || time() >= $this->accessExpireTime;
    }

    /**
     * Parses the token response from the Mia POS API.
     *
     * Extracts the access token, refresh token, and token expiration time from the response.
     *
     * @param array $response The decoded API response containing token details.
     */
    private function parseResponseToken($response)
    {
        $this->accessToken = isset($response['accessToken']) ? $response['accessToken'] : null;
        $this->refreshToken = isset($response['refreshToken']) ? $response['refreshToken'] : null;
        $this->accessExpireTime = time() + (isset($response['accessTokenExpiresIn']) ? $response['accessTokenExpiresIn'] : 0) - 10;
    }

    /**
     * Sends an HTTP request to the Mia POS API.
     *
     * Uses cURL to send the request and handles errors related to the request, such as network issues,
     * HTTP errors, and invalid JSON responses.
     *
     * @param string $method The HTTP method to use (e.g., 'POST').
     * @param string $url The endpoint URL.
     * @param array $data The data payload for the request.
     *
     * @return array The decoded JSON response from the API.
     *
     * @throws ClientApiException If the request fails, HTTP error occurs, or the response cannot be decoded.
     */
    private function sendRequest($method, $url, $data = [])
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        if ($method === 'POST') {
            $payload = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

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
