<?php 
use yii\helpers\Html;
use app\models\BtUser;
?>
<?php if ($role==BtUser::ROLE_OWNER):?>
<h2>Пользователи (владельцы майнеров)</h2>
<?php else:?>
	<h2>Пользователи (Админы)</h2>
<?php  endif;?>

<?php 
$columns=[
	['attribute'=>'id','label'=>'№'],
	['attribute'=>'status','label'=>'Статус','format'=>'userstatusvis'],
	['attribute'=>'name','label'=>'Ник'],
	['attribute'=>'mail','label'=>'Почта','content'=>function($m){return Html::a($m['mail'],['bt/user-edit','uid'=>$m['id'] ]);}],
];
if ($role==BtUser::ROLE_OWNER)
	$columns[]=['label'=>'Майнеры','content'=>function($m){return Html::a(sprintf('%d / %d',$m['mtc'],$m['mac']),['miner/user-minerslist','uid'=>$m['id']]);}];
	// http://bt.energo-uchet.ru/users/6/miners

echo Html::a('Добавить нового пользователя',['bt/user-edit']);
echo \yii\grid\GridView::widget([
	'dataProvider'=>$prov,
	'formatter'=>new \app\components\BtFormatter(),
	'columns'=>$columns,
	/*'columns'=>[
		'id','name',['label'=>'Майнеры','content'=>[BtUser::className(),'mainerscounts']],['attribute'=>'mail','content'=>function($m){return Html::a($m->mail,['bt/user-edit','uid'=>$m->id]);}],'status:userstatusvis','created','lastlogin'
	],*/

]);
?>