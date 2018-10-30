<?php 
use yii\helpers\Html; 
use app\controllers\MinerController;
use yii\widgets\Menu;

$this->registerJsFile('/js/miner-updater.js',['depends'=>[\yii\web\JqueryAsset::className()]]);
$this->params['breadcrumbs']=[
			['label'=>'Список владельцев майнерв','url'=>['bt/users','role'=>$role]],
			['label'=>'Редактирование пользователя '.$username,'url'=>['bt/user-edit','uid'=>$uid]],
			'Майнеры'
		];
?>
<h2>Список майнеров пользователя <i><?=$username;?></i></h2>
<?=Menu::widget(['items'=>[
	['label'=>'Майнеры online','url'=>['miner/user-minerslist-online','uid'=>$uid]],
	['label'=>'Добавить майнер','url'=>['miner/user-miner-edit','uid'=>$uid]],
	['label'=>'Добавить майнеры по диапазону IP','url'=>['miner/user-miner-addperiprange','uid'=>$uid]],
	['label'=>'Массовое обновление пулов','url'=>['miner/user-miner-pools-upd','uid'=>$uid]]
	]]);?>



 <?php 
echo \yii\grid\GridView::widget([
	'dataProvider'=>$p,
	'formatter'=>new \app\components\BtFormatter(),
	'columns'=>[
		['attribute'=>'ip','content'=>[MinerController::className(),'minerIp']],
		'login',
		'password',
		['content'=>[MinerController::className(),'minerTimeUpdate'],'label'=>'Время обновления','attribute'=>'timeupd'],
		'interval',
		'status:minerstatusvis'
	],
]);


?>



