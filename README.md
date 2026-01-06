# Ohmyfin PHP SDK

[![Packagist Version](https://img.shields.io/packagist/v/ohmyfin/ohmyfin-php.svg)](https://packagist.org/packages/ohmyfin/ohmyfin-php)
[![PHP Version](https://img.shields.io/packagist/php-v/ohmyfin/ohmyfin-php.svg)](https://packagist.org/packages/ohmyfin/ohmyfin-php)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Official PHP SDK for the [Ohmyfin API](https://ohmyfin.ai) - SWIFT transaction tracking, validation, and correspondent banking data.

**Ohmyfin** (previously known as TrackMySwift) provides real-time SWIFT payment tracking, transaction validation, and Standard Settlement Instructions (SSI) data for financial institutions and businesses.

## Features

- **Transaction Tracking** - Track SWIFT payments in real-time using UETR or reference
- **Payment Validation** - Validate transactions before sending (BIC, IBAN, sanctions)
- **SSI Data** - Access Standard Settlement Instructions and correspondent banking data
- **Status Updates** - Report transaction status (for financial institutions)

## Requirements

- PHP 7.4 or higher
- cURL extension
- JSON extension

## Installation

```bash
composer require ohmyfin/ohmyfin-php
```

## Quick Start

Get your API key at [https://ohmyfin.ai](https://ohmyfin.ai)

```php
<?php

require_once 'vendor/autoload.php';

use Ohmyfin\Ohmyfin;

$client = new Ohmyfin('your-api-key');

// Track a transaction
$result = $client->track([
    'uetr' => '97ed4827-7b6f-4491-a06f-b548d5a7512d',
    'amount' => 10000,
    'date' => '2024-01-15',
    'currency' => 'USD'
]);

echo $result['status']; // 'success', 'in progress', 'rejected', etc.
```

## API Reference

### Constructor

```php
$client = new Ohmyfin(
    'your-api-key',           // Required - get yours at https://ohmyfin.ai
    'https://ohmyfin.ai',     // Optional - API base URL
    30                        // Optional - request timeout in seconds
);
```

### track()

Track a SWIFT transaction by UETR or reference.

```php
$result = $client->track([
    'uetr' => '97ed4827-7b6f-4491-a06f-b548d5a7512d', // or use 'ref'
    'amount' => 10000,
    'date' => '2024-01-15',
    'currency' => 'USD'
]);
```

**Response:**
```php
[
    'status' => 'in progress',  // 'success', 'rejected', 'on hold', 'unknown'
    'lastupdate' => '2024-01-15',
    'details' => [
        [
            'id' => 0,
            'bank' => 'JP MORGAN CHASE',
            'swift' => 'CHASUS33',
            'status' => 'success',
            'reason' => '',
            'route' => 'confirmed'
        ]
    ],
    'limits' => ['daily' => 100, 'monthly' => 1000, 'annual' => 10000]
]
```

### validate()

Validate a transaction before sending.

```php
$result = $client->validate([
    'beneficiary_bic' => 'DEUTDEFF',
    'currency' => 'EUR',
    'beneficiary_iban' => 'DE89370400440532013000',
    'correspondent_bic' => 'COBADEFF',  // Optional
    'sender_bic' => 'CHASUS33'          // Optional
]);
```

**Response:**
```php
[
    'beneficiary_bic' => ['status' => 'ok'],
    'beneficiary_iban' => ['status' => 'ok'],
    'correspondent_bic' => [
        'status' => 'warning',
        'details' => 'Not the preferred correspondent'
    ],
    'avg_business_days' => 1,
    'available_correspondents' => [
        ['corresBIC' => 'COBADEFF', 'is_preferred' => true]
    ]
]
```

### getSSI()

Get Standard Settlement Instructions for a bank.

```php
$ssi = $client->getSSI([
    'swift' => 'DEUTDEFF',
    'currency' => 'EUR'
]);
```

**Response:**
```php
[
    'correspondents' => [
        [
            'id' => 1,
            'bank' => 'COMMERZBANK AG',
            'swift' => 'COBADEFF',
            'currency' => 'EUR',
            'account' => '400886700401',
            'is_preferred' => true
        ]
    ],
    'currencies' => ['EUR', 'USD', 'GBP']
]
```

### change()

Report transaction status updates (for financial institutions).

```php
$client->change([
    'uetr' => '97ed4827-7b6f-4491-a06f-b548d5a7512d',
    'amount' => 10000,
    'date' => '2024-01-15',
    'currency' => 'USD',
    'status' => 'success',     // 'in process', 'success', 'rejected', 'on hold'
    'role' => 'correspondent'  // 'originator', 'beneficiary', 'intermediary', 'correspondent', 'other'
]);
```

## Error Handling

```php
use Ohmyfin\Ohmyfin;
use Ohmyfin\OhmyfinException;

try {
    $result = $client->track([...]);
} catch (OhmyfinException $e) {
    echo $e->getStatusCode();  // HTTP status code
    print_r($e->getErrors());  // Validation errors
    echo $e->getMessage();     // Error message
}
```

## Links

- **Website:** [https://ohmyfin.ai](https://ohmyfin.ai)
- **API Documentation:** [https://ohmyfin.ai/api-documentation](https://ohmyfin.ai/api-documentation)
- **Get API Key:** [https://ohmyfin.ai](https://ohmyfin.ai)
- **Support:** support@ohmyfin.ai

## About Ohmyfin

[Ohmyfin](https://ohmyfin.ai) (previously known as TrackMySwift) is a software platform providing transaction tracking, validation, and correspondent banking reference data. We serve individuals, businesses, and financial institutions worldwide.

**We do not provide any financial services.**

## Trademarks

SWIFT, BIC, UETR, and related terms are trademarks owned by S.W.I.F.T. SC, headquartered at Avenue Adele 1, 1310 La Hulpe, Belgium. Ohmyfin is not affiliated with S.W.I.F.T. SC. Other product and company names mentioned herein may be trademarks of their respective owners.

## License

MIT License - see [LICENSE](LICENSE) file.
