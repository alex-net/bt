<?php 
//use yii\widgets\Pjax;
//$this->registerJsFile('/js/vuejs/miners-list-miltiaddperip.js',['depends'=>[\app\assets\VueAsset::className()]]);
//$this->registerJsVar('useridis',$uid);
use yii\helpers\Html;

$this->params['breadcrumbs']=[
			['label'=>'Список владельцев майнерв','url'=>['bt/users','role'=>$role]],
			['label'=>'Редактирование пользователя '.$username,'url'=>['bt/user-edit','uid'=>$uid]],
			['label'=>'Майнеры ','url'=>['miner/user-minerslist','uid'=>$uid]],
		];
?>

<h2>Добавить майнеры по IP диапазону</h2>

<?php $f=\yii\widgets\ActiveForm::begin(['options'=>['class'=>'miner-form'],'enableClientValidation'=>false,'id'=>'minereditor']);?>
<div class="grunt-elements">
	<?=$f->field($m,'ipfrom');?>
	<?=$f->field($m,'ipto');?>
</div>
<div class="grunt-elements">
	<?=$f->field($m,'port');?>
	<?=$f->field($m,'login');?>
	<?=$f->field($m,'password');?>
	<?=$f->field($m,'interval')->dropDownList(array_combine(range(1,10), range(1,10)));?>
</div>
<?=$f->field($m,'status')->checkbox();?>




<?=\yii\helpers\Html::submitButton('Сохранить',['class'=>'btn btn-primary','name'=>'save']);?>

<?php \yii\widgets\ActiveForm::end();?>