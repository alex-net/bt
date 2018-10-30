var v= new Vue({
	el:'#app-init-container',
	data:{
		uid:useridis,
		todoinfo:todoinfo,
		cip:'',
		results:[],

	},
	filters:{
		// отображаем статус ммайнера 
		statusdisplay:function(v)
		{
			return (v-0)?'Активный':'Не активный';
		}
	},
	methods:{
		gogenerate:function(e){
			var t=this;
			e.target.disabled=true;
			this.cip=this.todoinfo.ipfrom;
			this.nextip();
		},
		nextip:function(){
			var t=this;
			dat=new FormData();
			dat.append('ip',this.cip);
			dat.append(yii.getCsrfParam(),yii.getCsrfToken());

			//jQuery.post(location.href,dat,function(e){console.log(e);});
			
			axios.post(location.href,dat).then(function(res){
				console.log(res.data);
				// если сказали выйти - выходим .. 
				t.todoinfo.countips--;
				t.results.push({
					ip:t.cip,
					st:res.data.status,
				});
				if (res.data.end ){
					t.cip='Завершено';
					return ;
				}
				t.cip=res.data.nextip;
				t.nextip();

			}).catch(function(err){
				console.log(err);
			}).then(function(){

				
			});


		}
	}
});