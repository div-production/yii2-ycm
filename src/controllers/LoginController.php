<?php
namespace janisto\ycm\controllers;

use janisto\ycm\models\LoginForm;
use Yii;

class LoginController extends Controller
{
    public $layout = 'login';

    public function actionIndex()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $module = $this->module;

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->redirect(['/' . $module->urlPrefix]);
        }
        return $this->render('index', [
            'model' => $model,
        ]);
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }
}
