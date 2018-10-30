<?php 

namespace app\models\miner;

use Yii;
use yii\db\pgsql\ArrayParser;
use app\models\BtUser;
class BtMiner extends \yii\db\ActiveRecord
{

	public static function getallminerofuser($owner)
	{
		return self::find()->where(['owner'=>$owner]);
	}


	public function attributeLabels()
	{
		return [
			'status'=>'Активен',
			'port'=>'Порт',
			'login'=>'Логин',
			'password'=>'Пароль',
			'timeupd'=>'Время последнего обновленя',
			'minerid'=>'ID майнера',
			'interval'=>'Интервал обновления, мин'

		];
	}
	/** получить информацию о владельце */
	public function getOwnerInfo()
	{
		return BtUser::info($this->owner);
	}

	/** преобразование статистики */
	public static function prepareStatisticRow(&$stat)
	{
		if (!$stat) return [];

		$ar=new ArrayParser();
		foreach(['fans','temp','freq_avg','chain_rateideal','chain_acn','chain_rate'] as $k){
			if (!isset($stat[$k]))
				continue;
			$stat[$k]=$ar->parse($stat[$k]);
			if ($k!='temp'){
				foreach($stat[$k] as $x=>$y)
					if (!floatval($y))
						unset($stat[$k][$x]);
			}
			else
				foreach($stat[$k] as $x=>$y){
					foreach($stat[$k][$x] as $n=>$m)
						if (!$m)
							unset($stat[$k][$x][$n]);

					if (empty($stat[$k][$x]))
						unset($stat[$k][$x]);
				}
		}

	}
	/** получть последние данные по статистике для майнера ..  */ 
	public function getLastStaticsic()
	{
		static $data;
		if (!$data){
			$stat=Yii::$app->db->createCommand('select * from  bt_miner_history where id=:id order by dt desc limit 1;')->bindValues([':id'=>$this->id])->query()->read();
			if ($stat)
				self::prepareStatisticRow($stat);			
			$data=$stat;
		}
		return $data;
	}
	
	private function attachssh()
	{
		if (!empty($this->behaviors['minerssh']))
			return ;
		$this->attachBehavior('minerssh',[
			'class'=>\app\components\MinerConnector::className(),
			'fip'=>'ip',
			'fport'=>'port',
			'flogin'=>'login',
			'fpassword'=>'password',
		]);
	}
	/** восстановение после неудачного обновления .. */
	private function minerUpdateResetAfterBad()
	{
		Yii::$app->db->createCommand()->update('bt_miner',['updating'=>false,'badupds'=>new \yii\db\Expression('badupds + 1')],['id'=>$this->id])->execute();
	}
	/** обновление состояния майнера  */
	public function minerUpdate()
	{
		$this->attachssh();
		
		// ставим птичку о том что начинаем обновляться.. 
		Yii::$app->db->createCommand()->update('bt_miner',['updating'=>true],['id'=>$this->id])->execute();
		//$this->executecommandSoket('summary');
		//$this->executecommandSsh('summary');
		$t=time();//date('c');
		if($this->testminer()){
			$res=$this->statdata;
			if (empty($res)){// нет данных 
				$this->minerUpdateResetAfterBad();
				return false;
			}
			
			$ins=Yii::$app->db->createCommand()->insert('bt_miner_history',[
				'id'=>$this->id,
				'dt'=>date('c',$t),
				'elapsed'=>$res['Elapsed'],
				'ghs_5s'=>$res['GHS 5s'],
				'ghs_av'=>$res['GHS av'],
				'fans'=>$res['fans'],
				'temp'=>$res['temps'],
				'freq_avg'=>$res['freq_avgs'],
				'chain_rateideal'=>$res['chain_rateideals'],
				'chain_acn'=>$res['chain_acns'],
				'chain_rate'=>$res['chain_rates'],
			])->execute();
			if ($ins){// данные обновлены ...
				
				Yii::$app->db->createCommand()->update('bt_miner',['timeupd'=>date('c',$t),'updating'=>false,'badupds'=>0],['id'=>$this->id])->execute();
				return $t;
			}
			Yii::info($ins,'res');
		}
		else // не удалось обновиться .. 
			$this->minerUpdateResetAfterBad();
			
		

		Yii::$app->db->createCommand()->update('bt_miner',['updating'=>false],['id'=>$this->id])->execute();
		return false;
	}
	

	/** сохранть пулы в майнер ...  */
	public function savePoolsToMiner()
	{
		//  подключаем коннектор ...
		$this->attachssh();
		//Yii::info('from savePoolsToMiner ','app todo ');
		//Yii::info($this->pools,'pools');
		// запрос конфига ..
		$cfg=$this->minerConfig;
		// обнулить пулы .. 
		$cfg['pools']=[];
		foreach($this->pools as $p)
			$cfg['pools'][]=array_combine(['url','user','pass'], explode('|', $p));
		
		$this->minerConfig=$cfg;
		//Yii::info($cfg,'conf');

	}

	/** отдать данные текущего состояния ..  */
	public static function getLastStatistic(int $owner)
	{
		$q=new \yii\db\Query;
		$cols=[
			//'time'=>'to_char(((elapsed::varchar) || \' s\')::interval,\'DD HH24:MI:SS\')',// время работы 
			'timet'=>'elapsed',// время работы 
			'ghs'=>'ghs_av', // призводительность .. 
			'mkey'=>'m.id',
			'ip'=>'m.ip',
			'upd'=>'m.timeupd',
			'temp',
			'fans',
			'chain_rate',
			'chain_acn',
			'pools'=>'m.pools',
			'interval',
			'badupds',
			'updating'
		];
		$res=$q->select($cols)->from(['m'=>'bt_miner'])->where(['m.owner'=>$owner,'status'=>true])->leftJoin(['mh'=>'bt_miner_history'],'m.id=mh.id and m.timeupd=mh.dt')->orderBy(['m.timeupd'=>SORT_DESC])->all();
		$arpar=new ArrayParser();
		foreach($res as $x=>$y){
			self::prepareStatisticRow($y);
			$tt=$y['temp'];
			$y['updt']=strtotime($y['upd']);
			// заполняем данными . .если там пусто то кидаем пустой масссив
			foreach(['temp','chain_rate','chain_acn','fans'] as $k)
				$y[$k]=$y[$k]?$y[$k]:[];

			foreach($y['temp'] as $k=>$v)
				$y['temp'][$k]=max($v);

			$y['time']=Yii::$app->formatter->asDateTimeInterval($y['timet']);

			// определяемся с платами ... 
			$keys=array_unique(array_merge(array_keys($y['chain_rate']),array_keys($y['chain_acn'])));
			if ($keys){
				$y['plats']=[];
				foreach($keys as $k)
					$y['plats'][$k]=[
						'rate'=>empty($y['chain_rate'][$k])?'-':$y['chain_rate'][$k],
						'acn'=>empty($y['chain_acn'][$k])?'-':$y['chain_acn'][$k],
						't1'=>empty($tt[0][$k])?0:$tt[0][$k],
						't2'=>empty($tt[1][$k])?0:$tt[1][$k],
					];
				$y['platsco']=count($y['plats']);
			}
			unset($y['chain_acn'],$y['chain_rate']);
			$y['fans']=array_values($y['fans']);
			// выдераем пулы ..
			$y['iplong']=ip2long($y['ip']);
			$y['pools']=$arpar->parse($y['pools']);
			foreach($y['pools'] as $k=>$v)
				$y['pools'][$k]=array_combine(['url','user','pass'],explode('|', $v));

			$res[$x]=$y;
		}

		return $res;
	}
	/** перезапуск оболочки майнера ... */ 
	public function reboot()
	{
		$this->attachssh();
		return $this->reboottodo();
	}

	/** проверка ip в базе .  */
	public static function existsIpInDB($ip)
	{
		$cmd=Yii::$app->db->createCommand(sprintf('select count(*) from %s where ip=:ip ',self::tableName()));
		$cmd->bindValue(':ip',$ip);
		return $cmd->queryScalar();

		//->where(['ip'=>$ip])->->count();
	}
}