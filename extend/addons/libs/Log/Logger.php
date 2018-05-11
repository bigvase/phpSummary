<?php
/**
 * Created by PhpStorm.
 * User: 001424
 * Date: 2015/9/8
 * Time: 10:46
 */
namespace Addons\Libs\Log;

class Logger
{

    private static $inited = false;
    private static $request_no;
    private static $user_agent;
    private static $request_uri;
    private static $remote_ip;
    private static $user_id;
    private static $post_params;
    private static $request_time;
    private static $referer_url;
    private static $session_id;

    private static $cache = array();

    private static function init()
    {
        self::$inited = true;

        self::$request_no = md5($_SERVER['HTTP_USER_AGENT'] . time() . rand(1, 99999999));
        self::$user_agent = $_SERVER['HTTP_USER_AGENT'];
        self::$request_uri = $_SERVER['REQUEST_URI'];
        self::$remote_ip = $_SERVER['REMOTE_ADDR'];
        self::$post_params = substr(http_build_query($_POST), 0, 3000);
        self::$request_time = time();
        self::$referer_url = $_SERVER['HTTP_REFERER'];
        self::$session_id = session_id();

        //self::$user_id      = ;
    }

    /**
     * 记录日志
     * @param $msg 日志消息
     * @param string $tag 消息标签
     * @param int $level 等级 1-10之间的数值，10：特别关注  1：留意
     * @param array $ext_data 扩展数据
     */
    public static function record($msg, $tag = '', $level = 1, $ext_data = array())
    {
        if (!self::$inited) {
            self::init();
            register_shutdown_function(array(__CLASS__, 'save'));
        }

        $data = array(
            'msg'       => $msg,
            'level'     => min(10, max(1, intval($level))),
            'tag'       => $tag,
            'ext_data'  => $ext_data
        );

        if (C('APP_STATUS') != 'product' && $called = self::get_called()) {
            $data['file']   = str_replace(SITE_PATH, '', $called['file']);
            $data['line']   = $called['line'];
        }

	    if (C('APP_STATUS') != 'product') {
            self::save_to_db($data);
        }

        self::$cache[] = $data;
    }

    /**
     * 错误日志记录
     * @param string $msg 日志信息
     * @param string $tag 日志标签
     * @param array $ext_data 扩展数据
     */
    public static function err($msg, $tag = "", $ext_data = array())
    {
        self::record($msg, $tag, 10, $ext_data);
    }

    /**
     * 信息日志记录
     * @param string $msg 日志信息
     * @param string $tag 日志标签
     * @param array $ext_data 扩展数据
     */
    public static function info($msg, $tag = "", $ext_data = array())
    {
        self::record($msg, $tag, 1, $ext_data);
    }

    /**
     * 警示日志记录
     * @param string $msg 日志信息
     * @param string $tag 日志标签
     * @param array $ext_data 扩展数据
     */
    public static function wrong($msg, $tag = "", $ext_data = array())
    {
        self::record($msg, $tag, 5, $ext_data);
    }

    public static function save()
    {

        try {
            $data = array(
                'request_no'    => self::$request_no,
                'user_agent'    => self::$user_agent,
                'request_uri'   => self::$request_uri,
                'remote_ip'     => self::$remote_ip,
                'post_params'   => self::$post_params,
                'request_time'  => self::$request_time,
                'referer_url'   => self::$referer_url,
                'session_id'    => self::$session_id,
                'log_list'      => self::$cache,
            );

            $redis_config = C('REDIS');
            if (isset($redis_config['log'])) {
                $redis = \Addons\Libs\Cache\Redis::getInstance('log', false);
                $redis->rPush("xhh_app_logs", json_encode($data));
            }
        } catch (\Exception $e) {
            //不做任何处理，抛弃统计结果
        }

        if (C('APP_STATUS') != 'product') {
            self::print_log();
        }
    }

    public static function get_called()
    {
        $trace = debug_backtrace();

        foreach($trace as $index => $each){
            if( !isset($each['class']) || $each['class'] != __CLASS__ ){

                return array(
                    'file' => $trace[$index-1]['file'],
                    'line' => $trace[$index-1]['line'],
                );
            }
        }

        return false;
    }

    public static function print_log()
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            foreach (self::$cache as $each) {
                echo $each['msg'] . " 文件：{$each['file']} 行：{$each['line']}\n";
            }
        } elseif ($_GET['debug']) {
            echo "<div style=\"background-color:#DDD;padding:5px\">";
            foreach (self::$cache as $each) {
                echo $each['msg'] . " 文件：{$each['file']} 行：{$each['line']}<br/>";
            }
            echo "</div>";
        }
    }

    private static function save_to_file($data){

        $msg = "";
        $time = date('Y-m-d H:i:s');
        $ext_data = var_export($data['ext_data'], true);
        $msg .= "\n===============================\n[{$time}] {$data['tag']} {$data['msg']}  文件：{$data['file']} 行：{$data['line']} \n" .  $ext_data . "\n===============================\n\n";

        $path = SITE_DATA_PATH . '/logs/Logger/'.date('Y')."/".date("m")."/".date("d");
        $file = $data['tag'] . ".log";
        //防止带目录形式的。没有权限建立文件夹
        $file = str_replace("/", "-", $file);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        if ($f = fopen($path . "/" . $file, 'a+')) {
            fwrite($f, $msg);
            fclose($f);
        }

    }

    /**
     * 为了对应分布式，这里修改为数据库的形式
     * 生产上禁止使用这个方法
     * @param $data
     * @return mixed
     */
    private static function save_to_db($data)
    {
        $mod = M("admin_log");
        $log = array('data' => serialize($data), 'type' => $data['tag'], 'ctime' => time());
        if ($data['ext_data']['uid']) {
            $log['uid'] = $data['ext_data']['uid'];
        }
        $res = $mod->add($log);

        return $res;
    }
}
