<?php 
use yii\helpers\Html; 
use app\controllers\MinerController;
$this->registerJsFile('/js/vuejs/miners-list-online.js',['depends'=>[\app\assets\VueAsset::className()]]);
$this->registerJsVar('useridis',$uid);
$this->params['breadcrumbs']=[
			['label'=>'Список владельцев майнерв','url'=>['bt/users','role'=>$role]],
			['label'=>'Редактирование пользователя '.$username,'url'=>['bt/user-edit','uid'=>$uid]],
			['label'=>'Все майнеры пользователя','url'=>['miner/user-minerslist','uid'=>$uid]],
		];
?>
<h2>Список активных майнеров пользователя <i><?=$username;?></i></h2>

<polsunok-component :min='10000' :max='15000' :title='"Производительность"' @set-filter='updatefieler("filterbyghs",$event);'></polsunok-component>
<!-- <polsunok-component :min='45' :max='100' :title='"T1"' @set-filter='updatefieler("filterbyt1",$event);'></polsunok-component>-->
<polsunok-component :min='45' :max='100' :title='"T2"' @set-filter='updatefieler("filterbyt2",$event);'></polsunok-component>



<table v-if="minerslist.length>0" class="miners-list-online" border="1">
	<thead>
		<tr>
			<th class="sortable"  @click="setsortfield('ip')" rowspan="2">IP<span class="fa" v-bind:class='{"fa-sort":sortby!="ip","fa-sort-asc":sortby=="ip"&&sortdirect=="asc","fa-sort-desc":sortby=="ip"&&sortdirect=="desc"}'></span></th>
			
			<th class="sortable" rowspan="2" @click="setsortfield('upd')">Обновление<span class="fa" v-bind:class='{"fa-sort":sortby!="upd","fa-sort-asc":sortby=="upd"&&sortdirect=="asc","fa-sort-desc":sortby=="upd"&&sortdirect=="desc"}'></span></th>
			<th class="sortable" rowspan="2" @click="setsortfield('timet')">Время работы<span class="fa" v-bind:class='{"fa-sort":sortby!="time","fa-sort-asc":sortby=="time"&&sortdirect=="asc","fa-sort-desc":sortby=="time"&&sortdirect=="desc"}'></span></th>
			<th class="sortable" rowspan="2" @click="setsortfield('ghs')">Произвдительность<span class="fa" v-bind:class='{"fa-sort":sortby!="ghs","fa-sort-asc":sortby=="ghs"&&sortdirect=="asc","fa-sort-desc":sortby=="ghs"&&sortdirect=="desc"}'></span></th>
			<th rowspan="2" class="sortable" @click="setsortfield('t1')">Т<span class="fa" v-bind:class='{"fa-sort":sortby!="t1","fa-sort-asc":sortby=="t1"&&sortdirect=="asc","fa-sort-desc":sortby=="t1"&&sortdirect=="desc"}'></span></th>
			<th colspan="2">Вентиляторы</th>
		</tr>
		<tr>
			
			
			<th>F1</th>
			<th>F2</th>

		</tr>
	</thead>
	<tbody>
		<template v-for='miner in minerslistfiltred'>
			<tr class='miner-row' :class="{'bad-one':miner.badupds==1,'bad-more':miner.badupds>1}" @click='showplats(miner.ip);'>
				<td>{{miner.ip}}</td>
				<td>{{miner.upd}} (<span title="минут, интервал обновления">{{miner.interval}}</span>) <span v-if="miner.badupds" title="Пропущено раз подряд" >b={{miner.badupds}}</span></td>
				<td :data-time="miner.timet">{{miner.time}}</td>
				<td>{{miner.ghs}}</td>
				
				<td>{{miner.temp[1]}}</td>
				<td>{{miner.fans[0]}}</td>
				<td>{{miner.fans[1]}}</td>
			</tr>
			<tr class='miner-plussrow' v-if='controls[miner.ip].show'>
				<td colspan="9">
					Интервал обновения {{miner.interval}} мин.<br/>
					IP {{miner.ip}}
					<table v-if="miner.plats">
						<tr>
							<th>Номер</th>
							<th>Число рабочих чипов</th>
							<th>Производительнсть</th>
							<th>Темпратура платы </th>
							<th>Темпратура чипов</th>
							<th>Частота</th>
							<th> Действия</th>
						</tr>
						<tr v-for="(plata,num,ind) in miner.plats">
							<td>{{num}}</td>
							<td>{{plata.acn}}</td>
							<td>{{plata.rate}}</td> 
							
							<td>{{Math.max(plata.t1)}}</td> 
							<td>{{Math.max(plata.t2)}}</td> 
							<td>-</td>
							<td v-if='ind==0' :rowspan="miner.platsco"><button v-if="!miner.badupds && !miner.updating"  @click='torebootminer(miner,$event)'>перезагрузить</button></td>
						</tr>
					</table>

					<table v-if='miner.pools.length>0'>
						<thead><th>Url</th><th>логин</th><th>Пароль</th></thead>
						<tbody>
							<tr v-for="p in miner.pools">
								<td>{{p.url}}</td><td>{{p.user}}</td><td>{{p.pass}}</td>
							</tr>	
						</tbody>
						
					</table>
				</td>
			</tr>
		</template>
	</tbody>
</table>
