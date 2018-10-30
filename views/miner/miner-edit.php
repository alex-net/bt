<?php 
//use yii\widgets\Pjax;

use yii\helpers\Html;

$this->params['breadcrumbs']=[
			['label'=>'Список владельцев майнерв','url'=>['bt/users','role'=>$role]],
			['label'=>'Редактирование пользователя '.$username,'url'=>['bt/user-edit','uid'=>$uid]],
			['label'=>'Майнеры ','url'=>['miner/user-minerslist','uid'=>$uid]],
		];
?>
<?php if ($m->id):?>
<h2>Редактирование майнера</h2>
<?php else:?>
<h2>Создание майнера</h2>
<?php endif;?>


<?php $f=\yii\widgets\ActiveForm::begin(['options'=>['class'=>'miner-form'],'enableClientValidation'=>false,'id'=>'minereditor']);?>
<div class="grunt-elements">
	<?=$f->field($m,'ip');?>
	<?=$f->field($m,'port');?>
	<?=$f->field($m,'login');?>
	<?=$f->field($m,'password');?>
	<?=$f->field($m,'interval')->dropDownList(array_combine(range(1,10), range(1,10)));?>
</div>
<?=$f->field($m,'status')->checkbox();?>

<div class="pools-list">
	<?=$this->render('miner-form-pools-list',['pools'=>$m->pools,'f'=>$f]);?>
	
</div>
<?=$f->field($m,'updatepools')->checkbox();?>



<?=\yii\helpers\Html::submitButton('Сохранить',['class'=>'btn btn-primary','name'=>'save']);?>
<?php if ($m->id):?>
<?=\yii\helpers\Html::submitButton('Удалить',['class'=>'btn btn-danger','name'=>'kill','data-pjax'=>0]);?>
<?php endif;?>
<?php \yii\widgets\ActiveForm::end();?>
<?php //pjax::end();?>