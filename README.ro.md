### MIA POS PHP SDK

MIA POS, furnizat de Finergy Tech, permite procesarea sigură a plăților folosind coduri QR și cereri directe de plată.

Acest SDK permite comercianților să integreze sistemul de plăți online MIA POS. Simplifică procesul de înregistrare a plăților, obținerea statusurilor acestora și verificarea semnăturilor pentru callback-uri. SDK-ul este conceput să fie ușor de utilizat și compatibil cu aplicațiile PHP.

---

## Cerințe

- Versiune PHP: **>= 5.6**
- Extensii PHP:
    - `curl`
    - `json`

Asigurați-vă că aceste extensii sunt activate în configurația PHP (`php.ini`).

---

## Instalare

### Utilizând Composer

1. Rulați următoarea comandă pentru a adăuga SDK-ul în proiectul dvs.:
   ```bash
   composer require finergy/mia-pos-sdk
   ```

2. Includeți autoloader-ul Composer în proiect:
   ```php
   require_once __DIR__ . '/vendor/autoload.php';
   ```

### Instalare Manuală

1. Descărcați sau clonați acest repository.
2. Adăugați folderul `src` în directorul proiectului dvs.
3. Includeți manual fișierele SDK:
   ```php
   require_once __DIR__ . '/cale-către-sdk/src/MiaPosSdk.php';
   require_once __DIR__ . '/cale-către-sdk/src/Exceptions/ValidationException.php';
   require_once __DIR__ . '/cale-către-sdk/src/Exceptions/ClientApiException.php';
   ```

---

## Începeți Utilizarea

### Obținerea Credențialelor API

Pentru a utiliza sistemul de plăți online MIA POS, trebuie să obțineți următoarele credențiale de la bancă:

- `baseUrl` (Endpoint API)
- `merchantId` (Identificatorul comerciantului)
- `secretKey` (Cheia de autentificare)

Toate integrările trebuie testate mai întâi în **mediul de testare** furnizat de bancă.

---

## Fluxul de Utilizare

### 1. Înregistrarea unei Plăți

Când un client alege să plătească prin MIA POS pe site-ul dvs., trebuie să înregistrați plata în sistemul MIA POS.

- **Input**: Datele plății (de ex., sumă, monedă, orderId).
- **Output**: Un `paymentId` și un URL `checkoutPage` pentru redirecționarea clientului pentru confirmarea plății.

Exemplu:

```php
require_once __DIR__ . '/src/MiaPosSdk.php';

use Finergy\MiaPosSdk\MiaPosSdk;

$baseUrl = 'https://ecomm-test.miapos.md/';
$merchantId = 'merchantul_dvs';
$secretKey = 'cheia_secretă';

$sdk = MiaPosSdk::getInstance($baseUrl, $merchantId, $secretKey);

$paymentData = [
    'terminalId' => 'TE0001',
    'orderId' => 'comanda12345',
    'amount' => 150.75,
    'currency' => 'MDL',
    'language' => 'ro',
    'payDescription' => 'Plată pentru comanda #12345',
    'callbackUrl' => 'http://callback_url_dvs',
    'successUrl' => 'http://success_url_dvs',
    'failUrl' => 'http://fail_url_dvs',
];

$response = $sdk->createPayment($paymentData);

$paymentId = $response['paymentId'];
$checkoutPage = $response['checkoutPage'];

// Salvați $paymentId în baza de date și redirecționați clientul la $checkoutPage
header("Location: $checkoutPage");
```

---

### 2. Gestionarea Eșecului Plății (failUrl)

Când plata eșuează, clientul va fi redirecționat către `failUrl` specificat în timpul înregistrării plății. Parametrii cererii vor include aceleași date transmise în timpul înregistrării plății, cum ar fi `orderId`.

Utilizați `orderId` pentru a prelua `paymentId` corespunzător din baza de date, apoi folosiți SDK-ul pentru a verifica starea plății. În funcție de status, afișați clientului mesajul corespunzător.

Exemplu:

```php
require_once __DIR__ . '/src/MiaPosSdk.php';

use Finergy\MiaPosSdk\MiaPosSdk;

$baseUrl = 'https://ecomm-test.miapos.md/';
$merchantId = 'merchantul_dvs';
$secretKey = 'cheia_secretă';

$sdk = MiaPosSdk::getInstance($baseUrl, $merchantId, $secretKey);

// Preluați orderId din parametrii cererii
$orderId = $_GET['orderId'];

// Găsiți paymentId în baza de date utilizând orderId
$paymentId = getPaymentIdFromDatabase($orderId);

$response = $sdk->getPaymentStatus($paymentId);

if ($response['status'] === 'FAILED') {
    echo "Plata a eșuat. Încercați din nou.";
} else {
    echo "Plata a fost efectuată cu succes!";
    // Actualizați starea comenzii în baza de date
}
```

---

### 3. Gestionarea Plății Reușite (successUrl)

Când plata reușește, clientul va fi redirecționat către `successUrl`. Parametrii cererii vor include aceleași date transmise în timpul înregistrării plății, cum ar fi `orderId`.

Utilizați `orderId` pentru a prelua `paymentId` corespunzător din baza de date, apoi folosiți SDK-ul pentru a verifica starea plății.

Exemplu:

```php
require_once __DIR__ . '/src/MiaPosSdk.php';

use Finergy\MiaPosSdk\MiaPosSdk;

$baseUrl = 'https://ecomm-test.miapos.md/';
$merchantId = 'merchantul_dvs';
$secretKey = 'cheia_secretă';

$sdk = MiaPosSdk::getInstance($baseUrl, $merchantId, $secretKey);

// Preluați orderId din parametrii cererii
$orderId = $_GET['orderId'];

// Găsiți paymentId în baza de date utilizând orderId
$paymentId = getPaymentIdFromDatabase($orderId);

$response = $sdk->getPaymentStatus($paymentId);

if ($response['status'] === 'SUCCESS') {
    echo "Plata a fost efectuată cu succes! Order ID: " . $response['orderId'];
    // Actualizați starea comenzii în baza de date
} else {
    echo "Plata nu a putut fi verificată. Contactați suportul.";
}
```

---

### 4. Gestionarea Callback-urilor (callbackUrl)

După finalizarea plății, sistemul MIA POS va trimite un callback semnat către `callbackUrl`. Utilizați SDK-ul pentru a verifica semnătura și pentru a procesa datele din callback.

Exemplu:

```php
require_once __DIR__ . '/src/MiaPosSdk.php';

use Finergy\MiaPosSdk\MiaPosSdk;

$baseUrl = 'https://ecomm-test.miapos.md/';
$merchantId = 'merchantul_dvs';
$secretKey = 'cheia_secretă';

$sdk = MiaPosSdk::getInstance($baseUrl, $merchantId, $secretKey);

// Preluați datele din callback (înlocuiți cu inputul real)
$inputJson = file_get_contents('php://input');
$callbackData = json_decode($inputJson, true);

$result = $callbackData['result'];
$signature = $callbackData['signature'];

$signString = $sdk->formSignStringByResult($result);
$isValidSignature = $sdk->verifySignature($signString, $signature);

if ($isValidSignature) {
    echo "Callback verificat cu succes!";
    // Actualizați starea comenzii în baza de date pe baza $result['status']
} else {
    echo "Semnătura callback-ului este invalidă!";
}
```

---

### Note

1. Testați întotdeauna integrarea pe **mediul de testare** înainte de a trece la producție.
2. Asigurați stocarea sigură a credențialelor API (`merchantId`, `secretKey`) și nu le expuneți în codul clientului.