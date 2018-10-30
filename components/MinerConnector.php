<?php 
/// https://github.com/ckolivas/cgminer/blob/master/API-README
namespace app\components;

use Yii;

class MinerConnector extends \yii\base\Behavior
{
	public $fip;// поля ip 
	public $fport;// порт 
	public $flogin; // логин
	public $fpassword; // пароль

	private $ip;// ip
	private $port;
	private $login;
	private $password;

	private $sshresult=null;// сырые данные тут 
	private $statdata=[];// данные по статистике .. 
	private $errstr;
	private $_minerid ;// id манера ..

	const CONFIG_FILEPATH='/config/bmminer.conf';
	private $resourceid=null;

	public function attach($owner)
	{
		parent::attach($owner);
		$this->ip=$this->owner->{$this->fip};
		$this->port=$this->owner->{$this->fport};
		$this->login=$this->owner->{$this->flogin};
		$this->password=$this->owner->{$this->fpassword};
	}

	public function getStatData(){return $this->statdata;}
	public function getMinertErrorStr(){return $this->errstr;}

	
	/** вернуть текст ошибки ..  */
	public function getErrorString()
	{
		return $this->errstr;
	}

	/** запрос статистики ...  */
	public function testminer()
	{
		return $this->executecommandSsh('stats');
	}
	/** запрс списка команд ... */
	public function cmdsapply($cmds)
	{
		$ressmas=[];
		foreach($cmds as $c){
			$res=$this->executecommandSsh($c);
			$ressmas[]=[
				'cmd'=>$c,
				'result'=>$res,
				'data'=>$this->sshresult,
				'err'=>$this->errstr,
			];
		}
		return $ressmas;
	}

	/** подключение */ 
	private function connect()
	{
		if ($this->resourceid)
			return $this->resourceid;
		$this->errstr='';
		$this->sshresult=null;

		$res=@ssh2_connect($this->ip,$this->port);

		if (!$res){
			$this->errstr='Соединение отсуствует';
			Yii::error($this->errstr,'miner');
			return false;
		}

		if(!@ssh2_auth_password($res,$this->login,$this->password)){
			$this->errstr='Не авторезовались';
			Yii::error($this->errstr,'miner');
			return false;
		}

		Yii::info('Авторезовались','miner');
		$this->resourceid=$res;
		return true;
		// выполняем команду .. 
	}

	/** запрос команд к bmminer-api через ssh */
	private function executecommandSsh($cmd)
	{
		// подключение ..
		if (!$this->connect())
			return false;

		$this->errstr='';
		$this->sshresult=null;
		$this->statdata=[];

		$stream=ssh2_exec($this->resourceid,"bmminer-api '$cmd'");
		if ($stream===false){
			$this->errstr='ошибка выполнения команды '.$cmd;
			Yii::error($this->errstr,'miner');
			return false;
		}
		// переводим поток а блочный режим .. 
		stream_set_blocking($stream,true);
		// http://php.net/manual/en/function.ssh2-exec.php
		//$res=fgets($stream);
		//$res=preg_replace('#^Reply was\s*\'(.*?)\'$#i','$1',$res);
		
		//$res=json_decode($res);
		// читаем из потока .. 
		$res='';
		while($s=stream_get_contents($stream,1024))
			$res.=$s;
	

		if (preg_match("#'([^']+)'#im", $res,$res2)){

			$res2=explode('|',trim(end($res2),'|'));
			foreach($res2 as $x=>$el){
				//$arr=[];
				//$name='';
				foreach(explode(',',$el) as $el2)
					if (strpos($el2,'=')!==false){
						list($k,$v)=explode('=',$el2);
						switch($cmd){
							case 'stats':
								$this->detectparams($k,$v);
								break;
							default:
								$this->statdata[$x][$k]=$v;	

						}
						//$arr[$k]=$v;
					}
			}
			if (!empty($this->statdata))
				switch($cmd){
					/*case 'pools':
						$pools=[];
						foreach ($this->statdata as $p)
							if (isset($p['POOL']))
								$pools[]=[
									'url'=>$p['URL'],
									'user'=>$p['User'],
									'pass'=>'x',
								];
						$this->statdata=$pools;	
					break;*/
					case 'stats':
						//$this->_minerid=empty($this->statdata['miner_id'])?'':$this->statdata['miner_id'];
					break;
				}
	
			
		}
		else 
			$this->errstr=$res;
		//Yii::info($res,'miner before');
		//Yii::info($this->statdata,'miner');
		return true;

	}
	/** определение параметров из массива команда получения статистики .  команда stats. */
	private function detectparams($name,$value)
	{
		switch($name){
			case 'Elapsed': // время работы в секундах 
				$this->statdata[$name]=intval($value);
				break;
			case 'GHS 5s': // производительность в Гхешах ...за пять секунд 
				$this->statdata[$name]=floatval($value);
				break;
			case 'GHS av':// производительность в Гхешах средняяд ... 
				$this->statdata[$name]=floatval($value);
				break;
			/*case 'miner_id': // внешний идентификатор майнера ..аля mac адрес 
				$this->statdata[$name]=$value;
				break;*/

			default:
				// скорости работы вентияторов ..
				if(preg_match('#fan(\d+)#i', $name,$t))
					$this->statdata['fans'][end($t)]= intval($value);
				// температуры ...на платах 
				if(preg_match('#temp(\d+)_?(\d*)#i', $name,$t)){
					$r=strpos($name, '_')===false?1:intval($t[1]);
					$i=strpos($name, '_')===false?intval($t[1]):intval($t[2]);
					//Yii::info([$t,'r'=>$r,'i'=>$i],'temp');
					$this->statdata['temps'][$r][$i]=intval($value);
				}
				// средняя чатота работы .. 
				if (preg_match('#freq_avg(\d+)#', $name,$t))
					$this->statdata['freq_avgs'][intval(end($t))]=floatval($value);
				// идеальная производительность  .. 
				if (preg_match('#chain_rateideal(\d+)#', $name,$t))
					$this->statdata['chain_rateideals'][intval(end($t))]=floatval($value);
				// количество рабочих чипов 
				if(preg_match('#chain_acn(\d+)#', $name,$t))
					$this->statdata['chain_acns'][intval( end($t))]=intval($value);
				// реальная производительность 
				if(preg_match('#chain_rate(\d+)#', $name,$t))
					$this->statdata['chain_rates'][intval(end($t))]=intval($value);


		}
			
	}

	/** запрос конфигурации  */ 
	public function getMinerConfig()
	{
		// подключение ..
		if (!$this->connect())
			return false;
		
		// вполняем запрос файла конфига мафнера ..
		$stream=ssh2_exec($this->resourceid,"cat ".self::CONFIG_FILEPATH);
		if ($stream===false){
			$this->errstr='ошибка выполнения команды '.$cmd;
			//Yii::error($this->errstr,'miner');
			return false;
		}
		// переводим поток а блочный режим .. 
		stream_set_blocking($stream,true);
		// http://php.net/manual/en/function.ssh2-exec.php
		//$res=fgets($stream);
		//$res=preg_replace('#^Reply was\s*\'(.*?)\'$#i','$1',$res);
		
		//$res=json_decode($res);
		// читаем из потока .. 
		$res='';
		while($s=stream_get_contents($stream,1024))
			$res.=$s;
		$res=json_decode( preg_replace('#^[^\{]+#','',$res),1);
		return $res;
	}

	// сохраняем конфигурацию ... 
	public function setMinerConfig($data)
	{
		if (!$this->connect())
			return false;

		$cfg=json_encode($data);
		// создать файл временный .и сгрузить туда  данные .. 
		$fn=tempnam(sys_get_temp_dir(), 'bt-yii2-');
		$f=fopen($fn, 'w');
		fwrite($f, $cfg);
		fclose($f);

		// смена прав ..
		$otv=ssh2_exec($this->resourceid,'chmod u+w '.self::CONFIG_FILEPATH);
		//Yii::info($otv===false,'error chmod1');

		// льём новый  файл конфига ..
		$otv=ssh2_scp_send($this->resourceid, $fn, self::CONFIG_FILEPATH);
		//if (!$otv)
			//Yii::error('Файл не залит');
		// меняем права обратно ... 
		$otv=ssh2_exec($this->resourceid,'chmod u-w '.self::CONFIG_FILEPATH);
		//Yii::info($otv===false,'error chmod2');

		// отправляем майнер в ребут .. 
		//ssh2_exec($this->resourceid, 'bmminer-api restart');
		ssh2_exec($this->resourceid, '/etc/init.d/bmminer.sh restart');
		// бахнуть временный файл...
		unlink($fn);

		return true;
		//Yii::info($fn,'tempfile');
	}

	/* запрос списков пулов ..*/
	public function getPoolsList()
	{
		if (!$this->connect())
			return false;
		$cfg=$this->minerConfig;
		if (!empty($cfg['pools']))
			return $cfg['pools'];
		return [];

		
	}

	/** перезапуск .. */
	public function reboottodo()
	{
		if (!$this->connect())
			return false;
		// ребут ...
		ssh2_exec($this->resourceid, '/etc/init.d/bmminer.sh restart');
		return true;
	}
}