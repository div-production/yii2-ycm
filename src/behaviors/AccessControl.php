<?php
namespace janisto\ycm\behaviors;

use Yii;
use yii\web\ForbiddenHttpException;

class AccessControl extends \yii\filters\AccessControl
{
    public function denyAccess($user)
    {
        if ($user->getIsGuest()) {
            Yii::$app->getResponse()->redirect(['admin/login']);
        } else {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        }
    }
}
