<?php 
use yii\helpers\Html;
use app\models\BtUser;
$this->params['breadcrumbs']=[
			['label'=>'Список пользователей','url'=>['bt/users','role'=>$u->role]],
		];
?>
<h2>
	<?=$u->id?'Редактирование пользователя':'Созание нового пользователя';?> <i><?=$u->name?></i>
</h2>
<?php if($u->id && $u->role==BtUser::ROLE_OWNER):?>
	<?=Html::a('Список майнеров',['miner/user-minerslist','uid'=>$u->id]);?>
<?php endif;?>
<?php 
$f=\yii\widgets\ActiveForm::begin();
echo $f->field($u,'name');
echo $f->field($u,'mail');
echo $f->field($u,'pass')->passwordInput(['placeholder'=>'Оставить пустым,чтобы пароль остался прежним']);
echo $f->field($u,'role')->dropDownList($u->alowedRolesList);
echo $f->field($u,'status')->checkbox();
echo Html::submitButton('Сохранить',['class'=>'btn btn-primary','name'=>'save']);
if ($u->id)
	echo Html::submitButton('Удалить',['class'=>'btn btn-danger','name'=>'kill']);
\yii\widgets\ActiveForm::end();
?>