<?php
require_once __DIR__ . '/../src/MiaPosSdk.php';
require_once __DIR__ . '/../src/Exceptions/ValidationException.php';
require_once __DIR__ . '/../src/Exceptions/ClientApiException.php';

use Finergy\MiaPosSdk\MiaPosSdk;
use Finergy\MiaPosSdk\Exceptions\ValidationException;
use Finergy\MiaPosSdk\Exceptions\ClientApiException;

// Input callback JSON (replace with actual input)
$jsonInput = <<<JSON
{
  "result": {
    "terminalId": "TRMW0001",
    "orderId": "108",
    "paymentId": "2a663962-c954-4984-90e5-1d24c3305f7b",
    "status": "EXPIRED",
    "amount": 1775.00,
    "currency": "MDL",
    "paymentType": "qr",
    "paymentDate": "2024-12-17T11:54:23"
  },
  "signature": "gtWkQdF2X2oCwO/+a+DJxpDc5DhjC1PMVWrnCXsCX54qOo24siRTy4PAjHoYet1r0KERVEL65p7UZuHcaK+TOiJptlalMUVZWbGLPf05WpyKPOPSPI1P4ZoADzJpceYsKjjZImB/+ft6OAF+ahxazhHkiT1Ze05vwD2L1D6zRohcxZl9XRJMChZcVD9bdNy23ozwuq6FwlnneJJeCPNvqveg7f5e0CD1NXWdLJ3WryP0ypcGtQGZAY+PrhkdVG5SWhYr0FFniAZIrp9yOFn3vrsUP4rpZmeqIahSV6x12pyyRsm+bs/tjw/kPR34ygG7ksXsrpwhQbltAHWeWwnOmg=="
}
JSON;

$baseUrl = 'https://ecomm-test.miapos.md/'; // Mia POS API URL
$merchantId = '128';    // Replace with your Merchant ID
$secretKey = '1GraPpLIYafez9aD';      // Replace with your Secret Key

try {
    // Step 1: Initialize the SDK
    $sdk = MiaPosSdk::getInstance($baseUrl, $merchantId, $secretKey);

    // Step 2: Parse the input JSON
    $callbackData = json_decode($jsonInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }

    $result = isset($callbackData['result']) ? $callbackData['result'] : null;
    $signature = isset($callbackData['signature']) ? $callbackData['signature'] : null;

    if (!$result || !$signature) {
        throw new ValidationException('Missing result or signature in callback data.');
    }

    // Step 3: Form the signature string from the result
    $signString = $sdk->formSignStringByResult($result);

    echo "Callback sign string for verify: $signString\n";

    // Step 4: Verify the signature
    $isSignatureValid = $sdk->verifySignature($signString, $signature);

    if ($isSignatureValid) {
        echo "Signature verification: VALID\n";
    } else {
        echo "Signature verification: INVALID\n";
    }
} catch (ValidationException $e) {
    // Error in input validation
    echo "Validation Error: " . $e->getMessage() . "\n";
} catch (ClientApiException $e) {
    // API-related error
    echo "API Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    // Other errors
    echo "Error: " . $e->getMessage() . "\n";
}
die;