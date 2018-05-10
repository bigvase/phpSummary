<?php
/**
 * 积分业务的异常类
 * @TODO
 */
namespace Error\Exception\Exceptions;
class IntegralException extends ErrorException
{
    public function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }

}