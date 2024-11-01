document.addEventListener("DOMContentLoaded", () => {	
	new Vue({	
		el: '#view_tape',
		data() {
			return {	
				openWindow:false,
				window:'notifications',
				items:[],
				loaded:false,
				request:false,			
				count:0,
				page:0,
			}
		},
		computed: {		
			statusStarted: function () {	
				for (let k in this.items)
				{
					if ( this.items[k].status==='started' )
						return true;						
				}
				return false;
			},		
		},
		watch: {				
			window:function (val, oldVal) 
			{					
				if( val!==oldVal )
				{
					Vue.set(this, 'items', []);
					this.loaded = false;
					this.page = 0;
				//	this.requestData();
				}
			},
			openWindow:function (val, oldVal) 
			{					
				if( !val )
				{
					var b = document.querySelector('.usam_backdrop');
					!b || b.remove();
				}
				else
				{
					this.loadingMoreResults();	
					add_backdrop();
				}
			},
		},	
		mounted: function () {		
			var el = document.querySelector('#wp-admin-bar-menu-tape');
			if ( el )
				el.addEventListener('click', this.open);					
		},		
		methods: {			
			open()
			{ 				
				this.openWindow = !this.openWindow;
			},
			requestData( data )
			{
				this.count = 0;
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
				if ( this.window == 'notifications' )
				{
					Object.assign(data, {paged:this.page, orderby:'date', order:'desc', number:10, fields:['author', 'objects']});
					usam_api('notifications', data, 'POST', (r) => {	
						this.loaded = true;						
						this.request = false;						
						for (let i in r.items)	
							this.items.push(r.items[i]);				
						this.count = r.count;
					});	
				}
				else
				{					
					Object.assign(data, {paged:this.page, orderby:'comment_date', order:'desc', number:10, fields:['comments', 'author', 'object'], type:['task', 'meeting', 'call', 'sent_letter','inbox_letter', 'contacting']});
					usam_api('events', data, 'POST', (r) => {						
						for (let i in r.items)
						{
							r.items[i].comment = false;		
							r.items[i].new_comment = '';							
							for (let k in r.items[i].comments.items)
							{
								r.items[i].comments.items[k].message_html = r.items[i].comments.items[k].message.replace(/\n/g,"<br>");
								r.items[i].comments.items[k].show = k==0?true:false;								
							}
							this.items.push(r.items[i]);	
						}
						this.loaded = true;	
						this.request = false;
						this.count = r.count;
					});							
				}
			},	
			addComment(k) 
			{ 							
				if ( this.items[k].new_comment !== '' )
				{ 
					usam_api('comment', {object_id:this.items[k].id, object_type:'event', message:this.items[k].new_comment}, 'POST', (r) => {
						if ( r )
						{
							r.message_html = r.message.replace(/\n/g,"<br>");	
							this.items[k].comments.items.unshift(r);
						}
					});
					this.items[k].comment = false;
					this.items[k].new_comment = '';	
				}
			},	
			loadСomments(k) 
			{ 							
				var getApi = true;
				for (let i in this.items[k].comments.items)
				{
					if ( !this.items[k].comments.items[i].show )
						getApi = false;
					this.items[k].comments.items[i].show = true;			
				}
				if ( getApi )
				{					
					usam_api('comments', {object_id:this.items[k].id, object_type:'event', order:'DESC', status:0, offset:this.items[k].comments.items.length}, 'POST', (r) =>
					{						
						for (let j in r.items)
						{	
							r.items[j].message_html = r.items[j].message.replace(/\n/g,"<br>");	
							this.items[k].comments.items.push(r.items[j]);			
						}
					});
				}
			},		
			lookedAllNotifications() 
			{ 
				for (let i in this.items)					
					this.items[i].status = 'completed';				
				usam_api('notifications/read', 'GET');
				var el = document.querySelector('#wp-admin-bar-menu-tape .numbers');
				if ( el )
					el.innerHTML = '';				
			},			
			loadingMoreResults() 
			{ 
				if ( this.count !== this.items.length || !this.loaded )
				{ 
					const Observer = new IntersectionObserver((el, Ob) => {
						el.forEach((entry) => {
							if (entry.isIntersecting)
							{ 									
								this.page++;
								this.requestData();
							}
						})
					});			
					document.querySelectorAll('#view_tape .js-load-more').forEach((v) => {
						Observer.observe(v);
					}) 	
				}
			},
			localDate(d, f)
			{
				return local_date(d, f, false);
			},			
		}	
	})
})