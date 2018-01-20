<?php

$pdfPath = ADDON_PATH.'/libs/Tcpdf/';
require_once($pdfPath.'tcpdf.php');
class MYPDF extends TCPDF {

	//Page header
	public function Header() {	
		$image_file = K_PATH_IMAGES.'tcpdf_logo.jpg';
		$this->Image($image_file, '', '', '30', '', 'JPG', '', 'T', false, '', '', false, false, 0, false, false, false);
	}

}