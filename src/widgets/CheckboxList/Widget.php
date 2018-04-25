<?php
namespace janisto\ycm\widgets\CheckboxList;

use yii\base\Widget as BaseWidget;
use yii\bootstrap\Html;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * @author Владимир
 */
class Widget extends BaseWidget
{
    public $model;

    public $attribute;

    /**
     * @var string поле с названием в связанной модели
     */
    public $nameAttribute = 'name';

    /**
     * @var string название связи
     */
    public $relationName;

    /**
     * @var callable функция, которая проверяет, должен ли быть отображён чекбокс или нет
     * принимает модель и должна вернуть true или false
     */
    public $checkItem;

    /**
     * @var int число колонок в виджете
     */
    public $columnsCount = 1;

    public function init()
    {
        if (!$this->relationName) {
            $this->relationName = $this->attribute;
        }

        parent::init();
    }

    public function run()
    {
        /** @var ActiveRecord $model */
        $model = $this->model;
        $relation = $model->getRelation($this->relationName);
        $relationClass = $relation->modelClass;
        $values = $relationClass::find()->all();

        if (is_callable($this->checkItem)) {
            foreach ($values as $k => $v) {
                if (!call_user_func($this->checkItem, $v)) {
                    unset($values[$k]);
                }
            }
        }

        $selected = ArrayHelper::getColumn($this->model->{$this->attribute}, 'id');

        $name = Html::getInputName($this->model, $this->attribute);

        $columnsCount = min(max($this->columnsCount, 1), 10);
        $countInColumn = max(ceil(count($values) / $columnsCount), 1);
        $columns = array_chunk($values, $countInColumn);

        $result = '';
        foreach ($columns as $column) {
            $columnContent = Html::checkboxList(
                $name,
                $selected,
                ArrayHelper::map($column, 'id', $this->nameAttribute),
                [
                    'separator' => '',
                ]);
            $result .= '<div style="display: inline-block; vertical-align: top; width: ' . (100 / $columnsCount) . '%">' . $columnContent . '</div>';
        }

        return '<div>' . $result . '</div>';
    }
}
