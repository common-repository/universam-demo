document.addEventListener("DOMContentLoaded", () => {	
	if( document.querySelector('.files_grid') )
	{
		new Vue({		
			el: '.files_grid',
			data() {
				return {
					folders:[],
					files:[],					
					folder:0,
					total_items:0,
					trash:false,					
					folder_menu:false,		
					file_menu:false,
					file_timeoutId:false,
					folder_timeoutId:false,					
					breadcrumbs:[],		
					request_counter:0,	
					move: false,
					attachments:[],
					object_id:0,
					object_type:'file'
				};
			},			
			mixins: [data_filters],
			beforeMount() {				
				let url = new URL( document.location.href );	
				let params = new URLSearchParams(url.search);
				this.folder = Number(params.get('folder'));			
				this.requestData();
			},
			computed: {		
				numberSelectedItems() {	
					return this.folders.filter(x => x.checked).length+this.files.filter(x => x.checked).length;
				}
			},
			mounted() {	
				var el = document.querySelector('#button-add_folder');
				if ( el )
					el.addEventListener("click", this.add_folder );
				el = document.querySelector('#button-add_files');
				if ( el )
					el.addEventListener("click", this.fileAttach );
				document.addEventListener("click", this.clickEvent );
			},	
			methods: {
				clickEvent(e) { console.log(e.target);			
					if( e.target.closest('.menu_content') )
						return false;
					if( e.target.closest('.grid_item_folder') || e.target.closest('.grid_item_file') )
						return false;
					if( this.folder_menu || this.file_menu )
					{
						this.folder_menu = false;
						this.file_menu = false;
						if ( document.querySelector('.files_grid .active') )
							document.querySelector('.files_grid .active').classList.remove('active');
					}				
					this.cancelSelected();
				},
				cancelSelected() {
					for (let i in this.folders)
						this.folders[i].checked = false;
					for (let i in this.files)
						this.files[i].checked = false;
				},
				requestData( args, e )
				{ 								
					if ( args == undefined )						
						args = {};	
					else
						usam_active_loader();
					
					this.request_counter = 0;
					this.total_items = 0;
					this.folders = [];
					this.files = [];					
					let url = new URL( document.location.href );	
					let params = new URLSearchParams(url.search);
					if ( params.get('tab') == 'my_files' )
						args.user_id = 'current';					
					data = args;
					data.breadcrumbs = true;
					if ( this.trash )
						data.status = 'delete';
					else if ( args.search == undefined )
						data.parent = this.folder; 
					usam_api('folders', data, 'POST', (r) => { 
						this.request_counter++;					
						for (let k in r.items)	
						{							
							r.items[k].checked = false;
						}
						this.folders = r.items;
						this.breadcrumbs = r.breadcrumbs;
						this.total_items += r.count;	
						this.dataProcessing();						
					});									
					data = args;				
					if ( this.trash )
						data.status = 'delete';
					else if ( args.search == undefined )
						data.folder = this.folder;
					usam_api('files', data, 'POST', (r) => { 					
						this.request_counter++;
						for (let k in r.items)	
						{
							if ( k > 30 )
							{
								r.items[k].lzy = r.items[k].icon;
								r.items[k].icon = '';								
							}
							else
								r.items[k].lzy_icon = '';							
							r.items[k].checked = false;							
						}
						this.files = r.items;						
						this.total_items += r.count;						
						this.dataProcessing();
					});
				},
				dataProcessing()
				{ 
					if ( this.request_counter == 2 )
					{				
						setTimeout(()=> { 								
							var el = document.querySelector('.js-file-drop');
							let height = document.querySelector('#wpbody').offsetHeight - el.getBoundingClientRect().height;								
							if ( el.offsetHeight < height )
								el.style.height = height + 'px';
						}, 500);
						if ( this.files.length > 30 )
							setTimeout(()=>{ usam_lazy_image(); }, 500);	
					}
				},
				add_folder(e)
				{  
					e.preventDefault();						
					usam_api('folder', {parent:this.folder}, 'POST', (r) => { 						
						this.folders.push(r);						
						setTimeout(()=> this.editFolderName(r.id), 100);
					});				
				},					
				goo_folder( id )
				{ 
					this.folder = id;
					usam_set_url_attr( 'folder', this.folder );
					this.trash = false;
					this.requestData();
				},
				open_folder( k, e )
				{					
					if ( e.ctrlKey )
						this.folders[k].checked=!this.folders[k].checked
					else if ( this.folders[k].status != 'delete' )
						this.goo_folder( this.folders[k].id );
				},
				open_file(k, e)
				{ 
					if ( e.ctrlKey )
						this.files[k].checked=!this.files[k].checked
					else if ( this.files[k].status != 'delete' )
					{
						usam_set_url_attr( 'form', 'view' );
						usam_set_url_attr( 'form_name', 'file' );
						usam_set_url_attr( 'id', this.files[k].id );
						window.location.replace( location.href );
					}
				},
				open_basket()
				{ 
					this.trash = true;
					this.requestData();
				
				},	
				fileClick( k, e )
				{ 
					if( !this.file_timeoutId )
					{
						this.file_timeoutId = setTimeout(() => {
							this.open_file(k, e);							
							this.file_timeoutId = false;
						}, 300);
					}
					else
					{
						clearTimeout(this.file_timeoutId);							
						this.file_timeoutId = false;
						this.edit_file_name(e, this.files[k].id );						
					}		
				},	
				folderClick( k, e )
				{
					if( !this.folder_timeoutId )
					{
						this.folder_timeoutId = setTimeout(() => {
							this.open_folder( k, e );
							this.folder_timeoutId = false;
						}, 300);
					}
					else
					{
						clearTimeout(this.folder_timeoutId);
						this.folder_timeoutId = false;
						this.editFolderName( this.folders[k].id );						
					}		
				},				
				edit_file_name(e, id)
				{ 
					e.preventDefault();	
					this.$refs['filename'+id][0].hidden = true;
					this.$refs['filename_editor'+id][0].hidden = false;
					setTimeout(()=>{ this.$refs['filename_editor'+id][0].select(); }, 50);
					
				},
				editFolderName( id )
				{ 						
					this.$refs['foldername'+id][0].hidden = true;
					this.$refs['foldername_editor'+id][0].hidden = false;
					setTimeout(()=>{ this.$refs['foldername_editor'+id][0].select() }, 50);
				},
				fileSave(k)
				{ 
					this.$refs['filename'+this.files[k].id][0].hidden = false;	
					this.$refs['filename_editor'+this.files[k].id][0].hidden = true;
					usam_api('file/'+this.files[k].id, {title:this.files[k].title}, 'POST');
				},
				folderSave(k)
				{ 
					this.$refs['foldername'+this.folders[k].id][0].hidden = false;
					this.$refs['foldername_editor'+this.folders[k].id][0].hidden = true;
					usam_api('folder/'+this.folders[k].id, {name:this.folders[k].name}, 'POST');
				},										
				fileDelete()
				{ 									
					usam_api('file/'+this.files[this.file_menu].id, 'DELETE');
					this.files.splice(this.file_menu, 1);
				},
				folderDelete()
				{ 					
					usam_api('folder/'+this.folders[this.folder_menu].id, 'DELETE');
					this.folders.splice(this.folder_menu, 1);
				},	
				fileDrop(e)
				{ 					
					e.preventDefault();
					if ( this.move )
						return false;
					e.currentTarget.classList.remove('over');	
					this.fileUpload( e.dataTransfer.files );
				},		
				fileAttach(e)
				{ 			
					document.querySelector('input[type="file"]').click();
				},			
				allowAddingFiles(e) {					
					e.preventDefault();			
					if ( this.move )
						return false;
					e.currentTarget.classList.add('over');			
				},
				fileChange(e)
				{						
					if (!e.target.files[0] || this.move ) 
						return;					
					this.fileUpload( e.target.files );		
				},
				fileUpload(f)
				{	
					for (var i = 0; i < f.length; i++)
					{
						let k = this.files.length;
						Vue.set(this.files, k, {name:'',title:f[i].name, size:formatFileSize(f[i].size),icon:'', percent:0, load:true, error:''});
						var fData = new FormData();
						fData.append('file', f[i]);	
						fData.append('type', 'loaded');							
						for (let j in this.cFile)
							fData.append(j, this.cFile[j]);	
						usam_form_save( fData, (r) => {
							if ( r.status == 'success' )
							{ 
								r.load = false;
								Vue.set(this.files, k, r);	
								if ( this.folder )
									usam_api('file/'+r.id, {folder_id:this.folder}, 'POST');
							}
							else
								this.files[k].error = r.error_message;
						}, (e)=> this.files[k].percent = e.loaded*100/e.total, 'upload' );
					}
				},
				open_menu(menu, e){			
					var r = document.querySelector('.files_grid').getBoundingClientRect();
					menu.style.inset = (e.clientY-r.top+10)+'px auto auto '+(e.clientX-r.left-25)+'px';
				},			
				open_folder_menu(e, k)
				{			
					e.preventDefault();					
					menu = document.querySelector('.folder_menu');
					this.file_menu = false; 
					this.folder_menu = k;
					this.open_menu(menu, e);						
					if ( document.querySelector('.files_grid .active') )
						document.querySelector('.files_grid .active').classList.remove('active');
					e.currentTarget.classList.add('active');					
				},
				open_file_menu(e, k){		
					e.preventDefault();
					menu = document.querySelector('.file_menu');
					this.folder_menu = false;
					this.file_menu = k;
					this.open_menu(menu, e);						
					if ( document.querySelector('.files_grid .active') )
						document.querySelector('.files_grid .active').classList.remove('active');
					e.currentTarget.classList.add('active');					
				},
				downloadFolder(){
					usam_active_loader();	
					usam_send({action: 'download_folder', 'id': this.folders[this.folder_menu].id, nonce: USAM_Page_files.download_folder_nonce});
				},
				renameFolder(e){
					this.editFolderName( this.folders[this.folder_menu].id );
				},
				fileDownload(e){
					usam_active_loader();
					usam_send({action: 'download_file', 'id': this.files[this.file_menu].id, nonce: USAM_Page_files.download_file_nonce});
				},
				restoreFile(e)
				{ 				
					usam_api('file/'+this.files[this.file_menu].id, {status:'closed'}, 'POST');
					this.files.splice(this.file_menu, 1);	
				},				
				restoreFolder(e)
				{
					usam_api('folder/'+this.folders[this.folder_menu].id, {status:'closed'}, 'POST');
					this.folders.splice(this.folder_menu, 1);	
				},
				sidebar(type)
				{
					this.$refs['modal'+type].show = !this.$refs['modal'+type].show;
				},
				fileSend(e){
					this.attachments = [this.files[this.file_menu]];
					this.object_id = this.files[this.file_menu].id;
					this.sidebar('sendemail');						
				},
				addEmail(r){
					this.attachments = [];
					this.object_id = 0;
					this.$refs['modalsendemail'].show = false;			
				},				
				renameFile(e){
					this.edit_file_name(e, this.files[this.file_menu].id)
				},	
				allowDrop(e) 
				{
					e.preventDefault();						
					var el = document.querySelector('.files_grid .over');
					if ( el )
						el.classList.remove('over');
					e.currentTarget.classList.add('over');
					clearTimeout(this.file_timeoutId);
					this.file_timeoutId = setTimeout(() => {
						var el = document.querySelector('.files_grid .over');
						if ( el )
							el.classList.remove('over');	
						this.file_timeoutId = false;
					}, 300);
					
				},
				dragFolder(e, k) {
					this.move = true;
					this.folders[k].checked = true;
					e.currentTarget.classList.add('active');
				},
				dragFile(e, k) {
					this.move = true;
					this.files[k].checked = true;
					e.currentTarget.classList.add('active');	
				},
				dragEnd(e, i) {				
					e.currentTarget.classList.remove('active');
					if ( document.querySelector('.files_grid .over') )
						document.querySelector('.files_grid .over').classList.remove('over');
					this.move = false;
				},
				drop(e, id) {
					e.preventDefault();					
					for (let i = this.folders.length; i--;)
					{
						if( this.folders[i].id === id )
							this.folders[i].count++;
						else if( this.folders[i].checked )
						{
							data = {parent:id};
							if ( this.folders[i].status == 'delete' )
								data.status = 'closed';							
							usam_api('folder/'+this.folders[i].id, data, 'POST');
							this.folders.splice(i, 1);
						}						
					}	
					for (let i = this.files.length; i--;)		
					{
						if( this.files[i].checked )
						{
							data = {folder_id:id};
							if ( this.files[i].status == 'delete' )
								data.status = 'closed';
							usam_api('file/'+this.files[i].id, data, 'POST');
							console.log(i);
							this.files.splice(i, 1);
						}
					}
				},
			}
		})	
	}	
})