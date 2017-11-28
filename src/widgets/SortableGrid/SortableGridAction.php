<?php
/**
 * @link https://github.com/himiklab/yii2-sortable-grid-view-widget
 * @copyright Copyright (c) 2014 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace janisto\ycm\widgets\SortableGrid;

use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;

/**
 * Action for sortable Yii2 GridView widget.
 *
 * For example:
 *
 * ```php
 * public function actions()
 * {
 *    return [
 *       'sort' => [
 *          'class' => SortableGridAction::className(),
 *          'modelName' => Model::className(),
 *       ],
 *   ];
 * }
 * ```
 *
 * @author HimikLab
 * @package himiklab\sortablegrid
 */
class SortableGridAction extends Action
{

    public function run()
    {
        if (!$items = Yii::$app->request->post('items')) {
            throw new BadRequestHttpException('Don\'t received POST param `items`.');
        }
        if (!$modelClass = Yii::$app->request->post('model')) {
            throw new BadRequestHttpException('Don\'t received POST param `model`.');
        }
        /** @var \yii\db\ActiveRecord $model */
        $model = Yii::$container->get($modelClass);
        if (!($model instanceof ActiveRecord)) {
            throw new BadRequestHttpException('');
        }
        if (!$model->hasMethod('gridSort')) {
            throw new InvalidConfigException(
                "Not found right `SortableGridBehavior` behavior in `{$this->modelName}`."
            );
        }
        $model->gridSort(Json::decode($items));
    }
}
