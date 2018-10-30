<?php 

namespace app\controllers;

use Yii;
use app\models\BtUser;
use app\models\miner;
use app\models\miner\BtMinerForm;
use app\models\miner\BtMinerIPForm;
use app\models\miner\BtMinerPoolsultiForm;
use app\models\miner\BtMiner;
use yii\data\ActiveDataProvider;
use yii\web\HttpException;
use yii\Helpers\Html;
use yii\widgets\ActiveForm;
use yii\db\Query;
use yii\data\Pagination;
use yii\web\Response;

class MinerController extends \yii\web\Controller
{
	/** список майнеров ... */
	public function actionUserMinerslist(int $uid)
	{
		$u=	BtUser::findOne(intval($uid));
		if (!$u || $u->role!=BtUser::ROLE_OWNER)
			throw new HttpException(404,"Пользователь не найден. Перейти к ".Html::a('списку польователей',['bt/users'])); 
		
		$p=new ActiveDataProvider([
			'query'=>BtMiner::getallminerofuser($uid),
			'pagination'=>['pageSize'=>20],
			'sort'=>[
				'defaultOrder'=>['ip'=>SORT_ASC],
			],
		]);
		return $this->render('miners-list',['p'=>$p,'username'=>$u->name,'uid'=>$u->id,'role'=>$u->role]);
		
	}

	/** мониторинг активных майнеров ..  */ 
	public function actionUserMinerslistOnline(int $uid)
	{
		$u=	BtUser::findOne(intval($uid));
		if (!$u || $u->role!=BtUser::ROLE_OWNER)
			throw new HttpException(404,"Пользователь не найден. Перейти к ".Html::a('списку польователей',['bt/users'])); 

		return $this->render('miners-list-online',['username'=>$u->name,'uid'=>$u->id,'role'=>$u->role]);
	}

	/** редактирование майнера */
	public function actionUserMinerEdit($uid,$mid=0)
	{
		$u=BtUser::find()->where(['id'=>$uid])->one();//findOne(intval($uid));
		if (!$u || $u->role!=BtUser::ROLE_OWNER)
			throw new HttpException(404,"Неверный владелец. Перейти к ".Html::a('списку владельцев',['bt/users','role'=>BtUser::ROLE_OWNER]));
		
		///Yii::$app->session->addFlash('info',$mid);
		//if (!$mid)
		$m=new BtMinerForm(['id'=>$mid,'owner'=>$u->id]);

		
		// добавление удаление элементов пола ...
		if (Yii::$app->request->isAjax){
			$pd=Yii::$app->request->post();

			if ($m->load($pd)){
				// добавление пула ..
				if (isset($pd['addpool']))
					$m->addPool();
				// удаление пула 
				if (isset($pd['killpool']))
					$m->killpool($pd['killpool']);
				// обновление списков улов из майнеров  
				if (isset($pd['getpools']))
					$m->loadpools();

				return $this->renderPartial('miner-form-pools-list',['pools'=>$m->pools,'f'=>ActiveForm::begin()]);
		
			}
		}
		else
			if(Yii::$app->request->isPost){
				$pd=Yii::$app->request->post();
				// убить майнер ... 
				if (isset($pd['kill']) && !empty($m->id))
				{
					$m1=BtMiner::find()->where(['id'=>$m->id,'owner'=>$u->id])->limit(1)->one();
					if ($m1){
						$m1->delete();
						Yii::$app->session->addFlash('info',sprintf('Майнер %s удалён',$m1->ip));
						return $this->redirect(['miner/user-minerslist','uid'=>$u->id]);
					}
					// грузим майнер 
				}
				// сохранить данные по майнеру ...
				if (isset($pd['save']) && $m->saveForm($pd)){
					Yii::$app->session->addFlash('info','Данные майнера сохранены ');
					return $this->redirect(['miner/user-minerslist','uid'=>$u->id]);
				}
			}
		return $this->render('miner-edit',['m'=>$m,'role'=>$u->role,'username'=>$u->username,'uid'=>$u->id]);
	}


	// добавление майнерв по  ip диапазону .. 
	public function actionUserMinerAddperiprange($uid)
	{
		$u=BtUser::find()->where(['id'=>$uid])->one();//findOne(intval($uid));
		if (!$u || $u->role!=BtUser::ROLE_OWNER)
			throw new HttpException(404,"Неверный владелец. Перейти к ".Html::a('списку владельцев',['bt/users','role'=>BtUser::ROLE_OWNER]));

		$m=new BtMinerIPForm(['owner'=>$u->id]);

		if (Yii::$app->request->isPost && ($m->load(Yii::$app->request->post())) && ($m->validate())) {
			$data=$m->attributes;
			$data['countips']=ip2long($data['ipto'])-ip2long($data['ipfrom'])+1;
			Yii::$app->session->set('savedperip',$data);
			return $this->redirect(['add-miners-per-ip-todo','uid'=>$u->id]);
		}
		return $this->render('miner-multiaddperip',['m'=>$m,'role'=>$u->role,'username'=>$u->username,'uid'=>$u->id]);
	}

	//  пакетное добавление майнеров .. 
	public function actionAddMinersPerIpTodo($uid)
	{
		$u=BtUser::find()->where(['id'=>$uid])->one();//findOne(intval($uid));
		if (!$u || $u->role!=BtUser::ROLE_OWNER)
			throw new HttpException(404,"Неверный владелец. Перейти к ".Html::a('списку владельцев',['bt/users','role'=>BtUser::ROLE_OWNER]));

		$data=Yii::$app->session->get('savedperip');

		if (Yii::$app->request->isPost){
			Yii::$app->response->format=Response::FORMAT_JSON;
			$ip=Yii::$app->request->post('ip');
			$st='';
			// проверка наличия ip в базе ..
			if (BtMiner::existsIpInDB($ip))
				$st='Есть в базе ';
			
			else
			{
				// добавляем майнеры через форму .. 
				$confar=[];
				foreach(['port','login','password','interval','status','owner'] as $k)
					$confar[$k]=$data[$k];
				$confar['updatepools']=false;
				$confar['ip']=$ip;

				$m=new BtMinerForm($confar);
				// валидация ... проверка состояния .. 
				if ($m->validate()){
					$m->save();
					$st='Добавлено в базу ';	
				}
				else
					$st='Ошибка валидации: '.implode('<br/>',$m->geterrorsummary(true));
				

				// тут добавляем в базу со всеми вытекающими ...
			}

			return [
				'status'=>$st?$st:'ok',
				'nextip'=>long2ip(ip2long($ip)+1),
				'end'=>$ip==$data['ipto'],
			];
		}
		if (empty($data))
			return $this->goBack();
		

		return $this->render('multiadd-per-ip-todo',['data'=>$data,'role'=>$u->role,'username'=>$u->username,'uid'=>$u->id]);
	}

	// форма массового обновления пулов  
	public function actionUserMinerPoolsUpd($uid)
	{
		$u=BtUser::find()->where(['id'=>$uid])->one();//findOne(intval($uid));
		if (!$u || $u->role!=BtUser::ROLE_OWNER)
			throw new HttpException(404,"Неверный владелец. Перейти к ".Html::a('списку владельцев',['bt/users','role'=>BtUser::ROLE_OWNER]));
		if (Yii::$app->request->isAjax ){
			Yii::$app->response->format=Response::FORMAT_JSON;
			$post=Yii::$app->request->post();
			
			if (isset($post['addpool'])){
				Yii::info($post,'$post ajax');
				return 'ad';
			}


		}
		$m= new BtMinerPoolsultiForm();
		return $this->render('miner-pools-upd',['m'=>$m,'role'=>$u->role,'username'=>$u->username,'uid'=>$u->id]);
	}
	
	/** действо по обновлению  майнера */
	public function actionUserMinerUp($uid,$mid)
	{
		if ($mid && $uid)
			$m=BtMiner::findOne(['id'=>$mid,'owner'=>$uid]);

		if (!$m )
			throw new HttpException(404,"Майнер не найден");
		$m->minerUpdate();
		return $this->render('miner-up');
	}

	/** действо просмотр майнера */
	public function actionUserMinerView($uid,$mid)
	{
		$u=BtUser::findIdentity($uid);
		if (!$u || $u->role!=BtUser::ROLE_OWNER)
			throw new HttpException(404,"Неверный владелец. Перейти к ".Html::a('списку владельцев',['bt/users','role'=>BtUser::ROLE_OWNER]));
		$m=BtMiner::findOne($mid);
		if (!$m || $m->owner!=$u->id)
			throw new HttpException(404,"нет такого майнера. Перейти к ".Html::a('списку майнеров',['miner/user-minerslist','uid'=>$u->id]));

		$q=new Query();
		$q->from('bt_miner_history')->where(['id'=>$mid]);
		$qc=clone $q;

		$pages=new Pagination(['pageSize'=>50,'totalCount'=>$qc->count()]);
		$res=$q->limit($pages->limit)->offset($pages->offset)->all();
		$tempcount=0;
		foreach($res as $x=>$y){
			BtMiner::prepareStatisticRow($res[$x]);
			$tempcount=max($tempcount,count($res[$x]['temp']));
		}
		return $this->render('miner-view',[
			'pager'=>$pages,
			'data'=>$res,
			'tc'=>$tempcount,
			'role'=>$u->role,
			'uid'=>$u->id,
			'username'=>$u->username,
			'mid'=>$m->id,
			'ipport'=>$m->ip.':'.$m->port,
		]);
	}

	/** обновление статистики майнера  */
	public function actionUserMinerStatup(int $uid,int $mid)
	{
		$m=BtMiner::find()->where(['id'=>$mid,'owner'=>$uid])->limit(1)->one();
		if (!$m)
			throw new HttpException(404,"не могу обновить");
		
		Yii::$app->response->format=Response::FORMAT_JSON;
		if ($t=$m->minerUpdate())
			return ['updin'=>date('Y-m-d H:i:s',$t)];
		

		///

		return ['err'=>true,'errstr'=>$m->errorstring];
		//$this->render('miner-up');//['aa'=>5,'ip'=>$m->ip];
	}

	// ================================== ячейки .. ==============
	/** ячейка Время обнвления в списке майнеров */
	public static function minerTimeUpdate($m)
	{
		$a=Html::a('Up',['miner/user-miner-statup','uid'=>$m->owner,'mid'=>$m->id],['class'=>'link-update-miner']);
		return '<span class="date-upd">'.($m->timeupd?$m->timeupd:'-').'</span>&nbsp'.($m->status?$a:'');
	}
	/** Ячейка ip списка майнеров */
	public static function minerIp($m)
	{	
		$edit=Html::a('Править',['miner/user-miner-edit','mid'=>$m->id,'uid'=>$m->owner]);;
		$view=Html::a('Просмотр',['miner/user-miner-view','mid'=>$m->id,'uid'=>$m->owner]);;

		return $m->ip.':'.$m->port.'&nbsp;'.$edit.'&nbsp;'.$view; 
	}

	/**  отдать данные майнеров в json */
	public function actionUserMinerslistGetdata($uid){
		$at=BtMiner::getLastStatistic($uid);

		Yii::$app->response->format=Response::FORMAT_JSON;
		//d(['asd'=>55,'uid'=>$uid]);

		return $at;
	}

	// перезапуск майнера .  
	public function actionUserMinerToreboot($uid,$mid)
	{
		$m=BtMiner::find()->where(['id'=>$mid,'owner'=>$uid])->limit(1)->one();
		if (!$m)
			throw new HttpException(404,"не нашел");
		//$m->attachssh();

		Yii::$app->response->format=Response::FORMAT_JSON;
		return [
			'status'=>$m->reboot()?'ok':'fail',
		]; 
	}


}