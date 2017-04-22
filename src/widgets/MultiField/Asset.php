<?php

namespace janisto\ycm\widgets\MultiField;

use yii\web\AssetBundle;

/**
 * @author Владимир
 */

class Asset extends AssetBundle {
	public $sourcePath = '@ycm/widgets/MultiField/assets';
    
    public $js = [
        'js/multifield.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset'
    ];
}
