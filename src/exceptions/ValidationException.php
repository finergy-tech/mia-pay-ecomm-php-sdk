<?php
namespace Finergy\MiaPosSdk\Exceptions;

use RuntimeException;

/**
 * Class ValidationException
 *
 * Represents an exception thrown when validation of input data fails.
 */
class ValidationException extends RuntimeException
{
    /**
     * @var array List of invalid fields
     */
    private $invalidFields;

    /**
     * ValidationException constructor.
     *
     * @param string $message Error message
     * @param array $invalidFields List of invalid fields
     * @param int $code Exception code
     * @param \Exception|null $previous Previous exception
     */
    public function __construct($message, $invalidFields = array(), $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->invalidFields = $invalidFields;
    }

    /**
     * Get the invalid fields.
     *
     * @return array
     */
    public function getInvalidFields()
    {
        return $this->invalidFields;
    }
}

