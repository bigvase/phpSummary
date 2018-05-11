<?php
/**
 * Created by PhpStorm.
 * User: bigsave
 * Date: 2017/12/22
 * Time: 15:39
 */

namespace Signature\constants;


class SignParam
{
    //散标用户签章位置
    public static $signPosStoragePerson = array(
        'posPage' => 3,//页数
        'posX' => 180,
        'posY' => 280,
        'key' => '',
        'width' => '90'
    );
    //散标标企业签章位置
    public static $signPosStoragePc = array(
        'posPage' => 3,
        'posX' => 180,
        'posY' => 180,
        'key' => '',
        'width' => '90'
    );
    //存管标协议母版保存位置
    public static $srcPdfFileStorage = './Upload/sign/storage.pdf';

    //债权标用户签章位置
    public static $signPosTransferPerson = array(
        'posPage' => 5,
        'posX' => 100,
        'posY' => 100,
        'key' => '',
        'width' => '90'
    );

    //债转标协议企业签章位置
    public static $signPosTransferPc = array(
        'posPage' => 5,
        'posX' => 200,
        'posY' => 100,
        'key' => '',
        'width' => '90'
    );

    public static $srcPdfFileTransfer = 'D:\livestepup.pdf';

    //理财计划用户签章位置
    public static $signPosRegPerson = array(
        'posPage' => 5,
        'posX' => 180,
        'posY' => 780,
        'key' => '',
        'width' => '90'
    );

    //理财计划企业签署位置
    public static $signPosRegPc = array(
        'posPage' => 5,
        'posX' => 180,
        'posY' => 700,
        'key' => '',
        'width' => '90'
    );

    public static $srcPdfFileReg = './Upload/sign/registration_protocol.pdf';
    //借款协议借款人签署位置
    public static $signPosBorrowerPerson = array(
        'posPage' => 7,
        'posX' => 180,
        'posY' => 250,
        'key' => '',
        'width' => '90'
    );
    //借款协议出借人签署位置
    public static $signPosInvestPerson = array(
        'posPage' => 7,
        'posX' => 180,
        'posY' => 350,
        'key' => '',
        'width' => '90'
    );
    //借款人协议企业签署位置
    public static $signPosBorrowerPc = array(
        'posPage' => 7,
        'posX' => 180,
        'posY' => 150,
        'key' => '',
        'width' => '90'
    );
}