<?php 

namespace app\models\miner;



class BtMinerIPForm extends BtMinerBaseForm
{
	public $ipfrom;
	public $ipto;

	private $_ip;

	public function getIp()
	{
		return $this->_ip;
	}


	public function attributeLabels()
	{
		return array_merge(parent::attributeLabels(),[
			'ipfrom'=>'Начальный IP',
			'ipto'=>'Конечный IP',
		]);
	}

	public function rules()
	{
		return array_merge(parent::rules(),[
			[['ipfrom','ipto'],'required'],
		]);
	}
}
