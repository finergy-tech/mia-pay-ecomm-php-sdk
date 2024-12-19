<?php
require_once __DIR__ . '/../src/MiaPosSdk.php';
require_once __DIR__ . '/../src/Exceptions/ValidationException.php';
require_once __DIR__ . '/../src/Exceptions/ClientApiException.php';

use Finergy\MiaPosSdk\MiaPosSdk;
use Finergy\MiaPosSdk\Exceptions\ValidationException;
use Finergy\MiaPosSdk\Exceptions\ClientApiException;

$baseUrl = 'https://ecomm-test.miapos.md/'; // Mia POS API URL
$merchantId = '128';    // Replace with your Merchant ID
$secretKey = '1GraPpLIYafez9aD';      // Replace with your Secret Key

try {
    // Step 1: Initialize the SDK
    $sdk = MiaPosSdk::getInstance($baseUrl, $merchantId, $secretKey);

    // Step 2: Create a payment
    $paymentData = [
        'terminalId' => 'TE0001',
        'orderId' => 'order12345',
        'amount' => 150.75,
        'currency' => 'MDL',
        'language' => 'ro',
        'payDescription' => 'Payment for order #12345',
        'paymentType' => 'qr',
        'clientName' => 'Test Client',
        'clientPhone' => '00000000',
        'clientEmail' => 'test@test.com',
        'callbackUrl' => 'http://your_callback_url',
        'successUrl' => 'http://your_success_url?orderId=order12345',
        'failUrl' => 'http://your_failUrl_url?orderId=order12345'
    ];

    $response = $sdk->createPayment($paymentData);

    // Extract paymentId from the response
    $paymentId = $response['paymentId'];

    echo "Payment created successfully. Payment ID: {$paymentId}\n";

    // Step 3: Get payment status when redirecting a client to a success or fail URL. To find out the exact payment status and process the payment correctly
    $statusResponse = $sdk->getPaymentStatus($paymentId);

    // Example read the response fields into variables
    $terminalId = $statusResponse['terminalId'];
    $orderId = $statusResponse['orderId'];
    $payId = $statusResponse['paymentId'];
    $status = $statusResponse['status'];
    $amount = $statusResponse['amount'];
    $currency = $statusResponse['currency'];
    $paymentType = $statusResponse['paymentType'];
    $paymentDate = $statusResponse['paymentDate'];
    $swiftMessageId = isset($statusResponse['swiftMessageId']) ? $statusResponse['swiftMessageId'] : null;
    $swiftPayerBank = isset($statusResponse['swiftPayerBank']) ? $statusResponse['swiftPayerBank'] : null;

    // Print the retrieved status information
    echo "Payment Status Details:\n";
    print_r($statusResponse);

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