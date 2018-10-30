<?php 
use yii\helpers\Html;
$this->registerJsFile('/js/miner-form.js',['depends'=>[
	'\yii\web\JqueryAsset'
	//'yii\widgets\PjaxAsset',
]]);
?>
<div><label>Список пулов</label></div>
<?php foreach($pools as $i=>$pool):?>
	<div class="pool-el">
		<?=$f->field($pool,'['.$i.']url',['inputOptions'=>['class'=>'form-control','placeholder'=>'Ссылка']])->label(false);?>
		<?=$f->field($pool,'['.$i.']login',['inputOptions'=>['class'=>'form-control','placeholder'=>'Логин']])->label(false);?>
		<?=$f->field($pool,'['.$i.']password',['inputOptions'=>['class'=>'form-control','placeholder'=>'пароль']])->label(false);?>
		<?=Html::submitButton('x',['title'=>'Удалить','name'=>'killpool','value'=>$i]);?>
	</div>
<?php endforeach;?>
<?=Html::submitButton('добавить пул',['name'=>'addpool']); ?>
<?=Html::submitButton('запросить пулы',['name'=>'getpools']); ?>