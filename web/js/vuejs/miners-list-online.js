// компонент ползунка .. 
var polsunok={
	template:`<div class="polsunok">
		<span class='min-val'>{{min}}</span>
		
		<span class='max-val'>{{max}}</span>
		<div ref='wrapp' class='indicator' @mousemove='dragtodo' @mouseout='dragstop'>
			<div class="polosa"></div>
			<div class='title'>{{title}}</div>
			<div class="data">{{Math.round(val1)}} - {{Math.round(val2)}} </div>
			<span ref='left' class='left-point' :style="{left:lp,'z-index':zil}" @mousedown='dragstart("left");'  @mouseup='dragstop' ></span>
			<span ref='right' class='right-point' :style="{left:rp,'z-index':zir}" @mousedown='dragstart("right");' @mouseup='dragstop' ></span>
		</div>
	</div>`,
	created:function(){
		this.val1=this.from?this.from:this.min;
		this.val2=this.to?this.to:this.max;
	},
	props:['min','max','from','to','title'],
	data:function(){
		return {
			obj:'',
			val1:0,
			val2:0,
			zil:1,
			zir:1,
		};
	},
	computed:{
		lp:function(){
			return (this.val1-this.min)/ (this.max-this.min)*100+'%';
		},
		rp:function(){
			return ((this.val2-this.min)/ (this.max-this.min))*100+'%';
		}
	},
	methods:{
		// пробуем двигать чё то ...
		dragtodo:function(e){
			if (!this.obj || this.obj!='left' && this.obj!='right')
				return ;

			if (e.target==this.$refs.wrapp)
				x=this.min+(e.offsetX-0*this.$refs[this.obj].clientWidth)/this.$refs.wrapp.clientWidth*(this.max-this.min);
					
			else
				x=this.min+(this.$refs[this.obj].offsetLeft+e.offsetX-0*this.$refs[this.obj].clientWidth)/this.$refs.wrapp.clientWidth*(this.max-this.min);
			if (x<this.min)
				x=this.min;
			if (x>this.max)
				x=this.max;

			if(this.obj=='left' && this.val2>x )
				this.val1=x;

			if(this.obj=='right' && this.val1<x)
				this.val2=x;

				//console.log(e.offsetX,e.offsetY,e.target,this.$refs.wrapp.clientWidth);
		},
		// останавливаем перемещение ... 
		dragstop:function(){
			if (!this.obj)
				return ;
			this.obj="";
			this.$emit('set-filter',[Math.round(this.val1),Math.round(this.val2)]);
		},
		// начало движения ..
		dragstart:function(el){
			var compare={left:'zil',right:'zir'};
			this.obj=el;
			this[compare[el]]=2;
		},
	}

};
// оснвное приложение .. 
var vm= new Vue({
		el:'#app-init-container',
		data:{
			sortby:'',// сортировка по 
			sortdirect:'',// направление сортировки 
			minerslist:[],// список данных майнеров . 
			controls:{},// управление отображением ..
			filterbyghs:[],// фильтр по производительности ... 
			filterbyt1:[],// температура . t1  максимум ..
			filterbyt2:[],// температура . t2  максимум ..
			currentuserid:useridis,
			dateupdate:30,// каждые 20с идёт запрос данных
		},
		
		components:{
			'polsunok-component':polsunok,
		},
		computed:{
			minerslistfiltred:function(){
				var res=[];
				// фильтруем значения .. 
				for(var i=0;i<this.minerslist.length;i++){
					// фильтр по производительности
					if (this.filterbyghs.length==2 && (parseFloat(this.minerslist[i].ghs)<this.filterbyghs[0] || parseFloat(this.minerslist[i].ghs)>this.filterbyghs[1]))
						continue;
					// фильтр по первой темературе.. 
					if (this.filterbyt1.length==2 && (parseInt(this.minerslist[i].temp[0])<this.filterbyt1[0] || parseInt(this.minerslist[i].temp[0])>this.filterbyt1[1]))
						continue;
					// фильтр по второй темературе.. 
					if (this.filterbyt2.length==2 && (parseInt(this.minerslist[i].temp[1])<this.filterbyt2[0] || parseInt(this.minerslist[i].temp[1])>this.filterbyt2[1]))
						continue;

					res.push(this.minerslist[i]);
				}
				return res;//this.minerslist;
			}
		},
		created:function(){
			var t=this;
			t.getdata();
			setInterval(function(){
				t.getdata();

			},this.dateupdate*1000);
			//console.log('s');
			

		},
		methods:{
			// запрос данных 
			getdata:function(){
				var t=this;
				axios.get('/users/'+t.currentuserid+'/miners/json').then(function(data){
					Vue.set(t,'minerslist',[]);
					Vue.set(t,'minerslist',data.data);
					t.applysort();
					// настраиваем отображение .
					for(i=0;i<t.minerslist.length;i++)
						if (!t.controls[t.minerslist[i].ip])
							t.controls[t.minerslist[i].ip]={show:false};
				});
			},
			// показываем скрваем платы ... 
			showplats:function(ip){
				var c=this.controls;

				c[ip]['show']=!c[ip]['show'];
				Vue.set(this,'controls',{});
				//this.test=!this.test;
				Vue.set(this,'controls',c);

			},
			torebootminer:function(miner,e){
				e.target.disabled='disbled';
				/*axios.get('/users/'+this.currentuserid+'/miners/'+miner.mkey+'/reboot').then(function(res){
					console.log(res.data.status);
					if (res.data.status=='ok')
						alert('Перезапуск майнера '+miner.id+' запущен');
				});*/
				

			},
			// назначить сортировку... 
			setsortfield:function(field){
				if (field!=this.sortby)
					this.sortdirect='asc';
				else
					this.sortdirect=this.sortdirect=='asc'?'desc':'asc';

				this.sortby=field;
				this.applysort();
			},
			// сортируем массив .. 
			applysort:function(){
				if (!this.sortby)
					return ;
				var mas=this.minerslist;
				var field=this.sortby;
				var direct=this.sortdirect;

				mas.sort(function(v1,v2){
					var a,b;
					switch(field)
					{
						case 't0':
							a=v1.temp[0];
							b=v2.temp[0];
							break;
						case 't1':
							a=v1.temp[1];
							b=v2.temp[1];
							break;
						case 'upd':
							a=v1.updt;
							b=v2.updt;
							break;
						case 'ip':
							a=v1.iplong;
							b=v2.iplong;
							break;
						default:
							a=v1[field];
							b=v2[field];
					}
					a=parseFloat(a);
					b=parseFloat(b);
					
					orderobj={asc:-1,desc:1};
					if (a>b)
						return -orderobj[direct];
					if (a<b)
						return orderobj[direct];

					return 0;
				});
				Vue.set(this,'minerslist',[]);
				Vue.set(this,'minerslist',mas);
			},
			// применить фильтр 
			updatefieler:function(field,vals){
				this[field]=vals;
				//console.log(arguments,field,vals);
			},

		},

	});