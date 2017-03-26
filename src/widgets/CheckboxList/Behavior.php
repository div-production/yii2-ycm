<?php
namespace janisto\ycm\widgets\CheckboxList;

use Yii;

use yii\base\Behavior as BaseBahavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * @author Владимир
 */
class Behavior extends BaseBahavior
{
    public function events()
    {
        $owner_class = $this->owner->className();

        return [
            $owner_class::EVENT_AFTER_INSERT => 'saveData',
            $owner_class::EVENT_AFTER_UPDATE => 'saveData',
        ];
    }

    public function saveData()
    {
        $module = Yii::$app->controller->module;
        if ($module->id != 'ycm') {
            return false;
        }

        $post = Yii::$app->request->post();
        /** @var ActiveRecord $model */
        $model = $this->owner;
        $formName = $model->formName();
        $data = $post[$formName];

        if (empty($post[$formName])) {
            return null;
        }

        $fields = $model->attributeWidgets();

        foreach ($fields as $field) {
            if ($field[1] == 'widget' && isset($field['widgetClass']) && $field['widgetClass'] == 'janisto\ycm\widgets\CheckboxList\Widget') {
                $this->saveRelation($data, $model, $field[0]);
            }
        }
    }

    /**
     * метод сохраняет связи для одного поля
     * @param ActiveRecord $model
     * @param string $attribute
     */
    protected function saveRelation($data, $model, $attribute)
    {
        $relation = $model->getRelation($attribute);

        $valuesClass = $relation->modelClass;

        $oldValues = ArrayHelper::index($model->{$attribute}, 'id');

        if (isset($data[$attribute])) {
            $newValues = ArrayHelper::index($valuesClass::findAll($data[$attribute]), 'id');
        } else {
            $newValues = [];
        }

        foreach ($oldValues as $oldValue) {
            if (!isset($newValues[$oldValue->id])) {
                $model->unlink($attribute, $oldValue, true);
            }
        }

        foreach ($newValues as $newValue) {
            if (!isset($oldValues[$newValue->id])) {
                $model->link($attribute, $newValue);
            }
        }
    }
}
