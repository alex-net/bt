<?php 

namespace app\controllers;

use app\models\BtUser;
use app\models\BtMiner;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\HttpException;
use yii\data\ActiveDataProvider;

class BtController extends \yii\web\Controller
{

	public function actions()
	{
		return ['error'=>['class'=>'yii\web\ErrorAction']];
	}

	public function actionIndex()
	{
		if (Yii::$app->user->isGuest)
			return $this->loginpage();
		return $this->profile();	
	}
	// форма логина и её  обработка 
	private function loginpage()
	{
		$u=new BtUser();
		$u->scenario=BtUser::SCENARIO_LOGIN;
		if(Yii::$app->request->isPost && $u->load(Yii::$app->request->post()) && $u->validate()){
			$u1=BtUser::getByMail($u->mail);
			Yii::info($u1,'$u1');
			Yii::$app->user->login($u1);
			return $this->refresh();
		}
		return $this->render('login-form',['u'=>$u]);
	}
	// страница профиля ...
	private function profile()
	{
		$u=Yii::$app->user->identity;
		$u->scenario=BtUser::SCENARIO_PROFILE_EDIT;
		if (Yii::$app->request->isPost && $u->saverecord(Yii::$app->request->post())){
			Yii::$app->session->setFlash('info','Данные профиля обновлены');
			return $this->refresh();
		}

		$u->pass='';

		return  $this->render('user-profile',['u'=>$u]);
	}
	/** выход из системы */
	public function actionLogout()
	{
		if(!Yii::$app->user->isGuest)
			Yii::$app->user->logout();

		return $this->redirect(['/']);
	}

	/** страницы работы с пользователями */
	public function actionUsers()
	{
		
		// если роль не задана показываем владельцев 
		$role=Yii::$app->request->get('role',BtUser::ROLE_OWNER);
		$p=new \yii\data\SqlDataProvider([
			//'query'=>BtUser::getAllUsers($role)->all(),
			'sql'=>BtUser::getAllUsers($role),
			'params'=>[':qp0'=>$role],
			'pagination'=>['pageSize'=>20],
			'sort'=>new \yii\data\Sort(['attributes'=>['id','mail','status']]),
			//'totalCount'=>1,
			
		]);
		return $this->render('users-list',['prov'=>$p,'role'=>$role]);
	}


	/** Правка юзера .. */
	public function actionUserEdit( int $uid=0)
	{
		if ($uid){
			$u=	BtUser::findOne(intval($uid));
			if (!$u)
				throw new HttpException(404,"Пользователь не найден. Перейти к ".Html::a('списку пользователей',['bt/users']));
				
				// проверяем майнеров.. 
				if ($u->role==BtUser::ROLE_OWNER && $miners=Yii::$app->request->get('miners')){
					// показываем список манеров ... \
					return 'adasd miner list';
				}
				// редаетирование юзера . 
				$u->scenario=BtUser::SCENARIO_EDITUSER;

			}
			else{
				// новый юзер .. 
				$u=new BtUser();
				$u->role=BtUser::ROLE_OWNER;
				$u->scenario=BtUser::SCENARIO_NEWUSER;
			}
			
			$u->pass='';
			// сохранение пользвателя ..
			if (Yii::$app->request->isPost){
				// пристыковать событие .. 
				$post=Yii::$app->request->post();
				if ($u->saverecord($post)){
					if (isset($post['save']) )
						Yii::$app->session->addFlash('info','пользователь сохранён');
					if (isset($post['kill']))
						Yii::$app->session->addFlash('info','пользователь удалён');
				}
				
				return $this->redirect(['bt/users','role'=>$u->role]);
			}

			return $this->render('user-edit',['u'=>$u]);
	}

	
}