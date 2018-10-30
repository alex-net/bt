<?php 

namespace app\models\miner;

use yii\base\Model;

abstract class BtMinerBaseForm extends Model
{
	public $port; // порт 
	public $login; // Логин
	public $password; // пароль 
	public $interval; // интервал обновления майнера в секундах 
	public $status; // статус файнера 
	public $owner;// владелец ...

	public function attributeLabels()
	{
		return [
			'status'=>'Активен',
			'port'=>'Порт',
			'login'=>'Логин',
			'password'=>'Пароль',
			'interval'=>'интервал обновления майнера в минутах',
		];
	}

	public function rules()
	{
		return [
			[['port','login','password'],'required'],
			['status','\yii\validators\BooleanValidator'],
			['port','\yii\validators\NumberValidator','min'=>0,'max'=>65535],
			['interval','\yii\validators\NumberValidator','min'=>1],
			['interval',\yii\validators\DefaultValueValidator::className(),'value'=>10],
		];
	}


}