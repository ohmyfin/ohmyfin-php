<?php

declare(strict_types=1);

namespace Ohmyfin;

use Exception;

/**
 * Exception thrown for Ohmyfin API errors.
 *
 * @see https://ohmyfin.ai
 */
class OhmyfinException extends Exception
{
    /**
     * HTTP status code
     */
    protected int $statusCode;

    /**
     * Validation errors from the API
     */
    protected array $errors;

    /**
     * Create a new OhmyfinException.
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array $errors Validation errors
     */
    public function __construct(string $message, int $statusCode = 0, array $errors = [])
    {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
