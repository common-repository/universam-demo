Vue.component('filter-prices', {
template: '<div class="range_slider"><span class="range_slider_min_value">{{selected_min}}</span><span class="range_slider_max_value">{{selected_max}}</span><div class="track" ref="_vpcTrack"></div><div class="track-highlight" ref="trackHighlight"></div><div class="range_slider_plots"><span class="range_slider_plot" v-bind:class="class_plot(plot)" v-for="plot in 21" :style="{left:(plot-1)*5+`%`}"></span><span class="range_slider_text" v-for="(num, k) in numbers" :style="{left:(k*100/(division-1))+`%`,margin:`0 0 0`+ `-`+(k?String(num).length*5:0)+`px`}">{{num}}</span></div><span class="range_slider_min" ref="track1"></span><span class="range_slider_max track2" ref="track2"></span></div>',
	props: ['min', 'max', 'min_value', 'max_value'],
	data() {
		return {
			selected_min: this.min_value,
			selected_max: this.max_value,
			step: 1,
			division: 5,
			isDragging: false,
			timerId: false,
			numbers: [this.min],
			pos: { curTrack: null }
		}
	},
	watch: {		
		min_value(val, oldVal) 
		{			
			this.selected_min = val;	
			this.recalculate();			
		},
		max_value(val, oldVal) 
		{			
			this.selected_max = val;
			this.recalculate();
		},
		selected_min(val, oldVal) 
		{
			clearInterval(this.timerId);
			this.timerId = setTimeout(() => this.$emit('changeprice',[val, this.selected_max]), 500);
		},
		selected_max(val, oldVal) 
		{
			clearInterval(this.timerId);
			this.timerId = setTimeout(() => this.$emit('changeprice',[this.selected_min, val]), 500);
		},
		min(val, oldVal) 
		{			
			this.recalculate();
		},
		max(val, oldVal) 
		{			
			this.recalculate();
		},
	},
	computed:{
		totalSteps() { 
			return (this.max - this.min) / this.step;		  
		},		
		percentPerStep() { 
			return 100 / this.totalSteps;		  
		},
		totalDivisions() { 
			return (this.max - this.min) / this.division;		  
		},
	},
	mounted() 
	{
		this.recalculate();
		['mouseup', 'mousemove'].forEach(type => { document.body.addEventListener(type, (e) => {
				if(this.isDragging && this.pos.curTrack)
					this[type](e, this.pos.curTrack)			
			})
		});		
		['mousedown', 'mouseup', 'mousemove', 'touchstart', 'touchmove', 'touchend'].forEach(type => {			
			this.$refs.track1.addEventListener(type, (e) => {
				e.stopPropagation();
				this[type](e, 'track1');
			},{passive: true});
			this.$refs.track2.addEventListener(type, (e) => {
				e.stopPropagation();
				this[type](e, 'track2');
			},{passive: true});
		})
		this.$refs._vpcTrack.addEventListener('click', (e) => {
			e.stopPropagation();
			this.setClickMove(e);		  
		})
		this.$refs.trackHighlight.addEventListener('click', (e) => {
			e.stopPropagation();
			this.setClickMove(e);		  
		})
	},
	methods: {		
		recalculate()
		{	
			for (var i = 1; i < this.division-1; i++)
			{ 
				if ( this.max < 100000 )
					Vue.set(this.numbers, i, Math.round(this.min+this.totalDivisions*i));	
			}
			Vue.set(this.numbers, this.division-1, this.max);
			if ( this.min !== 'undefined' && this.selected_min < this.min ) 
				this.selected_min = this.min;
			if ( this.max !== 'undefined' &&  this.selected_max > this.max ) 
				this.selected_max = this.max;
			this.$refs.track1.style.left = this.valueToPercent(this.selected_min) + '%';
			this.$refs.track2.style.left = this.valueToPercent(this.selected_max) + '%';
			this.setTrackHightlight();
		},		
		class_plot(plot)
		{	
			if (plot==1 || plot==21 || (plot-1) % this.division == 0)
				return 'range_slider_plot_number';
			return '';
		},
		moveTrack(track, e)
		{ 
			moveInPct = (e.clientX-this.$refs._vpcTrack.getBoundingClientRect().left) / this.getPercentInPx();			
			if( moveInPct<=0 ) 
			{
				value = this.min;
				moveInPct = 0;
			}
			else if( moveInPct>=100 ) 
			{
				value = this.max;
				moveInPct = 100;
			}
			else		
				value = Math.round(( moveInPct / this.percentPerStep * this.step ) + this.min);	
			if(track === 'track1')
			{ 
				if(this.selected_min == value || value >= (this.selected_max - this.step)) 
					return;
				this.selected_min = value;
			}
			else if(track==='track2')
			{ 
				if( this.selected_max == value || value <= (this.selected_min + this.step)) 
					return;
				this.selected_max = value;
			} 	
			this.$refs[track].style.left = moveInPct + '%';
			this.setTrackHightlight()			
		},
		mousedown(e, track)
		{
			if(this.isDragging) return;
			this.isDragging = true;
			this.pos.curTrack = track;
		},
		touchstart(e, track){
			this.mousedown(e, track)
		},
		mouseup(e, track){
			if(!this.isDragging) return;
			this.isDragging = false;
		},
		touchend(e, track){
			this.mouseup(e, track)
		},
		mousemove(e, track){
			if(!this.isDragging) return;      
			this.moveTrack(track, e);
		},
		touchmove(e, track){
			this.mousemove(e.changedTouches[0], track);
		},
		valueToPercent(value){	
			return ((value - this.min) / this.step) * this.percentPerStep;
		},
		setTrackHightlight(){
			this.$refs.trackHighlight.style.left = this.valueToPercent(this.selected_min) + '%';  
			this.$refs.trackHighlight.style.width = this.valueToPercent(this.selected_max) - this.valueToPercent(this.selected_min) + '%';
		},
		getPercentInPx(){
			let oneStepInPx = this.$refs._vpcTrack.offsetWidth / this.totalSteps;
			return oneStepInPx / this.percentPerStep;
		},
		setClickMove(e)
		{
			let track1Left = this.$refs.track1.getBoundingClientRect().left;
			let track2Left = this.$refs.track2.getBoundingClientRect().left;     
			if(e.clientX < track1Left)
				this.moveTrack('track1', e)
			else if((e.clientX - track1Left) < (track2Left - e.clientX) )
				this.moveTrack('track1', e);
			else
				this.moveTrack('track2', e);
		}
	}
})

var pf,ps,pp;
var site_filter = {
  	data() {
		return {
			custom: {favorite_shop:{selected:0, active:false, data:[]}, individual_price:{selected:'', active:false, data:[]}, scat:{selected:[], selected_parent:'', active:false, show:false, data:[]}, rating:{selected:[], active:false, data:[1, 2, 3, 4, 5]}, prices:{active:false, min_price:0, max_price:0, interval:[], selected:[]}},
			attributes: [],			
			categories: '',
			blok_id:'catalog_list',
			keyword_input: '.js_search_page_keyword',
			use_search: true,
			tab:'',
			page:1,
			count:0, 
			more:false,
			load_result: true,
			load_more_result: true,
			filter_display: false,
			loading: false,			
			activation: 'button',
			new_products:false,
			stock_products:false,
			sort:'',
			order:'asc',	
			query: {},		
			timerId: 0,		
			option: {attr:0,shop:0,scat:0,prices:0},
			selectedFilters:false,
		};				
	},
	watch:{
		new_products: 'updateChanged',
		stock_products: 'updateChanged',	
		sort: 'updateChanged',	
		order: 'updateChanged'
	},
	mounted() {		
		this.defaultLoad();
	},	
	methods: {		
		defaultLoad()
		{
			var url = new URL( document.location.href );
			this.new_products = url.searchParams.get('new_products') || false;				
			this.stock_products = url.searchParams.get('stock_products') || false;
			e = document.getElementById('usam_products_sort');
			!e || e.addEventListener('change', this.apply_filters);
			if ( document.getElementById('product_filters') && document.getElementById('product_filters').classList.contains('filter_activation_auto') )
				this.activation = 'auto';	
			document.querySelectorAll('.js_number_products').forEach((el) => {
				if ( el.tagName == 'SELECT' )
					el.addEventListener('change', this.change_tool)
				else
					el.addEventListener('click', this.change_tool);
			});
			document.querySelectorAll('.js_option_display').forEach((el) => {el.addEventListener('click', this.change_tool)});		
			document.querySelectorAll('.pagination a').forEach((el) => {el.addEventListener('click', this.pagination)});
			document.querySelectorAll(this.keyword_input).forEach((el) => {el.addEventListener('keypress', this.keypress_keyword_input)});
			e = document.querySelector('.js-show-more');
			!e || e.addEventListener('click', this.show_more);
			usam_lazy_image();	
			this.loading_more_results();
			eventBus.$on('change_selected_attributes', (a) => {			
				for (let k in this.attributes ) 
					if ( this.attributes[k].id == a.id )
					{
						if( this.attributes[k].type == 'O' || this.attributes[k].type=='N' )
							this.attributes[k].selected = [this.attributes[k].min_price,this.attributes[k].max_price];
						else
							this.attributes[k].selected = this.attributes[k].selected.filter((i) => i !== a.filter_id);
						break;
					}
			})
		},
		updateChanged(val, oldVal)
		{
			if ( val != oldVal && oldVal != '' && !this.loading )
			{
				clearTimeout(this.timerId);	
				this.start();
			}
		},		
		updatedFilters(val, oldVal) 
		{
			this.start();
		},
		show_more(e)
		{
			e.preventDefault();
			this.page++;
			this.start(true);
		},
		load_filters()
		{			
			if ( this.option.attr || this.option.shop || this.option.scat || this.option.prices )
			{ 
				this.attributes = [];
				this.custom.scat.data = [];
				this.custom.prices.max_price = 0;					
				this.loading = true;	
				this.sort = USAM_Product_Filter.sort.orderby;
				this.order = USAM_Product_Filter.sort.order;
				usam_api('products/filters', {query: this.query, returned:this.option, type_price:USAM_Product_Filter.type_price}, 'POST', (r) =>
				{
					if ( this.option.attr && Object.keys(r.attributes).length )
					{
						for (k in r.attributes)
						{ 						
							r.attributes[k].active = false;	
							r.attributes[k].show = 0;
							r.attributes[k].search = '';							
							if ( typeof USAM_Product_Filter.select_filters[r.attributes[k].id] != typeof undefined ) 
								r.attributes[k].selected = USAM_Product_Filter.select_filters[r.attributes[k].id];
							else if ( r.attributes[k].type == 'O' || r.attributes[k].type == 'N' )			
								r.attributes[k].selected = [r.attributes[k].min_price, r.attributes[k].max_price];	
							else
								r.attributes[k].selected = [];	
							Vue.set(this.attributes, k, r.attributes[k]);													
						}	
						for (k in this.attributes)		
							this.$watch(['attributes', k, 'selected'].join('.'), this.updated_attributes);	
						eventBus.$emit('change_attributes', this.attributes);
						if ( this.attributes.length )
							this.filter_display = true;	
					} 					
					if (typeof r.categories != typeof undefined && Object.keys(r.categories).length >= 1)
					{
						Vue.set(this.custom.scat, 'data', r.categories);			
						this.filter_display = true;		
					}									
					if (typeof USAM_Product_Filter.select_categories != typeof undefined )
						Vue.set(this.custom.scat, 'selected', USAM_Product_Filter.select_categories);
					if ( this.option.shop )
					{
						this.custom.favorite_shop.data = r.storages;
						this.custom.favorite_shop.selected = parseInt(USAM_Product_Filter.shop);
						if ( this.custom.favorite_shop.data.length >= 1 )
							this.filter_display = true;	
					}
					if ( this.option.individual_price )
					{
						this.custom.individual_price.data = r.companies;
						this.custom.individual_price.selected = parseInt(USAM_Product_Filter.company);
						if ( this.custom.individual_price.data.length >= 2 )
							this.filter_display = true;	
					}						
					if (this.option.prices && typeof r.prices != typeof undefined)
					{
						r.prices.selected = USAM_Product_Filter.select_prices.length?USAM_Product_Filter.select_prices:[r.prices.min_price,r.prices.max_price];
						r.prices = Object.assign(this.custom.prices, r.prices);
						Vue.set(this.custom, 'prices',r.prices);
						if ( this.custom.prices.max_price )
							this.filter_display = true;					
					}
					this.custom.rating.selected = USAM_Product_Filter.select_rating;
					if ( this.activation == 'auto' )
					{
						this.$watch('custom.favorite_shop.selected', this.updatedFilters);	
						this.$watch('custom.individual_price.selected', this.updatedFilters);							
						this.$watch('custom.scat.selected', this.updatedFilters);
						this.$watch('custom.rating.selected', this.updatedFilters);
						this.$watch('custom.prices.selected', this.updatedFilters, {deep:true});
						if ( this.custom.prices.interval.length )
							this.$watch('custom.prices.interval', this.updated_prices_interval, {deep:true});
					}
					if ( document.documentElement.clientWidth > 1024 )
					{
						var w = document.querySelectorAll('.sidebar .widget');
						if (w.length == 1 && w[0].classList.contains('widget_filter_products'))
						{ 
							var c = document.querySelector('body').classList;
							if ( this.filter_display )
								c.remove('no_sidebar');
							else
								c.add('no_sidebar');
						}
					}					
					this.loading = false;	
				});
			}
		},
		keypress_keyword_input( e )
		{ 
			clearInterval(this.timerId);
			var code = e.keyCode ? e.keyCode : e.which;
			if (e.keyCode == 13) 
				v = e.target.value;
			else			
			{					
				if ( e.key.replace(/\\|\//g,'') )
					v = e.target.value+e.key;
				else
				{
					e.preventDefault();
					return false;
				}
			}						
			if ( v.length > 2 )
			{
				document.querySelectorAll('.js_search_phrase').forEach((el) => {el.innerHTML = v});								
				var	d = {s : v}; 
				if( typeof e.target.dataset.row !== 'undefined' )
					d["row"] = parseInt(e.target.dataset.row);			
				if (code == 13)
					this.search(d);
				else
				{
					var time = 1200;
					if( typeof e.target.dataset.time !== 'undefined' )
						time = parseInt(e.target.dataset.time);
					this.timerId = setTimeout(this.search, time, d);
				}
			}
		},
		updatedSort( sort, order )
		{			
			if ( order === undefined )
			{
				if ( this.sort != sort )
					this.order = 'asc';
				else
				{
					this.order = this.order == 'asc' ? 'desc' : 'asc';
					return;
				}				
			}
			else
				this.order = order;
			this.sort = sort;
		},
		updated_prices_interval(val, oldVal) 
		{
			var min = this.custom['prices'].min_price, max = this.custom['prices'].max_price;			
			for (let j in this.custom['prices'].interval ) 
			{							
				if ( this.custom['prices'].interval[j].selected )
				{
					if ( min == this.custom['prices'].min_price )
						min = this.custom['prices'].interval[j].step[0];
					max = this.custom['prices'].interval[j].step[1];
				}
			}	
			this.custom['prices'].selected = [min, max];
			this.start();
		},
		updated_attributes(val, oldVal) 
		{
			if ( this.activation == 'auto' )
			{ 
				clearTimeout(this.timerId);	
				this.timerId = setTimeout(this.start, 1000);
			}			
		},			
		cat_childrens(id) 
		{ 
			for (i in this.custom.scat.data) 
			{
				if( this.custom.scat.data[i].parent == id)
					return true;				
			}
			return false;
		},			
		click_tab( filter_type ) 
		{
			this.tab = this.tab==filter_type?'':filter_type;			
			var f = (ev)=> { 
				let el = ev.target.closest('#product_filters');				
				if ( el == null )
				{				
					this.tab = '';			
					document.removeEventListener("click", f);							
				}
				else
					return false;
			};
			setTimeout( ()=>{ document.addEventListener("click", f ) }, 500);
		},
		no_search_results(k) 
		{ 	
			if ( this.attributes[k].search !='' )
			{				
				for (i in this.attributes[k].filters) 	
				{
					if ( this.attributes[k].filters[i].name.toLowerCase().includes(this.attributes[k].search.toLowerCase()) )
						return false;
				}
				return true;
			}
			return false;
		},		
		class_custom( type ) 
		{			
			if ( type == 'prices' )
			{
				if ( this.custom.prices.max_price == 0 )	
					return 'hide';
				else 
					return this.custom[type].active?'active':'';	
			}
			else
			{
				if ( typeof this.custom[type].data == typeof undefined || this.custom[type].data.length < 2 )
					return 'hide';
				else
					return this.custom[type].active?'active':'';	
			}
		},
		addRemoveCustom( id, type ) 
		{				
			let i = this.custom[type].selected.indexOf(id);
			if (i !== -1)
				this.custom[type].selected.splice(i, 1);
			else
				this.custom[type].selected.push(id)
		},
		addRemove( a, f ) 
		{				
			let i = a.selected.indexOf(f.id);
			if (i !== -1)
				a.selected.splice(i, 1);
			else
				a.selected.push(f.id)
		},
		count_selected( a ) 
		{	
			return a.type!='O' && a.type!='N' && a.selected.length?'('+a.selected.length+')':'';
		},
		attributeSelected( a ) 
		{			
			return a.type=='O' || a.type=='N' ? a.min_price != a.selected[0] || a.max_price != a.selected[1] : a.selected.length;
		},
		apply_filters(e) 
		{			
			this.start();
			usam_scroll_to_element("#"+this.blok_id);
		},
		change_tool(e) 
		{ 
			e.preventDefault();
			if( e.currentTarget.classList.contains('active') )					
				return false;	
			if ( e.currentTarget.tagName != 'SELECT' )
			{
				e.currentTarget.parentNode.querySelectorAll('.active').forEach( (el) => {el.classList.remove('active')} );
				e.currentTarget.classList.add('active');
			}
			this.start();
		},
		custom_open( e, filter_type ) 				
		{					
			if ( !this.load_result )
				return false;			
			var sidebar = e.target.closest('.sidebar') ? true : false;
			for (k in this.custom) 	
			{
				if ( k == filter_type )
					this.custom[k].active = this.custom[k].active?false:true;								
				else if ( !sidebar )
					this.custom[k].active = false;
			}			
			if ( !sidebar )
			{
				setTimeout(() => { document.addEventListener("click", this.onclick ); }, 1);			
				this.all_filter_close();			
			}
			
		},
		filter_open(attribute_id, e, g) 
		{	
			if ( !this.load_result )
				return false;
			
			var sidebar = e.target.closest('.sidebar') && g !== true ? true : false;			
			for (k in this.attributes) 	
			{
				if ( this.attributes[k].id == attribute_id )
					this.attributes[k].active = this.attributes[k].active?false:true;
				else if ( !sidebar )
					this.attributes[k].active = false;
			}			
			if ( !sidebar )
			{
				this.all_custom_close();
				setTimeout(() => { document.addEventListener("click",this.onclick ); }, 1); 						
			}
		},
		all_custom_close( ) 				
		{ 
			document.removeEventListener("click", this.onclick );
			for (k in this.custom) 	
			{
				this.custom[k].active = false;
			}
		},
		all_filter_close() 				
		{
			document.removeEventListener("click", this.onclick );
			for (k in this.attributes) 	
			{
				this.attributes[k].active = false;
			}
		},
		onclick(e) 
		{
			if ( e.target.closest('.filter_form__list') != null )
				return false;				
			this.all_close();
		},
		all_close() 
		{				
			this.all_filter_close();
			this.all_custom_close();
		},
		custom_reset( ft ) 
		{
			this.custom[ft].selected = ft == 'prices' ? [this.custom[ft].min_price,this.custom[ft].max_price] : [];
			if ( this.activation != 'auto' )
				this.start();
		},		
		resetAll() 
		{ 
			for (k in this.custom)
				this.custom[k].selected = k == 'prices' ? [this.custom[k].min_price,this.custom[k].max_price] : [];	
			for (k in this.attributes)
				this.resetAttr(k);
			this.start();			
		},	
		resetAttr(k) 
		{		
			this.attributes[k].selected = this.attributes[k].type=='O' || this.attributes[k].type=='N' ? [this.attributes[k].min_price, this.attributes[k].max_price] : [];	
		},
		filter_reset(k) 
		{ 								
			this.resetAttr(k);
			if ( this.activation != 'auto' )
				this.start();
		},
		filter_delete(id,i) 
		{ 	 		
			for (k in this.attributes) 	
			{
				if (this.attributes[k].id == id )
				{
					if ( this.attributes[k].type=='O' || this.attributes[k].type=='N' )
						this.attributes[k].selected = [this.attributes[k].min_price, this.attributes[k].max_price];	
					else
					{
						var index = this.attributes[k].selected.indexOf(i);
						if (index !== -1)
							this.attributes[k].selected.splice(index, 1);
					}
				}
			}
			if ( this.activation != 'auto' )
				this.start();
		},
		pagination(e) 
		{
			e.preventDefault();		
			if(e.target.classList.contains('page-next'))
				this.page++;
			else if(e.target.classList.contains('page-prev'))
				this.page--;
			else
				this.page = parseInt(e.target.innerText);			
			usam_scroll_to_element("#"+this.blok_id);
			this.start();
		},
		get_query() 
		{ 
			var url = {}, min = 0, max = 0, $min, $max;
			var data = {action: 'get_products', nonce: USAM_Product_Filter.get_products_nonce, query: this.query, paged: this.page, f: {}};
			if ( this.use_search && document.querySelector(this.keyword_input) )
				data.s = document.querySelector(this.keyword_input).value;
			else if ( this.page > 1) 
				url["paged"] = this.page;						
			var el = document.querySelector('.js_number_products');
			if ( el )
			{ 
				if ( el.tagName == 'SELECT' )
					data.number = el.value;
				else
					data.number = document.querySelector('.js_number_products.active').innerHTML;
			}		
			if ( document.querySelector('.products_view_type .active') )
				data["view_type"] = document.querySelector('.products_view_type .active').getAttribute('view_type');
			if ( this.order )
				data.order = this.order;
			var el = document.querySelector('#usam_products_sort');			
			if ( el )
			{
				var s = el.value.split('-');
				if ( s[1] !== undefined )
					data.order = s[1];
				data.orderby = s[0];
			}
			else if( this.sort )
				data.orderby = this.sort;	
			if ( this.new_products )
				data.new_products = 1;			
			if ( this.stock_products )
				data.stock_products = 1;
			for (k in this.custom) 	
			{					
				if ( k == 'prices' )
				{
					i = 0;
					if ( this.custom[k].interval.length )
					{
						data['interval_prices'] = [];
						for (let j in this.custom[k].interval ) 
						{							
							if ( this.custom[k].interval[j].selected )
							{
								data['interval_prices'][i] = this.custom[k].interval[j].step;
								i++;
							}
						}					
					}
					if ( i===0 && this.custom[k].max_price && (this.custom[k].min_price != this.custom[k].selected[0] || this.custom[k].max_price != this.custom[k].selected[1]) )
						url[k] = data[k] = this.custom[k].selected;
				}
				else 
				{
					data[k] = this.custom[k].selected;	
					if ( this.custom[k].selected.length )
						url[k] = this.custom[k].selected;	
				}
				this.custom[k].active = false;
			} 	
			if ( this.option.attr )
			{				
				eventBus.$emit('change_attributes', this.attributes);
				for (k in this.attributes) 	
				{					
					if ( this.attributes[k].selected.length )
					{					
						if ( this.attributes[k].type=='O' || this.attributes[k].type=='N' )
						{
							if ( this.attributes[k].min_price != this.attributes[k].selected[0] || this.attributes[k].max_price != this.attributes[k].selected[1] )
								data.f[this.attributes[k].id] = this.attributes[k].selected.join('-');
						}
						else
						{
							data.f[this.attributes[k].id] = this.attributes[k].selected;
						}	
					}
					this.attributes[k].active = false;
				}			
			}
			for (let k in data.f)
				url['f['+k+']'] = data.f[k];
			this.url(data, url);
			return data;
		},
		start( append ) 
		{
			if( typeof append === typeof undefined )
				append = false;			
			var more; 
			if ( append )
			{				
				if ( !this.load_result || !this.load_more_result )
					return false;			
				more = document.querySelector('.js-more-result');
				!more || more.classList.add('active');
			}				
			this.load_result = false;			
			if ( !append )
				document.getElementById(this.blok_id).classList.add('is-loading-products');			
			let data = this.get_query();		
			data.append = append?1:0;			
			usam_send(data, (r) => {
				this.load_result = true;
				this.load_more_result = true;
				this.count = r.count;
				if ( append )
				{	
					!more || more.classList.remove('active');
					if ( r.products == '' )
					{ 							
						var d = document.querySelector('.js-no-more-search-results');
						if ( d )
						{
							d.classList.add('active');
							setTimeout(() => { d.classList.remove('active') }, 4000);
						}						
						var show_more = document.querySelector('.js-show-more');
						!show_more || show_more.remove();		
						this.load_more_result = false;
					}
					else
					{ 	
						var d = document.querySelector('.js-products');
						!d || d.insertAdjacentHTML('beforeEnd', r.products)
						usam_lazy_image();	
					}
				}
				else
				{					
					document.getElementById(this.blok_id).innerHTML=r.products;
					document.getElementById(this.blok_id).classList.remove('is-loading-products');					
					usam_lazy_image();	
					this.beforeAddContent();	
				}								
			});
		},	
		beforeAddContent() 		
		{		
			document.querySelectorAll('.pagination a').forEach((el) => {el.addEventListener('click', this.pagination)});
			if ( this.page == 1 )
			{
				var show_more = document.querySelector('.js-show-more');
				!show_more || show_more.addEventListener('click', this.show_more);
			}
		},
		loading_more_results() 
		{ 		
			const imageObserver = new IntersectionObserver((entries, imgObserver) => {
				entries.forEach((e) =>
				{					
					if (e.isIntersecting)
					{ 	
						e.target.parentNode.querySelectorAll('#'+this.blok_id).forEach( (t) => {
							if ( t.innerHTML !== "" )
							{
								if ( typeof USAM_Product_Filter.query.pagename == typeof undefined || USAM_Product_Filter.query.pagename != 'search' || document.querySelector(this.keyword_input) && document.querySelector(this.keyword_input).value != '' )
								{
									this.page++;
									this.start( true );
								}
								return;
							}
						}) 
					}
				})
			}, {rootMargin: '0px 0px 100px 0px'});
			document.querySelectorAll('.js-search-results-more-check').forEach( (v) => {
				imageObserver.observe(v);
			}) 	
		},
		url( data, u ) 
		{ 
			let url = new URL( document.location.href );	
			link = url.origin+url.pathname;
			if ( url.pathname == '/search' )
				link = url.pathname+'/'+data.s;
			else if ( url.pathname == '/search/' )
				link = url.pathname+data.s;
			else if ( url.pathname.includes('/search/') )
			{
				let s = url.pathname.replace('/search/', '');
				if ( s )
					link = link.replace(s, data.s)
				else
					link = url.pathname+data.s;
			}		
			if ( u != undefined )
			{	
				url = new URL( link );						
				for (k in u)
				{
					v = Array.isArray(u[k]) ? u[k].join('-') : u[k];
					if ( v !== '' )
						url.searchParams.set(k, v);				
				}
				link = url.href;
			}
			history.replaceState( '' , '', link );
		},
		search( data ) 
		{	
			if ( data.s )
			{ 
				document.getElementById(this.blok_id).classList.add('is-loading-products');				
				this.url(data);
				data.action = 'get_products';
				usam_send(data, (r) => {
					document.getElementById(this.blok_id).classList.remove('is-loading-products');
					if ( r.products == '' )
						html = "<div class='nothing_found'>"+USAM_THEME.message_search_nothing_found+"</div>";
					else
					{
						html = r.products;		
						this.load_more_result = true;
					}
					document.getElementById(this.blok_id).innerHTML = html;
					usam_lazy_image();
				});	
				this.query['s'] = data.s;
				this.load_filters();						
			}
		},
		change_attribute_slider(k,selected)
		{
			this.attributes[k].selected = selected;
		}
	},
	updated()
	{ 
		var s = false;
		for (k in this.custom)
			if( this.custom[k].selected.length && (k == 'prices' && (this.custom[k].min_price != this.custom[k].selected[0] || this.custom[k].max_price != this.custom[k].selected[1]) || k != 'prices') )
			{
				s = true;
				break;
			}
		if ( !s )
			for (k in this.attributes)
			{
				if( this.attributeSelected(this.attributes[k]) )
				{ 
					s = true;
					break;
				}
			}			
		eventBus.$emit('selected_filters', [this.attributes, this.custom]);
		if ( s !== this.selectedFilters )
			this.selectedFilters = s;
	}			
}

document.addEventListener("DOMContentLoaded", () => {
	if ( USAM_Product_Filter.query === null )
		USAM_Product_Filter = {query:{post_status:"publish"},select_filters:[]};	
	if( document.getElementById('product_filters') )
	{ 
		pf = new Vue({
			el: '#product_filters',
			mixins: [site_filter],	
			mounted() {					
				this.option.attr = 1;			
				if ( this.$refs.shop )
					this.option.shop = 1;
				if ( this.$refs.range_price )
					this.option.prices = 1;
				if ( this.$refs.individual_price )
					this.option.individual_price = 1;
				if ( this.$refs.scat )
					this.option.scat = 'no_hierarchy';	
				else if ( this.$refs.scat_hierarchy )
					this.option.scat = 'hierarchy';									
				this.query = USAM_Product_Filter.query;
				if (typeof this.query.paged !== typeof undefined)
					this.page = this.query.paged;
				if ( document.documentElement.clientWidth < 1024 )
				{
					var catalog_head = document.getElementsByClassName('catalog_head');		
					if( catalog_head.length )	
					{
						w = document.getElementById('product_filters');	
						if( w )	
							catalog_head[0].appendChild(w);		
					}
					usam_api('products/filter_categories', {show_active: 1, take_menu: 1, query:this.query}, 'POST', (r) => this.categories = r);										
				}
				this.load_filters();				
			}
		})			
	}
	else if( document.querySelector('.js-search-page #catalog_list') )
	{
		ps = new Vue({
			el: '#catalog_list',
			mixins: [site_filter],
			mounted() {
				this.query = USAM_Product_Filter.query;
				if (typeof this.query.paged !== typeof undefined)
					this.page = this.query.paged;
			},	
		})
	}
	if( document.querySelector('.widget_shop_tools') )
	{
		pp = new Vue({
			el: '.widget_shop_tools',
			mixins: [site_filter],
			data() {
				return {
					active:false,
					activation: 'auto',
					option:{attr:0,shop:0,scat:0,prices:0},
				};				
			},			
			mounted() {
				this.query = USAM_Product_Filter.query;
				if (typeof this.query.paged !== typeof undefined)
					this.page = this.query.paged;
				if ( document.querySelector('.widget_shop_tools .products_prices') )
				{
					this.option.prices = 1
					this.load_filters();					
				}
			},	
			methods: {	
				load_filters()
				{ 																	
					usam_api('products/filters', {query: this.query, returned:this.option, type_price:USAM_Product_Filter.type_price}, 'POST', (r) =>
					{ 					
						if ( typeof r.prices != typeof undefined )
						{
							r.prices.selected = USAM_Product_Filter.select_prices.length?USAM_Product_Filter.select_prices:[r.prices.min_price,r.prices.max_price];
							r.prices = Object.assign(this.custom.prices, r.prices);
							if( document.getElementById('product_filters') )
							{
								Vue.set(pf.custom, 'prices', r.prices);	
								this.$watch('custom.prices.selected', pf.updated_attributes, {deep:true});
							}
							else
							{
								Vue.set(this.custom, 'prices', r.prices);								
								this.$watch('custom.prices.selected', this.updated_attributes, {deep:true});
							}								
						}
					});
				}
			}
		})
	}
	if( document.querySelector('.selected_catalog_filters') )
	{
		new Vue({
			el: '.selected_catalog_filters',
			data() {
				return {
					custom: {},
					attributes: [],	
				};				
			},
			mounted() {
				eventBus.$on('selected_filters', (r) => {
					var a = [];
					for (k in r[0])
					{
						if( r[0][k].type == 'O' || r[0][k].type=='N' )
						{
							if( r[0][k].min_price != r[0][k].selected[0] || r[0][k].max_price != r[0][k].selected[1] )
								a.push({name:r[0][k].name+' '+r[0][k].selected[0]+'-'+r[0][k].selected[1], id:r[0][k].id})
						}						
						else if( r[0][k].selected.length )
							for (i in r[0][k].filters)
							{
								if ( r[0][k].selected.includes(r[0][k].filters[i].id) )
									a.push({name:r[0][k].filters[i].name, id:r[0][k].id, filter_id:r[0][k].filters[i].id})
							}
					}
					this.attributes = a;								
				//	this.custom = r[1];			
				})
			},
			methods: {	
				del(k)
				{ 												
					eventBus.$emit('change_selected_attributes', this.attributes[k]);
				}
			}
		})
	}
})