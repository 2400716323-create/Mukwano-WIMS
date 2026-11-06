<?php

namespace App\Exceptions;

use RuntimeException;

class DuplicateGoodsReceiptException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This goods receipt has already been confirmed.');
    }
}
