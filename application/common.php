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
function test2(){
    echo "all-common-function";
}

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
