<?php

use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Breadcrumbs;

/* @var $this \yii\web\View */
/* @var $content string */

/** @var $module \janisto\ycm\Module */
$module = Yii::$app->controller->module;

$assetBundle = $module->assetBundle;
$assetBundle::register($this);

$currentLang = null;
$langDropDownItems = [];

$langs = [];
if (is_callable($module->languages)) {
    $langs = $module->languages->__invoke();
}
else {
    $langs = $module->languages;
}

foreach ($langs as $langCode => $langName) {
    $item = ['label' => $langName, 'url' => ''];
    if ($langCode == Yii::$app->language) {
        $currentLang = $langName;
        $item['options'] = ['class' => 'disabled'];
    }
    else {
        $item['linkOptions'] = ['class' => 'lang-option', 'data-lang' => $langCode];
    }
    $langDropDownItems[] = $item;
}

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<?php
NavBar::begin([
    'brandLabel' => Yii::t('ycm', 'Administration'),
    'brandUrl' => ['default/index'],
    'innerContainerOptions' => ['class' => 'container-fluid'],
    'options' => [
        'class' => 'navbar navbar-inverse navbar-fixed-top',
    ],
]);

$items = [];
if (count($langDropDownItems) > 1) {
    $items[] = [
        'label' => Yii::t('ycm', 'Language').': '. $currentLang,
        'items' => $langDropDownItems,
    ];
}

$items[] = Yii::$app->user->isGuest ?
    ['label' => Yii::t('ycm', 'Login'), 'url' => ['/site/login']] :
    [
        'label' => Yii::t('ycm', 'Logout ({username})', ['username' => Yii::$app->user->identity->username]),
        'url' => ['/admin/logout'],
        'linkOptions' => ['data-method' => 'post'],
    ];

echo Nav::widget([
    'options' => ['class' => 'navbar-nav navbar-right'],
    'items' => $items
]);
NavBar::end();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm-3 col-md-2 sidebar">
            <?php
            $sidebarItems = $module->sidebarItems;

            echo Nav::widget([
                'options' => ['class' => 'nav nav-sidebar'],
                'activateParents' => true,
                'items' => $sidebarItems,
            ]);
            ?>
        </div>
        <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">

            <?= Breadcrumbs::widget([
                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
            ]) ?>

            <?= $content ?>

        </div>
    </div>
</div>

<?php $this->endBody() ?>
<script>
    $('.lang-option').click(function (e) {
        e.preventDefault();
        var date = new Date();
        date.setTime(date.getTime() + (30*24*60*60*1000));
        document.cookie = "lang=" + $(this).attr('data-lang')  + "; expires=" + date.toUTCString() + "; path=/";
        window.location.reload();
    });
</script>
</body>
</html>
<?php $this->endPage() ?>
