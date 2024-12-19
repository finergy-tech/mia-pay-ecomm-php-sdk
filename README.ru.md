### MIA POS PHP SDK

MIA POS, предоставляемая Finergy Tech, позволяет безопасно обрабатывать платежи с использованием QR-кодов и прямых запросов на оплату.

Этот SDK позволяет продавцам интегрировать систему электронной коммерции MIA POS. Он упрощает процесс регистрации платежей, получения их статусов и проверки подписей для обработки callback-запросов. SDK разработан так, чтобы быть лёгким в использовании и совместимым с PHP-приложениями.

---

## Требования

- Версия PHP: **>= 5.6**
- Расширения PHP:
    - `curl`
    - `json`

Убедитесь, что эти расширения включены в конфигурации PHP (`php.ini`).

---

## Установка

### С использованием Composer

1. Выполните следующую команду, чтобы добавить SDK в ваш проект:
   ```bash
   composer require finergy/mia-pos-sdk
   ```

2. Подключите autoloader Composer в вашем проекте:
   ```php
   require_once __DIR__ . '/vendor/autoload.php';
   ```

### Ручная установка

1. Скачайте или клонируйте этот репозиторий.
2. Добавьте папку `src` в директорию вашего проекта.
3. Подключите файлы SDK вручную:
   ```php
   require_once __DIR__ . '/путь-к-sdk/src/MiaPosSdk.php';
   require_once __DIR__ . '/путь-к-sdk/src/Exceptions/ValidationException.php';
   require_once __DIR__ . '/путь-к-sdk/src/Exceptions/ClientApiException.php';
   ```

---

## Начало работы

### Получение API-ключей

Чтобы использовать систему MIA POS, необходимо получить следующие данные у банка:

- `baseUrl` (Адрес API)
- `merchantId` (Идентификатор продавца)
- `secretKey` (Ключ для аутентификации)

Все интеграции должны быть сначала протестированы в **тестовой среде**, предоставленной банком.

---

## Процесс использования

### 1. Регистрация платежа

Когда клиент выбирает способ оплаты через MIA POS на вашем сайте, необходимо зарегистрировать платёж в системе MIA POS.

- **Входные данные**: Информация о платеже (например, сумма, валюта, orderId).
- **Выходные данные**: `paymentId` и URL `checkoutPage` для перенаправления клиента на страницу подтверждения оплаты.

Пример:

```php
require_once __DIR__ . '/src/MiaPosSdk.php';

use Finergy\MiaPosSdk\MiaPosSdk;

$baseUrl = 'https://ecomm-test.miapos.md/';
$merchantId = 'ваш_merchant_id';
$secretKey = 'ваш_secret_key';

$sdk = MiaPosSdk::getInstance($baseUrl, $merchantId, $secretKey);

$paymentData = [
    'terminalId' => 'TE0001',
    'orderId' => 'order12345',
    'amount' => 150.75,
    'currency' => 'MDL',
    'language' => 'ru',
    'payDescription' => 'Оплата заказа #12345',
    'callbackUrl' => 'http://ваш_callback_url',
    'successUrl' => 'http://ваш_success_url',
    'failUrl' => 'http://ваш_fail_url',
];

$response = $sdk->createPayment($paymentData);

$paymentId = $response['paymentId'];
$checkoutPage = $response['checkoutPage'];

// Сохраните $paymentId в вашей базе данных и перенаправьте клиента на $checkoutPage
header("Location: $checkoutPage");
```

---

### 2. Обработка ошибки оплаты (failUrl)

Если платёж не удался, клиент будет перенаправлен на `failUrl`, указанный при регистрации платежа. Параметры запроса будут содержать те же данные, что передавались при регистрации, например, `orderId`.

Используйте `orderId`, чтобы получить соответствующий `paymentId` из базы данных, затем проверьте статус платежа с помощью SDK. На основе статуса отобразите соответствующее сообщение клиенту.

Пример:

```php
require_once __DIR__ . '/src/MiaPosSdk.php';

use Finergy\MiaPosSdk\MiaPosSdk;

$baseUrl = 'https://ecomm-test.miapos.md/';
$merchantId = 'ваш_merchant_id';
$secretKey = 'ваш_secret_key';

$sdk = MiaPosSdk::getInstance($baseUrl, $merchantId, $secretKey);

// Получите orderId из параметров запроса
$orderId = $_GET['orderId'];

// Найдите paymentId в базе данных с использованием orderId
$paymentId = getPaymentIdFromDatabase($orderId);

$response = $sdk->getPaymentStatus($paymentId);

if ($response['status'] === 'FAILED') {
    echo "Оплата не удалась. Попробуйте ещё раз.";
} else {
    echo "Оплата прошла успешно!";
    // Обновите статус заказа в вашей базе данных
}
```

---

### 3. Обработка успешной оплаты (successUrl)

Если платёж успешно завершён, клиент будет перенаправлен на `successUrl`. Параметры запроса будут содержать те же данные, что передавались при регистрации, например, `orderId`.

Используйте `orderId`, чтобы получить соответствующий `paymentId` из базы данных, затем проверьте статус платежа с помощью SDK.

Пример:

```php
require_once __DIR__ . '/src/MiaPosSdk.php';

use Finergy\MiaPosSdk\MiaPosSdk;

$baseUrl = 'https://ecomm-test.miapos.md/';
$merchantId = 'ваш_merchant_id';
$secretKey = 'ваш_secret_key';

$sdk = MiaPosSdk::getInstance($baseUrl, $merchantId, $secretKey);

// Получите orderId из параметров запроса
$orderId = $_GET['orderId'];

// Найдите paymentId в базе данных с использованием orderId
$paymentId = getPaymentIdFromDatabase($orderId);

$response = $sdk->getPaymentStatus($paymentId);

if ($response['status'] === 'SUCCESS') {
    echo "Оплата прошла успешно! Номер заказа: " . $response['orderId'];
    // Обновите статус заказа в вашей базе данных
} else {
    echo "Платёж не удалось подтвердить. Обратитесь в поддержку.";
}
```

---

### 4. Обработка callback-запросов (callbackUrl)

После завершения платежа система MIA POS отправит подписанный callback на `callbackUrl`. Используйте SDK для проверки подписи и обработки данных из callback.

Пример:

```php
require_once __DIR__ . '/src/MiaPosSdk.php';

use Finergy\MiaPosSdk\MiaPosSdk;

$baseUrl = 'https://ecomm-test.miapos.md/';
$merchantId = 'ваш_merchant_id';
$secretKey = 'ваш_secret_key';

$sdk = MiaPosSdk::getInstance($baseUrl, $merchantId, $secretKey);

// Получите данные из callback (замените на реальный ввод)
$inputJson = file_get_contents('php://input');
$callbackData = json_decode($inputJson, true);

$result = $callbackData['result'];
$signature = $callbackData['signature'];

$signString = $sdk->formSignStringByResult($result);
$isValidSignature = $sdk->verifySignature($signString, $signature);

if ($isValidSignature) {
    echo "Callback успешно проверен!";
    // Обновите статус заказа в базе данных на основе $result['status']
} else {
    echo "Неверная подпись callback!";
}
```

---

### Заметки

1. Всегда тестируйте вашу интеграцию в **тестовой среде** перед переходом в продакшн.
2. Убедитесь, что данные API (`merchantId`, `secretKey`) хранятся в безопасности и не отображаются в клиентском коде.