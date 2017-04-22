<?php
namespace janisto\ycm\widgets\MultiField;

use yii\base\Object;
use yii\helpers\ArrayHelper;
use Yii;
use yii\db\ActiveRecord;

class DataTable extends Object {
	public $model;
	
	public $attribute;
	
	public $fields = [];
	
	public function getList() {
		$data = $this->model->{$this->attribute};
		$result = [];
		
		foreach($data as $row) {
			$_row = [];
			foreach($this->fields as $field) {
				$_row[$field['name']] = $row->{$field['name']};
			}
			$result[$row->primaryKey] = $_row;
		}
		
		return $result;
	}
	
	protected $_afterSave = [];
	
	protected $_relAtttibute;
	
	public function save() {
		$post = Yii::$app->request->post();
		$class =  basename(str_replace('\\', '/', get_class($this->model)));
		if(isset($post[$class][$this->attribute])) {
			$data = $post[$class][$this->attribute];
			$result = Widget::reBuild($data);
		}
		else $result = [];
		
		$oldValues = ArrayHelper::index($this->model->{$this->attribute}, 'id');
		
		$rel = $this->model->getRelation($this->attribute);
		$this->_relAtttibute = each($rel->link)['key'];
		
		foreach($result as $key => $row) {
			if(isset($oldValues[$key])) {
				$model = $oldValues[$key];
				foreach($row as $attr => $value) {
					$model->$attr = $value;
				}
				$model->save();
				unset($oldValues[$key]);
			}
			else {
				$modelClass = $rel->modelClass;
				$model = new $modelClass($row);
				if($this->model->id) {
					$model->{$this->_relAtttibute} = $this->model->id;
					$model->save();
				}
				else {
					$this->model->on(ActiveRecord::EVENT_AFTER_INSERT, [$this, 'afterSave']);
					$this->_afterSave[] = $model;
				}
			}
		}
		
		foreach($oldValues as $model) {
			$model->delete();
		}
		
		return true;
	}
	
	public function afterSave() {
		foreach($this->_afterSave as $model) {
			$model->{$this->_relAtttibute} = $this->model->id;
			$model->save();
		}
	}
}