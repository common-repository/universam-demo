var usam_resize_iframe = function() 
{
	var jiframe = jQuery('.product_variation_iframe iframe');
	var iframe = jiframe[0];
	var i_document = iframe.contentDocument;
	var height_elements = [i_document, i_document.documentElement, i_document.body];
	iframe.style.height = '';
	var content_height = 0;
	for (var i in height_elements) 
		content_height = Math.max(content_height, height_elements[i].scrollHeight || 0, height_elements[i].offsetHeight || 0, height_elements[i].clientHeight || 0);
	iframe.style.height = content_height + 'px';
};

var usam_display_media = function( attachment_id, post_id, product_title ) 
{ 
	var images_file_frame;
	images_file_frame = wp.media.frames.images_file_frame = wp.media({title: product_title, button: { text : USAM_Edit_Product.button_text }, library: { type: 'image' }, multiple: false});		
	wp.media.frames.images_file_frame.on( 'open', function() 
	{
		var selection = wp.media.frames.images_file_frame.state().get( 'selection' );		
		if ( attachment_id > 0 ) 
		{
			attachment = wp.media.attachment( attachment_id );
			attachment.fetch();
			selection.add( attachment ? [ attachment ] : [] );
		}
	});
	
	images_file_frame.on( 'select', function() 
	{				
		attachment = images_file_frame.state().get( 'selection' ).first().toJSON();		
		if ( attachment.sizes.thumbnail )
			var src = attachment.sizes.thumbnail.url;
		else
			var src = attachment.sizes.full.url;
				
		var iframe = jQuery('.product_variation_iframe iframe');		
		var callback = function(r)
		{	
			iframe.contents().find('#thumb_product_' + post_id).attr('src', src);
		};
		usam_send({nonce: USAM_Edit_Product.set_variation_thumbnail_nonce, action: 'set_variation_thumbnail', post_id: post_id, attachment_id: attachment.id}, callback);	
	});
	images_file_frame.open();
};

(function($)
{	
	$.extend(USAM_Edit_Product, {
		init : function() 
		{
			$(function()
			{
				$("body")				
					.on('click', '.usam_tab_table #doaction, .usam_tab_table #doaction2', USAM_Edit_Product.table_bulkactions )
					.on('click', '.usam_tab_table .js-table-action-link', USAM_Edit_Product.table_action_item )
					.on('click', '.usam_tab_table #delete_all', USAM_Edit_Product.delete_all_variations)
					.on('click', '.js-save-variation',USAM_Edit_Product.save_variation );
														
				$('#usam_link_webspy').on('click','#usam_webspy_loading_information',USAM_Edit_Product.webspy_loading_information);		
			});			
		},		
		
		save_variation: function( e ) 
		{ 
			e.preventDefault(); 		
			var formData = new FormData(document.forms.save_variation);
			formData.append("nonce", USAM_Edit_Product.save_variation_nonce);
			formData.append("usam_ajax_action", "save_variation");
			formData.append("action", 'usam_ajax');
			formData.append("parent_id", USAM_Edit_Product.product_id);			
			usam_form_save(formData);
		},
		
		table_action_item: function( e ) 
		{ 
			e.preventDefault();			
			usam_active_loader();
			$table = $(this).parents('.usam_tab_table');
			table = $table.find('.js-table-name').val();
			var action = $(this).attr('data-action');
			var tr = $(this).parents('tr');
			var id = tr.find('.check-column input:checkbox').val();			
			usam_send({nonce: USAM_Edit_Product.bulkactions_nonce, action: 'bulkactions', a: action, item: table, id: id}, USAM_Edit_Product.variations_action_callback);	
		},	
		
		delete_all_variations: function( e ) 
		{ 
			e.preventDefault();	
			usam_send({nonce: USAM_Edit_Product.bulkactions_nonce, action: 'bulkactions', a: 'delete_all_variations', item:'product_variations', id: USAM_Edit_Product.product_id}, USAM_Edit_Product.variations_action_callback);
		},
		
		table_bulkactions: function( e ) 
		{ 
			e.preventDefault();	
			var action = $(this).siblings('select').val();
			if ( action != '' )
			{
				usam_active_loader();
				$table = $(this).parents('.usam_tab_table');
				table = $table.find('.js-table-name').val();
				var ids = [];
				var i = 0;
				$table.find('.wp-list-table tbody .check-column input:checkbox:checked').each(function()
				{
					ids[i] = $(this).val();
					i++;
				});
				usam_send({nonce: USAM_Edit_Product.bulkactions_nonce, action: 'bulkactions', a: action, item: table, cb: ids}, USAM_Edit_Product.variations_action_callback);
			}		
		},
		
		variations_action_callback : function( response ) 		
		{
			if ( response )
			{						
				usam_active_loader();
				data = {table:'product_variations'};
				data.action = 'get_list_table';
				data.product_id = USAM_Edit_Product.product_id;						
				data.nonce  = USAM_Edit_Product.get_list_table_nonce;	
				var	callback = function(response)
				{
					if ( response.rows.length )
					{
						$('#the-list').html( response.rows );
						$('#the-list').trigger('table_update',[response]);
						usam_lazy_image();
					} 
					if ( response.column_headers.length )
						$('.wp-list-table thead tr').html( response.column_headers );	
					if ( typeof response.views !== "undefined" )
						$('.subsubsub').html( $(response.views).html() );								
					if ( response.column_footer.length )
						$('.wp-list-table tfoot tr').html( response.column_footer );					
					if ( response.pagination.top.length )
						$('.tablenav.top .tablenav-pages').html( $(response.pagination.top).html() );
					if ( response.pagination.bottom.length )
						$('.tablenav.bottom .tablenav-pages').html( $(response.pagination.bottom).html() );	
				};						
				usam_send(data, callback, 'GET');	
			}
			else
				usam_notifi({'text': UNIVERSAM.action_error});
		},		
							
		webspy_loading_information : function( e ) 		
		{
			e.preventDefault();				
			usam_active_loader();
			var post_data = {
					'action'     : 'loading_information',	
					'url'        : jQuery('#product_link').val(),		
					'product_id' : USAM_Edit_Product.product_id,					
					'nonce'      : USAM_Edit_Product.loading_information_nonce
				},
				handler = function(response) 
				{												
					if ( response.status == 1 ) 
					{
						alert(response.error);
					}						
					wp.data.dispatch( 'core/editor' ).editPost( { title: response.title } )
								
					jQuery('#usam_sku').val(response.sku);
					jQuery('.js-price-'+response.type_price).val(response.price);
											
					if ( jQuery('#excerpt_ifr')[0] )
					{		
						var frameHTML = jQuery('#excerpt_ifr')[0].contentWindow.document.documentElement;				
						jQuery('#tinymce', frameHTML).append(response.content);
						//$('#tinymce', frameHTML).css('background', 'red');
					}					
				};				
			usam_send(post_data, handler);			
			return false;
		}			
	});
})(jQuery);
USAM_Edit_Product.init();


document.addEventListener("DOMContentLoaded", function(e) 
{	
	if( document.getElementById('usam_attributes_forms') )
	{
		new Vue({		
			el: '#usam_attributes_forms .inside',	
			mixins: [edit_properties,changeProductProperties,files],	
			data() {
				return {
					data:{sale_type:'product', code_names:[], additional_units:[], variations:[], length:0,width:0,height:0,under_order:0, images:[], tabs:[], increase_sales_time:'', components:[]},
					sectionTab:'properties',
					showcases:[],
					productTabs:[],			
					editTab:false,
					confirmationDeletion:false,
					custom_tab:0,				
					files:[],
					meta:{},
					productVariations:[],
					reputation_items:[],	
					product_lists:{similar:[], crosssell:[], options:[]},
					posts:[],
					agreements:[],
					cFile:{object_id:this.id, type:'product'},
					attributeFilter:'all',
					id:0,	
					oldIndex:null,						
					load:false	
				};
			},
			watch: {
				sectionTab(v, old) {					
					let url = new URL( document.location.href );
					url.searchParams.set('tab', v);
					history.pushState({'url' : url.href}, '', url.href);
				},		
				custom_tab(v, old) {						
					this.setContentTinyMCE();
				},	
				editTab(v, old) {					
					this.setContentTinyMCE();
				},				
			},			
			mounted() {				
				let url = new URL( document.location.href );	
				var el = document.getElementById('post_ID');
				if ( el )
					this.id = el.value;
				else
				{	
					if ( url.searchParams.has('post') )
						this.id = url.searchParams.get('post');
				}
				if ( url.searchParams.has('tab') )					
					this.sectionTab = url.searchParams.get('tab');
				if ( this.id )
					this.loadProduct();
				else
					this.loadAttributes();
				this.fDownload();
				usam_api('product/tabs', 'GET', (r) => this.productTabs = r);				
				setTimeout(()=>{ 				
					const slider = document.querySelector('.section_tabs');
					if ( slider )
					{
						let mouseDown = false;
						let startX, scrollLeft;
						let startDragging = (e) => {
							mouseDown = true;
							if( e.type == 'touchstart' )
								 startX = e.changedTouches[0].pageX - slider.offsetLeft;
							else
								startX = e.pageX - slider.offsetLeft;						
							scrollLeft = slider.scrollLeft;
						};
						let stopDragging = (e) => mouseDown = false;
						let moveDragging = (e) => {	 							
							if(e.type == 'touchmove')
								x = e.changedTouches[0].pageX - slider.offsetLeft;
							else
								x = e.pageX - slider.offsetLeft;						
							slider.scrollLeft = scrollLeft - (x - startX);	
						}	
						slider.addEventListener('mousedown', startDragging, false);			
						slider.addEventListener('touchstart', startDragging, false);
						slider.addEventListener('mousemove', moveDragging, false);		
						slider.addEventListener('touchmove', moveDragging, false);						
						slider.addEventListener('mouseup', stopDragging, false);
						slider.addEventListener('touchend', stopDragging, false);
						slider.addEventListener('mouseleave', stopDragging, false);	
					}
				}, 100);	
			},
			methods: {					
				loadProduct()
				{
					if ( this.id )
					{
						this.load = true;		
						usam_api('product/'+this.id, {add_fields:['showcases','edit_attributes','codes','dimensions','sale_type','under_order', 'contractor', 'additional_units','sale_type','images','tabs','webspy_link','increase_sales_time', 'product_type','stock','not_limited','name_unit_measure','variations','posts', 'components', 'license_agreement']}, 'GET', (r) => {
							this.data = r;
							if( !this.data.components.length )
								this.data.components = [{id:0, quantity:1, component:''}]
							this.processProduct(r);
							this.load = false;
						});
						for (let l in this.product_lists)
						{
							usam_api('products', {associated_product: [{list:l, product_id:this.id}], status:'any', add_fields:['small_image', 'sku','price'],count:1000}, 'POST', (r) => {	
								for (let i in r.items)
									r.items[i].product_id = r.items[i].ID;
								this.product_lists[l] = r.items;	
								this.pLoaded = true;
							});			
						}							
						usam_api('showcases', 'POST', (r) => this.showcases = r.items);
						usam_api('products', {orderby:'menu_order post_title', order:'ASC', post_parent:this.id, post_status:['publish', 'inherit', 'draft'], order:'ASC', count:1000, add_fields:['edit_link','small_image', 'sku','price','stock','author','status_name']}, 'POST', (r) => this.productVariations = r.items);
						usam_api('posts', {associated_product: [{list:'posts', product_id:this.id}], status: 'any', count:1000, add_fields:['edit_link','views']}, 'POST', (r) => this.posts = r.items);
						usam_api('agreements', {status: 'any', count:1000}, 'POST', (r) => this.agreements = r.items);
						usam_api('product/reputation/items', {product_id:this.id, status: [0,1]}, 'GET', (r) => this.reputation_items = r.items);
						usam_api('post/metatags', {'id':this.id}, 'GET', (r) =>	this.meta = r);							
					}
				},
				setContentTinyMCE()
				{
					if( this.editTab )
					{
						document.getElementById('custom_product_tab_editor').value = this.productTabs[this.custom_tab].description;
						var t = tinyMCE.get('custom_product_tab_editor');
						if( t )
							t.setContent(this.productTabs[this.custom_tab].description);
					}
				},
				allowDrop(e, k) {
					e.preventDefault();
					if( this.oldIndex != k )
					{					
						let v = Object.assign({}, this.data.images[this.oldIndex]);					
						this.data.images.splice(this.oldIndex, 1);	
						this.data.images.splice(k, 0, v );	
						this.oldIndex = k;		
					}
				},
				drag(e, k) {				
					this.oldIndex = k;
					if( e.currentTarget.hasAttribute('draggable') )
						e.currentTarget.classList.add('draggable');	
					else
						e.preventDefault();
				},	
				dragEnd(e, i) {
					e.currentTarget.classList.remove('draggable');
				},
				loadAttributes()
				{								
					usam_api('product_attributes', {orderby:'name', order:'ASC', count:0, add_fields:'options'}, 'POST', (r) => {				
						if( r.items.length )
						{
							for (let k in r.items)
							{
								if( r.items[k].related_categories.length )
									delete r.items[k];
							}
							this.processProperties(r.items);
						}
					});	
				},
				addMedia(a) 
				{
					for (let i in a)
						this.data.images.push({ID:a[i].id, medium_image:a[i].url, full:a[i].full});
				},
				deleteMedia(k) 
				{
					this.data.images.splice(k, 1);	
				},
				addPost(e) 
				{
					this.posts.push(e);	
				},	
				reputationItemUpdate(k, data) 
				{
					this.reputation_items[k] = Object.assign(this.reputation_items[k], data);
					usam_api('product/reputation/item/'+this.reputation_items[k].id, data, 'POST');
				},
				reputationItemActionDelete(k) 
				{
					var id = this.reputation_items[k].id;
					usam_item_remove({data: {d: this.reputation_items[k]}, callback: (data) => { 
						this.reputation_items.splice(k, 0, data.d);						
					}, handler:() => { 
						this.reputationItemDelete( id );						
					}});
					this.reputation_items.splice(k, 1);
				},
				reputationItemDelete(id) 
				{ 
					usam_api('product/reputation/item/'+id, 'DELETE', (r) =>  usam_admin_notice(r, 'delete'));					
				},					
				addTab(e, i) 
				{ 
					var id = this.productTabs[i].id;
					if( e )
						this.data.tabs.push(id);
					else
					{						
						var x = this.data.tabs.indexOf(id);
						if( x!==-1 )
							this.data.tabs.splice(x, 1);
					}
				},
				saveTab() 
				{ 
					var t = tinyMCE.get('custom_product_tab_editor');	
					if( t )
						this.productTabs[this.custom_tab].description = t.getContent();
					else
						this.productTabs[this.custom_tab].description = document.getElementById('custom_product_tab_editor').value;
					usam_api('product/tab/'+this.productTabs[this.custom_tab].id, this.productTabs[this.custom_tab], 'POST', usam_admin_notice);
				},
				addTab() 
				{ 
					var data = {id:0, name:'', title:'Новая вкладка', code:'', description:'', active:0, global:0};						
					usam_active_loader();
					usam_api('product/tab', data, 'POST', (r) => {						
						this.productTabs[this.custom_tab].id = r;	
						usam_admin_notice(r, 'add');
					});
					data.id = 0;
					var i = this.productTabs.push(data);
					this.custom_tab = i-1;
				},	
				deleteTab(i) 
				{ 					
					usam_api('product/tab/'+this.productTabs[i].id, 'DELETE', (r) =>  usam_admin_notice(r, 'delete'));
					if( this.custom_tab == i )
						this.custom_tab = 0;
					this.productTabs.splice(i, 1);
				},
				openViewer() 
				{ 					
					if( !media_browser.images.length )					
						for (let i in this.data.images)
							media_browser.images.push({small_image:this.data.images[i].small_image, full:this.data.images[i].full});
					media_browser.open = true;
				}					
			}
		})
	}
})