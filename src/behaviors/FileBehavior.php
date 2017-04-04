<?php

namespace janisto\ycm\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;

class FileBehavior extends Behavior
{
    /** @var string folder name */
    public $folderName;

    /** @var string upload path  */
    public $uploadPath;

    /** @var string upload URL  */
    public $uploadUrl;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->uploadPath === null) {
            $this->uploadPath = Yii::getAlias('@uploadPath');
        }

        if ($this->uploadUrl === null) {
            $this->uploadUrl = Yii::getAlias('@uploadUrl');
        }
    }

    /**
     * Get file path.
     *
     * @param string $attribute Model attribute
     * @return string|false Model attribute file path
     */
    public function getFilePath($attribute)
    {
        /** @var $model \yii\db\ActiveRecord */
        $model = $this->owner;

        if ($model->hasAttribute($attribute) && !empty($model->$attribute)) {
            $file = $model->$attribute;
            $path = $this->uploadPath . DIRECTORY_SEPARATOR . strtolower($this->getFolderName()) . DIRECTORY_SEPARATOR . strtolower($attribute);
            return $path . DIRECTORY_SEPARATOR . $file;
        }
        return false;
    }

    /**
     * Get relative file URL.
     *
     * @param string $attribute Model attribute
     * @return string|false Model attribute relative file URL
     */
    public function getFileUrl($attribute)
    {
        /** @var $model \yii\db\ActiveRecord */
        $model = $this->owner;

        if ($model->hasAttribute($attribute) && !empty($model->$attribute)) {
            $file = $model->$attribute;
            $path = $this->uploadUrl . '/' . strtolower($this->getFolderName()) . '/' . strtolower($attribute);
            return $path . '/' . $file;
        }
        return false;
    }

    /**
     * Get absolute file URL.
     *
     * @param string $attribute Model attribute
     * @return string|false Model attribute absolute file URL
     */
    public function getAbsoluteFileUrl($attribute)
    {
        $url = $this->getFileUrl($attribute);
        if ($url !== false) {
            if (strpos($url, '//') === false) {
                return Yii::$app->getRequest()->getHostInfo() . $url;
            } else {
                return $url;
            }
        }
        return false;
    }
	
	protected function getFolderName()
	{
		if (is_null($this->folderName)) {
			$module = Yii::$app->modules['ycm'];
			return $module->getModelName($this->owner);
		} else {
			return $this->folderName;
		}
	}
	
	public function getSavePath($attribute)
	{
		return $this->uploadPath . DIRECTORY_SEPARATOR . strtolower($this->getFolderName()) . DIRECTORY_SEPARATOR . strtolower($attribute);
	}
	
	/**
	 * сохранение загруженного файла
	 * 
	 * @param UploadedFile|string имя файла из формы или объект загруженного файла
	 * @param string $attribute
	 * @return string
	 */
	public function saveUploadedFile($file, $attribute)
	{
		if (is_string($file)) {
			$file = UploadedFile::getInstanceByName($file);
		} elseif (!($file instanceof UploadedFile)) {
			throw new InvalidConfigException();
		}
		
		$module = Yii::$app->modules['ycm'];
		
		$attributePath = $module->getAttributePath($this->getFolderName(), $attribute);
		
		$fileName = md5($attribute . time() . uniqid(rand(), true)) . '.' . $file->extension;
		if (!is_dir($attributePath)) {
			if (!FileHelper::createDirectory($attributePath, $module->uploadPermissions)) {
				throw new InvalidConfigException('Could not create folder "' . $attributePath . '". Make sure "uploads" folder is writable.');
			}
		}
		$path = $attributePath . DIRECTORY_SEPARATOR . $fileName;
		if (file_exists($path) || !$file->saveAs($path)) {
			throw new ServerErrorHttpException('Could not save file or file exists: ' . $path);
		}
		$this->owner->$attribute = $fileName;
		
		return $fileName;
	}
}
