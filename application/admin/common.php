<?php
/**
 * Created by PhpStorm.
 * User: bigsave
 * Date: 2017/10/20
 * Time: 11:09
 */
 function test(){
    echo "admin-test-common-function";
}

/**
 *导出excel数据
 */
function exportExcel($filename,$data){
    //引用文件
    vendor('phpoffice.phpexcel.Classes.PHPExcel');
    $objPHPExcel = new \PHPExcel();

    $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
        ->setLastModifiedBy("Maarten Balliauw")
        ->setTitle("Office 2007 XLSX Test Document")
        ->setSubject("Office 2007 XLSX Test Document")
        ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
        ->setKeywords("office 2007 openxml php")
        ->setCategory("Test result file");


    // Add some data
    foreach ($data as $key => $val) {
        $LineNumber = 'A';
        foreach ($val as $k => $v) {
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValueExplicit($LineNumber . ($key + 1), $v, PHPExcel_Cell_DataType::TYPE_STRING);
            $LineNumber = getLineNumber($LineNumber);
        }
    }


    // Rename worksheet
    $objPHPExcel->getActiveSheet()->setTitle($filename);


    // Set active sheet index to the first sheet, so Excel opens this as the first sheet
    $objPHPExcel->setActiveSheetIndex(0);


    // Redirect output to a client’s web browser (Excel5)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="'.$filename.'.xls"');
    header('Cache-Control: max-age=0');
    // If you're serving to IE 9, then the following may be needed
    header('Cache-Control: max-age=1');

    // If you're serving to IE over SSL, then the following may be needed
    header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header ('Pragma: public'); // HTTP/1.0

    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
    exit();
}

function getLineNumber($Line){
    $Line = strtoupper($Line);
    $len = strlen($Line);

    if ($len == 0)
        return 'A';

    if ($Line[$len - 1] < 'Z') {
        $Line[$len - 1] = chr(ord($Line[$len - 1]) + 1);
    } else {
        return getLineNumber(substr($Line, 0, $len - 1)) . 'A';
    }

    return $Line;
}

function exportXML($filename,$data){
    vendor('phpoffice.phpexcel.Classes.PHPExcel');
    $xls = new \Excel_XML('UTF-8', false, $filename);
    $xls->addArray($data);
    $xls->generateXML($filename);
    exit;
}

?>