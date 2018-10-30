<h2>Вход в систему</h2>
<?php 

$form=\yii\widgets\ActiveForm::begin();

echo $form->field($u,'mail');
echo $form->field($u,'pass');

echo \yii\helpers\Html::submitButton('Войти');


\yii\widgets\ActiveForm::end();



