<?php
namespace janisto\ycm\widgets;

use yii\helpers\Html;

class LinkWidget extends \yii\base\Widget {
	public $model;
	
	public $attribute;
	
	public $caption = 'Перейти';
	
	public $url;
	
	public $blank = false;
	
	public function init() {
		
	}
	
	public function run() {
		$options = [];
		if ($this->blank) {
			$options['target'] = '_blank';
		}
		return Html::a($this->caption, $this->url, $options);
	}
}