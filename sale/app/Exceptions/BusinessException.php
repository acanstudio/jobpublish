<?php
namespace App\Exceptions;

use Exception;
use Throwable;

class BusinessException extends Exception
{
    public function __construct($message = "")
    {
        parent::__construct($message);
    }

    public function render()
    {
        return response()->json(['code' => 1, 'msg' => $this->message]);
    }
}
