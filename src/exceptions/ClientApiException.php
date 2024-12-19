<?php

namespace Finergy\MiaPosSdk\Exceptions;

use RuntimeException;

/**
 * Class ClientApiException
 *
 * Represents an exception thrown when an API request fails.
 */
class ClientApiException extends RuntimeException
{
    /**
     * @var int|null HTTP status code
     */
    private $httpStatusCode;

    /**
     * @var string|null Error code returned by the API
     */
    private $errorCode;

    /**
     * @var string|null Error message returned by the API
     */
    private $errorMessage;

    /**
     * ClientApiException constructor.
     *
     * @param string $message General error message.
     * @param int|null $httpStatusCode HTTP status code.
     * @param string|null $errorCode Error code returned by the API.
     * @param string|null $errorMessage Error message returned by the API.
     * @param int $code Exception code (default 0).
     * @param \Exception|null $previous Previous exception.
     */
    public function __construct(
        $message,
        $httpStatusCode = null,
        $errorCode = null,
        $errorMessage = null,
        $code = 0,
        $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->httpStatusCode = $httpStatusCode;
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get the HTTP status code.
     *
     * @return int|null
     */
    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }

    /**
     * Get the error code returned by the API.
     *
     * @return string|null
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Get the error message returned by the API.
     *
     * @return string|null
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}