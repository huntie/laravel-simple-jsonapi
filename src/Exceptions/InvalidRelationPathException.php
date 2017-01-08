<?php

namespace Huntie\JsonApi\Exceptions;

use Exception;

class InvalidRelationPathException extends Exception
{
    /**
     * Create a new InvalidRelationPathException instance.
     *
     * @param string         $path     The relation path attempted
     * @param int|null       $code     User defined exception code
     * @param Exception|null $previous Previous exception if nested
     */
    public function __construct($path, $code = 0, Exception $previous = null)
    {
        $message = sprintf('The relationship path "%s" could not be resolved', $path);

        parent::__construct($message, $code, $previous);
    }
}
