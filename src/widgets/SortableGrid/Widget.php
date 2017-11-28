<?php
/**
 * @link https://github.com/himiklab/yii2-sortable-grid-view-widget
 * @copyright Copyright (c) 2014 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace janisto\ycm\widgets\SortableGrid;

use yii\grid\GridView;
use yii\helpers\Url;

/**
 * Sortable version of Yii2 GridView widget.
 *
 * @author HimikLab
 * @package himiklab\sortablegrid
 */
class Widget extends GridView
{
    /** @var string|array Sort action */
    public $sortableAction = ['sort'];

    public $model;

    public function init()
    {
        parent::init();
        $this->sortableAction = Url::to($this->sortableAction);
    }

    public function run()
    {
        $this->registerWidget();

        parent::run();
    }

    protected function registerWidget()
    {
        $view = $this->getView();
        $model = addslashes(get_class($this->model));
        $view->registerJs("jQuery('#{$this->options['id']}').SortableGridView('{$this->sortableAction}', '{$model}');");
        SortableGridAsset::register($view);
    }
}
