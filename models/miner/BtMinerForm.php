<?php 

namespace app\models\miner;

use Yii;
use yii\base\Model;


class BtMinerForm extends BtMinerBaseForm
{

	public $id; // id записи ...
	public $ip;// IP адрес 
	public $updatepools=true; // обновить пулы на манере .

	private $_pools; // набоор пулов . 

	public function __construct($conf=[])
	{
		parent::__construct($conf);
		$this->_pools=[];
		if ($this->id && ($m=BtMiner::find()->where(['id'=>$this->id])->limit(1)->one())) {
			// присвоение общих полей .. 
			foreach(['id','ip','port','login','password','interval','status'] as $f)
				if (!empty($m->$f))
					$this->$f=$m->$f;
			// загрузка пулов ...
			foreach($m->pools->getValue() as $pool){
				$pool=explode('|',$pool);
				$this->_pools[]=new BtMinerPoolForm(['url'=>$pool[0],'login'=>$pool[1],'password'=>$pool[2]]);
			}
		}

	}


	/** вернть пулы */
	public function getPools()
	{
		return $this->_pools;
	}

	public function attributeLabels()
	{
		$l=parent::attributeLabels();
		$l=array_merge($l,[
			'updatepools'=>'обновить пулы на майнер',
		]);
		return $l;
	}

	public function rules()
	{
		$ruls=parent::rules();
		$ruls=array_merge($ruls,[
			[['ip'],'required'],
			['ip','\yii\validators\IpValidator'],
			[['ip','port','password','login'],'serveracces','params'=>['fields'=>['ip','port','password','login']]],
			['updatepools','\yii\validators\BooleanValidator'],
		]);
		return $ruls;
	}
	// присоединить корректор 
	private function attachConnector()
	{
		if (empty($this->behaviors['minerssh']))
			$this->attachBehavior('minerssh',[
					'class'=>\app\components\MinerConnector::className(),
					'fip'=>'ip','fport'=>'port','flogin'=>'login','fpassword'=>'password',
				]);
	}
	/** валидируем данные на длступ к майнеру */
	public function serveracces($attr,$param)
	{
		static $v=false;
		if (!$v){
			$v=true;
			// майнер  новый .. 
			if (empty($this->id) && BtMiner::find()->where(['ip'=>$this->ip])->count())
				// проверяем ip ...
				$this->addError('ip','Майнер с данным ip уже есть в базе ..');
				
			
			// стыкуем  коннектор ..
			$this->attachConnector();

			// запрашиваем данные ... 
			if (!$this->testminer()){
				foreach($param['fields'] as $x)
					$this->addError($x,$this->MinertErrorStr);	
			}

		}
	}

	/** загрузка .. данных их формы */
	public function load($data,$fn=null)
	{
		$res=parent::load($data,$fn);
		$this->_pools=[];
		if (isset($data['BtMinerPoolForm']))
		{
			for ($i=0; $i <count($data['BtMinerPoolForm']); $i++) 
				$this->_pools[$i]=new BtMinerPoolForm();
			$res = $res && Model::loadMultiple($this->_pools,$data);
		}
		return $res;
	}

	/** валидация  */
	public function validate($an=null,$ce=true)
	{
		$err=parent::validate($an,$ce) ;
		if ($this->_pools)
			$err=Model::validateMultiple($this->_pools) && $err;
		return $err; 
	}
	/** добавлене пула .. */
	public function addPool()
	{
		$this->_pools[]=new BtMinerPoolForm();
	}

	/** удаление пула  */
	public function killpool($poolid)
	{
		if(isset($this->_pools[$poolid]))
			unset($this->_pools[$poolid]);
		$this->_pools=array_values($this->_pools);
	}
	/** загрузка пулов из майнера .*/
	public function loadpools()
	{
		// жахнуть все пулы ...
		$this->_pools=[];
		// подключаем коннектр 
		$this->attachConnector();
		foreach($this->getPoolsList() as $p)
			$this->_pools[]=new BtMinerPoolForm(['url'=>$p['url'],'login'=>$p['user'],'password'=>$p['pass']]);

		//$this->_pools[]=new BtMinerPoolForm(['url'=>'url','login'=>'login','password'=>'pass']);

	}

	/** получить атрибуты модули .. */
	public function getAttributes($names=null,$except=[])
	{
		$attr=parent::getAttributes($names,$except);
		$attr['pools']=[];
		foreach ($this->_pools as $pool)
			$attr['pools'][]=$pool->attributes;
		return $attr;
	}


	/** сохранение данных из формы  */
	public function saveForm($data)
	{
		if (!$this->load($data) || !$this->validate())
			return false;
		return $this->save();
	}

	public function save()
	{
		// сохраняем основные данные... 
		$m=empty($this->id)?new BtMiner() : BtMiner::findOne($this->id);

		foreach(['ip','port','login','password','status','interval','owner'] as $f)
			//if (isset($this->attributes[$f]))
				$m->$f=$this->$f;

		if ($this->id)
			$m->id=$this->id;

		// собираем пулы . 
		$pools=[];
		
		$nopools=empty($this->pools);
		if ($nopools){
			// подгружаем полы к себе юю
			$this->loadpools();
			//$pools=
			//Yii::info($this->_pools,'pools from miner');
		}
	
		foreach($this->pools as $p){
			$p=array_map('trim',$p->attributes);
			$pools[]=implode('|', $p);
		}
		
		$m->pools=$pools;
		// сохраняем .. 
		$m->save();

		// если надо сохранить пулы в майнер .. то делаем колдунства .. 
		if ($this->updatepools && !$nopools && $this->id)
			$m->savePoolsToMiner();

		return true;
	}
}