document.addEventListener("DOMContentLoaded", () => {			
	if( document.getElementById('tab_backup_content') )
	{
		new Vue({		
			el: '#tab_backup_content',
			data() {
				return {					
					options:{backup_bd_active:0},			
				};
			},	
			mounted: function () 
			{	
				this.options = usam_options;
			},
			methods: {				
				backup_bd(){ 				
					usam_active_loader();
					usam_send({nonce: USAM_Tabs.bulkactions_nonce, action: 'bulkactions', a: 'backup_bd', item:'tools'});
				},
				backup_themes(){ 				
					usam_active_loader();	
					usam_send({nonce: USAM_Tabs.bulkactions_nonce, action: 'bulkactions', a: 'backup_themes', item:'tools'});
				},				
				save(){ 				
					usam_send({nonce: USAM_Tabs.save_option_nonce, action: 'save_option', options: this.options});
				},
			}
		})	
	}
	if( document.getElementById('tab_theme_file_content') )
	{
		new Vue({		
			el: '#tab_theme_file_content',
			data() {
				return {					
					templates:[],				
				};
			},			
			mounted: function () {							
				this.templates = templates;
				for (var k in templates)
				{
					Vue.set(this.templates, k, templates[k]);
				}
			},	
			methods: {
				save()
				{
					cb = [];
					i = 0;
					for (var k in this.templates)
					{							
						if ( this.templates[k].selected )
						{
							cb[i] = this.templates[k].name;
							i++;
						}
					} 
					usam_send({nonce: USAM_Tabs.bulkactions_nonce, action: 'bulkactions', a: 'move_themes', item:'tools', cb:cb});
				},
			}
		})	
	}
	if( document.getElementById('tab_nuke_content') )
	{
		new Vue({		
			el: '#tab_nuke_content',
			data() {
				return {					
					actions:[],
					directory:[],					
				};
			},			
			methods: {				
				del(){ 				
					usam_active_loader();					
					usam_send({nonce: USAM_Tabs.bulkactions_nonce, action: 'bulkactions', a: this.actions, item:'nuke'});
					this.actions = [];
				},
				perform_action(){ 				
					usam_active_loader();
					usam_send({nonce: USAM_Tabs.bulkactions_nonce, action: 'bulkactions', a: 'directory', item:'nuke', cb: this.directory});
					this.directory = [];
				}
			}
		})			
	}
	if( document.getElementById('tab_log_content') )
	{
		new Vue({		
			el: '#tab_log_content',
			data() {
				return {					
					ids:[],
					action:'',	
					file:'',					
					autorefresh:false,		
					timerId:false,						
				};
			},			
			watch:{
				autorefresh: function (val, oldVal) 
				{
					if ( val )
					{
						document.cookie = "autorefresh=1; path=/";
						this.timerId = setInterval(this.refresh, 30000 );
					}
					else if ( this.timerId )
					{
						document.cookie = "autorefresh=0; path=/";
						clearTimeout(this.timerId);
					}
				},
			},
			mounted: function () 
			{
				var file = document.querySelector('input[name="file"]');
				if ( file )
					this.file = file.value;			
				var textarea = document.getElementById('newcontent');				
				if ( textarea )
					textarea.scrollTop = textarea.scrollHeight;
				if ( getCookie('autorefresh') == 1 )
					this.autorefresh = true;
			},
			methods: {				
				del(){ 
					callback = (response) => {
						window.location.replace( location.href );
					}
					usam_send({nonce: USAM_Tabs.bulkactions_nonce, action: 'bulkactions', a: 'delete', item:'log', cb:this.ids}, callback);
				},
				bulkaction(){ 
					if ( this.action )
					{						
						callback = (response) => {
							for (var key in response)
							{							
								usam_set_url_attr( key, response[key] );
							}
							window.location.replace( location.href );
						} 
						usam_send({nonce: USAM_Tabs.bulkactions_nonce, action: 'bulkactions', a: this.action, item:'log', cb:[this.file]}, callback);
					}
				},				
				checked_all(e){ 					
					if ( e.target.checked )
					{
						document.querySelectorAll('#file_list ul input[type="checkbox"]').forEach((e, i) => {
							Vue.set(this.ids, i, e.value);											
						});	
					}
					else
						this.ids = [];
					
				},
				refresh(){   					
					window.location.replace(document.URL);
				},
			}
		})	
	}
})