<?php
namespace janisto\ycm\widgets;

use yii\db\ActiveRecord;
use yii\grid\GridView;
use yii\data\ActiveDataProvider;

/**
 * @author home
 */
class GridItemsWidget extends \yii\base\Widget {
    /**
     * @var ActiveRecord
     */
	public $model;

    /**
     * @var string
     */
	public $attribute;

    /**
     * @var array
     */
	public $columns = [];

    /**
     * @var ActiveDataProvider
     */
	public $dataProvider;
	
	public function run() {
	    if (!$this->dataProvider) {
	        $query = $this->model->getRelation($this->attribute);
	        if (!$query) {
	            return null;
            }
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
        } else {
	        $dataProvider = $this->dataProvider;
        }
		
		return GridView::widget([
		    'dataProvider' => $dataProvider,
            'columns' => $this->columns,
        ]);
	}
}
