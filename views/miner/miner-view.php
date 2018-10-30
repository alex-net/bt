<?php 
use yii\widgets\LinkPager;

$this->params['breadcrumbs']=[
			['label'=>'Список владельцев майнерв','url'=>['bt/users','role'=>$role]],
			['label'=>'Редактирование пользователя '.$username,'url'=>['bt/user-edit','uid'=>$uid]],
			['label'=>'Майнеры','url'=>['miner/user-minerslist','uid'=>$uid]],
			'Статистика майнера '.$ipport
		];

?>
<h2>Статистика майнера <?=$ipport;?></h2>

<div class="minerhistory-list">
	<table border="1">
		<tr>
			<th rowspan="2">Дата обновления</th>
			<th colspan="4">Производительность, Гхеш</th>
			<th colspan="<?=$tc;?>">Температуры, град</th>
			<th rowspan="2">Вентеляторы, об/мин</th>
		</tr>
		<tr>
			<th>за 5 с</th>
			<th>Средняя</th>
			<th>Реальная</th>
			<th>Идеальная</th>
			<?php for($i=0;$i<$tc;$i++):?>
				<th>T<?=$i+1;?></th>
			<?php endfor;?>
		</tr>
	
<?php foreach($data as $y):?>
	<?php //=$this->render('item-history',['el'=>$y]);?>
	<tr>
		<td class="ac"><?=$y['dt'];?></td>
		<td class="ar"><?=$y['ghs_5s'];?></td>
		<td class="ar"><?=$y['ghs_av'];?></td>
		<td class="ar"><?=array_sum($y['chain_rate']);?></td>
		<td class="ar"><?=array_sum($y['chain_rateideal']);?></td>
		<?php for($i=0;$i<$tc;$i++):?>
			<td class="ac">
			<?php  if (empty($y['temp'][$i])):?>-<?php else:?>
				<b><?=max($y['temp'][$i]);?></b>&nbsp;{<?=Yii::$app->formatter->asAssocToStr($y['temp'][$i]);?>}
			<?php endif;;?>
			</td>
		<?php endfor;?>
		<td class="ac"><?=Yii::$app->formatter->asAssocToStr($y['fans']);?></td>
	</tr>
<?php endforeach;?>
	</table>
</div>
<?php 
echo LinkPager::widget(['pagination'=>$pager]);
?>