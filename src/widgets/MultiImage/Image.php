<?php

namespace janisto\ycm\widgets\MultiImage;

use Yii;
use janisto\ycm\behaviors\FileBehavior;

/**
 * This is the model class for table "images".
 *
 * @property integer $id
 * @property string $model
 * @property string $image
 * @property integer $model_id
 */
class Image extends \yii\db\ActiveRecord
{
	protected $_parent;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'images';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['model', 'image', 'model_id'], 'required'],
            [['model_id'], 'integer'],
            [['model'], 'string', 'max' => 32],
            [['image'], 'string', 'max' => 64],
			[['title', 'link'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'model' => 'Model',
            'image' => 'Image',
            'model_id' => 'Model ID',
        ];
    }
	
	public function getParent() {
		if($this->_parent) {
			return $this->_parent;
		}
		else {
			return $this->hasOne($this->model, ['id' => 'model_id']);
		}
	}
	
	public function setParent($parent) {
		$this->_parent = $parent;
	}
	
	public function getFileUrl() {
		$module = Yii::$app->getModule('ycm');
		$model = $this->parent;
		if(!$model) {
			return '';
		}
		$model_name = $module->getModelName($model);
		$upload_url = Yii::getAlias('@uploadUrl');
		
		$path = $upload_url . '/' . strtolower($model_name) . '/' . $this->attribute;
        return $path . '/' . $this->image;
	}
	
	public function getFilePath() {
		return Yii::getAlias('@webroot') . $this->getFileUrl();
	}
}
