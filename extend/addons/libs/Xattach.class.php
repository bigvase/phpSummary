<?php
Import("libs.NewUploadFile", ADDON_PATH);
class Xattach{
	/**
	 * 上传附件
	 *
	 * @param string $attach_type   附件类型
	 * @param array  $input_options 配置选项[不推荐修改, 默认使用后台的配置]
	 */
	public function upload($uid, $attach_type='attach',$input_options=array()){
		$system_default['attach_path_rule']		 = 'Y/m/d/';
		$system_default['attach_max_size']		 = '20'; // 默认20M
		$system_default['attach_allow_extension']  = 'jpg,gif,png,jpeg,bmp,zip,rar,doc,xls,ppt,docx,xlsx,pptx,pdf';
		
		//载入默认规则
		$default_options =	array();
		$default_options['custom_path']	=	date($system_default['attach_path_rule']);				//应用定义的上传目录规则：'Y/md/H/'
		$default_options['max_size']	=	floatval($system_default['attach_max_size'])*1000000;	//单位: 兆
		$default_options['allow_exts']	=	$system_default['attach_allow_extension']; 				//'jpg,gif,png,jpeg,bmp,zip,rar,doc,xls,ppt,docx,xlsx,pptx,pdf'
		$default_options['allow_types']	=	'';
		$default_options['save_path']	=	UPLOAD_PATH.'/'.$default_options['custom_path'];
		$default_options['save_name']	=	'';
		$default_options['thumb']	=	false; //设置需要生成缩略图，仅对图像文件有效
		$default_options['thumbRemoveOrigin']	=	false; //设置需要生成缩略图，仅对图像文件有效
		$default_options['save_rule']	=	'uniqid';
		$default_options['save_to_db']	=	true;
		
		$default_options['is_thumb'] = false;
		$default_options['thumbParam'] = array();
		
		//定制化设这 覆盖默认设置
		$options	=	array_merge($default_options,$input_options);
		if( intval($options['uid'])==0 )	$options['uid']	= $uid;
		//导入上传类
		$upload = new NewUploadFile();
		//设置上传文件大小
		$upload->maxSize            = $options['max_size'];
		//设置上传文件类型
		$upload->allowExts          = explode(',', $options['allow_exts']);
		//设置附件上传目录
		$upload->savePath           = $options['save_path'];
		//设置需要生成缩略图，仅对图像文件有效
		$upload->thumb              = $options['thumb'];
		$upload->autoSub = false;
		//设置上传文件规则
		$upload->saveRule           = $options['save_rule'];
		$upload->thumbRemoveOrigin = $options['thumbRemoveOrigin'];
		$upload->setThumb($options['is_thumb'], $options['thumbParam']);
		if (!$upload->upload()) {
			//上传失败，返回错误
			$return['status']	=	false;
			$return['info']		=	$upload->getErrorMsg();
			return	$return;
		}else{
			$upload_info	=	$upload->getUploadFileInfo();
			$xattach		=	M('Attach');
			//保存信息到附件表
			if($options['save_to_db']){
				foreach($upload_info as $u){
					$map['attach_type']	=	$attach_type;
					$map['uid']		=	$options['uid'];
					$map['origName']	=	$u['name'];
					$map['name']		=	$u['name'];
					$map['type']		=	$u['type'];
					$map['size']		=	$u['size'];
					$map['extension']	=	strtolower($u['extension']);
					$map['hash']		=	$u['hash'];
					$map['savepath']	=	$u['savepath'];
					$map['saveapppath']	=	$u['saveapppath'];
						
					$map['pdfpathfile']	=	'';
					$map['imgserver']	=	$u['imgserver'];
					$map['savename']	=	$u['savename'];
					$map['noTemp']	=	0;
					$map['uploadTime']	=	time();
					//$map['savedomain']=	C('ATTACH_SAVE_DOMAIN'); //如果做分布式存储，需要写方法来分配附件的服务器domain
					$aid		=	$xattach->add($map);
					$map['id']	=	intval($aid);
					$map['key']	=	$u['key'];
					$infos[]	=	$map;
					unset($map);
				}
			}else{
				foreach($upload_info as $u){
					$map['attach_type']	=	$attach_type;
					$map['uid']		=	$options['uid'];
					$map['origName']	=	$u['name'];
					$map['name']		=	$u['name'];
					$map['type']		=	$u['type'];
					$map['size']		=	$u['size'];
					$map['extension']	=	strtolower($u['extension']);
					$map['hash']		=	$u['hash'];
					$map['savepath']	=	empty($input_options['save_path'])?$options['custom_path']:$input_options['save_path'];
					$map['savename']	=	$u['savename'];
					$map['imgserver']	=	$u['imgserver'];
					$map['noTemp']	=	0;
					$map['uploadTime']	=	time();
					//$map['savedomain']=	C('ATTACH_SAVE_DOMAIN'); //如果做分布式存储，需要写方法来分配附件的服务器domain
					$map['key']	=	$u['key'];
					$infos[]	=	$map;
					unset($map);
				}
			}
			//输出信息
			$return['status']	=	true;
			$return['info']	= $infos;
			$return['aid']	= intval($aid);
			//上传成功，返回信息
			return	$return;
		}
	}
}