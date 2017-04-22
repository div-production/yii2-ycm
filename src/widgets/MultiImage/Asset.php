<?php

namespace janisto\ycm\widgets\MultiImage;

use yii\web\AssetBundle;

/**
 * @author Владимир
 */

class Asset extends AssetBundle {
	public $sourcePath = '@ycm/widgets/MultiImage/assets';
    
    public $js = [
        'js/multiimage.js',
    ];
	
	public $css = [
		'css/multiimage.css'
	];
    public $depends = [
        'yii\web\JqueryAsset'
    ];
}
