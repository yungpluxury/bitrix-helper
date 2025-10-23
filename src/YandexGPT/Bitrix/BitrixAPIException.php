<?php

namespace YandexGPT\Bitrix;

class BitrixAPIException extends \Exception
{
    private $bitrixErrorCode;

    public function __construct($message = "", $bitrixErrorCode = null, $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->bitrixErrorCode = $bitrixErrorCode;
    }

    public function getBitrixErrorCode()
    {
        return $this->bitrixErrorCode;
    }
}
