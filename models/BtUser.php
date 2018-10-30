<?php 

namespace app\models;
use Yii;

class BtUser extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{
	const SCENARIO_LOGIN='user-login';
	const SCENARIO_PROFILE_EDIT='user-profile-edit';
	const SCENARIO_EDITUSER='user account edit';
	const SCENARIO_NEWUSER='new user account';

	const EVENT_USER_OWNER_KILL='kill user';

	const ROLE_SADMIN='s-admin';
	const ROLE_ADMIN='admin';
	const ROLE_OWNER='owner';
	const ROLE_ANONIM='null';
	
	static $rolesdescr=[
		self::ROLE_SADMIN=>'Супер админ',
		self::ROLE_ADMIN=>'Админ',
		self::ROLE_OWNER=>'Владелец майнера',
	];
	
	// поик по id 
	public static function findIdentity($id)
	{
		return self::findOne($id);
	}
	// поиск по токену 
	public static function findIdentityByAccessToken($token,$type='')
	{
		return self::findOne(['token'=>$token]);
	}

	// вернуть айдишник 
	public function getId()
	{
		return $this->id;
	}

	public function getAuthKey()
	{
		return null;
	}
	public function validateAuthKey($key)
	{
		return false;
	}

	public function attributeLabels()
	{
		return [
			'mail'=>'Почта',
			'pass'=>'Пароль',
			'name'=>'Имя',
			'status'=>'Активен',
			'role'=>'роль',
			'created'=>'Создан',
			'lastlogin'=>'Последний вход',
			'minerscount'=>'Число майнеров',
		];
	}

	public static function getByMail($mail)
	{
		return self::find()->where(['mail'=>$mail])->limit(1)->one();
	}

	public function rules()
	{


		return [
			['mail','required'],
			['mail','email'],
			['mail','userdetect'],
			['pass','required','on'=>[self::SCENARIO_LOGIN,self::SCENARIO_NEWUSER]],
			['pass','safe','on'=>[self::SCENARIO_PROFILE_EDIT,self::SCENARIO_EDITUSER]],

			['name','required','on'=>[self::SCENARIO_PROFILE_EDIT,self::SCENARIO_EDITUSER,self::SCENARIO_NEWUSER]],
			
			['status','boolean','on'=>[self::SCENARIO_EDITUSER,self::SCENARIO_NEWUSER]],
			['role','required','on'=>[self::SCENARIO_EDITUSER,self::SCENARIO_NEWUSER]],
			['role','yii\validators\RangeValidator','range'=>array_keys($this->alowedRolesList),'on'=>[self::SCENARIO_EDITUSER,self::SCENARIO_NEWUSER]],
		];
	}

	public function beforeSave($isIns)
	{
		if (!parent::beforeSave($isIns))
			return false;

		if ($isIns)// новая запись .. 
			$this->token=Yii::$app->security->generateRandomString(60);
		// нужно сконветить пароль ...
		if ($this->pass)
			$this->pass=Yii::$app->security->generatePasswordHash($this->pass);
		else{
			// грузим юзера и вешаем оттуда пароль
			$u=self::findOne($this->id);
			$this->pass=$u->pass;

		}
		return true;
	}

	/** валидация наличия юзера в базе .. */ 
	public function userdetect($attr,$param)
	{
		$u=self::findOne(['mail'=>$this->mail]);
		//Yii::info([$this->scenario,$this->pass,$u->pass],'scenario');
		switch($this->scenario){
			case self::SCENARIO_LOGIN:
				if (!$u || !Yii::$app->security->validatePassword($this->pass,$u->pass) ){
					$this->addError('mail','неверный логин или пароль');
					$this->addError('pass','неверный логин или пароль');
				}
				if (!$u->status){
					$this->addError('mail','учётная запись не существует или заблокирована');
					$this->addError('pass','учётная запись не существует или заблокирована');	
				}

				break;
			case self::SCENARIO_PROFILE_EDIT:
			case self::SCENARIO_EDITUSER:
				if ($u && $this->id!=$u->id)
					$this->addError('mail','Ящик уже занят');
				break;
			case self::SCENARIO_NEWUSER:
				if ($u)
					$this->addError('mail','Ящик уже занят');
				break;
		}
	}
	/** вернуть допустимй списк ролей для создания юзера  */
	public function getAlowedRolesList()
	{
		$roles=[];
		if (in_array($this->scenario, [self::SCENARIO_EDITUSER,self::SCENARIO_NEWUSER])){
			$roles[self::ROLE_OWNER]=self::$rolesdescr[self::ROLE_OWNER];
			if (Yii::$app->params['urole']==self::ROLE_SADMIN)
				$roles[self::ROLE_ADMIN]=self::$rolesdescr[self::ROLE_ADMIN];
		}

		return $roles;
	}

	/**  как зовут юзера .. */ 
	public function getUsername()
	{
		return $this->name;
	}
	
	/** обноаение данных  профиля .. */
	public function saverecord($data)
	{
		if ($this->load($data) && $this->validate() ){
			if(isset($data['save']));
				$this->save();
			if (isset($data['kill'])){
				//$this->delete();
				if ($this->role==self::ROLE_OWNER)
					$this->trigger(self::EVENT_USER_OWNER_KILL);
			}
			return true;
		}
		return false;
	}

	/** обновление даты входа для юера */
	public static function userloginupd($event)
	{
		//$event->identity->lastlogin=date('c');
		Yii::$app->db->createCommand()->update(self::tableName(),['lastlogin'=>date('c')],'id = '.$event->identity->id)->execute();

		//$event->identity->save();
	}

	/** список всех владельцев майнеров ...*/
	public static function getAllUsers($role=self::ROLE_OWNER)
	{
		$role=in_array($role, [self::ROLE_OWNER,self::ROLE_ADMIN])?$role:'';
		return (new yii\db\Query())->select(['u.id','u.name','u.mail','u.status','u.created','u.lastlogin','mtc'=>'count(m.*)','mac'=>'sum(m.status::int)'])->from(['u'=>'bt_user'])->where(['role'=>$role])->leftjoin(['m'=>'bt_miner'],'m.owner=u.id')->groupBy('u.id')->createCommand()->sql;
		
	}

	/** получаем информацию  о пользователе ...*/
	public static function info($id)
	{
		static $us=[];
		if(!$id)
			return [];
		if (isset($us[$id]))
			return $us[$id];

		$u=self::find()->where(['id'=>$id])->limit(1)->one();
		if (!$u)
			$us[$id]=[];
		else
			$us[$id]=$u->attributes;
		return $us[$id];
	}

	// ============================================
	/** определяем права на действия */
	public static function todoactions($e)
	{

		$role=self::ROLE_ANONIM;
		if (!Yii::$app->user->isGuest){
			$role=Yii::$app->user->identity->role;
			// ралогинить если статус сброшен ...
			if (!Yii::$app->user->identity->status)
				Yii::$app->user->logout();
		}
		Yii::$app->params['urole']=$role;

		$c=$e->action->controller->id;
		$a=$e->action->id;
		$get=Yii::$app->request->get();
		switch($c){
			case 'bt':
				Yii::info($a,'action');
				switch($a){
					// редактирование пользователя 
					case 'user-edit':
						if (!in_array($role,[self::ROLE_ADMIN,self::ROLE_SADMIN]))
							throw new \yii\web\HttpException(403,"Error Processing Request");
					/// просмотр списка пользователей .. 
					case 'users':
						if (!in_array($role,[self::ROLE_ADMIN,self::ROLE_SADMIN]) || $role==self::ROLE_ADMIN && Yii::$app->request->get('role')==self::ROLE_ADMIN)
							throw new \yii\web\HttpException(403,"Error Processing Request");
					break;
					//  просмотр и редактирование майнеров .. 
					case 'user-miner-edit':
					case 'user-miner-view':
					case 'user-miner-up':
					case 'user-minerslist':
						
					
						/*$uid=Yii::$app->request->get('uid');
						Yii::info($uid,'uid from edit');
						if (($uid=Yii::$app->request->get('uid')) && ($uid=self::findOne($uid)) && ($role==$uid->role ||$uid->role == self::ROLE_SADMIN ) )
							throw new \yii\web\HttpException(403,"Error Processing Request");*/
					break;	
				}
			break;
			case 'miner':
				switch($a){
					case 'user-minerslist': // список майнеров .. 
					case 'user-minerslist-getdata': // список майнеров .. 
					case 'user-minerslist-online': // страница с онлайн данными майнеров .. 
					case 'user-miner-addperiprange':// добавить  майнер  по ip диапазону ..
					case 'user-miner-pools-upd': // массовое обновление пулов ..
					case 'user-miner-edit':// редактировать майнер  
					case 'user-miner-view': // промотр майнера 
					case 'user-miner-statup': // обновить статитику ..
					case 'user-miner-toreboot':// презапуск майнера ..
						if (!in_array($role, [self::ROLE_ADMIN,self::ROLE_SADMIN,self::ROLE_OWNER]))
							throw new \yii\web\HttpException(404,"Error Processing Request");
							// чужой владелец .. .
						if ($role==self::ROLE_OWNER && $get['uid']!=Yii::$app->user->identity->id)	
							throw new HttpException(403,"Error Processing Request");
							
					break;
				}
			break;
		}
		// забить всех кроме сообщений об ошибке .. 
		if ($e->action->controller->id!='bt' || $e->action->id!='error')
		{
			//$e->isValid=false;
			//throw new \yii\web\HttpException(403,"Error Processing Request");
		}
		//d(Yii::$app->user);
		///todoactions
		
	}



}