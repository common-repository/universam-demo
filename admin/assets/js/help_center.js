var help_center;
document.addEventListener("DOMContentLoaded", () => {
if( document.getElementById('help_center') )
{
	document.getElementById('usam_help_center_handle').addEventListener("click", (e)=>{ help_center.open = !help_center.open });
	
	help_center = new Vue({
		el: '#help_center',		
		data() {
			return {
				open:false,
				search:'',
				search_results:{items:[], count:null},	
				message_search:false,
				email_subject:'info',
				email_message:'',
				send_message:false,
				paged:1,
				loadMore:true,				
				messages:[],
				message_loading:false,
				Observer:false,				
				tabs:[],	
				active_tab:'search',				
			};
		},		
		watch:{
			tab: function (val, oldVal) 
			{
				if ( val == 'support-messages' )
					this.enable_message_receiving();
			},
		},
		created: function () {
			this.tabs = HC.tabs;	
		},
		mounted: function () {	
			this.enable_message_receiving();
		},
		methods: {
			start_search()
			{				
				this.message_search = true;
				var callback = (r) => {
					this.search_results = r;	
					this.message_search = false;					
				}
				usam_api('knowledge_base', {search:this.search}, 'GET', callback);				
			},
			send_email()
			{
				if ( this.email_message != '' )
				{
					this.send_message = true;
					var callback = (r) => {
						this.send_message = false;						 					
						this.email_message = '';
						this.paged = 1;
						this.load_messages();
					};
					usam_api('support_messages', {subject:this.email_subject, message:this.email_message}, 'POST', callback);
				}			
			},
			load_messages()
			{				
				if ( !this.message_loading )
				{
					this.message_loading = true;
					var callback = (r) => {
						this.message_loading = false;
						if ( r.items.length )
						{
							if ( this.paged == 1 )
								this.messages = r.items;
							else
							{
								for (let k in r.items)							
									this.messages.push(r.items[k]);
							}
							this.paged++;
						}
						else
						{							
							this.Observer.unobserve( document.querySelector('.js-load-support-messages') );
							this.loadMore = false;
						}
					};
					usam_api('support_messages', {paged:this.paged}, 'POST', callback);
				}
			},
			enable_message_receiving()
			{
				this.Observer = new IntersectionObserver((el, Observer) => { 
					el.forEach((entry) => {
						if ( entry.isIntersecting )	
							this.load_messages();
					})
				});
				this.Observer.observe( document.querySelector('.js-load-support-messages') );	
			}
		}
	})
}
})