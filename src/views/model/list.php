<?php

use yii\helpers\Html;
use yii\grid\GridView;
use janisto\ycm\widgets\Alert;
use janisto\ycm\widgets\SortableGrid\Widget as SortableGrid;

/* @var $this \yii\web\View */
/* @var $config array */
/* @var $model \yii\db\ActiveRecord */
/* @var $name string */

/** @var $module \janisto\ycm\Module */
$module = Yii::$app->controller->module;

$this->title = $module->getAdminName($model);
//$this->params['breadcrumbs'][] = ['label' => Yii::t('ycm', 'Content'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="ycm-model-list">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= Alert::widget() ?>

    <p>
        <?php
        if ($module->getHideCreate($model) === false) {
			if(method_exists($model, 'getCreateParams')) {
				echo Html::a(Yii::t('ycm', 'Create {name}', ['name' => $module->getSingularName($name)]), array_merge(['create', 'name' => $name], $model->createParams), ['class' => 'btn btn-success']);
			}
            else {
				echo Html::a(Yii::t('ycm', 'Create {name}', ['name' => $module->getSingularName($name)]), ['create', 'name' => $name], ['class' => 'btn btn-success']);
			}
        }
        ?>
    </p>
	<?php if (!empty($model->enableAdminSort)): ?>
		<?php
		$config['sortableAction'] = ['sort'];
		$config['model'] = $model;
		?>
		<?= SortableGrid::widget($config); ?>
	<?php else: ?>
		<?= GridView::widget($config) ?>
	<?php endif ?>
</div>
