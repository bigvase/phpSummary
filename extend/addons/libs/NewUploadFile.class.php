<?php
import("ORG.Net.UploadFile");
class NewUploadFile extends UploadFile{
	/**
	 * 上传所有文件
	 * @access public
	 * @param string $savePath  上传文件保存路径
	 * @return string
	 */
	protected  $is_thumb = false;
	protected  $thumbParam = array();
	
	public function setThumb($is_thumb, $thumbParam){
		$this->is_thumb = $is_thumb;
		$this->thumbParam = $thumbParam;
	}
	
	public function upload($savePath ='') {
		//如果不指定保存文件名，则由系统默认
		if(empty($savePath))
			$savePath = $this->savePath;
		// 检查上传目录
		if(!is_dir($savePath)) {
			// 检查目录是否编码后的
			if(is_dir(base64_decode($savePath))) {
				$savePath	=	base64_decode($savePath);
			}else{
				// 尝试创建目录
				if(!mkdir($savePath, 0777, true)){
					$this->error = '上传目录'.$savePath.'不存在';
					return false;
				}
			}
		}else {
			if(!is_writeable($savePath)) {
				$this->error = '上传目录'.$savePath.'不可写';
				return false;
			}
		}
		$fileInfo   = array();
		$isUpload   = false;

		// 获取上传的文件信息
		// 对$_FILES数组信息处理
		$files	 =	 $this->dealFiles($_FILES);
		foreach($files as $key => $file) {
			//过滤无效的上传
			if(!empty($file['name'])) {
				//登记上传文件的扩展信息
				if(!isset($file['key']))   $file['key']    =   $key;
				$file['extension']  =   $this->getExt($file['name']);
				$file['savepath']   =   $savePath;
				$file['savename']   =   $this->getSaveName($file);

				// 自动检查附件
				if($this->autoCheck) {
					if(!$this->check($file))
						return false;
				}

				$savepath = str_replace(SITE_PATH, '', $file['savepath']);
				 
				$filename = $file['savepath'].$file['savename'];
				if(!$this->uploadReplace && is_file($filename)) {
					$this->error	=	'文件已经存在！'.$filename;
					return false;
				}
				if(!move_uploaded_file($file['tmp_name'], $filename)) { // auto_charset($filename,'utf-8','gbk')
					$this->error = '文件上传保存错误！';
					return false;
				}
	
				if($this->is_thumb && $this->thumbParam){
					$newPath = $file['savepath']."tb_".$file['savename'];
					try{
						import("ORG.Util.Image.ThinkImage");
						$img = new ThinkImage(THINKIMAGE_GD, $filename);
						$img->thumb($this->thumbParam['width'], $this->thumbParam['height'],THINKIMAGE_THUMB_SCALING)->save($newPath);
						unlink($file['savepath'].$file['savename']);
						$file['savename'] = "tb_".$file['savename'];
					}catch (Exception $e){
			            $this->error  =  $e->getMessage();
			            return false;
			        }
				}
				
				//保存上传文件
// 				if(!$this->save($file)) return false;
				include_once(SITE_PATH . '/addons/libs/Http.class.php');
				$imgserver = getMainImgServer();
				$return = Http::postUrl($imgserver.'/do_upload/index.php',
						array('file' =>'@'.SITE_PATH.$savepath.$file['savename'], 'savepath'=>$savepath, 'savename'=>$file['savename']));
				$return = json_decode($return);
				if($return->boolen == false){
					$this->error	=	$return->message;
					return false;
				}
				
				if(function_exists($this->hashType)) {
					$fun =  $this->hashType;
					$file['hash']   =  $fun($this->autoCharset($file['savepath'].$file['savename'],'utf-8','gbk'));
				}
				$file['savepath'] = $return->savepath;
				$file['saveapppath'] = $return->saveapppath;
				$file['imgserver'] = $imgserver;
				
				//上传成功后保存文件信息，供其他地方调用
				unset($file['tmp_name'],$file['error']);
				$fileInfo[] = $file;
				$isUpload   = true;
			}
		}
		if($isUpload) {
			$this->uploadFileInfo = $fileInfo;
			return true;
		}else {
			$this->error  =  '没有选择上传文件';
			return false;
		}
	}
}