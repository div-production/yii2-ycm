<?php
namespace janisto\ycm\widgets\TreeGrid;

use yii\web\AssetBundle;

/**
 * This asset bundle provides the [jQuery TreeGrid plugin library](https://github.com/maxazan/jquery-treegrid)
 *
 * @author Leandro Gehlen <leandrogehlen@gmail.com>
 */
class TreeGridAsset extends AssetBundle
{

    public $sourcePath = '@ycm/assets';

    public $js = [
        'js/jquery.treegrid.js',
    ];

    public $css = [
        'css/jquery.treegrid.css',
    ];

    public $depends = [
        'yii\web\JqueryAsset',
    ];

    public $publishOptions = [
        // 'forceCopy' => true
    ];

} 
