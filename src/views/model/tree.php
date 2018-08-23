<?php

use yii\helpers\Html;

/* @var $this \yii\web\View */
/* @var $config array */
/* @var $model \yii\db\ActiveRecord */
/* @var $name string */

$module = Yii::$app->controller->module;

$this->title = $module->getAdminName($model);

$this->params['breadcrumbs'][] = $this->title;
?>
    <h1><?= Html::encode($this->title) ?></h1>
    <p>
	<?php
    if ($module->getHideCreate($model) === false) {
        if (method_exists($model, 'getCreateParams')) {
            echo Html::a(Yii::t('ycm', 'Create {name}', ['name' => $module->getSingularName($name)]),
                array_merge(['create', 'name' => $name], $model->createParams), ['class' => 'btn btn-success']);
        } else {
            echo Html::a(Yii::t('ycm', 'Create {name}', ['name' => $module->getSingularName($name)]),
                ['create', 'name' => $name], ['class' => 'btn btn-success']);
        }
    }
    ?>
</p>
<?= $this->render('_tree', ['model' => $model, 'config' => $config]) ?>
