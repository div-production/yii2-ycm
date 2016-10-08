<?php
namespace janisto\ycm\controllers;

use Yii;
use janisto\ycm\models\LoginForm;

class LoginController extends Controller {
	public $layout = 'login';
	
	public function actionIndex() {
		if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->redirect('/admin');
        }
        return $this->render('index', [
            'model' => $model,
        ]);
	}
}