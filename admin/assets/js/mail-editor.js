Vue.component('block-newsletter',{
	props:{
		block:{type:Object, required:true},
		index:{type:Number, required:true},
	},
	data(){
		return {
			dragId:null,			
			edit:false,
			timeoutId:null
		}
	},			
	watch:{	
		edit(v, oldVal) 
		{	
			if( v && this.block.type === 'content' )
			{				
				var f =(e)=>{
					if( e.target.closest('.block_text') || e.target.classList.contains("block_text") )
						return false;			
					if( e.target.classList.contains("slides") || e.target.closest(".slides") )
					{
						this.edit = false;						
						document.removeEventListener('click', f, false);
					}
				}					
				setTimeout(()=> document.addEventListener('click', f), 1);
			}
		},
	},		
	methods:{		
		blockCSS( d ) 
		{
			if ( d === undefined)
				return '';
			var css = '';			
			for (k in d)
				if ( d[k] !== '') css += k+':'+d[k]+';';
			return css;
		},
		selectBlock( type ) 
		{		
			switch ( type ) 
			{				
				case '50x50':
				case '33x66':
				case '66x33':			
					this.block.columns = [{},{}];
				break;
				case '33x33x33':
				case '25x50x25':
					this.block.columns = [{},{},{}];
				break;
			}			
		},		
		mousedown(e)
		{
			this.$root.block = this.block;
			this.$root.moveY = e.pageY;		
		},
		dropWidget(i)
		{
			this.dragId = i;
			this.newWidget();
			this.dragBlock = false;
		},	
		allowDrop(e) 
		{
			e.preventDefault();						
			var el = document.querySelector('.over');
			if ( el )
				el.classList.remove('over');
			e.currentTarget.classList.add('over');		
		},			
		newWidget()
		{
			var nBlock = this.$root.widget();
			switch ( nBlock.type ) 
			{				
				case 'image':						
					wp.media.frames.images_file_frame = wp.media({title: this.$root.text_media_upload, library: { type: 'image' }, multiple: false});
					wp.media.frames.images_file_frame.on( 'select', () =>
					{									
						a = wp.media.frames.images_file_frame.state().get( 'selection' ).first().toJSON();							
						nBlock.contentCSS = {width:a.width, height:a.height};
						nBlock.object_id = a.id;
						nBlock.object_url = a.url;
						this.addBlock( nBlock );
					}).open();	
				break;				
				case 'content':
				case 'product':	
				case 'basket':	
				case 'indentation':					
				case 'columns':		
				case 'button':											
					this.addBlock( nBlock );
				break;
				case 'divider':			
					this.sidebar('divider');
				break;			
			}			
			this.dragBlock = false;
		},			
		addBlock( nBlock ) 
		{	
			Vue.set(this.block.columns, this.dragId, nBlock);
			this.dragId = false;
		},			
	},
	template: '#block-newsletter'
});

document.addEventListener("DOMContentLoaded", () => {
	new Vue({
		el: '#edit_form_email_newsletter',		
		mixins: [files, formTools, rulerEditor],
		data() {
			return {
				cFile:{type:'newsletter'},
				steps:[],			
				id:1,
				tab:'blocks',			
				section:{},		
				editName:false,			
				dragBlock:false,
				moveY:null,	
				timeoutId:null,				
				dragId:false,
				current_step:3,
				email:'',				
				validation:true,
				block_editor:'content',			
				data:{conditions:{lists:[]}},
				block:null,
				text_media_upload:'',		
				mailingLists:[],
				templates:[],
			};
		},
		watch:{	
			block(v, oldVal) 
			{
				if( v!== null && (v.type === 'content' || v.type === 'button' || v.type === 'product') )
					this.tab = 'editor';
				if( v === null )
					this.tab='blocks';
			},				
		},
		computed: {	
			blocks(){
				return this.data.settings.blocks;
			},
			nameXmlFile(){
				return this.data.subject;
			}
		},
		created() {					
			for( let k in form_args)
				this[k] = form_args[k];				
			for( let k in this.tabSettings)		
				Vue.set(this.section, k, Object.keys(this.tabSettings[k].icons)[0]);
			this.dataFormatting( form_data );
			if( this.data.subject === '' )
				this.current_step = 1;
			else if( !this.data.settings.blocks.length )
				this.current_step = 2;			
			usam_api('mailing_lists', 'POST', (r) => this.mailingLists = r.items)			
		},
		mounted() {			
			this.cFile.object_id = this.data.id;
			this.fDownload();
			this.$on('active', (e) => this.block = e);			
			var f =(e)=>{
				if( this.block !== null )
				{
					if( e.target.closest('.usam_block') || e.target.classList.contains("usam_block") )
						return true;										
					if( e.target.classList.contains("slides") || e.target.closest(".slides") )
						this.block = null;	
				}
			}					
			setTimeout(()=> document.addEventListener('click', f), 1);
		},				
		methods: {
			moveUp( k ) 
			{
				if ( k > 0 )
					this.move(k, k-1 );
			},
			moveDown( k ) 
			{
				if ( this.blocks.length > k )
					this.move(k, k+1 );
			},
			move( k, i ) 
			{
				let v = structuredClone(this.blocks[i]);	
				this.blocks.splice(i, 1);	
				this.blocks.splice(k, 0, v );
			},
			dataFormatting( r )
			{
				for (i in r.settings.blocks )
				{
					var l = r.settings.blocks[i];
					if ( l === null )
						r.settings.blocks.splice(i, 1);
					else
					{
						l.id = l.id !== null ? l.id : i;	
						l.id = parseInt(l.id);				
					}
				}
				this.data = r;		
			},		
			blockCSS( d ) 
			{
				if ( d === undefined)
					return '';
				var css = '';			
				for (k in d)
					if ( d[k] !== '') css += k+':'+d[k]+';';
				return css;
			},
			delBlock( k ) 
			{ 
				this.blocks.splice(k, 1);
			},
			dragWidget(e, type)
			{
				this.dragBlock = type;				
				this.block = null;
			},	
			dragendWidgetEnd(e, type)
			{
				this.dragBlock = false;
			},		
			allowDrop(e) 
			{
				e.preventDefault();						
				var el = document.querySelector('.over');
				if ( el )
					el.classList.remove('over');
				e.currentTarget.classList.add('over');
				clearTimeout(this.timeoutId);
				this.timeoutId = setTimeout(() => {
					var el = document.querySelector('.over');
					if ( el )
						el.classList.remove('over');	
					this.timeoutId = false;
				}, 300);
				
			},
			dropWidget(k)
			{
				this.dragId = k;				
				this.newWidget();
				this.dragBlock = false;
			},
			mousemove(e)
			{
				if( this.moveY )
				{ 
					var h = parseInt(this.block.css.height) + e.pageY - this.moveY;					
					this.block.css.height = (h>0?h:5)+'px';					
					this.moveY = e.pageY;
				}
			},			
			handleUp(e)
			{
				document.onmousemove = null;
				this.moveY = 0;
			},	
			widget()
			{
				var nBlock = {type:this.dragBlock, css:{'background-color': '', 'border-radius':'', 'border-color': '', 'border-style': '', 'border-width':'0', margin:'', padding:'20px', height:'', width:'', 'text-align':'left'}}
				switch ( this.dragBlock ) 
				{				
					case 'content':			
						nBlock.contentCSS = {'color':'#000000', 'text-align':'center', 'font-family':'Verdana', 'font-size':'13px', 'font-weight':'400', 'line-height':'1.3', 'text-decoration': 'none'};
						nBlock.text = 'Нажмите сюда, чтобы добавить заголовок или текст.';	
					break;								
					case 'button':		
						nBlock.text = 'Кнопка';
						nBlock.url = '';
						nBlock.contentCSS = {'text-align':'center', width:'180px', height:'40px', 'background-color':'#2ea1cd', color:'#ffffff', 'font-family':'Verdana', 'font-size':'18px', 'font-weight':'400', 'line-height':'40px', 'border-color':'', 'border-style':'', 'border-width':'0', 'border-radius':'5px', 'text-decoration': 'none', padding:'20px', 'text-transform':'none'};
					break;	
					case 'product':		
						nBlock.product_id = 0;
						nBlock.url = '';
						nBlock.contentCSS = {'text-align':'left', color:'#444444', 'font-family':'Verdana', 'font-size':'12px', 'font-weight':'400', 'line-height':'1.3', 'text-decoration': 'none', padding:'5px', 'text-transform':'none'};
						nBlock.image = {url:'', width:'160px', height:'160px'}
						nBlock.price_currency = '0.00';
						nBlock.text = '';
						nBlock.oldprice_currency = '0.00';
						nBlock.priceCSS = {'text-align':'left', color:'#FF6347', 'font-family':'Verdana', 'font-size':'12px', 'font-weight':'400', 'text-decoration': 'none', 'line-height':'1', padding:'5px', 'text-transform':'none'};
						nBlock.oldpriceCSS = {'text-align':'left', color:'#444444', 'font-family':'Verdana', 'font-size':'12px', 'font-weight':'400', 'text-decoration': 'line-through', 'line-height':'1', 'text-transform':'none'};	
					break;
					case 'basket':		
						
					break;				
					case 'indentation':
						nBlock = {type:this.dragBlock, css:{'background-color': '', 'border-radius':'', 'border-color': '', 'border-style': '', 'border-width': '0', margin:'', height:'30px'}}
					break;					
					case 'columns':		
						nBlock.columns = [];
						nBlock.css.width = '100%';						
						nBlock.css.padding = '0';
						nBlock.column_type = '';
					break;				
				}							
				return nBlock;
			},	
			newWidget( block )
			{
				var nBlock = this.widget();
				switch ( this.dragBlock ) 
				{				
					case 'image':						
						wp.media.frames.images_file_frame = wp.media({title: this.text_media_upload, library: { type: 'image' }, multiple: false});									
						wp.media.frames.images_file_frame.on( 'select', () =>
						{									
							a = wp.media.frames.images_file_frame.state().get( 'selection' ).first().toJSON();							
							nBlock.contentCSS = {width:a.width+'px', height:a.height+'px'};
							nBlock.object_id = a.id;
							nBlock.object_url = a.url;
							if ( block !== null && typeof block === 'object' )
								block[0] = nBlock;
							else
								this.addBlock( nBlock );
						}).open();	
					break;				
					case 'content':
					case 'product':	
					case 'basket':	
					case 'indentation':					
					case 'columns':		
					case 'button':											
						if ( block !== null && typeof block === 'object' )
							block = nBlock;
						else
							this.addBlock( nBlock );
					break;
					case 'divider':			
						this.sidebar('divider');
					break;			
				}			
				this.dragBlock = false;
			},			
			addBlock( nBlock ) 
			{			
				nBlock.id = this.blocks.length ? this.blocks[this.blocks.length-1].id+1:1;
				this.blocks.splice(this.dragId, 0, nBlock);
				this.block = this.blocks[this.dragId];		
				this.dragId = false;
			},			
			selectDivider(src){
				if ( this.block === null )
					this.addBlock({type:'divider', css:{'background-color':'', margin:'', padding:'', height:'', width:''},src:src});
				else
					this.block.src = src;
			},	
			saveForm( back )
			{
				if( this.data.id )
					usam_api('newsletter/'+this.data.id, this.data, 'POST', (r) => back === true ? this.backList() : usam_admin_notice(r) );
				else
					usam_api('newsletter', this.data, 'POST', this.afterAdding);
			},
			deleteItem()
			{					
				if ( this.data.id )
					usam_api('newsletter/'+this.data.id, 'DELETE', this.backList);
			},		
			send_preview()
			{
				usam_active_loader();
				usam_api('newsletter/'+this.data.id+'/preview', {email: this.email}, 'GET', (r) => usam_admin_notice(r, 'send'));
			},		
			next_step(e)
			{						
				e.preventDefault();
				if ( this.current_step > 3 )
					return false;				
				if ( this.data.subject !== '' )
				{
					this.current_step++;
					this.setParamsUrl('step', this.current_step);
					
				}		
			},			
			previous(e)
			{
				e.preventDefault();
				if ( this.current_step > 1 )
				{
					this.current_step--;
					this.setParamsUrl('step', this.current_step);
				}
			},			
			changeStatus( status )
			{
				this.data.status = status;
				this.saveForm();
			},
			send(e)
			{
				this.data.status = 5;
				this.saveForm(true);
			},	
			selectTemplateAndNext(i)
			{ 
				this.current_step++;
				this.selectTemplate(i)
			},		
		}	
	})
})