<?php
use janisto\ycm\widgets\TreeGrid\Widget as TreeGrid;
use yii\helpers\Url;

/* @var $this \yii\web\View */
/* @var $config array */

$module = Yii::$app->controller->module;
$name = $module->getModelName($model);

$treeConfig = [
    'model' => $model,
    'keyColumnName' => 'id',
    'parentColumnName' => 'parent_id',
    'ajaxMode' => false,
    'pluginOptions' => [
        'initialState' => 'collapsed',
    ],
    'columns' => isset($config['columns']) ? $config['columns'] : [],
];

if (method_exists($model, 'treeConfig')) {
    $treeConfig = array_merge($treeConfig, $model->treeConfig());
}
?>
<?= TreeGrid::widget($treeConfig) ?>
