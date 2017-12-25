<?php
/**
 * Created by PhpStorm.
 * User: iconblog
 * Date: 2017/10/27
 * Time: 下午6:43
 */

namespace app\sys\api;


use think\Exception;

class Excel
{

    /**
     * 允许导入导出的文件类型
     * @var array
     */
    public static $type = [
        'CSV',
        'Excel5',
        'Excel2007',
        'HTML',
        'PDF'
    ];


    /**
     * PHPExcel实例
     * @var \PHPExcel
     */
    private $PHPExcel;


    /**
     * 需要导出的数据
     * @var array
     */
    private $exportData;


    /**
     * 导出的路径
     * @var string
     */
    private $exportPath;


    /**
     * 导出的名称
     * @var string
     */
    private $exportName;


    /**
     * 当前选定的类型
     * @var string
     */
    private $nowType;


    /**
     * 输出类型
     * 1/下载
     * 2/生成文件到目录
     * 3/下载并生成文件到目录
     * @var int
     */
    public $outputType = 1;


    /**
     * Excel constructor.
     * @param $type
     * @throws \Exception
     */
    public function __construct($type)
    {
        if (in_array($type, self::$type)) {
            $this->nowType = $type;
        } else {
            throw new \Exception('不支持您选择的类型');
        }
        // 载入文件
        require_once VENDOR_PATH . 'PHPExcel/PHPExcel.php';


        $this->PHPExcel = new \PHPExcel();
    }


    /**
     * 导入数据
     * @param $file
     * @throws \Exception
     * @return array
     */
    public function import($file)
    {
        if (is_file($file)) {
            require_once VENDOR_PATH . 'PHPExcel/PHPExcel/IOFactory.php';
            require_once VENDOR_PATH . 'PHPExcel/PHPExcel/Reader/' . $this->nowType . '.php';
            // 创建读取对象
            $reader = \PHPExcel_IOFactory::createReader($this->nowType);
            // 读取文件
            $readerFile = $reader->load($file);
            $sheetCount = $readerFile->getSheetCount();
            $importData = [];
            for ($sheetIndex = 0; $sheetIndex < $sheetCount; $sheetIndex++) {
                $sheet = $readerFile->getSheet($sheetIndex);
                $importData[$sheetIndex] = $this->processImport($sheet);
            }
            return $importData;
        } else {
            throw new \Exception('文件不存在,请重新选择');
        }
    }

    /**
     * 处理导入数据
     * @param \PHPExcel_Worksheet $sheet
     * @return array
     */
    public function processImport(\PHPExcel_Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumn = $sheet->getHighestColumn(); // 取得总列数

        $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);
        $data = [];
        $keys = [];
        for ($line = 0; $line < $highestColumnIndex; $line++) {
            for ($column = 1; $column <= $highestRow; $column++) {
                $value = $sheet->getCellByColumnAndRow($line, $column)->getValue();
                if ($column == 1) {
                    $keys[$line] = $value;
                } else {
                    $data[$column][$keys[$line]] = $value;
                }
            }
        }
        return $data;
    }


    /**
     * 导出数据
     * @throws \PHPExcel_Exception
     * @return mixed
     */
    public function export()
    {
        if (empty($this->exportData)) {
            throw new \PHPExcel_Exception('请输入需要导出的数据');
        }
        if (empty($this->exportName)) {
            throw new \PHPExcel_Exception('请输入需要导出的文件名称');
        }
        $i = 0;
        foreach ($this->exportData as $sheetKey => $sheet) {
            if ($i > 0) {
                $this->PHPExcel->createSheet();
            }
            $sheetExcel = $this->PHPExcel->setActiveSheetIndex($i);
            $lineIndex = 1;
            foreach ($sheet as $lineKey => $line) {
                $columnIndex = 0;
                $maxLine = 1;
                foreach ($line as $columnKey => $columnData) {

                    if ($columnKey === 'config') {
                        continue;
                    }

                    $columnIndexString = \PHPExcel_Cell::stringFromColumnIndex($columnIndex);

                    // 设置单元格数据
                    if (isset($columnData['Y'])) {

                        $sheetExcel->setCellValue($columnIndexString . $lineIndex, $columnData['value']);
                        // 开始单元格
                        $startLine = $columnIndexString . $lineIndex;

                        // 结束单元格
                        $nowLineIndex = $lineIndex;

                        $maxLine = $nowLineIndex > $maxLine ? $nowLineIndex : $maxLine;

                        $columnIndex = !isset($columnData['Y']) ? $columnIndex : $columnIndex + $columnData['Y'] - 1;

                        $endLine = \PHPExcel_Cell::stringFromColumnIndex($columnIndex) . $nowLineIndex;
                        $index = $startLine . ':' . $endLine;

                        $this->PHPExcel->getActiveSheet()->mergeCells($index);
                    } else {
                        $index = $columnIndexString . $lineIndex;

                        $sheetExcel->setCellValue($index, $columnData['value']);
                    }

                    $this->PHPExcel->getActiveSheet()->getStyle($index)->getAlignment()->setWrapText(true);

                    $line['config'] = empty($line['config']) ? [] : $line['config'];
                    $columnData['config'] = empty($columnData['config']) ? [] : $columnData['config'];
                    // 合并
                    $rIndex = $this->mergeLine($lineIndex, \PHPExcel_Cell::stringFromColumnIndex($columnIndex), $columnData['config'], $line['config']);
                    $index = !empty($rIndex) ? $rIndex : $index;

                    if (!empty($line['config'])) {
//                        dump($line['config']);
                        $this->setLineStyle('', $index, $line['config']);
                    }
                    $this->setCellStyle($index, $columnData);

                    $columnIndex++;
                }
                $lineIndex += $maxLine;
            }
            $this->PHPExcel->getActiveSheet()->setTitle('User' . $i);
            $i++;
        }

//        die;
        $objWriter = \PHPExcel_IOFactory::createWriter($this->PHPExcel, $this->nowType);
        if ($this->outputType == 2 || $this->outputType == 3) {
            // 生成
            $objWriter->save($this->exportPath . '/' . $this->exportName);
        } elseif ($this->outputType == 1 || $this->outputType == 3) {
            // 下载/读取
            $this->header();
            $objWriter->save('php://output');
        }
    }


    /**
     * 设置输出类型
     * @param $outputType
     * @return $this
     */
    public function setOutputType($outputType)
    {
        $this->outputType = $outputType;
        return $this;
    }


    /**
     * 设置需要导出的数据
     * 数据具有严格要求,需要设置一级 sheet页面 , 第一行数据为字段名称,一条数据已一行保存,一一对应
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        $this->exportData = $data;
        return $this;
    }


    /**
     * 设置导出的文件名
     * @param $name
     * @return $this
     */
    public function setFileName($name)
    {
        $this->exportName = $name;
        return $this;
    }


    /**
     * 设置导出的路径
     * @param $path
     * @return $this
     */
    public function setExportPath($path)
    {
        $this->exportPath = $path;
        return $this;
    }


    /**
     * 设置头部数据
     * @return void
     */
    public function header()
    {
        switch ($this->nowType) {
            case 'Excel2007':
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="' . $this->exportName . '"');
                break;
            case 'Excel5':
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="' . $this->exportName . '"');
                break;
            case 'PDF':
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment;filename="' . $this->exportName . '"');
                break;
        }
        header('Cache-Control: max-age=0');
    }


    /**
     * 设置行样式
     * @param $line
     * @param $index
     * @param $config
     * @return void
     */
    protected function setLineStyle($line = '', $index, $config)
    {
        // 设置样式
        if (!empty($config)) {
            if (!empty($config['background-color'])) {
                $this->PHPExcel->getActiveSheet()->getStyle($index)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
                $this->PHPExcel->getActiveSheet()->getStyle($index)->getFill()->getStartColor()->setARGB('FF' . $config['background-color']);
            }
            if (!empty($config['color'])) {
                $this->PHPExcel->getActiveSheet()->getStyle($index)->getFont()->getColor()->setARGB('FF' . $config['color']);
            }

            if (!empty($config['font-size'])) {
                $this->PHPExcel->getActiveSheet()->getStyle($index)->getFont()->setSize($config['font-size']);
            }

            if (!empty($config['font-family'])) {
                $this->PHPExcel->getActiveSheet()->getStyle($index)->getFont()->setName($config['font-family']);
            }

            if (!empty($config['border-color'])) {
                $this->PHPExcel->getActiveSheet()->getStyle($index)->getBorders()->getAllBorders()->getColor()->setRGB($config['border-color']);
            }
        }

        $this->PHPExcel->getActiveSheet()->getStyle($index)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $this->PHPExcel->getActiveSheet()->getStyle($index)->applyFromArray(
            array(
                'font' => array(
                    'bold' => true,
                ),
            )
        );
    }


    /**
     * 设置单元格样式
     * @param string $index 索引
     * @param array $cellData 配置数据
     * @return void
     */
    protected function setCellStyle($index, $cellData)
    {
        if (!empty($cellData['background-color'])) {
            $this->PHPExcel->getActiveSheet()->getStyle($index)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
            $this->PHPExcel->getActiveSheet()->getStyle($index)->getFill()->getStartColor()->setARGB('FF' . $cellData['background-color']);
        }
    }


    /**
     * 合并行
     * @param string $line 当前行
     * @param string $cell 当前列
     * @param array $config 单元格配置数据
     * @param array $lineConfig 行配置数据
     * @return string
     */
    protected function mergeLine($line, $cell, $config, $lineConfig)
    {
        if (isset($config['mergeTop'])) {
            $mergeTop = $config['mergeTop'];
        } elseif (isset($lineConfig['mergeTop'])) {
            $mergeTop = $lineConfig['mergeTop'];
        } else {
            return '';
        }
        if ($mergeTop == 0 ){
            return '';
        }
        $startLine = $cell . ($line - $mergeTop);
        $endLine = $cell . $line;
        $this->PHPExcel->getActiveSheet()->mergeCells($startLine . ':' . $endLine);
        return $startLine . ':' . $endLine;
    }


}