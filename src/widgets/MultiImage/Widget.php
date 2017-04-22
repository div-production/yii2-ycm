<?php
namespace janisto\ycm\widgets\MultiImage;

use yii\helpers\Html;

class Widget extends \yii\base\Widget {
	public $model;
	
	public $attribute;
	
	/**
	 * @var boolean использовать поле для названия картинки
	 */
	public $useTitle = false;
	
	/**
	 * @var boolean использовать поле для ссылки
	 */
	public $useLink = false;
	
	public function init() {
		
	}
	
	public function run() {
		$this->register();
		
		$images = Image::find()->
			where(['model' => $this->model->className()])->
			andWhere(['attribute' => $this->attribute])->
			andWhere(['model_id' => $this->model->id])->
			all();
		
		return $this->render('view', ['images' => $images]);
	}
	
	public function register() {
		Asset::register($this->getView());
	}
	
	public function fieldName($name, $id = '') {
		$model = basename(str_replace('\\', '/', $this->model->className()));
		return $model.'['.$this->attribute.'__'.$name.']['.$id.']';
	}
}
