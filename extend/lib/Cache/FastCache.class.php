<?php
require("phpfastcache.php");

function FastCache($storage = "", $option = array())
{

    if (empty($storage)) {
        $storage = 'memcache'; // files, sqlite, auto, apc, wincache, xcache, memcache, memcached,
        $host = C('MEMCACHE_HOST');
        $port = C('MEMCACHE_PORT');
        $weight = 1;

        $option = array(
            "storage" => $storage,
            "securityKey" => "",
            "server" => array(
                array($host, $port, $weight),
            ),
        );
    }

    if (!isset(phpFastCache_instances::$instances[$storage])) {
        phpFastCache_instances::$instances[$storage] = new phpFastCache($storage, $option);
    }
    return phpFastCache_instances::$instances[$storage];
}