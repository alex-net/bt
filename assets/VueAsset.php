<?php 

namespace app\assets;

class VueAsset extends \yii\web\AssetBundle
{
	public $basePath='@webroot/js/vuejs';
	public $baseUrl='@web/js/vuejs';
	public $js=[
		'axios.min.js',
		'vue.js',
		'base-components.js',


	];
}