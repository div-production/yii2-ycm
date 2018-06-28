<?php
namespace janisto\ycm\widgets\MultiField;

use yii\base\Widget as BaseWidget;
use yii\helpers\Html;
use yii\web\View;

class Widget extends BaseWidget
{
    public $model;

    public $attribute;

    public $fields = [];

    public $dataClass = 'janisto\ycm\widgets\MultiField\DataAttribute';

    public $dataClassConfig = [];

    public $showRemove = true;

    public $showCreate = true;

    protected static $registered = false;

    protected $_data;

    public function init()
    {
        if (!$this->model) {
            throw new InvalidConfigException('В виджет не передана модель');
        }
        if (!$this->attribute) {
            throw new InvalidConfigException('В виджет не передано название поля для хранения данных');
        }

        $config = [
            'model' => $this->model,
            'attribute' => $this->attribute,
            'fields' => $this->fields,
        ];

        $config = array_merge($config, $this->dataClassConfig);

        $this->_data = new $this->dataClass($config);
    }

    public function run()
    {
        $this->register();
        $data = $this->_data->list;
        $result = '';
        foreach ($data as $id => $row) {
            $row['id'] = $id;
            $result .= $this->createRow($row);
        }
        if (!$result) {
            $result = $this->createRow([]);
        }
        if ($this->showCreate) {
            $result .= '<div class="btn btn-primary js-field-add" widget_attr=' . $this->attribute . '>Добавить поле</div>';
        }
        return '<div>' . $result . '</div>';
    }

    public function createRow($data)
    {
        $r = '';
        $width = min(
            floor(100 / count($this->fields)) - 2,
            40
        );
        if (isset($data['id'])) {
            $id = $data['id'];
        } else {
            $id = null;
        }

        foreach ($this->fields as $field) {
            $name = $this->attribute . '[' . $field['name'] . '][' . $id . ']';

            $options = [
                'class' => 'form-control',
                'style' => "width: $width%; display: inline-block; margin-right: 2%;",
            ];

            if (isset($field['name'])) {
                if (isset($data[$field['name']])) {
                    $options['value'] = $data[$field['name']];
                }
                unset($field['name']);
            }
            if (isset($field['hint'])) {
                $options['placeholder'] = $field['hint'];
                unset($field['hint']);
            }
            if (isset($field['type'])) {
                $type = $field['type'];
                unset($field['type']);
            } else {
                $type = 'text';
            }

            $options = array_merge($options, $field);

            $r .= Html::activeInput($type, $this->model, $name, $options);
        }
        if ($this->showRemove) {
            $r .= '<div class="btn btn-warning js-field-remove" style="position: absolute; top: 0; left: 100%;">Удалить</div>';
        }
        return '<div style="position: relative;">' . $r . '<br><br></div>';
    }

    public function register()
    {
        $view = $this->getView();

        $view->registerJs(
            'var data = $(window).data("multiFieldConfig"); if (data == null) { data = {}; } data.' . $this->attribute . ' = \'' . addslashes($this->createRow([])) . '\'; $(window).data("multiFieldConfig", data);',
            View::POS_END
        );

        if (self::$registered) {
            return;
        }
        self::$registered = true;

        Asset::register($this->getView());
    }

    public function save()
    {
        $this->_data->save();
    }

    public static function reBuild($data)
    {
        $result = [];
        foreach ($data as $field_name => $field_array) {
            foreach ($field_array as $id => $value) {
                $result[$id][$field_name] = $value;
            }
        }
        return $result;
    }
}
