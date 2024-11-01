var products = {
	watch: {
		id:function (val, oldVal) 
		{
			if ( val )
			{
				this.tab = 'product';
				this.loadProduct();				
			}
			else
			{ 
				this.product = {};
				this.requestData();
			}
			let url = new URL( document.location.href );
			if( this.tab=='list' )			
				url.searchParams.delete('id');
			else
				url.searchParams.set('id', val);
			history.pushState(null, null, url.href);
		},
		tab:function (val, oldVal) 
		{ 
			if( val=='list' )
			{
				this.id = 0;
				this.product = {};
			}
			else if( val=='product' )
			{
				if ( !this.id && !Object.keys(this.product).length )
				{
					this.defaultPropertyProduct();
					this.productLoaded = true;
				}
			}
			var d = document.querySelector("h1");
			if ( d )
				d.scrollIntoView({behavior:'smooth', block:'start'});
			else
				document.body.scrollIntoView({behavior:'smooth', block:'start'});
		},
		page:function (val, oldVal) 
		{ 
			if( val!==oldVal )
				this.requestData();
		},
	},
	data() {
		return {
			tab:'list',			
			items:[],
			loaded:false,
			request:false,			
			count:0,
			page:1,
			menu:null,			
		}
	},	
	methods: {				
		requestData( data )
		{
			this.menu = null;
			if ( data === undefined)
				data = {};
			if ( data.page_sorting !== undefined)
			{
				let str = data.page_sorting.split('-');
				delete data.page_sorting;
				data.orderby = str[0];
				data.order = str[1];
			}			
			this.request = true;
			Object.assign(data, {seller:'my', paged:this.page, add_fields:['basket', 'desired', 'views', 'category', 'price_currency', 'attributes', 'images']});
			usam_api('products', data, 'POST', (r) => {				
				this.loaded = true;	
				this.request = false;
				this.items = r.items;
				this.count = r.count;
			});	
		},		
		setProperty(k, data)
		{					
			Object.assign(this.items[k], data);
			this.saveProduct(this.items[k].ID, data);
		},	
		localDate(d, f)
		{
			return local_date(d, f, false);
		},
		menuOpen(k)
		{								
			this.menu = this.menu === null || this.menu !== k ? k : null;
			setTimeout(()=> document.addEventListener("click", this.menuClose), 300);
		},
		menuClose(e)
		{
			var el = e.target.closest('.action_menu');
			if ( !el )
				this.menu = null;
			document.removeEventListener("click", this.menuClose);
		},
		displayProperty(v)
		{
			return v.join(', ');
		},
		getPropertiesDisplay(p)
		{
			if ( p.attributes == undefined )
				return [];		
			const asArray = Object.entries(p.attributes);
			const filtered = asArray.filter(x => x[1].parent!==0 && (typeof x[1].value === 'object' || x[1].value.length) );
			return Object.values(Object.fromEntries(filtered));
		},
		deleteProduct(k)
		{	
			usam_api('product/'+this.items[k].ID, 'DELETE', () => {
				if ( !this.items[k].length && this.count )
					this.requestData();
			});
			this.count--;
			this.items.splice(k, 1);
		},		
		updateProduct(e)
		{ 				
			for (k in this.items)		
			{
				if ( this.items[k].ID == this.id )
				{
					Object.assign(this.items[k], this.product);	
					break;
				}
			}
			this.saveProduct(this.id, this.getDataSave());
		}			
	}
}
var files = {
	data() {
		return {					
			items:[],			
			query_vars:{},				
			loaded: false,
			request: false,
			count:0,
			page:1	
		}
	},
	watch: {				
		page:function (val, oldVal) 
		{ 
			if( val!==oldVal )
				this.requestData();
		},
	},	
	mounted: function () {
		this.requestData(); 
	},
	methods: {	
		requestData( data )
		{								
			if ( data === undefined)
				data = {};						
			data = Object.assign(data, this.query_vars);
			data.paged = this.page;
			this.request = true;
			usam_api('files', data, 'POST', (r) => {
				this.loaded = true;	
				this.request = false;
				this.items = r.items;
				this.count = r.count;
			});	
		},
		localDate(d, f)
		{
			return local_date(d, f, false);
		},
	}
}
var orders = {
	data() {
		return {					
			filtersData:{},
			items:[],			
			order:{},
			request: false,
			propertyGroups:[],					
			key:false,
			tab:'list',	
			loaded: false,
			query_vars: {user:'my', fields:['products', 'status_data', 'properties', 'shipping', 'payments'], count:20},
			loadedOrder:false,								
			loadedItems:false,
			count:0,
			page:1,	
			menu:null,			
		}
	},
	watch: {
		key(val, oldVal) 
		{ 
			let url = new URL( document.location.href );		
			if ( val!== null )
			{
				if ( this.tab == 'list' )
					this.tab = 'order';
				this.order = this.items[this.key];										
				url.searchParams.set('id', this.order.id);	
				var d = document.querySelector("h1");
				if ( d )
					d.scrollIntoView({behavior:'smooth', block:'start'});
				else
					document.querySelector(".lists").scrollIntoView({behavior:'smooth', block:'start'});
			}
			else
			{
				this.tab ='list';	
				url.searchParams.delete('id');
			}
			history.pushState(null, null, url.href);
		},
		tab(val, oldVal) 
		{
			if ( val == 'list' )
			{
				if ( !this.loadedItems )
					this.requestData(); 
			}
		},
		page(val, oldVal) 
		{ 
			if( val!==oldVal )
				this.requestData();
		},		
	},		
	methods: {				
		loadOrder()
		{
			this.tab = 'order';					
			usam_api('order/'+this.id, {add_fields:'properties,groups,products.shipping,payments'}, 'GET', (r) => {	
				this.loadedOrder = true;	
				this.loaded = true;							
				this.order = r;
			});	
		},
		requestData( d )
		{						
			if ( d === undefined)
				data = {};
			else
				data = structuredClone(d);
			if ( data.page_sorting !== undefined)
			{
				let str = data.page_sorting.split('-');
				delete data.page_sorting;
				data.orderby = str[0];
				data.order = str[1];
			}
			Object.assign(data, this.query_vars);
			data.paged = this.page;
			this.request = true;			
			usam_api('orders', data, 'POST', (r) => {
				this.loadedItems = true;	
				this.loaded = true;	
				this.request = false;
				for( let k in r.items )						
					for( let i in r.items[k].products )
					{
						r.items[k].products[i].hover = 0;
						r.items[k].products[i].myrating = 0;
					}				
				this.items = r.items;
				this.count = r.count;			
			});	
		},	
		menuOpen(k)
		{					
			this.menu = this.menu === null || this.menu !== k ? k : null;
			setTimeout(()=> document.addEventListener("click", this.menuClose), 300);
		},						
		menuClose(e)
		{
			var el = e.target.closest('.action_menu');
			if ( !el )
				this.menu = null;
			document.removeEventListener("click", this.menuClose);		
		},
		localDate(d, f)
		{
			return local_date(d, f, false);
		},
		check_group(code)
		{
			for (k in this.order.properties)
			{
				if ( this.order.properties[k].group==code )
					return true;
			}
			return false;
		},
		addToCart(k, url)
		{
			usam_api('basket/order/'+this.items[k].id, 'GET', (r) => window.location.replace(url));
		},
		orderCopy(k)
		{
			usam_api('order/copy/'+this.items[k].id, 'GET', (r) => {	
				this.items.unshift(r);
				this.key = 0;
			});					
		},	
		cancelOrder()
		{					
			if ( this.items[this.key].cancellation_reason )
			{
				this.items[this.key].status = 'canceled';
				this.items[this.key].status_is_completed = true;
				usam_api('order/'+this.items[this.key].id, {status:'canceled', cancellation_reason:this.items[this.key].cancellation_reason}, 'POST');
			}
		},
		displayCancelOrder(k)
		{
			if ( !this.items[k].status_is_completed )
			{
				this.tab = 'cancel_order';
				this.key = k;					
			}
		},
		updateProductRating(p, n)
		{
			if ( !p.myrating )
			{
				p.myrating = n;
				update_product_rating( p.product_id, n );
			}
		}
	}
}

var profile = {
	data() {
		return {				
			tab:'view',	
			contact:{},		
			company:{},	
			profile_type:'contact',
			subscriptions:[],		
			pass:'',	
			pass2:'',
			error:'',		
			send:false,	
			codeError:false,				
			loaded:false,		
			confirmAction:'',					
		};
	},	
	watch: {
		tab:function (val, oldVal) {
			document.body.scrollIntoView({block: "center", behavior: "smooth"});
		}
	},
	methods: {
		change_contact(e)
		{ 	
			this.contact[e.code] = e.id;				
		},			
		change_pass(e)
		{
			this.codeError = false;
			var number = this.pass.replace(/\D/g, '');
			if ( !this.pass || this.pass !== this.pass2 )			
				this.codeError = 'pass_notequal';				
			else if ( this.pass.length < 6 )
				this.codeError = 'pass_smalllength';
			else if ( this.pass == number || number == '' )
				this.codeError = 'simple';
			if ( !this.codeError )
			{				
				this.send = true;
				usam_api('user/password', {pass:this.pass}, 'POST', (r) => { 
					this.send = false; 
					if ( r.result )
					{
						usam_notifi({'text': USAM_THEME.message_savepassword}) 
						this.timerId = setTimeout(()=> document.location.href = r.redirect_to, 1000);
					}
				});
			}
		},
		save()
		{ 
			if ( this.confirm && !this.send )
			{ 							
				if ( this.propertiesVerification() )
					return;					 
				
				var data = {subscriptions:{}, contact:{}};		
				Object.assign(data.contact, this.contact);			
				for (let k in this.properties)
					data[this.properties[k].code] = this.properties[k].value;
				for (let k in this.subscriptions)
					data.subscriptions[this.subscriptions[k].id] = this.subscriptions[k].subscribe ? 1 : 2;				
			
				this.send = true;
				usam_api('profile', data, 'POST', (r) => {
					this.send = false;
					usam_notifi({'text': USAM_THEME.message_saved});
				});
			}
		},
		save_subscriptions()
		{ 
			var data = {communication:'email', lists:{}};
			for (let k in this.subscriptions)
				data.lists[this.subscriptions[k].id] = this.subscriptions[k].subscribe ? 1 : 2;
			usam_api('contact/subscriptions', data, 'POST', (r) => usam_notifi({'text': USAM_THEME.message_saved}) );
		},		
		deleteProfile(e)
		{
			usam_api('user', 'DELETE', (r) => {							
				usam_notifi({'text': USAM_THEME.message_deleted});
				document.location.href = document.location.href;
			});		
		},				
	}
}

document.addEventListener("DOMContentLoaded", () => {		
	if( document.getElementById('my-profile') )
	{
		new Vue({		
			el: '#my-profile',				
			mixins: [profile, edit_properties],
			mounted() {
				usam_api('profile', 'GET', (r) => {				
					this.contact = r.contact;
					this.company = r.company;
					this.profile_type = r.profile_type;
					this.properties = this.propertyProcessing(r.properties);
					this.propertyGroups = r.groups
					for (let k in this.properties)
						this.$watch(['properties', k].join('.'), this.propertyChange, {deep:true});
					this.loaded = true;
				});				
				usam_api('mailing_lists', {subscribed:'email', view:1}, 'POST', (r) => {
					this.loaded = true;
					this.subscriptions = r.items;
					for (let k in this.subscriptions)
						this.$watch(['subscriptions', k].join('.'), this.save_subscriptions, {deep:true});	
				})
			}			
		})	
	}
	else if( document.getElementById('my-referral') )
	{
		new Vue({		
			el: '#my-referral',
			data() {
				return {
					loaded:false,
					coupon:{},
					referral:{}
				}
			},
			mounted() {				
				usam_api('referral', 'GET', (r) => {	
					this.loaded = true;
					this.coupon = r.coupon;	
					this.referral = r.referral;
				});
			},
			methods: {				
				clipboard(e)
				{ 						
					usam_copy_clipboard(e.currentTarget, 'Скопировано');					
				}		
			}
		})
	}
	else if( document.getElementById('my-company') )
	{
		new Vue({		
			el: '#my-company',
			data() {
				return {
					tab:'list',
					inn:'',	
					ppc:'',
					company_key:null,
					company:'',				
					companies:[],
					propertyGroups:[],			
					properties:{},
					send:false,
					loaded:false,
				}
			},
			mounted() {
				usam_api('companies', {user_id:-1,fields:'properties'}, 'POST', (r) => {	
					for (k in r.items)
						r.items[k].delete = 0;			
					this.companies = r.items;
					this.loaded = true;
				});
				usam_api('property_groups', {type:'company'}, 'POST', (r) => this.propertyGroups = r.items);					
			},
			methods: {				
				search()
				{ 						
					this.company_key=null;
					if ( this.inn )
					{
						var handler = (r) => {	
							this.properties = r.properties;								
							this.tab='company';					
							this.company = {id:r.id, name: r.name};
							this.inn = '';
							this.ppc = '';
						};
						usam_api('company/search', {inn: this.inn, ppc: this.ppc}, 'GET', handler);
					}
				},
				open(i)
				{	
					this.properties = this.companies[i].properties;				
					this.company = {id:this.companies[i].id, name: this.companies[i].name};
					this.company_key=i;			
					this.tab='company';
				},	
				get_save()
				{ 	
					let data = {};
					for (let k in this.properties)
						data[this.properties[k].code] = this.properties[k].value;
					return data;
				},
				add()
				{ 						
					let data = this.get_save();
					data.user_id = -1;
					
					handler = (id) => {	
						if ( id )
						{					
							let company = {properties: this.properties};			
							company.name = this.properties.company_name !== undefined ? this.properties.company_name.value : this.properties.full_company_name.value;						
							company.id = this.company.id ? this.company.id : id;						
							company.delete = 0;	
							this.companies.unshift(company);
							this.tab = 'list';
						}				
					};
					if ( this.company.id )
						usam_api('company/'+this.company.id, data, 'POST', handler);
					else
						usam_api('company', data, 'POST', handler);
				},
				edit()
				{ 	
					if ( this.send )
						return;
					handler = (r) => {	
						this.send = false;
						if ( r ) 
							usam_notifi({'text': USAM_THEME.message_saved});			
					};	
					this.send = true;				
					let data = this.get_save();
					usam_api('company/'+this.company.id, data, 'POST', handler);
				},
				change_company(e)
				{				
					this.properties[e.code].value = e.name;
					for (k in this.properties)
					{
						if ( typeof e[k] !== typeof undefined )
						{
							this.properties[k].value = e[k];						
							if ( this.properties[k].field_type=='location' )							
								this.properties[k].search = e._name_legallocation;
						}
					}	
				},
				check_group(code)
				{
					for (k in this.properties)
					{
						if ( this.properties[k].group==code )
							return true;
					}
					return false;
				},
				del(k)
				{
					usam_api('company/'+this.companies[k].id, {"user_id":0}, 'POST');
					this.companies.splice(k, 1); 
				},		
			}
		})
	}
	else if( document.getElementById('products') )
	{
		new Vue({			
			el: '#products',
			mixins: [data_filters, add_product, products],
			mounted() {
				let url = new URL( document.location.href );
				if ( url.searchParams.get('id') )  
					this.id = url.searchParams.get('id');
				
				this.requestData(); 
				this.loadCategories(); 
			}
		})
	}
	else if( document.getElementById('my-orders') )
	{ 
		new Vue({			
			el: '#my-orders',				
			mixins: [data_filters, orders],
			mounted() {
				let url = new URL( document.location.href );
				if ( url.searchParams.get('id') )  
				{
					this.id = url.searchParams.get('id');
					this.loadOrder();
				}
				else
					this.requestData(); 
				usam_api('property_groups', {type:'order'}, 'POST', (r) => this.propertyGroups = r.items );
			}	
		})
	}
	else if( document.getElementById('notifications') )
	{ 
		new Vue({			
			el: '#notifications',		
			data() {
				return {					
					items:[],					
					loaded: false,
					request: false,
					query_vars: {author:'my', count:20},	
					count:0,
					page:1	
				}
			},
			watch: {				
				page:function (val, oldVal) 
				{ 
					if( val!==oldVal )
						this.requestData();
				},
			},	
			mounted: function () {
				this.requestData(); 
			},
			methods: {	
				requestData( data )
				{								
					if ( data === undefined)
						data = {};						
					Object.assign(data, this.query_vars);
					data.paged = this.page;
					this.request = true;
					usam_api('notifications', data, 'POST', (r) => {
						this.loaded = true;	
						this.request = false;
						this.items = r.items;
						this.count = r.count;
					});	
				},
				localDate(d, f)
				{
					return local_date(d, f, false);
				},
			}
		})
	}	
	else if( document.getElementById('my-file') )
	{ 
		new Vue({			
			el: '#my-file',		
			mixins: [files],			
			data() {
				return {
					query_vars: {user_id:'current', count:20},	
				}
			}
		})
	}
	else if( document.getElementById('my-downloadable') )
	{ 
		new Vue({			
			el: '#my-downloadable',		
			mixins: [files],			
			data() {
				return {
					query_vars: {purchased_user_files:1, count:20},	
				}
			}
		})
	}
	else if( document.getElementById('messenger') )
	{ 
		new Vue({			
			el: '#messenger',		
			mixins: [chat],	
			watch:{
				id(val, oldVal) 
				{
					let url = new URL( document.location.href );
					if ( val && val != oldVal )
					{
						this.loadDialog( val );
						url.searchParams.set('id', val);						
					}
					else
					{
						this.messages = [];
						url.searchParams.delete('id');
					}
					history.pushState({'url' : url.href}, '', url.href);
				},				
			},	
			data() {
				return {
					query_vars: {add_fields:['end_message'], user:'my', order:'DESC', orderby:'date_insert', count:10},	
				}
			},			
			mounted() {
				this.loadDialogs(); 
				let url = new URL( document.location.href );
				if ( url.searchParams.get('id') ) 
					this.id = url.searchParams.get('id');
			},
			methods: {				
				loadDialogs( data ) 
				{
					if ( data === undefined)
						data = {};						
					Object.assign(data, this.query_vars);
					data.paged = this.page;
					this.request = true;
					usam_api('chat/dialogs', data, 'POST', (r) => {
						this.loaded = true;	
						this.request = false;
						this.dialogs = r.items;
						this.count = r.count;
					});						
				}								
			}
		})
	}
	else if( document.getElementById('seller-orders') )
	{ 
		new Vue({			
			el: '#seller-orders',				
			mixins: [data_filters],
			data() {
				return {					
					items:[],
					order:{},		
					propertyGroups:[],					
					key:false,
					tab:'list',	
					loaded: false,
					loadedOrder:false,								
					loadedItems:false,
					count:0,
					page:1,	
					menu:null,
				}
			},
			watch: {
				key:function (val, oldVal) 
				{ 
					let url = new URL( document.location.href );		
					if ( val!== null )
					{
						if ( this.tab == 'list' )
							this.tab = 'order';
						this.order = this.items[this.key];										
						url.searchParams.set('id', this.order.id);	
						document.querySelector("h1").scrollIntoView({behavior:'smooth',  block:'start'});						
					}
					else
					{
						this.tab ='list';	
						url.searchParams.delete('id');
					}
					history.pushState(null, null, url.href);
				},
				tab:function (val, oldVal) 
				{
					if ( val == 'list' )
					{
						if ( !this.loadedItems )
							this.requestData(); 
					}
				},
				page:function (val, oldVal) 
				{ 
					if( val!==oldVal )
						this.requestData();
				},
			},			
			mounted: function () {
				let url = new URL( document.location.href );
				if ( url.searchParams.get('id') )  
				{
					this.id = url.searchParams.get('id');
					this.loadOrder();
				}
				else
				{
					this.loaded = true;	
					this.requestData();
				}
				usam_api('property_groups', {type:'shipped'}, 'POST', (r) => this.propertyGroups = r.items );
			},	
			methods: {				
				loadOrder()
				{
					this.tab = 'order';					
					usam_api('shipped/'+this.id, {add_fields:'products'}, 'GET', (r) => {	
						this.loadedOrder = true;	
						this.loaded = true;							
						this.order = r;
					});	
				},
				requestData( data )
				{					
					if ( data === undefined)
						data = {};
					if ( data.page_sorting !== undefined)
					{
						let str = data.page_sorting.split('-');
						delete data.page_sorting;
						data.orderby = str[0];
						data.order = str[1];
					}
					Object.assign(data, {user:'my', paged:this.page, fields:['products', 'status_data', 'properties'], count:20});
					usam_api('shippeds', data, 'POST', (r) => {
						this.loadedItems = true;
						this.items = r.items;
						this.count = r.count;
					});	
				},	
				menuOpen(k)
				{					
					this.menu = this.menu === null || this.menu !== k ? k : null;
					setTimeout(()=> document.addEventListener("click", this.menuClose), 300);
				},						
				menuClose(e)
				{
					var el = e.target.closest('.action_menu');
					if ( !el )
						this.menu = null;
					document.removeEventListener("click", this.menuClose);		
				},	
				localDate(d, f)
				{
					return local_date(d, f, false);
				},
				check_group(code)
				{
					for (k in this.order.properties)
					{
						if ( this.order.properties[k].group==code )
							return true;
					}
					return false;
				}		
			}
		})
	}
	else if( document.getElementById('my-contacting') )
	{ 
		new Vue({			
			el: '#my-contacting',				
			mixins: [webform, data_filters],
			data() {
				return {					
					code:'my-appeal',
					items:[],					
					data:{},
					newdata:{},					
					key:false,
					tab:'list',	
					loaded: false,
					loadedEvent:false,								
					loadedItems:false,
					count:0,
					page:1,
					menu:null,
					request: false,
				}
			},
			watch: {
				key:function (val, oldVal) 
				{ 
					let url = new URL( document.location.href );
					if ( val!== null )
					{
						if ( this.tab == 'list' )
							this.tab = 'contacting';
						this.data = this.items[this.key];
						this.properties = this.propertyProcessing( this.items[this.key].properties );
						this.propertyGroups = this.items[this.key].groups;
						url.searchParams.set('id', this.data.id);	
						document.querySelector("h1").scrollIntoView({behavior:'smooth',  block:'start'});						
					}
					else
					{
						this.tab='list';						
						url.searchParams.delete('id');
						if ( !this.loadedItems )
							this.requestData(); 
					}
					history.pushState(null, null, url.href);
				},
				tab:function (val, oldVal) 
				{
					if( val == 'list' )
					{
						let url = new URL( document.location.href );
						url.searchParams.delete('id');
						history.pushState(null, null, url.href);
						if ( !this.loadedItems )
							this.requestData(); 
					}
					else if( val == 'new_contacting' )
					{
						this.data = this.newdata.webform;
						this.properties = this.propertyProcessing( this.newdata.properties );
					}
				},
				page:function (val, oldVal) 
				{ 
					if( val!==oldVal )
						this.requestData();
				},
			},			
			mounted: function () {
				let url = new URL( document.location.href );
				if ( url.searchParams.get('id') )  
				{
					this.id = url.searchParams.get('id');
					this.loadContacting();
				}
				else
				{					
					this.requestData();
				}		
				usam_api('webform/'+this.code, 'GET', (r) => this.newdata = r);	
			},	
			methods: {				
				loadContacting()
				{
					this.tab = 'contacting';					
					this.request = true;
					usam_api('contacting/'+this.id, {add_fields:['webform', 'request_solution', 'status_data']}, 'GET', (r) => {	
						this.loadedEvent = true;	
						this.loaded = true;		
						this.request = false;
						this.properties = this.propertyProcessing(r.properties);
						this.propertyGroups = r.groups;		
						delete r.properties;
						delete r.groups;
						this.data = r;													
					});	
				},					
				requestData( data )
				{					
					if ( data === undefined )
						data = {};
					if ( data.page_sorting !== undefined)
					{
						let str = data.page_sorting.split('-');
						delete data.page_sorting;
						data.orderby = str[0];
						data.data = str[1];
					}		
					Object.assign(data, {author:'my', paged:this.page, add_fields:['status_data', 'request_solution', 'author'], count:20, orderby: 'date', order: 'DESC'});
					usam_api('contactings', data, 'POST', (r) => {
						this.loadedItems = true;
						this.loaded = true;	
						this.items = r.items;
						this.count = r.count;
					});	
				},					
				localDate(d, f)
				{
					return local_date(d, f, false);
				},	
				processSendResponse(r) 		
				{ 		
					this.send = false;				
					this.message_result = r;
					this.requestData();
				},				
			}
		})
	}
})