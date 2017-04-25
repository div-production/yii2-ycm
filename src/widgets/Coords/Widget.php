<?php
namespace janisto\ycm\widgets\Coords;

use Yii;
use yii\base\Widget as BaseWidget;
use yii\helpers\Html;
use yii\web\View;

class Widget extends BaseWidget {
	public $model;
	
	public $attribute;
	
	public $addressAttribute = 'address';
	
	public function init() {
		if(!$this->model) throw new InvalidConfigException('В виджет не передана модель');
		if(!$this->attribute) throw new InvalidConfigException('В виджет не передано название поля для хранения данных');
	}
	
	public function run() {
		$id = Html::getInputId($this->model, $this->addressAttribute);
		Asset::register(Yii::$app->view);
		$result = '';
		$result .= $this->input();
		$result .= '<div class="hint-block">Координаты можно подучить из поля "Адрес" <div class="btn btn-primary js-coords-button">Получить</div></div>';
		Yii::$app->view->registerJs("var coordsWidgetAttributeId = '$id';", View::POS_END);
		return $result;
	}
	
	public function input() {
		$options = [
			'class' => 'form-control',
			'value' => $this->model->{$this->attribute},
			'pattern' => '\d{1,3}\.\d{0,6},\d{1,3}\.\d{0,6}',
			'title' => '12.34567,12.34567'
		];
		return Html::activeInput('text', $this->model, $this->attribute, $options);
	}
	
	public function register() {
		Asset::register(Yii::$app->view);
	}
}
