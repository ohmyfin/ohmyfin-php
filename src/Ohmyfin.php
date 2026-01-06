<?php

declare(strict_types=1);

namespace Ohmyfin;

/**
 * Ohmyfin API Client
 *
 * Official PHP SDK for Ohmyfin API - SWIFT transaction tracking,
 * validation, and correspondent banking data.
 *
 * Get your API key at https://ohmyfin.ai
 *
 * Ohmyfin is previously known as TrackMySwift.
 *
 * @see https://ohmyfin.ai
 * @license MIT
 *
 * Example:
 * ```php
 * $client = new Ohmyfin('your-api-key');
 * $result = $client->track([
 *     'uetr' => '97ed4827-7b6f-4491-a06f-b548d5a7512d',
 *     'amount' => 10000,
 *     'date' => '2024-01-15',
 *     'currency' => 'USD'
 * ]);
 * echo $result['status'];
 * ```
 */
class Ohmyfin
{
    /**
     * API key
     */
    protected string $apiKey;

    /**
     * Base URL for the API
     */
    protected string $baseUrl;

    /**
     * Request timeout in seconds
     */
    protected int $timeout;

    /**
     * Create an Ohmyfin client.
     *
     * @param string $apiKey Your Ohmyfin API key (get one at https://ohmyfin.ai)
     * @param string $baseUrl API base URL (default: https://ohmyfin.ai)
     * @param int $timeout Request timeout in seconds (default: 30)
     *
     * @throws \InvalidArgumentException If API key is empty
     */
    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://ohmyfin.ai',
        int $timeout = 30
    ) {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException(
                'API key is required. Get your API key at https://ohmyfin.ai'
            );
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Make an API request.
     *
     * @param string $method HTTP method
     * @param string $path API endpoint path
     * @param array $data Request data
     * @return array Response data
     *
     * @throws OhmyfinException If the request fails
     */
    protected function request(string $method, string $path, array $data = []): array
    {
        $url = $this->baseUrl . $path;

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'KEY: ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: ohmyfin-php/1.0.0'
            ],
            CURLOPT_CUSTOMREQUEST => $method,
        ]);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new OhmyfinException('Request failed: ' . $error, 0);
        }

        $decoded = json_decode($response, true);

        if ($statusCode >= 400) {
            throw new OhmyfinException(
                $decoded['message'] ?? 'API request failed',
                $statusCode,
                $decoded['errors'] ?? []
            );
        }

        return $decoded ?? [];
    }

    /**
     * Track a SWIFT transaction.
     *
     * @param array $params Tracking parameters:
     *   - uetr: string (optional) UETR (Universal End-to-End Transaction Reference)
     *   - ref: string (optional) Transaction reference (required if uetr not provided)
     *   - amount: float (required) Transaction amount
     *   - date: string (required) Transaction date (YYYY-MM-DD format)
     *   - currency: string (required) Currency code (e.g., 'USD', 'EUR')
     *
     * @return array Transaction tracking result with status, lastupdate, details, limits
     *
     * @throws \InvalidArgumentException If required parameters are missing
     * @throws OhmyfinException If the API request fails
     *
     * Example:
     * ```php
     * $result = $client->track([
     *     'uetr' => '97ed4827-7b6f-4491-a06f-b548d5a7512d',
     *     'amount' => 10000,
     *     'date' => '2024-01-15',
     *     'currency' => 'USD'
     * ]);
     * ```
     */
    public function track(array $params): array
    {
        if (empty($params['uetr']) && empty($params['ref'])) {
            throw new \InvalidArgumentException('Either uetr or ref is required');
        }

        if (empty($params['amount']) || empty($params['date']) || empty($params['currency'])) {
            throw new \InvalidArgumentException('amount, date, and currency are required');
        }

        return $this->request('POST', '/api/track', $params);
    }

    /**
     * Update/report transaction status (for financial institutions).
     *
     * @param array $params Transaction update parameters:
     *   - uetr: string (optional) UETR
     *   - ref: string (optional) Transaction reference
     *   - amount: float (required) Transaction amount
     *   - date: string (required) Transaction date (YYYY-MM-DD)
     *   - currency: string (required) Currency code
     *   - status: string (required) 'in process', 'success', 'rejected', 'on hold'
     *   - role: string (required) 'originator', 'beneficiary', 'intermediary', 'correspondent', 'other'
     *   - swift: string (optional) Your SWIFT/BIC code
     *   - nextName: string (optional) Next bank name in chain
     *   - nextSwift: string (optional) Next bank SWIFT code
     *   - message: string (optional) Additional message
     *   - details: string (optional) Additional details
     *
     * @return array Update confirmation
     *
     * @throws \InvalidArgumentException If required parameters are missing
     * @throws OhmyfinException If the API request fails
     *
     * Example:
     * ```php
     * $client->change([
     *     'uetr' => '97ed4827-7b6f-4491-a06f-b548d5a7512d',
     *     'amount' => 10000,
     *     'date' => '2024-01-15',
     *     'currency' => 'USD',
     *     'status' => 'success',
     *     'role' => 'correspondent'
     * ]);
     * ```
     */
    public function change(array $params): array
    {
        if (empty($params['uetr']) && empty($params['ref'])) {
            throw new \InvalidArgumentException('Either uetr or ref is required');
        }

        if (empty($params['status']) || empty($params['role'])) {
            throw new \InvalidArgumentException('status and role are required');
        }

        return $this->request('POST', '/api/change', $params);
    }

    /**
     * Validate a transaction before sending.
     *
     * @param array $params Validation parameters:
     *   - beneficiary_bic: string (required) Beneficiary bank SWIFT/BIC
     *   - currency: string (required) Currency code
     *   - correspondent_bic: string (optional) Correspondent bank BIC
     *   - correspondent_account: string (optional) Correspondent account
     *   - beneficiary_iban: string (optional) Beneficiary IBAN
     *   - beneficiary_owner: string (optional) Beneficiary name
     *   - beneficiary_country: string (optional) Beneficiary country
     *   - beneficiary_region: string (optional) Beneficiary region
     *   - sender_bic: string (optional) Sender bank BIC
     *   - sender_correspondent_bic: string (optional) Sender correspondent BIC
     *
     * @return array Validation result with status for each field and available_correspondents
     *
     * @throws \InvalidArgumentException If required parameters are missing
     * @throws OhmyfinException If the API request fails
     *
     * Example:
     * ```php
     * $result = $client->validate([
     *     'beneficiary_bic' => 'DEUTDEFF',
     *     'currency' => 'EUR',
     *     'beneficiary_iban' => 'DE89370400440532013000'
     * ]);
     * ```
     */
    public function validate(array $params): array
    {
        if (empty($params['beneficiary_bic']) || empty($params['currency'])) {
            throw new \InvalidArgumentException('beneficiary_bic and currency are required');
        }

        return $this->request('POST', '/api/validate', $params);
    }

    /**
     * Get Standard Settlement Instructions (SSI) for a bank.
     *
     * @param array $params SSI query parameters:
     *   - swift: string (required) Bank SWIFT/BIC code
     *   - currency: string (required) Currency code
     *
     * @return array SSI data with correspondents, currencies, and limits
     *
     * @throws \InvalidArgumentException If required parameters are missing
     * @throws OhmyfinException If the API request fails
     *
     * Example:
     * ```php
     * $ssi = $client->getSSI([
     *     'swift' => 'DEUTDEFF',
     *     'currency' => 'EUR'
     * ]);
     * foreach ($ssi['correspondents'] as $correspondent) {
     *     echo $correspondent['bank'] . ' - ' . $correspondent['swift'] . "\n";
     * }
     * ```
     */
    public function getSSI(array $params): array
    {
        if (empty($params['swift']) || empty($params['currency'])) {
            throw new \InvalidArgumentException('swift and currency are required');
        }

        return $this->request('POST', '/api/getssi', $params);
    }
}
