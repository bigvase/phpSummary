<?php

$pdfPath = ADDON_PATH.'/libs/Tcpdf/';
require_once($pdfPath.'tcpdf.php');
class MYPDF extends TCPDF {

	//Page header
	public function Header() {	
		$image_file = K_PATH_IMAGES.'tcpdf_logo.jpg';
        /*不要把我注释的还原，这是不要了的，谢谢*/
//		$this->Image($image_file, '', '', '30', '', 'JPG', '', 'T', false, '', '', false, false, 0, false, false, false);
	}

}