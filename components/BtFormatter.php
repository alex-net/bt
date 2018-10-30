<?php 

namespace app\components;

class BtFormatter extends \yii\i18n\Formatter
{
	/** визуализация статуса . пользвателя  */
	public function asUserStatusVis($val)
	{
		return $val?'Активен':'Заблокирован';
	}
	/** статус майнера ... визаализация .. */
	public function asMinerStatusVis($val)
	{
		return $val?'Активен':'-';
	}

	public function asAssocToStr($val)
	{
		//print_r($val);
		$res=[];
		if (empty($val))
			return '';
		foreach($val as $x=>$y)
			$res[]=sprintf('%s) %s;',$x,$y);
		return trim(implode(' ',$res),';');
	}
	// формат интервала ..
	public function asDateTimeInterval($val)
	{
		// date('mмес dд Hч iм sс',$y['timet']
		$day=3600*24;
		$hour=3600;
		$min=60;


		return sprintf('%dд %dч %dм %dс',
			(int)$val/$day,
			(int)($val%$day)/$hour,
			(int)($val%$hour)/$min,
			(int)($val%$min)

		);
	}
}