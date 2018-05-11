<?php
namespace Addons\Libs\Cache;

use Predis\Client;

class Predis extends Client{

    public function __construct()
    {

        $server = array(
            'scheme'   => 'tcp',
            'host'     => C('REDIS_HOST') ? C('REDIS_HOST') : '127.0.0.1',
            'port'     => C('REDIS_PORT') ? C('REDIS_PORT') : 6379,
            'timeout'  => C('DATA_CACHE_TIMEOUT') ? (int)C('DATA_CACHE_TIMEOUT') : 2,
            'database' => 7,
            'password' => C('REDIS_PWD'),
            'alias'    => 'first',
        );
        $options = array(
            'prefix' => ''
        );
        parent::__construct($server,$options);

    }

    public function incr_expire($key,$expire=3600)
    {
        $res = $this->incr($key);
        $this->expire($key,(int)$expire);
        return $res;
    }

    public function set_expire($key,$value,$expire=3600)
    {
        $res = $this->set($key,$value);
        $this->expire($key,(int)$expire);
        return $res;
    }

}