<?php

namespace janisto\ycm;

use app\widgets\timetable\Widget as TimetableWidget;
use dosamigos\ckeditor\CKEditor;
use janisto\timepicker\TimePicker;
use mihaildev\elfinder\ElFinder;
use vova07\select2\Widget as Select2Widget;
use Yii;
use yii\base\InvalidConfigException;
use yii\bootstrap\Modal;
use yii\bootstrap\Tabs;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\imagine\Image;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/**
 * Main module class for yii2-ycm.
 *
 * You can modify its configuration by adding an array to your application config under `modules`
 * as shown in the following example:
 *
 * 'modules' => [
 *     ...
 *     'ycm' => [
 *         'class' => 'janisto\ycm\Module',
 *         'admins' => ['admin'],
 *         'urlPrefix' => 'xxx',
 *         'registerModels' => [
 *             'test' => 'app\models\Test',
 *             'user' => [
 *                 'class' => 'app\models\User',
 *                 'attribute' => 'value',
 *             ],
 *         ],
 *     ],
 *     ...
 * ],
 *
 * @property array $models Registered models. This property is read-only.
 *
 * @author Jani Mikkonen <janisto@php.net>
 * @license public domain (http://unlicense.org)
 * @link https://github.com/janisto/yii2-ycm
 */
class Module extends \yii\base\Module
{
    /** @inheritdoc */
    public $controllerNamespace = 'janisto\ycm\controllers';

    /** @var array An array of administrator usernames. */
    public $admins = [];

    /** @var string Asset bundle. */
    public $assetBundle = 'janisto\ycm\YcmAsset';

    /** @var string URL prefix. */
    public $urlPrefix = 'admin';

    /** @var array The default URL rules to be used in module. */
    public $urlRules = [
        '' => 'default/index',
        'login' => 'login/index',
        'logout' => 'login/logout',
        'model/<action:[\w-]+>/<name:\w+>/<pk:\d+>' => 'model/<action>',
        'model/<action:[\w-]+>/<name:\w+>' => 'model/<action>',
        'model/<action:[\w-]+>' => 'model/<action>',
        'download/<action:[\w-]+>/<name:\w+>' => 'download/<action>',
    ];

    /** @var array Register models to module. */
    public $registerModels = [];

    /** @var array Register additional controllers to module. */
    public $registerControllers = [];

    /** @var array Register additional URL rules to module. */
    public $registerUrlRules = [];

    /** @var array Sidebar Nav items. */
    public $sidebarItems = [];

    /** @var array Models. */
    protected $models = [];

    /** @var array Model upload paths. */
    protected $modelPaths = [];

    /** @var array Model upload URLs. */
    protected $modelUrls = [];

    /** @var string Upload path. */
    public $uploadPath;

    /** @var string Upload URL. */
    public $uploadUrl;

    /** @var string Url for save files to FTP. */
    public $ftpUploadPath;

    /** @var integer Upload permissions for folders. */
    public $uploadPermissions = 0755;

    /** @var boolean Whether to delete the temporary uploaded file after saving. */
    public $uploadDeleteTempFile = true;

    /** @var boolean Whether to enable redactor image uploads. */
    public $redactorImageUpload = true;

    /** @var array Redactor image upload validation rules. */
    public $redactorImageUploadOptions = [
        'maxWidth' => 100000,
        'maxHeight' => 100000,
        'maxSize' => 1024 * 1024 * 32,
    ];

    /** @var boolean Whether to enable redactor file uploads. */
    public $redactorFileUpload = true;

    /** @var array Redactor file upload validation rules. */
    public $redactorFileUploadOptions = [
        'maxSize' => 8388608, // 1024 * 1024 * 8 = 8MB
    ];

    public $uploadToServer = true;

    public $uploadToFtp = false;

    /** @var integer Number of columns to show in model/list view by default. */
    public $maxColumns = 8;

    public $ckeWidgets = [];

    public $ckeContentsCss;

    public $enableSmiles = false;

    protected $attributeWidgets;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->setAliases([
            '@ycm' => __DIR__,
        ]);

        $this->setViewPath('@ycm/views');

        if ($this->uploadPath === null) {
            $this->uploadPath = Yii::getAlias('@uploadPath');
            if (!is_dir($this->uploadPath)) {
                mkdir($this->uploadPath, $this->uploadPermissions, true);
            }
        }

        if ($this->uploadUrl === null) {
            $this->uploadUrl = Yii::getAlias('@uploadUrl');
        }

        foreach ($this->registerModels as $name => $class) {
            if (is_array($class) && isset($class['folderName'])) {
                $folder = strtolower($class['folderName']);
                unset($class['folderName']);
            } else {
                $folder = strtolower($name);
            }
            $model = Yii::createObject($class);

            if (is_subclass_of($model, 'yii\db\ActiveRecord')) {
                $this->models[$name] = $model;
                $this->modelPaths[$name] = $folder;
                $this->modelUrls[$name] = $this->uploadUrl . '/' . $folder;
            }

            if (isset($model->adminUrl)) {
                $viewUrl = $model->adminUrl;
            } else {
                $viewUrl = ['model/list', 'name' => $name];
            }

            if ($this->getHideList($model)) {
                continue;
            }

            $this->sidebarItems[] = ['label' => $this->getPluralName($model), 'url' => $viewUrl];
        }

        foreach ($this->registerControllers as $name => $class) {
            $this->controllerMap[$name] = $class;
        }
    }

    /**
     * Get models.
     *
     * @return array Models
     */
    public function getModels()
    {
        return $this->models;
    }

    /**
     * Get Model name from models array.
     *
     * @param \yii\db\ActiveRecord $model Model object
     * @return string Model name
     * @throws NotFoundHttpException
     */
    public function getModelName($model)
    {
        foreach ($this->models as $name => $class) {
            $className = $model->className();
            if (get_class($class) === $className || is_subclass_of($class, $className)) {
                return $name;
            }
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Load model.
     *
     * @param string $name Model name
     * @param null|int $pk Primary key
     * @return \yii\db\ActiveRecord
     * @throws NotFoundHttpException
     */
    public function loadModel($name, $pk = null)
    {
        $name = (string)$name;
        if (!ArrayHelper::keyExists($name, $this->models)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        /** @var $model \yii\db\ActiveRecord */
        $model = $this->models[$name];

        if ($pk !== null) {
            if (($model = $model->findOne((int)$pk)) !== null) {
                /** @var $model \yii\db\ActiveRecord */
                return $model;
            } else {
                throw new NotFoundHttpException('The requested page does not exist.');
            }
        }

        return $model;
    }

    /**
     * Create ActiveForm widget.
     *
     * @param \yii\widgets\ActiveForm $form
     * @param \yii\db\ActiveRecord $model Model
     * @param string $attribute Model attribute
     */
    public function createWidget($form, $model, $attribute)
    {
        $widget = $this->getAttributeWidget($model, $attribute);
        if (!$widget) {
            return null;
        }
        $tableSchema = $model->getTableSchema();

        switch ($widget) {
            case 'widget':
                return $this->createField($form, $model, $attribute, [], 'widget');

            case 'wysiwyg':
                $assetsUrl = Yii::$app->assetManager->getPublishedUrl('@ycm/assets');
                $videoEmbedUrl = $assetsUrl . '/video-embed/plugin.js';
                Yii::$app->view->registerJs("CKEDITOR.plugins.addExternal('videoembed', '$videoEmbedUrl', '');");

                $ycmAsset = new YcmAsset();
                $ycmAsset->publish(Yii::$app->assetManager);

                $extraPlugins = ['filetools', 'colorbutton', 'colordialog', 'justify', 'videoembed', 'font', 'iframe'];

                if ($this->enableSmiles) {
                    $smilePath = Yii::$app->assetManager->getPublishedPath('@ycm/assets') . '/img/smiles/';
                    $smileItems = scandir($smilePath);
                    $smileFiles = [];
                    $smileNames = [];
                    foreach ($smileItems as $_item) {
                        if ($_item == '.' || $_item == '..') {
                            continue;
                        }

                        $smileFiles[] = $_item;
                        $_name = preg_replace('/[._].+/', '', $_item);
                        $smileNames[] = preg_replace('/-/', ' ', $_name);
                    }
                    $extraPlugins[] = 'smiley';
                }

                $widgetButtons = [];
                if ($this->ckeWidgets) {
                    $extraPlugins[] = 'widget';

                    $dropdownUrl = $assetsUrl . '/dropdown-toolbar/plugin.js';
                    Yii::$app->view->registerJs("CKEDITOR.plugins.addExternal('dropdown-toolbar', '$dropdownUrl', '');");
                    $extraPlugins[] = 'dropdown-toolbar';

                    foreach ($this->ckeWidgets as $widget) {
                        Yii::$app->view->registerJs("CKEDITOR.plugins.addExternal('$widget[name]', '" . Yii::getAlias($widget['url']) . "', '');");
                        $extraPlugins[] = $widget['name'];
                        $widgetButtons[] = [
                            'name' => $widget['name'],
                            'command' => $widget['name'],
                            'label' => $widget['label'],
                        ];
                    }
                }

                $contentsCss = [$assetsUrl . '/css/cke-contents.css'];
                if ($this->ckeContentsCss) {
                    $contentsCss[] = Yii::getAlias($this->ckeContentsCss);
                }

                $options = [
                    'widgetClass' => CKEditor::className(),
                    'preset' => 'custom',
                    'clientOptions' =>
                        ElFinder::ckeditorOptions('elfinder', [
                            'inline' => false,
                            'toolbar' => [
                                ['name' => 'document', 'items' => ['Source']],
                                [
                                    'name' => 'clipboard',
                                    'items' => ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'],
                                ],
                                [
                                    'name' => 'basicstyles',
                                    'items' => [
                                        'Bold',
                                        'Italic',
                                        'Underline',
                                        'Strike',
                                        'Subscript',
                                        'Superscript',
                                        'FontSize',
                                        'Blockquote',
                                        '-',
                                        'RemoveFormat',
                                    ],
                                ],
                                [
                                    'name' => 'paragraph',
                                    'items' => [
                                        'NumberedList',
                                        'BulletedList',
                                        '-',
                                        'Outdent',
                                        'Indent',
                                        '-',
                                        'JustifyLeft',
                                        'JustifyCenter',
                                        'JustifyRight',
                                        'JustifyBlock',
                                    ],
                                ],
                                ['name' => 'links', 'items' => ['Link', 'Unlink',]],
                                [
                                    'name' => 'insert',
                                    'items' => ['Image', 'Table', 'HorizontalRule', 'SpecialChar', 'VideoEmbed', 'Iframe', 'Smiley'],
                                ],
                                ['name' => 'styles', 'items' => ['Format']],
                                ['name' => 'colors', 'items' => ['TextColor', 'BGColor']],
                                ['name' => 'tools', 'items' => ['ShowBlocks']],
                                ['name' => 'about', 'items' => ['About']],
                                ['Widgets'],
                            ],
                            'extraPlugins' => implode(',', $extraPlugins),
                            'extraAllowedContent' => 'img[title]; div; *(*)',
                            'removeButtons' => '',
                            'height' => 500,
                            'dropdownmenumanager' => [
                                'Widgets' => [
                                    'items' => $widgetButtons,
                                    'label' => [
                                        'text' => 'Виджеты',
                                        'width' => 80,
                                    ],
                                ],
                            ],
                            'contentsCss' => $contentsCss,
                        ]),
                ];

                if ($this->enableSmiles) {
                    $options['clientOptions']['smiley_path'] = Yii::$app->assetManager->getPublishedUrl('@ycm/assets') . '/img/smiles/';
                    $options['clientOptions']['smiley_images'] = $smileFiles;
                    $options['clientOptions']['$smileNames'] = $smileNames;
                }

                return $this->createField($form, $model, $attribute, $options, 'widget');

            case 'date':
                $options = [
                    'widgetClass' => TimePicker::className(),
                    'mode' => 'date',
                    'clientOptions' => [
                        'dateFormat' => 'yy-mm-dd',
                    ],
                ];
                return $this->createField($form, $model, $attribute, $options, 'widget');

            case 'time':
                $options = [
                    'widgetClass' => TimePicker::className(),
                    'mode' => 'time',
                    'clientOptions' => [
                        'timeFormat' => 'HH:mm:ss',
                        'showSecond' => true,
                    ],
                ];
                return $this->createField($form, $model, $attribute, $options, 'widget');

            case 'datetime':
                $options = [
                    'widgetClass' => TimePicker::className(),
                    'mode' => 'datetime',
                    'clientOptions' => [
                        'dateFormat' => 'yy-mm-dd',
                        'timeFormat' => 'HH:mm:ss',
                        'showSecond' => true,
                    ],
                ];
                return $this->createField($form, $model, $attribute, $options, 'widget');

            case 'select':
                $options = [
                    'options' => [
                        'placeholder' => Yii::t('ycm', 'Choose {name}',
                            ['name' => $model->getAttributeLabel($attribute)]),
                    ],
                    'settings' => [
                        'allowClear' => true,
                        'width' => '100%',
                    ],
                    'items' => [
                        '' => '', // Add empty item for placeholder
                    ],
                ];
                return $this->createField($form, $model, $attribute, $options, 'select');

            case 'selectMultiple':
                $options = [
                    'options' => [
                        'multiple' => true,
                        'placeholder' => Yii::t('ycm', 'Choose {name}',
                            ['name' => $model->getAttributeLabel($attribute)]),
                    ],
                    'settings' => [
                        'width' => '100%',
                    ],
                ];
                return $this->createField($form, $model, $attribute, $options, 'select');

            case 'image':
                $options = [];
                if (!$model->isNewRecord && !empty($model->$attribute)) {
                    $className = StringHelper::basename($model->className());
                    $inputName = $className . '[' . $attribute . '_delete]';
                    $inputId = strtolower($className . '-' . $attribute . '_delete');
                    $url = $this->getAttributeUrl($this->getModelName($model), $attribute, $model->$attribute);
                    ob_start();
                    echo '<div class="checkbox"><label for="' . $inputId . '">
                        <input type="checkbox" name="' . $inputName . '" id="' . $inputId . '" value="delete"> ' . Yii::t('ycm',
                            'Delete image') . '
                    </label></div>';
                    Modal::begin([
                        'size' => Modal::SIZE_LARGE,
                        'header' => '<h4>' . Yii::t('ycm', 'Preview image') . '</h4>',
                        'toggleButton' => ['label' => Yii::t('ycm', 'Preview image'), 'class' => 'btn btn-info btn-sm'],
                    ]);
                    echo Html::img($url, ['class' => 'modal-image']);
                    Modal::end();
                    $html = ob_get_clean();
                    $options['hint'] = $html;
                }
                return $this->createField($form, $model, $attribute, $options, 'fileInput');

            case 'file':
                $options = [];
                if (!$model->isNewRecord && !empty($model->$attribute)) {
                    $className = StringHelper::basename($model->className());
                    $inputName = $className . '[' . $attribute . '_delete]';
                    $inputId = strtolower($className . '-' . $attribute . '_delete');
                    $url = $this->getAttributeUrl($this->getModelName($model), $attribute, $model->$attribute);
                    $html = '<div class="checkbox"><label for="' . $inputId . '">
                        <input type="checkbox" name="' . $inputName . '" id="' . $inputId . '" value="delete"> ' . Yii::t('ycm',
                            'Delete file') . '
                    </label></div>';
                    $html .= Html::a(Yii::t('ycm', 'Download file'), $url, ['class' => 'btn btn-info btn-sm']);
                    $options['hint'] = $html;
                }
                return $this->createField($form, $model, $attribute, $options, 'fileInput');

            case 'text':
                $options = [];
                if (isset($tableSchema->columns[$attribute])) {
                    $options['maxlength'] = $tableSchema->columns[$attribute]->size;
                }

                return $this->createField($form, $model, $attribute, $options, 'textInput');

            case 'hidden':
                $options = [
                    'maxlength' => $tableSchema->columns[$attribute]->size,
                ];
                $options = $this->getAttributeOptions($attribute, $options);
                return Html::activeHiddenInput($model, $attribute, $options);

            case 'password':
                $options = [
                    'maxlength' => $tableSchema->columns[$attribute]->size,
                ];
                return $this->createField($form, $model, $attribute, $options, 'passwordInput');

            case 'textarea':
                $options = [
                    'rows' => 6,
                ];
                return $this->createField($form, $model, $attribute, $options, 'textarea');

            case 'radio':
                return $this->createField($form, $model, $attribute, [], 'radio');

            case 'boolean':
            case 'checkbox':
                return $this->createField($form, $model, $attribute, [], 'checkbox');

            case 'dropdown':
                $options = [
                    'prompt' => Yii::t('ycm', 'Choose {name}', ['name' => $model->getAttributeLabel($attribute)]),
                ];
                return $this->createField($form, $model, $attribute, $options, 'dropDownList');

            case 'listbox':
                $options = [
                    'prompt' => '',
                ];
                return $this->createField($form, $model, $attribute, $options, 'listBox');

            case 'checkboxList':
                return $this->createField($form, $model, $attribute, [], 'checkboxList');

            case 'radioList':
                return $this->createField($form, $model, $attribute, [], 'radioList');

            case 'disabled':
                $options = [
                    'maxlength' => $tableSchema->columns[$attribute]->size,
                    'readonly' => true,
                ];
                return $this->createField($form, $model, $attribute, $options, 'textInput');

            case 'hide':
                return;

            default:
                $options = $this->getAttributeOptions($attribute);
                return $form->field($model, $attribute)->$widget($options);
        }
    }

    /**
     * Create ActiveField object.
     *
     * @param \yii\widgets\ActiveForm $form
     * @param \yii\db\ActiveRecord $model Model
     * @param string $attribute Model attribute
     * @param array $options Attribute options
     * @param string $type ActiveField type
     * @return \yii\widgets\ActiveField ActiveField object
     * @throws InvalidConfigException
     */
    protected function createField($form, $model, $attribute, $options, $type = 'textInput')
    {
        $options = $this->getAttributeOptions($attribute, $options);
        $field = $form->field($model, $attribute);
        if (isset($options['hint'])) {
            $hintOptions = [];
            if (isset($options['hintOptions'])) {
                $hintOptions = $options['hintOptions'];
                unset($options['hintOptions']);
            }
            $field->hint($options['hint'], $hintOptions);
            unset($options['hint']);
        }
        if (isset($options['label'])) {
            $labelOptions = [];
            if (isset($options['labelOptions'])) {
                $labelOptions = $options['labelOptions'];
                unset($options['labelOptions']);
            }
            $field->label($options['label'], $labelOptions);
            unset($options['label']);
        }
        if (isset($options['input'])) {
            $input = $options['input'];
            unset($options['input']);
            $field = $field->input($input, $options);
        } else {
            if ($type == 'dropDownList' || $type == 'listBox' || $type == 'checkboxList' || $type == 'radioList') {
                $items = $this->getAttributeChoices($model, $attribute);
                $field->$type($items, $options);
            } elseif ($type == 'select') {
                if (isset($options['items'])) {
                    $options['items'] = $options['items'] + $this->getAttributeChoices($model, $attribute);
                } else {
                    $options['items'] = $this->getAttributeChoices($model, $attribute);
                }
                $field->widget(Select2Widget::className(), $options);
            } elseif ($type == 'widget') {
                if (isset($options['widgetClass'])) {
                    $class = $options['widgetClass'];
                    unset($options['widgetClass']);
                } else {
                    throw new InvalidConfigException('Widget class missing from configuration.');
                }
                $field->widget($class, $options);
            } else {
                $field->$type($options);
            }
        }
        return $field;
    }

    /**
     * Get attribute file path.
     *
     * @param string $name Model name
     * @param string $attribute Model attribute
     * @return string Model attribute file path
     * @throws NotFoundHttpException
     */
    public function getAttributePath($name, $attribute)
    {
        if (!isset($this->modelPaths[$name])) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        return $this->modelPaths[$name] . DIRECTORY_SEPARATOR . strtolower($attribute);
    }

    /**
     * Get attribute file URL.
     *
     * @param string $name Model name
     * @param string $attribute Model attribute
     * @param string $file Filename
     * @return string Model attribute file URL
     * @throws NotFoundHttpException
     */
    public function getAttributeUrl($name, $attribute, $file)
    {
        if (!isset($this->modelUrls[$name])) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        return $this->modelUrls[$name] . '/' . strtolower($attribute) . '/' . $file;
    }

    /**
     * Get attributes widget.
     *
     * @param \yii\db\ActiveRecord $model Model
     * @param string $attribute Model attribute
     * @return null|string|object
     */
    public function getAttributeWidget($model, $attribute)
    {
        if ($this->attributeWidgets !== null) {
            if (isset($this->attributeWidgets->$attribute)) {
                return $this->attributeWidgets->$attribute;
            } else {
                $tableSchema = $model->getTableSchema();
                if (!isset($tableSchema->columns[$attribute])) {
                    return null;
                }
                $column = $tableSchema->columns[$attribute];
                if ($column->phpType === 'boolean') {
                    return 'checkbox';
                } elseif ($column->type === 'text') {
                    return 'textarea';
                } elseif (preg_match('/^(password|pass|passwd|passcode)$/i', $column->name)) {
                    return 'password';
                } else {
                    return 'text';
                }
            }
        }

        $attributeWidgets = [];
        if (method_exists($model, 'attributeWidgets')) {
            $attributeWidgets = $model->attributeWidgets();
        }

        $data = [];
        if (!empty($attributeWidgets)) {
            foreach ($attributeWidgets as $item) {
                if (isset($item[0]) && isset($item[1])) {
                    $data[$item[0]] = $item[1];
                    $data[$item[0] . 'Options'] = $item;
                }
            }
        }

        $this->attributeWidgets = (object)$data;

        return $this->getAttributeWidget($model, $attribute);
    }

    /**
     * Get an array of attribute choice values.
     * The variable or method name needs ​​to be: attributeChoices.
     *
     * @param \yii\db\ActiveRecord $model Model
     * @param string $attribute Model attribute
     * @return array
     */
    protected function getAttributeChoices($model, $attribute)
    {
        $data = [];
        $choicesName = (string)$attribute . 'Choices';
        if (method_exists($model, $choicesName) && is_array($model->$choicesName())) {
            $data = $model->$choicesName();
        } elseif (isset($model->$choicesName) && is_array($model->$choicesName)) {
            $data = $model->$choicesName;
        }
        return $data;
    }

    /**
     * Get attribute options.
     *
     * @param string $attribute Model attribute
     * @param array $options Model attribute form options
     * @return array
     */
    protected function getAttributeOptions($attribute, $options = [])
    {
        $optionsName = (string)$attribute . 'Options';
        if (isset($this->attributeWidgets->$optionsName)) {
            $attributeOptions = array_slice($this->attributeWidgets->$optionsName, 2);
            if (empty($options)) {
                return $attributeOptions;
            } else {
                if (empty($attributeOptions)) {
                    return $options;
                } else {
                    return ArrayHelper::merge($options, $attributeOptions);
                }
            }
        } else {
            if (empty($options)) {
                return [];
            } else {
                return $options;
            }
        }
    }

    /**
     * Get model's administrative name.
     *
     * @param string|\yii\db\ActiveRecord $model
     * @return string
     */
    public function getAdminName($model)
    {
        if (is_string($model)) {
            $model = $this->loadModel($model);
        }
        if (!isset($model->adminNames)) {
            return Inflector::pluralize(Inflector::camel2words(StringHelper::basename($model->className())));
        } else {
            return $model->adminNames[0];
        }
    }

    /**
     * Get model's singular name.
     *
     * @param string|\yii\db\ActiveRecord $model
     * @return string
     */
    public function getSingularName($model)
    {
        if (is_string($model)) {
            $model = $this->loadModel($model);
        }
        if (!isset($model->adminNames)) {
            return Inflector::singularize(Inflector::camel2words(StringHelper::basename($model->className())));
        } else {
            return $model->adminNames[1];
        }
    }

    /**
     * Get model's plural name.
     *
     * @param string|\yii\db\ActiveRecord $model
     * @return string
     */
    public function getPluralName($model)
    {
        if (is_string($model)) {
            $model = $this->loadModel($model);
        }
        if (!isset($model->adminNames)) {
            return Inflector::pluralize(Inflector::camel2words(StringHelper::basename($model->className())));
        } else {
            return $model->adminNames[2];
        }
    }

    /**
     * Hide create model action?
     *
     * @param string|\yii\db\ActiveRecord $model
     * @return bool
     */
    public function getHideCreate($model)
    {
        if (is_string($model)) {
            $model = $this->loadModel($model);
        }
        if (isset($model->hideCreateAction)) {
            return (bool)$model->hideCreateAction;
        } else {
            return false;
        }
    }

    /**
     * Hide OK model action?
     *
     * @param string|\yii\db\ActiveRecord $model
     * @return bool
     */
    public function getHideOk($model)
    {
        if (is_string($model)) {
            $model = $this->loadModel($model);
        }
        if (isset($model->hideOkAction)) {
            return (bool)$model->hideOkAction;
        } else {
            return false;
        }
    }

    /**
     * Hide update model action?
     *
     * @param string|\yii\db\ActiveRecord $model
     * @return bool
     */
    public function getHideUpdate($model)
    {
        if (is_string($model)) {
            $model = $this->loadModel($model);
        }
        if (isset($model->hideUpdateAction)) {
            return (bool)$model->hideUpdateAction;
        } else {
            return false;
        }
    }

    /**
     * Hide edit model action?
     *
     * @param string|\yii\db\ActiveRecord $model
     * @return bool
     */
    public function getHideEdit($model)
    {
        if (is_string($model)) {
            $model = $this->loadModel($model);
        }
        if (isset($model->hideEditAction)) {
            return (bool)$model->hideEditAction;
        } else {
            return false;
        }
    }

    /**
     * Hide delete model action?
     *
     * @param string|\yii\db\ActiveRecord $model
     * @return bool
     */
    public function getHideDelete($model)
    {
        if (is_string($model)) {
            $model = $this->loadModel($model);
        }
        if (isset($model->hideDeleteAction)) {
            return (bool)$model->hideDeleteAction;
        } else {
            return false;
        }
    }

    /**
     * Hide list model action?
     *
     * @param string|\yii\db\ActiveRecord $model
     * @return bool
     */
    public function getHideList($model)
    {
        if (is_string($model)) {
            $model = $this->loadModel($model);
        }
        if (isset($model->hideListAction)) {
            return (bool)$model->hideListAction;
        } else {
            return false;
        }
    }

    /**
     * @param \yii\db\ActiveRecord $model
     */
    public function getListUrl($model)
    {
        if (method_exists($model, 'getListUrl')) {
            return $model->getListUrl();
        } elseif (isset(Yii::$app->session['last_list_url'])) {
            return Yii::$app->session['last_list_url'];
        } else {
            return [
                '/ycm/model/list',
                'name' => $this->getModelName($model),
            ];
        }
    }

    /**
     * Download CSV?
     *
     * @param string|\yii\db\ActiveRecord $model
     * @return bool
     */
    public function getDownloadCsv($model)
    {
        if (is_string($model)) {
            $model = $this->loadModel($model);
        }
        if (isset($model->downloadCsv)) {
            return $model->downloadCsv;
        } else {
            return false;
        }
    }

    /**
     * Download MS CSV?
     *
     * @param string|\yii\db\ActiveRecord $model
     * @return bool
     */
    public function getDownloadMsCsv($model)
    {
        if (is_string($model)) {
            $model = $this->loadModel($model);
        }
        if (isset($model->downloadMsCsv)) {
            return $model->downloadMsCsv;
        } else {
            return false;
        }
    }

    /**
     * Download Excel?
     *
     * @param string|\yii\db\ActiveRecord $model
     * @return bool
     */
    public function getDownloadExcel($model)
    {
        if (is_string($model)) {
            $model = $this->loadModel($model);
        }
        if (isset($model->downloadExcel)) {
            return $model->downloadExcel;
        } else {
            return false;
        }
    }

    /**
     * Download Excel in new format?
     *
     * @param string|\yii\db\ActiveRecord $model
     * @return bool
     */
    public function getDownloadExcelNew($model)
    {
        if (is_string($model)) {
            $model = $this->loadModel($model);
        }
        if (isset($model->downloadExcelNew)) {
            return $model->downloadExcelNew;
        } else {
            return false;
        }
    }

    public function getAllowCopy($model)
    {
        if (is_string($model)) {
            $model = $this->loadModel($model);
        }
        if (isset($model->allowCopy)) {
            return $model->allowCopy;
        } else {
            return false;
        }
    }

    /**
     * Get excluded download fields.
     *
     * @param string|\yii\db\ActiveRecord $model
     * @return array
     */
    public function getExcludeDownloadFields($model)
    {
        if (is_string($model)) {
            $model = $this->loadModel($model);
        }
        if (isset($model->excludeDownloadFields)) {
            return $model->excludeDownloadFields;
        } else {
            return [];
        }
    }

    public function isDemo()
    {
        $demo = getenv('YII_DEMO');
        if ($demo == 'true' || $demo === true) {
            return true;
        } else {
            return false;
        }
    }

    public function createTabs($form, $model, $attributes)
    {
        if (method_exists($model, 'attributeTabs')) {
            $attributeTabs = $model->attributeTabs();
            $tabs = [
                [
                    'label' => 'Основное',
                    'content' => '<br>',
                    'active' => true,
                ],
            ];
            foreach ($attributeTabs as $tabConfig) {
                $tab = [
                    'label' => $tabConfig['label'],
                    'content' => '<br>',
                ];
                foreach ($tabConfig['fields'] as $field) {
                    $tab['content'] .= $this->createWidget($form, $model, $field);
                    $attrKey = array_search($field, $attributes);
                    if ($attrKey !== false) {
                        unset($attributes[$attrKey]);
                    }
                }
                $tabs[] = $tab;
            }
            foreach ($attributes as $attr) {
                $tabs[0]['content'] .= $this->createWidget($form, $model, $attr);
            }

            return Tabs::widget([
                'items' => $tabs,
            ]);
        } else {
            $result = '';
            foreach ($attributes as $attribute) {
                $result .= $this->createWidget($form, $model, $attribute);
            }
            return $result;
        }
    }

    public function getUploadPaths()
    {
        $paths = [];

        if ($this->uploadToServer) {
            $paths[] = $this->uploadPath;
        }
        if ($this->uploadToFtp) {
            $paths[] = $this->ftpUploadPath;
        }

        return $paths;
    }

    public function saveFile(UploadedFile $file, $path)
    {
        $results = [];
        foreach ($this->getUploadPaths() as $uploadPath) {
            $savePath = $uploadPath . DIRECTORY_SEPARATOR . $path;
            if (file_exists($savePath)) {
                $results[] = false;
                continue;
            }
            $saveDir = dirname($savePath);
            if (!is_dir($saveDir)) {
                @mkdir($saveDir, $this->uploadPermissions, true);
            }

            if (dirname($file->type) == 'image' && $file->type != 'image/svg+xml' && $file->type != 'image/x-icon') {
                try {
                    Image::thumbnail($file->tempName, 1920, null)->save($savePath, [
                        'quality' => 70,
                    ]);
                    $results[] = true;
                } catch (\Exception $e) {
                    $results[] = false;
                }

            } else {
                $results[] = $file->saveAs($savePath);
            }
        }

        return array_search(false, $results) === false;
    }

    public function deleteFile($path)
    {
        $results = [];
        foreach ($this->getUploadPaths() as $uploadPath) {
            $deletePath = $uploadPath . DIRECTORY_SEPARATOR . $path;
            if (file_exists($deletePath)) {
                $results[] = @unlink($deletePath);
            } else {
                $results[] = false;
            }
        }

        return array_search(false, $results) === false;
    }
}
