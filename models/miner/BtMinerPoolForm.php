<?php 

namespace app\Models\miner;

class BtMinerPoolForm extends \yii\base\Model
{
	public $url;// это урл + порт через двоеточие ..
	public $login; // логин для дотупа
	public $password;// пароль

	public function rules()
	{
		return [
			[['url','login'],'required'],
			['password',\yii\validators\DefaultValueValidator::className(),'value'=>''],
		];
	}
	public function attributeLabels()
	{
		return [
			'url'=>'Ссылка',
			'login'=>'Логин',
			'password'=>'Пароь',
		];
	}
}