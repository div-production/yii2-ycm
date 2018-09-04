<?php
namespace janisto\ycm\widgets\MultiImage;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use yii\imagine\Image as Imagine;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;

class Behavior extends \yii\base\Behavior
{
    public $relations = [];

    public function events()
    {
        $owner_class = $this->owner->className();
        return [
            $owner_class::EVENT_AFTER_INSERT => 'saveFiles',
            $owner_class::EVENT_AFTER_UPDATE => 'saveFiles',
        ];
    }

    public function saveFiles()
    {
        $model = $this->owner;
        $module = Yii::$app->controller->module;
        if ($module->id != 'ycm') {
            return;
        }
        $model_name = $module->getModelName($model);
        $class_name = basename(str_replace('\\', '/', $model->className()));
        $post = Yii::$app->request->post();

        $widgets = $model->attributeWidgets();
        $attributes = [];
        foreach ($widgets as $widget) {
            if ($widget[1] == 'widget' && $widget['widgetClass'] == 'janisto\ycm\widgets\MultiImage\Widget') {
                $attributes[] = $widget[0];
            }
        }

        foreach ($attributes as $attribute) {
            $attributePath = $module->getAttributePath($model_name, $attribute);
            $files = UploadedFile::getInstances($this->owner, $attribute);
            foreach ($files as $file) {
                $fileName = md5($attribute . time() . uniqid(rand(), true)) . '.' . $file->extension;
                if (!is_dir($attributePath)) {
                    if (!FileHelper::createDirectory($attributePath, $module->uploadPermissions)) {
                        throw new InvalidConfigException('Could not create folder "' . $attributePath . '". Make sure "uploads" folder is writable.');
                    }
                }
                $path = $attributePath . DIRECTORY_SEPARATOR . $fileName;

                try {
                    $module->saveFile($file, $path);
                } catch (\Exception $e) {
                    throw new ServerErrorHttpException('Could not save file or file exists: ' . $path);
                }

                $image = new Image();
                $image->image = $fileName;
                $image->model = $model->className();
                $image->attribute = $attribute;
                $image->link('parent', $model);
                $image->save();
            }

            if (isset($post[$class_name])) {
                $data = $post[$class_name];
            } else {
                $data = [];
            }

            if (isset($data[$attribute . '__remove']) ||
                isset($data[$attribute . '__title']) ||
                isset($data[$attribute . '__link'])
            ) {
                $images = $model->getImages($attribute);

                if (!isset($data[$attribute . '__remove'])) {
                    $data[$attribute . '__remove'] = [];
                }

                foreach ($images as $img) {
                    $save = false;
                    if (in_array($img->id, $data[$attribute . '__remove'])) {
                        $module->deleteFile($attributePath . DIRECTORY_SEPARATOR . $img->image);
                        $img->delete();
                        continue;
                    }
                    if (isset($data[$attribute . '__title'][$img->id])) {
                        $img->title = $data[$attribute . '__title'][$img->id];
                        $save = true;
                    }
                    if (isset($data[$attribute . '__link'][$img->id])) {
                        $img->link = $data[$attribute . '__link'][$img->id];
                        $save = true;
                    }

                    if ($save) {
                        $img->save();
                    }
                }
            }
        }

        return true;
    }

    protected function saveFile($attribute, $file)
    {
        $module = Yii::$app->controller->module;
        $model = $this->owner;
        $name = $this->module->getModelName($model);
        $attributePath = $module->getAttributePath($name, $attribute);
        $file = UploadedFile::getInstance($model, $attribute);
        if ($file) {
            $model->$attribute = $file;
            if ($model->validate()) {
                $fileName = md5($attribute . time() . uniqid(rand(), true)) . '.' . $file->extension;
                if (!is_dir($attributePath)) {
                    if (!FileHelper::createDirectory($attributePath, $module->uploadPermissions)) {
                        throw new InvalidConfigException('Could not create folder "' . $attributePath . '". Make sure "uploads" folder is writable.');
                    }
                }

                $path = $attributePath . DIRECTORY_SEPARATOR . $fileName;
                try {
                    Imagine::thumbnail($file->tempName, 1920, null)->save($path);
                } catch (\Exception $e) {
                    throw new ServerErrorHttpException('Could not save file or file exists: ' . $path);
                }

                $model->$attribute = $fileName;
            }
        }
    }

    protected $_images = false;

    public function getImages($attribute)
    {
        if ($this->_images === false) {
            $this->_images = Image::find()->
            where(['model' => $this->owner->className()])->
            andWhere(['attribute' => $attribute])->
            andWhere(['model_id' => $this->owner->id])->
            all();

            foreach ($this->_images as $img) {
                $img->parent = $this->owner;
            }
        }


        return $this->_images;
    }

    public function removeFiles()
    {
        $model = $this->owner;

        $images = $model->getImages('images');

        foreach ($images as $image) {
            $file = $image->getFilePath();
            if (file_exists($file)) {
                unlink($file);
            }
            $image->delete();
        }
    }
}
