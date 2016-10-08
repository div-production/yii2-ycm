<?php
use leandrogehlen\treegrid\TreeGrid;
use yii\helpers\Url;
use yii\helpers\Html;

$module = Yii::$app->controller->module;

$this->title = $module->getAdminName($model);

$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
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
<?= TreeGrid::widget([
    'dataProvider' => $config['dataProvider'],
    'keyColumnName' => 'id',
    'parentColumnName' => 'parent_id',
    'pluginOptions' => [
        'initialState' => 'collapsed',
    ],
    'columns' => [
        'name',
        'id',
        'parent_id',
        [
			'class' => 'yii\grid\ActionColumn',
			'template' => '{update} {delete}',
			'urlCreator' => function ($action, $model, $key, $index) {
                $name = Yii::$app->getRequest()->getQueryParam('name');
                return Url::to(['model/'.$action, 'name' => 'catalog', 'pk' => $key]);
            }
		],
		
    ]
]) ?>