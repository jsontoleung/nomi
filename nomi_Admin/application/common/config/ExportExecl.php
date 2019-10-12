<?php 
namespace app\common\config;
use PHPExcel;
use PHPExcel_IOFactory;

/*
** EXCEL 控制器
*/

class ExportExecl {

	public static function exportExcel($expTitle, $expCellName, $expTableData, $topData) {
	    $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
	    $fileName = $xlsTitle;//or $xlsTitle 文件名称可根据自己情况设定
	    $cellNum = count($expCellName);
	    $dataNum = count($expTableData);
	    $topNum  = count($topData);

	    $objPHPExcel = new \PHPExcel();
	    $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');

	    $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');//合并单元格
	    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle);

	    for ($i = 0; $i < count($topData); $i++) {
	        for ($j = 0; $j < count($topData[$i]); $j++) {
	            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$j] . ($i + 2), $topData[$i][$j]);
	        }
	    }

	    for ($i = 0; $i < $cellNum; $i++) {
	        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . ($topNum + 2), $expCellName[$i][1]);
	    }
	    // Miscellaneous glyphs, UTF-8
	    for ($i = 0; $i < $dataNum; $i++) {
	        for ($j = 0; $j < $cellNum; $j++) {
	            if ($expCellName[$j][0] == 'account_type') {
	                if ($expTableData[$i][$expCellName[$j][0]] == 0) {
	                    $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + $topNum + 3), '餐饮');
	                } else {
	                    $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + $topNum + 3), '果蔬');
	                }
	            } else {
	                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + $topNum + 3), $expTableData[$i][$expCellName[$j][0]]);
	            }
	        }
	    }

	    header('pragma:public');
	    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
	    header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
	    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	    $objWriter->save('php://output');
	    exit;
	}


}