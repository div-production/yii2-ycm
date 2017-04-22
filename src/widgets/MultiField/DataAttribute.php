<?php
namespace janisto\ycm\widgets\MultiField;

use yii\base\Object;

class DataAttribute extends Object {
	public $model;
	
	public $attribute;
	
	public $fields;
	
	public function getList() {
		$data = $this->model->{$this->attribute};
		if($data) return unserialize($data);
		else return [];
	}
	
	public function save() {
		$model = $this->model;
		if(is_array($model->{$this->attribute})) {
			$data = $model->{$this->attribute};
			$result = Widget::reBuild($data);
			$model->{$this->attribute} = serialize($result);
		}
		else $model->{$this->attribute} = '';
	}
}