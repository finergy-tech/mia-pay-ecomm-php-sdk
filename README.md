# MIA POS PHP SDK
MIA POS, provided by Finergy Tech, allows secure payment processing using QR codes and direct payment requests.

This SDK allows merchants to integrate with the MIA POS ecommerce payment system. It simplifies the process of registering payments, retrieving payment statuses, and verifying signatures for callbacks. The SDK is designed to be lightweight and compatible with PHP applications.

---

## Requirements

- PHP version: **>= 5.6**
- PHP Extensions:
    - `curl`
    - `json`

Ensure that these extensions are enabled in your PHP configuration (`php.ini`).

---

## Installation

### Using Composer

1. Run the following command to add the SDK to your project:
   ```bash
   composer require finergy/mia-pos-sdk
   ```

2. Include the Composer autoloader in your project:
   ```php
   require_once __DIR__ . '/vendor/autoload.php';
   ```

### Manual Installation

1. Download or clone this repository.
2. Add the `src` folder to your project directory.
3. Include the SDK files manually:
   ```php
   require_once __DIR__ . '/path-to-sdk/src/MiaPosSdk.php';
   require_once __DIR__ . '/path-to-sdk/src/Exceptions/ValidationException.php';
   require_once __DIR__ . '/path-to-sdk/src/Exceptions/ClientApiException.php';
   ```

---

## Getting Started

### Obtaining API Credentials

To use the MIA POS ecommerce system, you must obtain the following credentials from the bank:

- `baseUrl` (API Endpoint)
- `merchantId` (Merchant Identifier)
- `secretKey` (Authentication Key)

All integrations must first be tested on the **test environment** provided by the bank.

---

## Usage Flow

### 1. Registering a Payment

When a client chooses to pay via MIA POS on your website, you must register the payment with the MIA POS system.

- **Input**: Payment data (e.g., amount, currency, orderId).
- **Output**: A `paymentId` and a `checkoutPage` URL for redirecting the client to confirm the payment.

Example:

```php
require_once __DIR__ . '/src/MiaPosSdk.php';

use Finergy\MiaPosSdk\MiaPosSdk;

$baseUrl = 'https://ecomm-test.miapos.md/';
$merchantId = 'your_merchant_id';
$secretKey = 'your_secret_key';

$sdk = MiaPosSdk::getInstance($baseUrl, $merchantId, $secretKey);

$paymentData = [
    'terminalId' => 'TE0001',
    'orderId' => 'order12345',
    'amount' => 150.75,
    'currency' => 'MDL',
    'language' => 'ro',
    'payDescription' => 'Payment for order #12345',
    'callbackUrl' => 'http://your_callback_url',
    'successUrl' => 'http://your_success_url',
    'failUrl' => 'http://your_fail_url',
];

$response = $sdk->createPayment($paymentData);

$paymentId = $response['paymentId'];
$checkoutPage = $response['checkoutPage'];

// Save $paymentId in your database and redirect the client to $checkoutPage
header("Location: $checkoutPage");
```

---


### 2. Handling Payment Failure (failUrl)

When the payment fails, the client will be redirected to the `failUrl` specified during payment registration. The request parameters will include the same data passed during the payment registration, such as the `orderId`.

Use the `orderId` to retrieve the corresponding `paymentId` from your database, and then use the SDK to check the payment status. Based on the status, display the appropriate message to the client.

Example:

```php
require_once __DIR__ . '/src/MiaPosSdk.php';

use Finergy\MiaPosSdk\MiaPosSdk;

$baseUrl = 'https://ecomm-test.miapos.md/';
$merchantId = 'your_merchant_id';
$secretKey = 'your_secret_key';

$sdk = MiaPosSdk::getInstance($baseUrl, $merchantId, $secretKey);

// Retrieve orderId from the request parameters
$orderId = $_GET['orderId'];

// Fetch paymentId from your database using orderId
$paymentId = getPaymentIdFromDatabase($orderId);

$response = $sdk->getPaymentStatus($paymentId);

if ($response['status'] === 'FAILED') {
    echo "Payment failed. Please try again.";
} else {
    echo "Payment successful!";
    // Update order status in your database
}
```

---

### 3. Handling Payment Success (successUrl)

When the payment succeeds, the client will be redirected to the `successUrl`. The request parameters will include the same data passed during the payment registration, such as the `orderId`.

Use the `orderId` to retrieve the corresponding `paymentId` from your database, and then use the SDK to verify the payment status.

Example:

```php
require_once __DIR__ . '/src/MiaPosSdk.php';

use Finergy\MiaPosSdk\MiaPosSdk;

$baseUrl = 'https://ecomm-test.miapos.md/';
$merchantId = 'your_merchant_id';
$secretKey = 'your_secret_key';

$sdk = MiaPosSdk::getInstance($baseUrl, $merchantId, $secretKey);

// Retrieve orderId from the request parameters
$orderId = $_GET['orderId'];

// Fetch paymentId from your database using orderId
$paymentId = getPaymentIdFromDatabase($orderId);

$response = $sdk->getPaymentStatus($paymentId);

if ($response['status'] === 'SUCCESS') {
    echo "Payment successful! Order ID: " . $response['orderId'];
    // Update order status in your database
} else {
    echo "Payment could not be verified. Please contact support.";
}
```

---

### 4. Handling Callbacks (callbackUrl)

After the payment is finalized, the MIA POS system will send a signed callback to the `callbackUrl`. Use the SDK to verify the signature and process the callback data.

Example:

```php
require_once __DIR__ . '/src/MiaPosSdk.php';

use Finergy\MiaPosSdk\MiaPosSdk;

$baseUrl = 'https://ecomm-test.miapos.md/';
$merchantId = 'your_merchant_id';
$secretKey = 'your_secret_key';

$sdk = MiaPosSdk::getInstance($baseUrl, $merchantId, $secretKey);

// Retrieve callback data (replace with actual input)
$inputJson = file_get_contents('php://input');
$callbackData = json_decode($inputJson, true);

$result = $callbackData['result'];
$signature = $callbackData['signature'];

$signString = $sdk->formSignStringByResult($result);
$isValidSignature = $sdk->verifySignature($signString, $signature);

if ($isValidSignature) {
    echo "Callback verified successfully!";
    // Update order status in your database based on $result['status']
} else {
    echo "Invalid callback signature!";
}
```

---

## Notes

1. Always test your integration in the **test environment** before going live.
2. Ensure secure storage of your API credentials (`merchantId`, `secretKey`) and do not expose them in client-side code.