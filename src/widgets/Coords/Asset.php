<?php

namespace janisto\ycm\widgets\Coords;

use yii\web\AssetBundle;

/**
 * @author Владимир
 */
class Asset extends AssetBundle
{
    public $sourcePath = '@ycm/widgets/Coords/assets';

    public $js = [
        'js/coords.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
