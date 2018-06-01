<?php
namespace app\admin\logic;
/**
 * Created by PhpStorm.
 * User: bigsave
 * Date: 2018/5/30
 * Time: 12:50
 */
class fileLogic
{
    /**
     * 创建目录
     * @param $path
     */
    public function makeDir($path){
        if(!file_exists($path)){
            $this->makeDir(dirname($path));
            mkdir($path,0777,true);
        }
    }
    /**
     * 检测文件是否存在
     * @param $file
     * @return int
     */
    public function isWriteAble($file){
        $writeAble=0;
        if(is_dir($file)){
            $dir=$file;
            if($fp=@fopen("$dir/test.txt",'w')){
                @fclose($fp);
                @unlink("$dir/test.txt");
                $writeAble=1;
            }
        }else{
            if($fp=@fopen($file,'a+')){
                @fclose($fp);
                $writeAble=1;
            }
        }
        return $writeAble;
    }
    /**
     * 删除当前目录下的文件或目录
     * @param $dir
     * @param bool $forceClear
     */
    public function clearDir($dir,$forceClear=false) {
        if(!is_dir($dir)){
            return;
        }
        $directory=dir($dir);
        while($entry=$directory->read()){
            $filename=$dir.'/'.$entry;
            if(is_file($filename)){
                @unlink($filename);
            }elseif(is_dir($filename)&$forceClear&$entry!='.'&$entry!='..'){
                chmod($filename,0777);
                $this->clearDir($filename,$forceClear);
                rmdir($filename);
            }
        }
        $directory->close();
    }
    /**
     * 删除当前目录及目录下的文件
     * @param $dir
     * @return bool
     */
    public function removeDir($dir){
        if (is_dir($dir) && !is_link($dir)){
            if ($dh=opendir($dir)){
                while (($sf= readdir($dh))!== false){
                    if('.'==$sf || '..'==$sf){
                        continue;
                    }
                    $this->removeDir($dir.'/'.$sf);
                }
                closedir($dh);
            }
            return rmdir($dir);
        }
        return @unlink($dir);
    }
    /**
     * 复制文件
     * @param $srcDir
     * @param $dstDir
     */
    public function copyDir($srcDir, $dstDir) {
        if(!is_dir($dstDir)) mkdir($dstDir);
        if($curDir = opendir($srcDir)) {
            while($file = readdir($curDir)) {
                if($file != '.' && $file != '..') {
                    $srcFile = $srcDir . '/' . $file;
                    $dstFile = $dstDir . '/' . $file;
                    if(is_file($srcFile)) {
                        copy($srcFile, $dstFile);
                    }
                    else if(is_dir($srcFile)) {
                        $this->copyDir($srcFile, $dstFile);
                    }
                }
            }
            closedir($curDir);
        }
    }

    /**
     * 读取文件
     * @param $filename
     * @return bool|string
     */
    public function readFile($filename) {
        if ($fp=@fopen($filename,'rb')) {
            if(PHP_VERSION >='4.3.0' && function_exists('file_get_contents')){
                return file_get_contents($filename);
            }else{
                flock($fp,LOCK_EX);
                $data=fread($fp,filesize($filename));
                flock($fp,LOCK_UN);
                fclose($fp);
                return $data;
            }
        }else{
            return '';
        }
    }
    /**
     * 写入文件
     * @param $filename
     * @param $data
     * @return bool|int|void
     */
    public function writeToFile($filename,$data){
        if($fp=@fopen($filename,'wb')){
            if (PHP_VERSION >='4.3.0' && function_exists('file_put_contents')) {
                return @file_put_contents($filename,$data);
            }else{
                flock($fp, LOCK_EX);
                $bytes=fwrite($fp, $data);
                flock($fp,LOCK_UN);
                fclose($fp);
                return $bytes;
            }
        }else{
            return;
        }
    }
    //上传文件
    public function uploadfile($attachment,$target,$maxsize=1024,$is_image=1){
        $result=array ('result'=>false,'msg'=>'upload mistake');
        if($is_image){
            $attach=$attachment;
            $filesize=$attach['size']/1024;
            if(0==$filesize){
                $result['msg'] = '上传错误';
                return $result;
            }
            if(substr($attach['type'],0,6)!='image/'){
                $result['msg'] ='格式错误';
                return $result;
            }
            if($filesize>$maxsize){
                $result['msg'] ='文件过大';
                return $result;
            }
        }else{
            $attach['tmp_name']=$attachment;
        }
        $filedir=dirname($target);
        $this->makeDir($filedir);
        if(@copy($attach['tmp_name'],$target) || @move_uploaded_file($attach['tmp_name'],$target)){
            $result['result']=true;
            $result['msg'] ='上传成功';
        }
        if(!$result['result'] && @is_readable($attach['tmp_name'])){
            @$fp = fopen($attach['tmp_name'], 'rb');
            @flock($fp, 2);
            @$attachedfile = fread($fp, $attach['size']);
            @fclose($fp);
            @$fp = fopen($target, 'wb');
            @flock($fp,2);
            if(@fwrite($fp, $attachedfile)) {
                @unlink($attach['tmp_name']);
                $result['result']=true;
                $result['msg']= '上传失败';
            }
            @fclose($fp);
        }
        return $result;
    }
    public function hheader($string, $replace = true, $http_response_code = 0){
        $string = str_replace(array("\r", "\n"), array('', ''), $string);
        if(emptyempty($http_response_code) || PHP_VERSION <'4.3'){
            @header($string, $replace);
        }else{
            @header($string, $replace, $http_response_code);
        }
        if(preg_match('/^\s*location:/is', $string)){
            exit();
        }
    }

    /**
     * 下载文件
     * @param $filePath
     * @param string $filename
     * @return int
     */
    public function downloadFile($filePath,$filename=''){
        global $encoding;
        if(!file_exists($filePath)){
            return 1;
        }
        if(''==$filename){
            $tem=explode('/',$filePath);
            $num=count($tem)-1;
            $filename=$tem[$num];
            $fileType=substr($filePath,strrpos($filePath,".")+1);
        }else{
            $fileType=substr($filename,strrpos($filename,".")+1);
        }
        $filename ='"'.(strtolower($encoding) == 'utf-8' && !(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') === FALSE) ? urlencode($filename) : $filename).'"';
        $fileSize = filesize($filePath);
        $dateline=time();
        $this->hheader('date: '.gmdate('d, d m y h:i:s', $dateline).' gmt');
        $this->hheader('last-modified: '.gmdate('d, d m y h:i:s', $dateline).' gmt');
        $this->hheader('content-encoding: none');
        $this->hheader('content-disposition: attachment; filename='.$filename);
        $this->hheader('content-type: '.$fileType);
        $this->hheader('content-length: '.$fileSize);
        $this->hheader('accept-ranges: bytes');
        if(!@emptyempty($_SERVER['HTTP_RANGE'])) {
            list($range) = explode('-',(str_replace('bytes=', '', $_SERVER['HTTP_RANGE'])));
            $rangeSize = ($fileSize - $range) > 0 ?  ($fileSize - $range) : 0;
            $this->hheader('content-length: '.$rangeSize);
            $this->hheader('http/1.1 206 partial content');
            $this->hheader('content-range: bytes='.$range.'-'.($fileSize-1).'/'.($fileSize));
        }
        if($fp = @fopen($filePath, 'rb')) {
            @fseek($fp, $range);
            echo fread($fp, filesize($filePath));
        }
        fclose($fp);
        flush();
        ob_flush();
    }

    /**
     * 返回文件类型
     * @param $filename
     * @return string
     */
    public function extName($filename){
        $pathInfo=pathinfo($filename);
        return strtolower($pathInfo['extension']);
    }
    public function createAccessFile($path){
        if(!file_exists($path.'index.htm')){
            $content=' ';
            $this->writetofile($path.'index.htm',$content);
        }
        if(!file_exists($path.'.htaccess')){
            $content='Deny from all';
            $this->writetofile($path.'.htaccess',$content);
        }
    }

    /**
     * 返回文件大小
     * @param $fileDir
     * @return int
     */
    public function getDirSize($fileDir){
        $handle=opendir($fileDir);
        $totalSize = 0;
        while($filename=readdir($handle)){
            if ('.' != $filename && '..' != $filename){
                $totalSize += is_dir($fileDir.'/'.$filename) ? $this->getDirSize($fileDir.'/'.$filename) : (int)filesize($fileDir.'/'.$filename);
            }
        }
        return $totalSize;
    }

    /**
     * 按行读取
     * @param $filename
     * @param $rowStart
     * @param $num
     */
    public function get_row_txt($filename,$rowStart,$num,$type=','){
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

}