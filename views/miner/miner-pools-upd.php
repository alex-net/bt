<?php 
use yii\helpers\Html;

$this->params['breadcrumbs']=[
			['label'=>'Список владельцев майнерв','url'=>['bt/users','role'=>$role]],
			['label'=>'Редактирование пользователя '.$username,'url'=>['bt/user-edit','uid'=>$uid]],
			['label'=>'Майнеры ','url'=>['miner/user-minerslist','uid'=>$uid]],
		];
?>

<h2>Обновить пулы для майнеров </h2>
<?php $f=\yii\widgets\ActiveForm::begin(['options'=>['class'=>'miner-form'],'enableClientValidation'=>false,'id'=>'minereditor']);?>

<div class="pools-list">
	<?=$this->render('miner-form-pools-list',['pools'=>$m->pools,'f'=>$f]);?>
	
</div>


<?php \yii\widgets\ActiveForm::end();?>