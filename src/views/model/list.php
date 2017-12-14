<?php

use janisto\ycm\widgets\Alert;
use janisto\ycm\widgets\SortableGrid\Widget as SortableGrid;
use yii\grid\GridView;
use yii\helpers\Html;

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
    <?php if (Yii::$app->request->get('sort')): ?>
        <?php
        $get = Yii::$app->request->get();
        unset($get['sort']);
        ?>
        <a href="?<?= http_build_query($get) ?>" class="btn btn-primary">Сбросить сортировку</a>
        <?php if (!empty($model->enableAdminSort)): ?>
            <p>Менять местами записи можно только при отключенной сортировке</p>
        <?php endif ?>
    <?php endif ?>
    <?php if (!empty($model->enableAdminSort) && !Yii::$app->request->get('sort')): ?>
        <?php
        $config['sortableAction'] = ['sort'];
        $config['model'] = $model;
        ?>
        <?= SortableGrid::widget($config); ?>
    <?php else: ?>
        <?= GridView::widget($config) ?>
    <?php endif ?>
</div>
