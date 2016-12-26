<?php
use janisto\ycm\widgets\TreeGrid\Widget as TreeGrid;
use yii\helpers\Url;

$module = Yii::$app->controller->module;
$name = $module->getModelName($model);

$config = [
    'model' => $model,
    'keyColumnName' => 'id',
    'parentColumnName' => 'parent_id',
    'ajaxMode' => false,
    'pluginOptions' => [
        'initialState' => 'collapsed',
    ],
    'columns' => [
        'name',
        'id',
        [
			'class' => 'yii\grid\ActionColumn',
			'template' => '{update} {delete}',
			'urlCreator' => function ($action, $model, $key, $index) use ($name) {
                $name = Yii::$app->getRequest()->getQueryParam('name');
                return Url::to(['model/'.$action, 'name' => $name, 'pk' => $key]);
            }
		],
    ]
];

if(method_exists($model, 'treeConfig')) {
    $config = array_merge($config, $model->treeConfig());
}
?>
<?= TreeGrid::widget($config) ?>