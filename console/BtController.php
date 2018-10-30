<?php 

namespace app\commands;

use Yii;
use yii\console\ExitCode;


class BtController extends  yii\console\BtController
{
	function actionIndex ()
	{
		echo 'dasda';
		return ExitCode::OK;
	}
}
