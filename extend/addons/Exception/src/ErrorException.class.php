<?php
/**
 * 错误的异常父类
 * @TODO
 */
namespace Error\Exception\Exceptions;
class ErrorException extends \Exception
{
    public function __construct($message, $code, \Exception $previous)
    {
        parent::__construct($message, $code, $previous);
    }
}