<h2>Редактирование профиля пользователя <i><?=$u->name;?></i></h2>


<?php 
$f=\yii\widgets\ActiveForm::begin();
echo $f->field($u,'name');
echo $f->field($u,'mail');
echo $f->field($u,'pass')->passwordInput(['placeholder'=>'Оставить пустым,чтобы пароль остался прежним']);
echo \yii\helpers\Html::submitButton('Сохранить',['class'=>'btn btn-primary']);
\yii\widgets\ActiveForm::end();

?>