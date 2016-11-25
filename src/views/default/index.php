<?php

use yii\bootstrap\ButtonDropdown;
use yii\helpers\Html;

/* @var $this \yii\web\View */

/** @var $module \janisto\ycm\Module */
$module = Yii::$app->controller->module;

$this->title = Yii::t('ycm', 'Content');

?>

<div class="ycm-default-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php foreach ($module->models as $name => $class): ?>
        <?php
        $download = false;
        $downloadItems = [];
		$model = $module->loadModel($name);
        if ($module->getDownloadCsv($name)) {
            $download = true;
            array_push($downloadItems, [
                'label' => Yii::t('ycm', 'CSV'),
                'url' => ['download/csv', 'name' => $name],
            ]);
        }
        if ($module->getDownloadMsCsv($name)) {
            $download = true;
            array_push($downloadItems, [
                'label' => Yii::t('ycm', 'MS CSV'),
                'url' => ['download/mscsv', 'name' => $name],
            ]);
        }
        if ($module->getDownloadExcel($name)) {
            $download = true;
            array_push($downloadItems, [
                'label' => Yii::t('ycm', 'Excel'),
                'url' => ['download/excel', 'name' => $name],
            ]);
        }
        ?>

        <h3><?= $module->getAdminName($name) ?></h3>
	
		<?php
        if (isset($model->adminUrl)) {
            $viewUrl = $model->adminUrl;
        } else {
            $viewUrl = ['model/list', 'name' => $name];
        }
        echo Html::a(Yii::t('ycm', 'List {name}', ['name' => $module->getPluralName($name)]), $viewUrl, ['class' => 'btn btn-primary']);
        ?>

        <?php
        if ($module->getHideCreate($name) === false) {
            echo Html::a(Yii::t('ycm', 'Create {name}', ['name' => $module->getSingularName($name)]), ['model/create', 'name' => $name], ['class' => 'btn btn-success']);
        }
        ?>

        <?php
        if ($download === true) {
            echo ButtonDropdown::widget([
                'split' => true,
                'label' => Yii::t('ycm', 'Download {name}', ['name' => $module->getPluralName($name)]),
                'dropdown' => [
                    'items' => $downloadItems,
                ],
                'options' => [
                    'class' => 'btn btn-default',
                ]
            ]);
        }
        ?>

    <?php endforeach; ?>

</div>
