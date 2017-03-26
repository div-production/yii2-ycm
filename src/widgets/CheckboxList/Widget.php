<?php
namespace janisto\ycm\widgets\CheckboxList;

use yii\base\Widget as BaseWidget;
use yii\bootstrap\Html;
use yii\helpers\ArrayHelper;

/**
 * @author Владимир
 */
class Widget extends BaseWidget {
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

	public function init() {
		if(!$this->relationName) {
			$this->relationName = $this->attribute;
		}
		
		parent::init();
	}
	
	public function run() {
		$relation = $this->model->getRelation($this->relationName);
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
		
		/*$result = '';
		foreach($properties as $prop) {
			if(isset($values[$prop->id])) $value = $values[$prop->id]->id;
			else $value = null;
			$result .= $this->createRow($prop, $value);
		}*/
		
		$name = Html::getInputName($this->model, $this->attribute);
		
		return Html::checkboxList($name, $selected, ArrayHelper::map($values, 'id', $this->nameAttribute), [
			'separator' => '<br>'
		]);
	}
	
	protected function createRow($prop, $value = null) {
		
	}
}
