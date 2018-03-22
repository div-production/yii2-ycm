<?php

namespace janisto\ycm\controllers;

use janisto\ycm\behaviors\AccessControl;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Response;

class DownloadController extends Controller
{
    /** @inheritdoc */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['csv', 'mscsv', 'excel', 'excel-new'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return in_array(Yii::$app->user->identity->username, $this->module->admins);
                        },
                    ],

                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'csv' => ['get'],
                    'mscsv' => ['get'],
                    'excel' => ['get'],
                ],
            ],
        ];
    }

    /**
     * Download CSV.
     *
     * @param string $name Model name
     */
    public function actionCsv($name)
    {
        /** @var $module \janisto\ycm\Module */
        $module = $this->module;
        /** @var $model \yii\db\ActiveRecord */
        $model = $module->loadModel($name);
        $exclude = $module->getExcludeDownloadFields($name);

        $memoryLimit = 5 * 1024 * 1024;  // 5M
        $delimiter = ";";
        $enclosure = '"';
        $header = [];
        $select = '';

        foreach ($model->tableSchema->columns as $column) {
            // skip excluded fields
            if (in_array($column->name, $exclude)) {
                continue;
            }

            // no new lines in CSV format.
            $header[] = str_replace(["\r", "\r\n", "\n"], '', trim($model->getAttributeLabel($column->name)));
            if ($select !== '') {
                $select .= ', ';
            }
            $select .= Yii::$app->db->quoteColumnName($column->name);
        }

        $provider = Yii::$app->db->createCommand('SELECT ' . $select . ' FROM ' . $model->tableSchema->name)->queryAll();

        // Memory limit before php://temp starts using a temporary file
        $fp = fopen("php://temp/maxmemory:$memoryLimit", 'w');

        // Header line
        fputcsv($fp, $header, $delimiter, $enclosure);

        // Content lines
        foreach ($provider as $row) {
            $fields = [];
            foreach ($row as $item) {
                if ($item == 0 || !empty($item)) {
                    // no new lines in CSV format.
                    $fields[] = str_replace(["\r", "\r\n", "\n"], '', trim(strip_tags($item)));
                } else {
                    $fields[] = '';
                }
            }
            fputcsv($fp, $fields, $delimiter, $enclosure);
        }

        rewind($fp);
        $content = stream_get_contents($fp);
        $filename = $name . '_' . date('Y-m-d') . '.csv';
        $options = [
            'mimeType' => 'text/csv',
            'inline' => false,
        ];
        Yii::$app->response->sendContentAsFile($content, $filename, $options);
        Yii::$app->end();
    }

    /**
     * Download Microsoft formatted CSV.
     *
     * @param string $name Model name
     */
    public function actionMscsv($name)
    {
        /** @var $module \janisto\ycm\Module */
        $module = $this->module;
        /** @var $model \yii\db\ActiveRecord */
        $model = $module->loadModel($name);
        $exclude = $module->getExcludeDownloadFields($name);

        $memoryLimit = 5 * 1024 * 1024;  // 5M
        $delimiter = "\t"; // UTF-16LE needs "\t"
        $enclosure = '"';
        $header = [];
        $select = '';

        foreach ($model->tableSchema->columns as $column) {
            // skip excluded fields
            if (in_array($column->name, $exclude)) {
                continue;
            }

            // no new lines in CSV format.
            $header[] = str_replace(["\r", "\r\n", "\n"], '', trim($model->getAttributeLabel($column->name)));
            if ($select !== '') {
                $select .= ', ';
            }
            $select .= Yii::$app->db->quoteColumnName($column->name);
        }

        $provider = Yii::$app->db->createCommand('SELECT ' . $select . ' FROM ' . $model->tableSchema->name)->queryAll();

        // Memory limit before php://temp starts using a temporary file
        $fp = fopen("php://temp/maxmemory:$memoryLimit", 'w');

        // Header line
        fputcsv($fp, $header, $delimiter, $enclosure);

        // Content lines
        foreach ($provider as $row) {
            $fields = [];
            foreach ($row as $item) {
                if ($item == 0 || !empty($item)) {
                    // no new lines in CSV format.
                    $fields[] = str_replace(["\r", "\r\n", "\n"], '', trim(strip_tags($item)));
                } else {
                    $fields[] = '';
                }
            }
            fputcsv($fp, $fields, $delimiter, $enclosure);
        }

        rewind($fp);
        $content = stream_get_contents($fp);
        $content = chr(255) . chr(254) . mb_convert_encoding($content, 'UTF-16LE', 'UTF-8');
        $filename = $name . '_' . date('Y-m-d') . '.csv';
        $options = [
            'mimeType' => 'application/vnd.ms-excel; charset=UTF-16LE',
            'inline' => false,
        ];
        Yii::$app->response->sendContentAsFile($content, $filename, $options);
        Yii::$app->end();
    }

    /**
     * Download Excel.
     *
     * @param string $name Model name
     */
    public function actionExcel($name)
    {
        /** @var $module \janisto\ycm\Module */
        $module = $this->module;
        /** @var $model \yii\db\ActiveRecord */
        $model = $module->loadModel($name);
        $exclude = $module->getExcludeDownloadFields($name);

        $memoryLimit = 5 * 1024 * 1024; // 5M
        $select = '';
        $begin = '<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"><title>' . $name;
        $begin .= '</title></head><body><table cellpadding="3" cellspacing="0" width="100%" border="1">';
        $end = '</table></body></html>';

        $header = '<tr>';
        foreach ($model->tableSchema->columns as $column) {
            // skip excluded fields
            if (in_array($column->name, $exclude)) {
                continue;
            }

            $header .= '<th align="left" style="color: #f74902;">' . trim($model->getAttributeLabel($column->name)) . '</th>';
            if ($select !== '') {
                $select .= ', ';
            }
            $select .= Yii::$app->db->quoteColumnName($column->name);
        }
        $header .= '</tr>';

        $provider = Yii::$app->db->createCommand('SELECT ' . $select . ' FROM ' . $model->tableSchema->name)->queryAll();

        // Memory limit before php://temp starts using a temporary file
        $fp = fopen("php://temp/maxmemory:$memoryLimit", 'w');

        // Header
        fwrite($fp, $begin);

        // Header line
        fwrite($fp, $header);

        // Content lines
        foreach ($provider as $row) {
            $fields = '<tr>';
            foreach ($row as $item) {
                if ($item == 0 || !empty($item)) {
                    $fields .= '<td>' . trim(strip_tags($item)) . '</td>';
                } else {
                    $fields .= '<td>&nbsp;</td>';
                }
            }
            $fields .= '</tr>';
            fwrite($fp, $fields);
        }

        // Footer
        fwrite($fp, $end);

        rewind($fp);
        $content = stream_get_contents($fp);
        $filename = $name . '_' . date('Y-m-d') . '.xls';
        $options = [
            'mimeType' => 'application/vnd.ms-excel; charset=UTF-8',
            'inline' => false,
        ];
        Yii::$app->response->sendContentAsFile($content, $filename, $options);
        Yii::$app->end();
    }

    public function actionExcelNew($name)
    {
        if (!class_exists('PHPExcel')) {
            throw new \Exception('You need to install phpoffice/phpexcel');
        }

        Yii::$app->response->isSent = true;

        /** @var $module \janisto\ycm\Module */
        $module = $this->module;
        /** @var $model \yii\db\ActiveRecord */
        $model = $module->loadModel($name);

        $xls = new \PHPExcel();
        $xls->setActiveSheetIndex(0);
        $sheet = $xls->getActiveSheet();

        if (method_exists($model, 'getExcelColumns')) {
            $columns = $model->getExcelColumns();
        } else {
            $columns = ArrayHelper::getColumn($model->tableSchema->columns, 'name');
        }

        $rows = $model->find()->all();

        array_unshift($rows, []);

        $rowId = 1;
        foreach ($rows as $row) {
            $colId = 0;
            foreach ($columns as $key => $col) {
                if (is_int($key)) {
                    $attribute = $col;
                } else {
                    $attribute = $key;
                }

                $cell = $sheet->getCellByColumnAndRow($colId, $rowId);
                $style = $sheet->getStyle($cell->getCoordinate());
                if ($rowId == 1) {
                    $cell->setValue($model->getAttributeLabel($attribute));
                    $style->getFont()->setBold(true);

                    $colLetter = \PHPExcel_Cell::stringFromColumnIndex($colId);

                    if (is_array($col) && isset($col['width'])) {
                        $sheet->getColumnDimension($colLetter)->setWidth($col['width']);
                    } else {
                        $sheet->getColumnDimension($colLetter)->setAutoSize(true);
                    }
                } else {
                    if ($col instanceof \Closure) {
                        $value = call_user_func($col, $row, $attribute);
                    } elseif (is_array($col)) {
                        if (isset($col['content'])) {
                            $value = call_user_func($col['content'], $row, $attribute);
                        } else {
                            $value = $row->$key;
                        }
                    } else {
                        $value = $row->$col;
                    }
                    if ($value instanceof \PHPExcel_Cell_Hyperlink) {
                        $cell->setHyperlink($value);
                        $anchor = $value->getTooltip();
                        if (!$anchor) {
                            $anchor = 'Перейти';
                        }
                        $cell->setValue($anchor);
                        $style
                            ->getFont()
                            ->setUnderline(true)
                            ->setColor(new \PHPExcel_Style_Color(\PHPExcel_Style_Color::COLOR_BLUE));
                    } else {
                        $cell->setValue($value);
                    }

                }
                $colId++;
            }
            $rowId++;
        }

        Yii::$app->response->format = Response::FORMAT_RAW;

        header('Content-type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=export-' . date('d-m-Y') . '.xls');

        $objWriter = new \PHPExcel_Writer_Excel5($xls);
        $objWriter->save('php://output');
    }
}
