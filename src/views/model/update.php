<?php

use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $model \yii\db\ActiveRecord */
/* @var $name string */

/** @var $module \janisto\ycm\Module */
$module = Yii::$app->controller->module;

if(!empty($model->name)) {
	$this->title = $model->name;
}
else {
	$this->title = Yii::t('ycm', 'Update {name}', ['name' => $module->getSingularName($name)]);
}

//$this->params['breadcrumbs'][] = ['label' => Yii::t('ycm', 'Content'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $module->getAdminName($model), 'url' => ['list', 'name' => $name]];
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="ycm-model-update">

    <h1><?= $module->getSingularName($name) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'name' => $name,
    ]) ?>

</div>
