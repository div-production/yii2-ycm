<?php

namespace janisto\ycm;

use yii\web\AssetBundle;

class YcmAsset extends AssetBundle
{
    public $sourcePath = '@ycm/assets';
    public $css = [
        'css/ycm.css',
    ];
    public $js = [
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
