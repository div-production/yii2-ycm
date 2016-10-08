<?php

namespace janisto\ycm\controllers;

use Yii;
use janisto\ycm\behaviors\AccessControl;
use yii\filters\VerbFilter;

class DefaultController extends Controller
{
    /** @inheritdoc */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return in_array(Yii::$app->user->identity->username, $this->module->admins);
                        }
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'index' => ['get'],
                ],
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
	
//	public function actionLogin() {
//		return 'Страница входа в админку';
//	}
}
