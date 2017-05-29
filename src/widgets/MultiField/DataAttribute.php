<?php
namespace janisto\ycm\widgets\MultiField;

use yii\base\Object;

class DataAttribute extends Object {
	public $model;
	
	public $attribute;
	
	public $fields;
	
	public function getList() {
		$data = $this->model->{$this->attribute};
		
		if (is_array($data)) {
		    return $data;
        } elseif(is_string($data)) {
		    return unserialize($data);
        } else {
		    return [];
        }
	}
	
	public function save() {
		$model = $this->model;
		if(is_array($model->{$this->attribute})) {
			$data = $model->{$this->attribute};
			$result = [];
			foreach (Widget::reBuild($data) as $key => $item) {
			    $isEmpty = true;
			    foreach ($item as $value) {
                    if (trim($value)) {
                        $isEmpty = false;
                    }
                }

                if (!$isEmpty) {
			        $result[$key] = $item;
                }
            }

			$model->{$this->attribute} = serialize($result);
		}
		else $model->{$this->attribute} = '';
	}
}
