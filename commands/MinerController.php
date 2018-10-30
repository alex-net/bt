<?php 

namespace app\commands;

use Yii;
use yii\console\ExitCode;
use app\models\miner\BtMiner;
use yii\console\widgets\Table;
use yii\helpers\Console;

/** 
Управление майнерами 
*/
class MinerController extends  \yii\console\Controller
{
	/** 
	 тестовая команда .. в будущем будет отображать майнеры  
	*/
	public function actionIndex ()
	{
		$tbl=new Table;
		$tbl->setHeaders(['#','id','IP:PORT','дата обновления','Период обновления,c','Статус']);
		$ms=BtMiner::find()->all();
		$rows=[];
		foreach($ms as $m)
			$rows[]=[$m->id,$m->minerid,$m->ip.':'.$m->port,$m->timeupd,$m->interval,$m->status];

		$tbl->setRows($rows);
		echo $tbl->run();
	
		return ExitCode::OK;
	}
	/** 
		информация по майнеру ... id 
	*/
	public function actionInfo(int $mid)
	{
		$m=BtMiner::findOne($mid);
		if (!$m)
			throw new \yii\onsole\Exception("Майнер не найден");
		

		echo $this->ansiFormat('Информация по майнеру ',Console::FG_YELLOW).$this->ansiFormat('№'.$m->id,Console::FG_RED) ."\n"; 	
		$tbl=new Table();
		$tbl->setHeaders(['Поле','Значение']);
		

		//print_r($m->laststaticsic);
		$stat=$m->laststaticsic;

		if (!empty($stat['temp']))
			foreach($stat['temp'] as $x=>$y)
				$stat['temp'][$x]=max($y);
		// производительности .
		$pr=[];
		if (!empty($stat['chain_rate']))
			foreach($stat['chain_rate'] as $x=>$y)
				$pr[$x]['real']=$y;
		if (!empty($stat['chain_rateideal']))
			foreach($stat['chain_rateideal'] as $x=>$y)
				$pr[$x]['ideal']=$y;
		foreach($pr as $x=>$y)
			$pr[$x]=sprintf('%s(%s)',empty($y['real'])?'-':$y['real'],empty($y['ideal'])?'-':$y['ideal']);

		//print_r($m->OwnerInfo);
		$tbl->setRows([
			['#',$m->id],
			['id',empty($m->minerid)?'-':$m->minerid],
			['IP',$m->ip],
			['Порт',$m->port],
			['Обновлён',empty($m->timeupd)?'-':$m->timeupd],
			['Интеррвал',$m->interval.' с'],
			['Владелец',$m->owner?($m->OwnerInfo['name'].' ('.$m->OwnerInfo['mail'].')'):'-'],
			['Время работы',empty($stat['elapsed'])?'-':$stat['elapsed'].' с'],
			['Производительность, Гхеш',$stat['ghs_5s']],
			['Вентиляторы, об/мин',Yii::$app->formatter->asAssocToStr($stat['fans'])],
			['Температуры, град',empty($stat['temp'])?'-':Yii::$app->formatter->asAssocToStr($stat['temp'])],
			['Работающих чипов',empty($stat['chain_acn'])?'-':Yii::$app->formatter->asAssocToStr($stat['chain_acn'])],
			['Реальныая(идеальная) производительности, Гхеш',empty($pr)?'-':Yii::$app->formatter->asAssocToStr($pr)],
		]);
		// запрашиваем последние данные по статистике .. 

		echo $tbl->run();

	}

	// обновление для  сех майнеров 
	private function multipleupd()
	{
		$mm=[];
		// достать тех кого надо обновить ..
		$res=Yii::$app->db->createCommand('select id from bt_miner where updating=false and status=true and (extract(epoch from (now()-timeupd))::integer >= "interval"*60 or timeupd is null) ')->queryColumn();
		if (!$res)
			return ;
		printf("%s) Будут обновлены майнеры %s \n",date('c'),implode(', ',$res));	
		foreach($res as $x=>$y){
			$r=popen(Yii::$app->basepath.'/yii miner/up '.$y, 'r');
			if ($r)
				$mm[]=$r;
		}
		if (!$mm)
			return ;
		sleep(10);
		do{
			
			$readed=false;
			foreach($mm as $m)
				while($s=fgets($m)){
					printf("%s ",$s);
					$readed=true;
				}
			if ($readed)
				sleep(5);
		}while($readed);
		foreach($mm as $m)
			pclose($m);

	}
	/** 
		обновить майнер с номером mid   если номер не задан ..  обновлем всех .. 
	*/
	public function actionUp(int $mid=0)
	{
		if (!$mid){ // запрос на обновление всех кого надо .. 
			$this->multipleupd();
			return ExitCode::OK;
			
			/*if ($mm)
				$mm=BtMiner::findAll($mm);*/
			//$mm=BtMiner::find()->where(['and',['<','extract(epoch from timeupd)','extract(epoch from now())::integer-"interval"'],['=','updating',false]]);
			//echo $mm->createCommand()->sql."\n";
		
		}

		$m=BtMiner::find()->where(['id'=>$mid])->limit(1)->one();
		if (!$m)
			throw new \yii\console\Exception(sprintf('Майнер с номером %s не сущствует!',$mid));
		// обновляем майнер .. 
		$retcode=ExitCode::OK;
		if ($m->minerUpdate())
			echo $this->ansiFormat(sprintf('%s) Майнер %d (%s) успешно обновлён!',date('c'),$m->id,$m->minerid),Console::FG_GREEN);
		else{
			echo $this->ansiFormat(sprintf('Обновить майнер %d не удолось!',$m->id),Console::FG_RED)."\n";
			echo "Текст ошибки: ".$this->ansiFormat($m->errorstring,Console::FG_RED);
			echo "\n";
			$retcode=ExitCode::UNSPECIFIED_ERROR;
		}
		echo "\n";

		return $retcode;
			//$mm=[$m->id];
		
		

		
		
	}
}
