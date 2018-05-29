<?php
namespace app\yar\service;
/**
 * Created by PhpStorm.
 * User: bigsave
 * Date: 2018/4/28
 * Time: 10:28
 */
class YarServer extends baseYarServer
{
    /**
     * Add two operands
     * @param interge
     * @return interge
     */
    public function add($a, $b) {
        return $this->_add($a, $b);
    }

    /**
     * Sub
     */
    public function sub($a, $b) {
        return $a - $b;
    }

    /**
     * Mul
     */
    public function mul($a, $b) {
        return $a * $b;
    }

    /**
     * Protected methods will not be exposed
     * @param interge
     * @return interge
     */
    protected function _add($a, $b) {
        return $a + $b;
    }

}


$server = new \Yar_Server(new YarServer());
$server->handle();