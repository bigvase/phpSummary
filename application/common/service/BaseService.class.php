<?php

/**
 * service 基类
 * @author
 *
 */
class BaseService {
    protected $error;

    protected static $api_key, //接入点KEY
        $session_id, //客户端SESSION ID
        $login_user_id;

    public $tablePrefix = 'tc_';

    function __construct() {
        //$api_keys = BaseModel::getApiKey();
        //self::$api_key          = $api_keys['api_key'];
        //self::$session_id       = $api_keys['session_id'];
        //self::$login_user_id    = $api_keys['login_user_id'];
    }

    function getError() {
        return $this->error;
    }
}