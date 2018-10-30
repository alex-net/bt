<?php 

namespace app\components;

class AppPlushko extends \yii\base\Behavior
{
	const EVENT_START='start-job';
	const BEHAVIOR_FORMATTRS_SET='set formatter bt';

	public static function todoevent($e)
	{
		//print_r($e);
		echo get_class($e);
		//exit;
		echo 'da';	
	}
}