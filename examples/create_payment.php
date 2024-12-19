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
    // Initialize the SDK
    $sdk = MiaPosSdk::getInstance($baseUrl, $merchantId, $secretKey);

    // Data for creating a payment
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

    // Create the payment
    $response = $sdk->createPayment($paymentData);

    // Save the result in your system
    $orderId = $response['orderId'];
    $paymentId = $response['paymentId'];
    $checkoutPage = $response['checkoutPage'];

    // Redirect the client to the Mia POS checkout page
    header("Location: " . $checkoutPage);

    // Output the result
    echo "Payment created successfully:\n";
    print_r($response);
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
