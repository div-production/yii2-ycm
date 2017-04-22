<?php
namespace janisto\ycm\widgets\MultiField;

use yii\base\Behavior as BaseBehavior;
use janisto\ycm\widgets\MultiField\Widget;
use Yii;

class Behavior extends BaseBehavior {
	public function events() {
		$owner_class = $this->owner->className();
		return [
			$owner_class::EVENT_BEFORE_VALIDATE => 'saveFields',
		];
	}
	
	private $saved = false;
	
	public function saveFields() {
		if ($this->saved) {
			return true;
		}
		$this->saved = true;
		
		$module = Yii::$app->controller->module;
		
		if($module->id != 'ycm') {
			return false;
		}
		
		$model = $this->owner;
		
		$fields = $model->attributeWidgets();
		
		foreach($fields as $field) {
			if($field[1] == 'widget' && isset($field['widgetClass']) && $field['widgetClass'] == 'janisto\ycm\widgets\MultiField\Widget') {
				if(isset($field['dataClass'])) $dataClass = $field['dataClass'];
				else $dataClass = 'janisto\ycm\widgets\MultiField\DataAttribute';
				$data = new $dataClass([
					'model' => $model,
					'attribute' => $field[0]
				]);
				
				$data->save();
			}
		}
		return true;
	}
	
	public function onUnsafeAttribute($name, $value) {
		
	}
}