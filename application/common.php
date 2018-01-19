<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/**
 * 查看当前文件导入的文件
 */
function export_class_look(){
    $included_files = get_included_files();
    foreach ($included_files as $filename) {
        echo "$filename";
        echo "<br>";
    }
    die();
}


function array_sorts($array,$sort='desc'){
    echo "aaa";
}

/**********************************/
/*********【日期时间函数】***********/
/**********************************/

/**
 * @param $date
 * @param string $t
 * @param int $n
 * @return array
 */
function get_date($date, $t = "d", $n = 0){
    $dt = [];
    if ($t == "d") {
        $timeStart = date("Y-m-d 00:00:00", strtotime("{$n} day"));
        $timeEnd = date("Y-m-d 23:59:59", strtotime("{$n} day"));
    } else if ($t == "w") {
        if ($n != 0) {
            $date = date("Y-m-d", strtotime("{$n} week"));
        }
        $timeEnd = date("Y-m-d 23:59:59", strtotime("{$date} Sunday"));
        $timeStart = date("Y-m-d 00:00:00", strtotime("{$timeEnd} -6 days"));
    } else if ($t == "m") {
//        echo date('Y-m-d H:i:s',$date);die;
        if ($n != 0) {
            if (date("m", time()) == 1) {
                $date = date("Y-m-d", strtotime("{$n} months -1 day"));
            } else {
                $date = date("Y-m-d", strtotime("{$n} months"));
            }
        }
        $timeStart = date("Y-m-01 00:00:00", strtotime($date));
        $timeEnd = date("Y-m-d 23:59:59", strtotime("{$timeStart} +1 month -1 day"));
    }
    return [$timeStart,$timeEnd];
}

function get_times($data = array()){
    if (isset($data['time']) && $data['time'] != "") {
        $time = $data['time'];
    } else if (isset($data['date']) && $data['date'] != "") {
        $time = strtotime($data['date']);
    } else {
        $time = time();
    }
    if (isset($data['type']) && $data['type'] != "") {
        $type = $data['type'];
    } else {
        $type = "month";
    }
    if (isset($data['num']) && $data['num'] != "") {
        $num = $data['num'];
    } else {
        $num = 1;
    }
    if ($type == "month") {
        $month = date("m", $time);
        $year = date("Y", $time);
        $_result = strtotime("{$num} month", $time);
        $_month = ( integer )date("m", $_result);
        if (12 < $month + $num) {
            $_num = $month + $num - 12;
            $year += 1;
        } else {
            $_num = $month + $num;
        }
        if ($_num != $_month) {
            $_result = strtotime("-1 day", strtotime("{$year}-{$_month}-01"));
        }
    } else {
        $_result = strtotime("{$num} {$type}", $time);
    }
    if (isset($data['format']) && $data['format'] != "") {
        return date($data['format'], $_result);
    } else {
        return $_result;
    }
}

function timeDiff($begin_time, $end_time){
    if ($begin_time < $end_time) {
        $starttime = $begin_time;
        $endtime = $end_time;
    } else {
        $starttime = $end_time;
        $endtime = $begin_time;
    }
    $timeDiff = $endtime - $starttime;
    $days     = intval($timeDiff / 86400);
    $remain   = $timeDiff % 86400;
    $hours    = intval($remain / 3600);
    $remain %= 3600;
    $mins     = intval($remain / 60);
    $secs     = $remain % 60;
    $res = array(
        "day" => $days,
        "hour" => $hours,
        "min" => $mins,
        "sec" => $secs
    );
    return $res;
}

function getLastTimeFormt($time, $type = 0){
    if ($type == 0) {
        $f = "m-d H:i";
    } else if ($type == 1) {
        $f = "Y-m-d H:i";
    }
    $agoTime = time() - $time;
    if ($agoTime <= 60 && 0 <= $agoTime) {
        return $agoTime."秒前";
    } else if ($agoTime <= 3600 && 60 < $agoTime) {
        return intval($agoTime / 60)."分钟前";
    } else if (date("d", $time) == date("d", time()) && 3600 < $agoTime) {
        return "今天 ".date("H:i", $time);
    } else if (date("d", $time + 86400) == date("d", time()) && $agoTime < 172800) {
        return "昨天 ".date("H:i", $time);
    } else {
        return date($f, $time);
    }
}

function getLeftTime($timeend, $type = 1){
    if ($type == 1) {
        $timeend = strtotime(date("Y-m-d", $timeend)." 23:59:59");
        $timenow = strtotime(date("Y-m-d", time())." 23:59:59");
        $left = ceil(($timeend - $timenow) / 3600 / 24);
    } else {
        $left_arr = timeDiff(time(), $timeend);
        $left = $left_arr['day']."天 ".$left_arr['hour']."小时 ".$left_arr['min']."分钟 ".$left_arr['sec']."秒";
    }
    return $left;
}

function numberFormat($number,$precision=2){
    return number_format($number, $precision, '.', '');
}

function timeFormat($time){
    if($time<0)
        return "-天-时-分-秒";
    $days     = intval($time / 86400);
    $remain   = $time % 86400;
    $hours    = intval($remain / 3600);
    $remain %= 3600;
    $mins     = intval($remain / 60);
    $secs     = $remain % 60;
    $t = "";
    if($days)
        $t .= $days."天 ";
    if($hours)
        $t .= $hours."时 ";
    if($mins)
        $t .= $mins."分 ";
    if($secs)
        $t .= $secs."秒";
    return $t;
}

/**
 * @param $time Boolean 日期参数是否自带 H:s:i
 */
function datecondition($etime=false){
    $sdate = urldecode(trim($_REQUEST['start_time']));
    $edate = urldecode(trim($_REQUEST['end_time']));
    $etime = $etime ? '' : ' 23:59:59';
    $map = array();

    if (!empty($sdate) && !empty($edate)) {
        $map['timespan'] = array("between", strtotime($sdate) . "," . strtotime($edate.$etime));
        $map['start_time'] = strtotime($sdate);
        $map['end_time']   = strtotime($edate.$etime);
    } elseif (!empty($sdate)) {
        $map['timespan'] = array("gt", strtotime($sdate));
        $map['start_time'] = strtotime($sdate);
    } elseif (!empty($edate)) {
        $map['timespan'] = array("lt", strtotime($edate.$etime));
        $map['end_time']   = strtotime($edate.$etime);
    }
    return $map;
}

function second2string($second, $type = 0){
    $day = floor($second / 86400);
    $second %= 86400;
    $hour = floor($second / 3600);
    $second %= 3600;
    $minute = floor($second / 60);
    $second %= 60;
    switch ($type) {
        case 0 :
            if (1 <= $day) {
                $res = $day."天";
            } else if (1 <= $hour) {
                $res = $hour."小时";
            } else {
                $res = $minute."分钟";
            }
            break;
        case 1 :
            if (5 <= $day) {
                $res = date("Y-m-d H:i", time() + $second);
            } else if (1 <= $day && $day < 5) {
                $res = $day."天前";
            } else if (1 <= $hour) {
                $res = $hour."小时前";
            } else {
                $res = $minute."分钟前";
                break;
            }
    }
    return $res;
}

/**
 * 根据类型验证信息
 * @param $type /cellphone,email,
 * @param $data /要验证的信息
 * @return bool|int
 */
function verify_valid($type,$data){
    switch ($type){
        case 'cellphone':
            return preg_match("/^1[3-5,7,8]{1}[0-9]{9}$/",$data);
            break;
        case 'email':
            return preg_match("/^[0-9a-z][0-9a-z-._]+@{1}[0-9a-z.-]+[a-z]{2,4}$/i",$data);
            break;
        default:
            return false;
    }
}

/**
 * 发送http request请求
 * @param $url
 */
function http_request($url){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}

/**
 * 按行读取
 * @param $filename
 * @param $rowStart
 * @param $num
 */
function get_row_txt($filename,$rowStart,$num,$type=','){
    $row     = 0; //行数
    $pointer = 0; //指针
    $dt      = []; //容器
    $f = fopen($filename,'r');
    while (!feof($f) && $row<=$num)
    {
        $pointer ++;
        $line = fgets($f,2048);//fgets指针自动下移
        if($pointer > $rowStart){
            $dt[] = explode($type, $line);
        }
    }
}

