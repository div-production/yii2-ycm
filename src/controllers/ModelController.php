<?php

namespace janisto\ycm\controllers;

use janisto\ycm\behaviors\AccessControl;
use janisto\ycm\widgets\SortableGrid\SortableGridAction;
use vova07\imperavi\helpers\FileHelper as RedactorFileHelper;
use Yii;
use yii\base\DynamicModel;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\validators\BooleanValidator;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;

class ModelController extends Controller
{
    public $enableCsrfValidation = false;

    /** @inheritdoc */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => [
                            'index',
                            'list',
                            'create',
                            'update',
                            'delete',
                            'redactor-upload',
                            'redactor-list',
                            'get-tree',
                            'sort',
                        ],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return in_array(Yii::$app->user->identity->username, $this->module->admins);
                        },
                    ],

                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'redactor-upload' => ['post'],
                    'redactor-list' => ['get'],
                    'delete' => ['get', 'post'],
                    'sort' => ['get', 'post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'sort' => [
                'class' => SortableGridAction::className(),
            ],
        ];
    }

    /**
     * Default action.
     *
     * @return string the rendering result.
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Redactor upload action.
     *
     * @param string $name Model name
     * @param string $attr Model attribute
     * @param string $type Format type
     * @return array List of files
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     */
    public function actionRedactorUpload($name, $attr, $type = 'image')
    {
        /** @var $module \janisto\ycm\Module */
        $module = $this->module;
        $name = (string)$name;
        $attribute = (string)$attr;
        $uploadType = 'image';
        $validatorOptions = $module->redactorImageUploadOptions;
        if ((string)$type == 'file') {
            $uploadType = 'file';
            $validatorOptions = $module->redactorFileUploadOptions;
        }
        $attributePath = $module->getAttributePath($name, $attribute);
        $file = UploadedFile::getInstanceByName('upload');
        $model = new DynamicModel(compact('file'));
        $model->addRule('file', $uploadType, $validatorOptions)->validate();
        if ($model->hasErrors()) {
            $result = [
                'error' => $model->getFirstError('file'),
            ];
        } else {
            if ($model->file->extension) {
                $model->file->name = md5($attribute . time() . uniqid(rand(), true)) . '.' . $model->file->extension;
            }
            $path = $attributePath . DIRECTORY_SEPARATOR . $model->file->name;
            if ($module->saveFile($model->file, $path)) {
                $result = ['url' => $module->getAttributeUrl($name, $attribute, $model->file->name)];
                $result['fileName'] = $model->file->name;
                $result['uploaded'] = 1;
            } else {
                $result = [
                    'error' => Yii::t('ycm', 'Could not upload file.'),
                ];
            }
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $result;
    }

    /**
     * Redactor file list.
     *
     * @param string $name Model name
     * @param string $attr Model attribute
     * @param string $type Format type
     * @return array List of files
     */
    public function actionRedactorList($name, $attr, $type = 'image')
    {
        /** @var $module \janisto\ycm\Module */
        $module = $this->module;
        $name = (string)$name;
        $attribute = (string)$attr;
        $attributePath = $module->getAttributePath($name, $attribute);
        $attributeUrl = $module->getAttributeUrl($name, $attribute, '');
        $format = 0;
        $options = [
            'url' => $attributeUrl,
            'only' => ['*.png', '*.gif', '*.jpg', '*.jpeg'],
            'caseSensitive' => false,
        ];
        if ((string)$type == 'file') {
            $format = 1;
            $options = [
                'url' => $attributeUrl,
            ];
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        return RedactorFileHelper::findFiles($attributePath, $options, $format);
    }

    /**
     * List models.
     *
     * @param string $name Model name
     * @return string the rendering result.
     */
    public function actionList($name)
    {
        /** @var $module \janisto\ycm\Module */
        $module = $this->module;
        /** @var $model \yii\db\ActiveRecord */
        $model = $module->loadModel($name);

        Yii::$app->session['last_list_url'] = Yii::$app->request->url;

        $columns = [];
        if (method_exists($model, 'gridViewColumns')) {
            $columns = $model->gridViewColumns();
        } else {
            //$columns = $model->getTableSchema()->getColumnNames();

            $validators = [
                'boolean' => [],
            ];

            foreach ($model->validators as $validator) {
                if ($validator instanceof BooleanValidator) {
                    $validators['boolean'] += $validator->attributes;
                }
            }

            $i = 0;
            foreach ($model->getTableSchema()->columns as $column) {
                if (in_array($column->name, $validators['boolean'])) {
                    $columns[] = [
                        'attribute' => $column->name,
                        'content' => function ($model, $key, $index, $column) {
                            return $model->{$column->attribute} ? 'Да' : 'Нет';
                        },
                        'filter' => [
                            1 => 'Да',
                            0 => 'Нет',
                        ],
                    ];
                } else {
                    $columns[] = $column->name;
                }

                $i++;
                if ($i === $module->maxColumns) {
                    break;
                }
            }
        }
        //array_unshift($columns, ['class' => 'yii\grid\SerialColumn']);
        array_push($columns, [
            'class' => 'yii\grid\ActionColumn',
            'template' => '{update} {delete} {copy}',
            'buttons' => [
                'update' => function ($url, $model, $key) {
                    /** @var $module \janisto\ycm\Module */
                    $module = $this->module;
                    if ($module->getHideEdit($model) === false) {
                        return Html::a('<span class="glyphicon glyphicon-pencil"></span>', $url, [
                            'title' => Yii::t('ycm', 'Update'),
                            'data-pjax' => '0',
                        ]);
                    }

                },
                'delete' => function ($url, $model, $key) {
                    /** @var $module \janisto\ycm\Module */
                    $module = $this->module;
                    if ($module->getHideDelete($model) === false) {
                        return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, [
                            'title' => Yii::t('ycm', 'Delete'),
                            'data-confirm' => Yii::t('ycm', 'Are you sure you want to delete this item?'),
                            'data-method' => 'post',
                            'data-pjax' => '0',
                        ]);
                    }
                },
                'copy' => function ($url, $model, $key) {
                    /** @var $module \janisto\ycm\Module */
                    $module = $this->module;
                    if ($module->getAllowCopy($model) !== false) {
                        return Html::a('<span class="glyphicon glyphicon-copy"></span>', ['/ycm/model/create', 'name' => Yii::$app->request->get('name'), 'copy_id' => $model->id], [
                            'title' => Yii::t('ycm', 'Copy'),
                            'data-pjax' => '0',
                        ]);
                    }

                }
            ],
            'urlCreator' => function ($action, $model, $key, $index) {
                $name = Yii::$app->getRequest()->getQueryParam('name');
                return Url::to([$action, 'name' => $name, 'pk' => $key]);
            },
        ]);

        if (method_exists($model, 'search')) {
            $scenarios = $model->scenarios();
            if (isset($scenarios['ycm-search'])) {
                $model->setScenario('ycm-search');
            }
            $dataProvider = $model->search(Yii::$app->request->queryParams);
            $config = [
                'dataProvider' => $dataProvider,
                'filterModel' => $model,
                'columns' => $columns,
                'showOnEmpty' => true,
            ];
        } else {
            $sort = [];
            if (method_exists($model, 'gridViewSort')) {
                $sort = $model->gridViewSort();
            }

            $dataProvider = new ActiveDataProvider([
                'query' => $model->find(),
                'sort' => $sort,
                'pagination' => [
                    'pageSize' => 20,
                ],
            ]);
            $config = [
                'dataProvider' => $dataProvider,
                'columns' => $columns,
                'showOnEmpty' => false,
            ];
        }

        if (isset($model->viewType)) {
            $viewType = $model->viewType;
            $config['dataProvider']->pagination = false;
        } else {
            $viewType = 'list';
        }

        return $this->render($viewType, [
            'config' => $config,
            'model' => $model,
            'name' => $name,
        ]);
    }

    /**
     * Create model.
     *
     * @param string $name Model name
     * @return mixed
     * @throws InvalidConfigException
     * @throws ServerErrorHttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionCreate($name, $copy_id = null)
    {
        /** @var $module \janisto\ycm\Module */
        $module = $this->module;
        /** @var $model \yii\db\ActiveRecord */
        $model = $module->loadModel($name);

        $demo = $module->isDemo();

        if ($model->load(Yii::$app->request->post()) && !$demo) {
            $filePaths = [];
            foreach ($model->tableSchema->columns as $column) {
                $attribute = $column->name;
                $widget = $module->getAttributeWidget($model, $attribute);
                if ($widget == 'file' || $widget == 'image') {
                    $attributePath = $module->getAttributePath($name, $attribute);
                    $file = UploadedFile::getInstance($model, $attribute);
                    if ($file) {
                        $model->$attribute = $file;
                        if ($model->validate()) {
                            $fileName = md5($attribute . time() . uniqid(rand(), true)) . '.' . $file->extension;
                            $path = $attributePath . DIRECTORY_SEPARATOR . $fileName;
                            if (file_exists($path) || !$module->saveFile($file, $path)) {
                                Yii::$app->session->setFlash('error', 'Не удалось сохранить файл на сервер');
                                return $this->render('create', [
                                    'model' => $model,
                                    'name' => $name,
                                ]);
                            }
                            array_push($filePaths, $path);
                            $model->$attribute = $fileName;
                        }
                    }
                }
            }
            if ($model->save()) {
                Yii::$app->session->setFlash('success',
                    Yii::t('ycm', '{name} has been created.', ['name' => $module->getSingularName($name)]));
                if (Yii::$app->request->post('_addanother')) {
                    return $this->redirect(['create', 'name' => $name]);
                } elseif (Yii::$app->request->post('_continue')) {
                    return $this->redirect(['update', 'name' => $name, 'pk' => $model->primaryKey]);
                } else {
                    return $this->redirect($module->getListUrl($model));
                }
            }
        } elseif (Yii::$app->request->isPost && $demo) {
            Yii::$app->session->setFlash('danger', Yii::t('ycm', 'You can\'t create entries in demo mode'));
        } elseif (Yii::$app->request->isGet && $copy_id) {
            $copyModel = $model::findOne($copy_id);
            if ($copyModel) {
                $model->setAttributes($copyModel->attributes, false);
                $model->id = null;
            } else {
                Yii::$app->session->setFlash('error', Yii::t('ycm', 'Entry for copy not found'));
            }
        }

        return $this->render('create', [
            'model' => $model,
            'name' => $name,
        ]);
    }

    /**
     * Update model.
     *
     * @param string $name Model name
     * @param integer $pk Primary key
     * @return mixed
     * @throws InvalidConfigException
     * @throws ServerErrorHttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionUpdate($name, $pk)
    {
        /** @var $module \janisto\ycm\Module */
        $module = $this->module;
        /** @var $model \yii\db\ActiveRecord */
        $model = $module->loadModel($name, $pk);

        $demo = $module->isDemo();

        if ($model->load(Yii::$app->request->post()) && !$demo) {
            $filePaths = [];
            foreach ($model->tableSchema->columns as $column) {
                $attribute = $column->name;
                $widget = $module->getAttributeWidget($model, $attribute);
                if ($widget == 'file' || $widget == 'image') {
                    $attributePath = $module->getAttributePath($name, $attribute);
                    $className = StringHelper::basename($model->className());
                    $postData = Yii::$app->request->post();
                    $delete = (isset($postData[$className][$attribute . '_delete']));
                    if ($delete) {
                        $path = $attributePath . DIRECTORY_SEPARATOR . $model->getOldAttribute($attribute);
                        if (!$module->deleteFile($path)) {
                             Yii::$app->session->setFlash('error', 'Не удалось удалить файл с сервера');
                            return $this->render('update', [
                                'model' => $model,
                                'name' => $name,
                            ]);
                        }
                        $model->$attribute = '';
                    } else {
                        $file = UploadedFile::getInstance($model, $attribute);
                        if ($file) {
                            $model->$attribute = $file;
                            if ($model->validate()) {
                                $fileName = md5($attribute . time() . uniqid(rand(), true)) . '.' . $file->extension;
                                $path = $attributePath . DIRECTORY_SEPARATOR . $fileName;
                                if (file_exists($path) || !$module->saveFile($file, $path)) {
                                    Yii::$app->session->setFlash('error', 'Не удалось сохранить файл на сервер');
                                    return $this->render('create', [
                                        'model' => $model,
                                        'name' => $name,
                                    ]);
                                }
                                array_push($filePaths, $path);
                                $model->$attribute = $fileName;
                            }
                        } else {
                            $model->$attribute = $model->getOldAttribute($attribute);
                        }
                    }
                }
            }
            if ($model->save()) {
                Yii::$app->session->setFlash('success',
                    Yii::t('ycm', '{name} has been updated.', ['name' => $module->getSingularName($name)]));
                if (Yii::$app->request->post('_addanother')) {
                    return $this->redirect(['create', 'name' => $name]);
                } elseif (Yii::$app->request->post('_continue')) {
                    return $this->redirect(['update', 'name' => $name, 'pk' => $model->primaryKey]);
                } else {
                    return $this->redirect($module->getListUrl($model));
                }
            }
        } elseif (Yii::$app->request->method == 'POST' && $demo) {
            Yii::$app->session->setFlash('danger', Yii::t('ycm', 'You can\'t edit entries in demo mode'));
        }

        return $this->render('update', [
            'model' => $model,
            'name' => $name,
        ]);
    }

    /**
     * Delete model.
     *
     * @param string $name Model name
     * @param integer $pk Primary key
     * @return Response
     */
    public function actionDelete($name, $pk)
    {
        /** @var $module \janisto\ycm\Module */
        $module = $this->module;
        /** @var $model \yii\db\ActiveRecord */
        $model = $module->loadModel($name, $pk);

        if ($module->isDemo()) {
            Yii::$app->session->setFlash('danger', Yii::t('ycm', 'You can\'t delete entries in demo mode'));
        } elseif ($model->delete() !== false) {
            Yii::$app->session->setFlash('success',
                Yii::t('ycm', '{name} has been deleted.', ['name' => $module->getSingularName($name)]));
        } else {
            Yii::$app->session->setFlash('error',
                Yii::t('ycm', 'Could not delete {name}.', ['name' => $module->getSingularName($name)]));
        }

        return $this->redirect($module->getListUrl($model));
    }

    public function actionGetTree()
    {
        if ($model = Yii::$app->request->post('model')) {
            $model = new $model;
        } else {
            return false;
        }
        return $this->renderPartial('_tree', ['model' => $model]);
    }

    public function redirect($url, $statusCode = 302)
    {
        return Yii::$app->getResponse()->redirect(Url::to($url), $statusCode, false);
    }
}
