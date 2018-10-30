$(function(){
	///$()
	//$.pjax({url:yii.getCurrentUrl(),container:'.miner-form .pools-list'});
	///$('.miner-form .pools-list button').pjax('.miner-form .pools-list');
	//$('.miner-form .pools-list').pjax()

	$('body').on('click','.miner-form .pools-list button[type=submit]',function(e){
		e.preventDefault();
		$(this).parent().find('button').attr('disabled','disabled');
		//$.pjax({url:yii.getCurrentUrl(),container:'.miner-form .pools-list'});
		var postdata=$(this.form).serialize()+'&'+this.name+'='+this.value;
		//console.log(postdata);
		$.post(yii.getCurrentUrl(),postdata,function(data){
			$('.miner-form .pools-list').html(data);
		});
		
	});
})