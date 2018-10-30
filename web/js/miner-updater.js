$(function(){
	$('body').on('click','.link-update-miner',function (e){
		e.preventDefault();
		var a=$(this);
		var el=$(this).parent().find('span.date-upd');
		el.html('пробуем обновить...');
		a.hide();
		$.ajax({
			complete:function(){
				a.show();
			},
			url:this.href,
			method:'post',
			success:function(data){
				if(typeof data.err !='undefined')
					el.html('<span title="'+data.errstr+'">ошибка!</span>');
				if (typeof data.updin!='undefined')
					el.html(data.updin);
			},
			error:function(e){
				el.html('<span title="'+e.status+' '+e.statusText+'">ошибка!</span>');
			}
		});
		/*$.post(this.href,{checkstat:true},function(data){
			

		});*/
	});
});