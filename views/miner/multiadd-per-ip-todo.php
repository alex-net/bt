<?php 

$this->registerJsVar('baseinfo',$data);
$this->registerJsFile('/js/vuejs/miners-list-miltiaddperip.js',['depends'=>[\app\assets\VueAsset::className()]]);
$this->registerJsVar('useridis',$uid);
$this->registerJsVar('todoinfo',$data);
$this->params['breadcrumbs']=[
			['label'=>'Список владельцев майнерв','url'=>['bt/users','role'=>$role]],
			['label'=>'Редактирование пользователя '.$username,'url'=>['bt/user-edit','uid'=>$uid]],
			['label'=>'Майнеры ','url'=>['miner/user-minerslist','uid'=>$uid]],
		];
?>

<h2>Генерация майнеров</h2>
<table>
	<caption>Сводная информация</caption>
	<tr><th>Поле</th><th>Значение</th></tr>
	<tr><td>Начальный IP</td><td>{{todoinfo.ipfrom}}</td></tr>
	<tr><td>Конечный IP</td><td>{{todoinfo.ipto}}</td></tr>
	<tr><td>Текущий IP</td><td>{{cip}}</td></tr>
	<tr><th colspan="2" style="text-align: center;"  >Для всех</th></tr>
	<tr><td>Порт</td><td>{{todoinfo.port}}</td></tr>
	<tr><td>Логин</td><td>{{todoinfo.login}}</td></tr>
	<tr><td>Пароль</td><td>{{todoinfo.password}}</td></tr>
	
	<tr><td>Интервал обновления</td><td>{{todoinfo.interval}} мин</td></tr>
	<tr><td>Статус</td><td>{{todoinfo.status | statusdisplay}}</td></tr>
	<tr><td colspan="2"><button @click="gogenerate" class="btn btn-default center-block">Поехали (осталось {{todoinfo.countips}})</button></td></tr>
</table>


<table v-if="results.length">
	<caption>Резултат выполнения</caption>
	<tr><th>IP</th><th>статус выполнения</th></tr>
	<tr v-for="r in results">
		<td>{{r.ip}}</td>
		<td v-html="r.st"></td>
	</tr>
</table>
