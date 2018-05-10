<?php
/**
 * User: Administrator
 * Date: 2017/1/11
 */

namespace Signature\result;


class Result extends AbstractResult
{
    public function parseData()
    {
        $res = $this->rawResponse;

        return $res;
    }

}