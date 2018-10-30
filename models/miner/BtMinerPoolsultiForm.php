<?php 

namespace app\models\miner;



class BtMinerPoolsultiForm extends \yii\base\Model
{
	private $_pools=[];

	public function getPools()
	{
		return $this->_pools;
	}
}