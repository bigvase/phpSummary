<?php
/**
 * 无效的异常类
 * @TODO
 */
namespace Error\Exception\Exceptions;
class ValidException extends ErrorException
{
    public function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }
}