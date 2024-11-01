(function($)
{
	$.extend(USAM_Tabs, 
	{ 
		unsaved_settings : false,		
		including_editing : false,	
		tinymce_id : false,	
		start_droppable : false,	
		select_contacts : [],		
		
		init : function() 
		{			
			$(window).on('popstate', USAM_Tabs.event_pop_state);
			$(function()
			{				
				USAM_Tabs.wrapper = $('#tab_'+USAM_Tabs.tab);				
				// отключил не правильно работают запросы форм	
			/*	$('#usam_page_tabs').on('click', '.navigation-tab', USAM_Tabs.event_tab_button_clicked).
				                     on('change', input, textarea, select', USAM_Tabs.event_changed).									 
				                     on('submit', '#usam-tab_form', USAM_Tabs.event_form_submitted);	
			*/				
								
				USAM_Tabs.wrapper				 
					.on('click', 'table .button_delete', USAM_Tabs.event_delete_table_row)
					.on('click', 'table tbody #delete', USAM_Tabs.event_delete_table_row)	
					.on('click', 'a#link_description', USAM_Tabs.show_description)
					.on('keypress', '#sms', USAM_Tabs.event_change_sms)
					.on('keyup', '#sms', USAM_Tabs.event_change_sms)
					.on('dblclick', '.usam_change_content', USAM_Tabs.change_content)
					.on('click', '.js-communication-phone',  USAM_Tabs.click_phone_call)
					.on('click', '.js-open-message-send', USAM_Tabs.open_message_send)
					.on('click', '.js-open-sms-send', USAM_Tabs.open_sms_send)			
					.on('click', '.tablenav-pages a', USAM_Tabs.table_navigation )
					.on('click', '.manage-column.sortable a, .manage-column.sorted a', USAM_Tabs.table_sorting )
					.on('keyup', '.usam_tab_table input[name=paged]', USAM_Tabs.table_select_page_number )
					.on('click', '.usam_tab_table #doaction, .usam_tab_table #doaction2', USAM_Tabs.table_bulkactions )
					.on('click', '.usam_tab_table .js-table-action-link', USAM_Tabs.table_action_item )			
					.on('click', '.js-add-favorites-page', USAM_Tabs.add_favorites_page)	
					.on('click', '#bulk-action-selector-top', USAM_Tabs.bulk_actions)	
					.on('click', '.usam-delete-link', USAM_Tabs.get_form_confirmation_delete_item)
					.on('click', '.inside .show_help', USAM_Tabs.show_help_setting_box)		
					.on('change', '#customer_type', USAM_Tabs.select_customer)					
					.on('change', '.js-shipped-document-status', USAM_Tabs.shipped_document_status)		
					.on('change', '.js-shipped-document-courier', USAM_Tabs.shipped_document_courier)
					.on('click', '#save_add', USAM_Tabs.save_add);											
									
				document.querySelectorAll('.js-action-item').forEach((el) => {el.addEventListener('click', USAM_Tabs.action_item)});
				document.querySelectorAll('.js-action').forEach((el) => {el.addEventListener('click', USAM_Tabs.actions)});				
								
				if ( USAM_Tabs.call.id !== undefined)
				{
					USAM_Tabs.phone_call( USAM_Tabs.call );					
				} 			 
				$('#usam_page_tabs')
					.on('mouseenter', '.main_menu li', USAM_Tabs.show_subtabs_menu)
					.on('mouseleave', '.main_menu li', USAM_Tabs.hide_subtabs_menu)														
					.on('click', '.main_menu #main_menu_settings_button', USAM_Tabs.open_settings_menu_menu)
					.on('click', '#menu-toggle', USAM_Tabs.toggle_menu); // Изменить поле		
															
				$(window).on('beforeunload', USAM_Tabs.event_before_unload);
				$('.settings-error').insertAfter('.main_menu');
											
				USAM_Tabs.loading_help_setting();	
				USAM_Tabs.hide_description();					
				
				if ( screen.width >= 1400)
				{
					var top = 90;
					if ( $(".calendar_view").length )
						top = 0;
					
					if ( $(".js-fasten-toolbar").length )
						top = 125;					
				//	$('.menu_fixed_right').hcSticky({stickTo: '.tab-content', top: top});		
				}
				if ( screen.width > 850)
				{	
					$('.js-fasten-toolbar').hcSticky({stickTo: '.tab-content', top: 30});		
				}				
				$('body')
					.on('click', '.cancel_call', USAM_Tabs.cancel_phone_call)
					.on('click', '.insert_text li', USAM_Tabs.insert_text)						
					.on('click', '.user_block .js_delete_action', USAM_Tabs.delete_manager)		
					.on('change', '#usam_remind', USAM_Tabs.event_remind_clicked)	
					.on('click', '.js-modal', USAM_Tabs.open_modal_window)		
					.on('click', '.usam_menu .menu_name', USAM_Tabs.select_name_menu)
					.on('change', '#signature', USAM_Tabs.change_signature)
					.on('change', '.taxonomy_box h3 input', USAM_Tabs.checked_all);						

				$('.tab-content').on('submit', 'form', USAM_Tabs.form_submit);		
			
			//	$('.hidden_menu_tabs').on('click', '.hidden_item', USAM_Tabs.show_hidden_tab);			
																		
				$('#checklist_radio').on('change', '.input-radio', USAM_Tabs.change_checklist_radio);	
				$('.main_menu__settings').on('click', '.show_all', USAM_Tabs.click_show_all_tab);		
						
				if ( $(".form_view #map").length )
				{
					DG.then(function() {
						return DG.plugin('https://2gis.github.io/mapsapi/vendors/Leaflet.markerCluster/leaflet.markercluster-src.js');
					}).then(function() 
					{
						latitude = $(".form_view #map").attr("latitude");
						longitude = $(".form_view #map").attr("longitude");
						map = DG.map('map', {
							center: DG.latLng([latitude, longitude]),
							zoom: 13
						});						
						markers = DG.markerClusterGroup( { zoomToBoundsOnClick: false } );
						marker = DG.marker([latitude,longitude], { title: $(".form_view #map").attr("title") });
						if ( $('.js-map-description').length )
							marker.bindPopup($('.js-map-description').html());						
						markers.addLayer(marker);
						map.addLayer(markers);			
					});							
				}
				$( ".droppable-menu .droppable-item" ).draggable({				
				//	helper: "clone",
					revert: 'invalid', 
				//	snap:  '.hidden_menu_tabs',	
				//	addClasses: false,
					cursor: "move",		
					delay:300,
				//	activeClass:  "active-draggable-tab",	
					connectToSortable: ".droppable-menu",
					zIndex: 9900,					
				});			
				$(".droppable-menu").droppable({					
					accept: ".droppable-item",
					activate: function( e, ui ) 
					{	
						$('.settings-menu-tabnav').show();						
						if ( $('.main_menu__settings #list_hidden_items li.hidden_tab').length > 1 )					
						{
							$('.main_menu__settings .none_hidden_items').hide();	
						}	
						else if ( $('.main_menu__settings #list_hidden_items li.hidden_tab').length == 1  )
						{	
							$('.main_menu__settings .none_hidden_items').show();	
						}												
						USAM_Tabs.start_droppable = true;
						$('.subtab-menu').hide();		
					},					
					drop: function( e, ui) 
					{ 								
						$('.settings-menu-tabnav').hide();	
						USAM_Tabs.start_droppable = false;						
						var link = ui.draggable.find('a');
						var id = link.data('tab-id');				
							
						ui.draggable.appendTo('#list_hidden_items');							
						$('.main_menu__settings .none_hidden_items').hide();							
						usam_send({action: 'hidden_menu_tab',	'tab': id, 'page': USAM_Tabs.page, nonce: USAM_Tabs.hidden_menu_tab_nonce});	
					}							
				});
				$('.droppable-menu').sortable({
					zIndex: 9900,	
					cursor: "move",
				//	axis  : 'x',		
				//	helper: "clone",
				//	items: "li",				
				//	connectWith  : ".hidden_tab",
					start: function( e, ui ) 
					{
						USAM_Tabs.start_droppable = true;						
					},
					deactivate: function(e, ui) 
					{					
						var element = $(ui.item);		
						if ( element.parents( '#list_hidden_items' ) ) 
						{  
							if ( $('.main_menu__settings #list_hidden_items li.hidden_tab').length > 0 )						
								$('.main_menu__settings .none_hidden_items').hide();							
							else
								$('.main_menu__settings .none_hidden_items').show();	
						}										
						if ( element.hasClass( 'hidden_tab' ) && element.closest(".main-menu").length > 0 ) 
						{ 
							element.removeClass('hidden_tab').attr('style','');
						}		
						else if ( element.closest(".hidden_menu_tabs").length > 0 )
						{ 	
							element.removeClass('navigation-tab-active').addClass('hidden_tab').attr('style','');			
						}					
					},	
					stop: function( e, ui ) 
					{										
						var element = $(ui.item);
											
						USAM_Tabs.start_droppable = false;
						var tabs = [];
						var j = 0;						
						$('#menu_tabs-1 li a').each(function(i,elem) 
						{							
							tabs[j] = $(this).data('tab-id');	
							j++;							
						});			
						usam_send({action: 'sort_menu_tabs','tabs': tabs,	'page': USAM_Tabs.page,	nonce: USAM_Tabs.sort_menu_tabs_nonce});						
					},	
				});	
				const tableObserver = new IntersectionObserver(function(entries, server) {
					entries.forEach((e) => {
						if (e.isIntersecting)
						{ 
							const table = e.target
							table.classList.remove("js-add-products-table");
							server.unobserve(table);
							usam_send({'action': 'add_items_table', 'nonce': USAM_Tabs.add_items_table_nonce}, (r) => e.target.innerHTML = r);	
						}
					})
				});
				document.querySelectorAll('.js-add-products-table').forEach( function(v){
					tableObserver.observe(v);
				})									
				document.querySelectorAll('#element_editing_form textarea').forEach((el) => {
					if ( el.classList.contains('wp-editor-area') || el.hasAttribute('disabled-height'))
						return;
					let height = el.scrollHeight;			
					height = height<66?65:height;
					el.setAttribute('style', 'height:' +height+ 'px;overflow-y:hidden;');					
					el.addEventListener("input", (v) => el.style.height = el.scrollHeight < 66 ? '65px' : el.scrollHeight + 'px', false);
				});
			});
		},		
			
		save_add : function (e) 
		{
			e.preventDefault(); 		
			var formData = new FormData( document.forms.element_editing_form );		
			formData.append("nonce", USAM_Tabs.form_save_nonce);			
			formData.append("usam_ajax_action", 'form_save');
			formData.append("action", 'usam_ajax');
			formData.append("a", 'save');		
			var	callback = (r) =>			
			{
				let url = new URL(location.href);
				url.searchParams.delete('id');
				setTimeout( function(){ window.location.replace( url ); }, 500);				
			}	 
			usam_form_save( formData, callback );
		},		
				
		actions : function (e) 
		{
			usam_active_loader();
			e.preventDefault();			
			var t = e.currentTarget;
			t.classList.add('is-loading');
			action = t.getAttribute("data-action");	
			var	callback = (r) =>			
			{
				t.classList.remove('is-loading');
				if ( typeof r.reload !== typeof undefined )
					location.reload()
				if ( typeof r.url !== typeof undefined )
					window.location.replace( r.url );
				else if ( typeof r.form_name !== typeof undefined )
				{
					for (let k in r) 
						usam_set_url_attr( k, r[k] );
					window.location.replace( location.href );
				}
			}					
			item = USAM_Tabs.table?USAM_Tabs.table:USAM_Tabs.tab;
			usam_send({nonce: USAM_Tabs.bulkactions_nonce, action: 'bulkactions', a: action, item: item}, callback);
		},		
				
		bulk_actions : function() 
		{							
			switch ( $(this).val() ) 
			{			
				case 'bulk_actions' :				
					var modal_id = 'bulk_actions_'+USAM_Tabs.table;	
					$('html').on('loading_modal', 'body', function(){ usam_active_loader(); });	
					$.usam_get_modal(modal_id);
					$( "body" ).on( "append_modal", function(e)
					{				
						$('.loader__full_screen').remove();
						$(".chzn-select").chosen({ width: "200px" });	
						var title = '';		
						var row = '';						
						$('.wp-list-table tbody .check-column input:checkbox:checked').each(function()
						{
							title = $(this).closest('tr').find('.column-primary .row-title').text(); 
							row = row+ "<li>"+title+"</li>";				
						});
						if ( row != '' )
							$('#'+modal_id+' .selected_items').html('<ul>'+row+'</ul>');
						else
							$('#'+modal_id+' .selected_items').html('<div class="all_items_selected">все</div>');			
						$("#"+modal_id).on('click', '#modal_action', USAM_Tabs.save_bulk_actions);
						$('.js-autocomplete').each(function () 
						{					
							var t = $(this);
							$(this).autocomplete({	
								source      : $(this).data('url'),					
								minLength   : 2, 
								autoFocus 	: true,		
								select      : (e, ui) => {
									$(this).siblings().val(ui.item.value).trigger( "change" );	
									ui.item.value = ui.item.label;							
								}	
							});	
						})	
					});	
				break;		
				case 'email' :
					var html = '';
					var name = '';
					if ( USAM_Tabs.tab == 'contacts' ) 	
						name = 'to_contacts';
					else if ( USAM_Tabs.tab == 'company' ) 						
						name = 'to_companies';	
					else if ( USAM_Tabs.tab == 'orders' ) 						
						name = 'to_orders';					
										
					if ( name != '' )
					{
						$('.wp-list-table tbody .check-column input:checkbox:checked').each(function()
						{ 						
							html = html + '<input type="hidden" name="'+name+'[]" value="'+$(this).val()+'">';															
						});	
					}
					else
					{
						var emails;
						$('.wp-list-table tbody .check-column input:checkbox:checked').each(function()
						{ 
							emails = $(this).parents("tr").find("js-open-message-send").data("emails").split(',');						
							html = html + '<input type="hidden" name="'+name+'[]" value="'+emails[0]+'">';															
						});							
					}					
					if ( $('#send_mail').length )
					{
						$('#send_mail .js-to-email').html( html );		
						$('#send_mail .js-to-email-row').hide();
						$('#send_mail').modal();											
					}
					else
					{
						usam_active_loader();
						data = {action: 'get_email_sending_form', nonce: USAM_Tabs.get_email_sending_form_nonce};
						if ( $('.mce-tinymce ').length )
						{
							data.tinymce_scripts_loaded = true;
						}	
						usam_send(data, (r) =>
						{
							$('body').append( r );
							$('#send_mail .js-to-email').html( html );		
							$('#send_mail .js-to-email-row').hide();
							
							usam_set_height_modal( $('#send_mail') );
							tinyMCE.init({ 					
								themes : 'modern',
								skin : 'lightgray',
								remove_script_host: false,
								relative_urls: false,
								branding: false,
								statusbar: false,
								selector:'#email_editor',
								plugins: 'textcolor lists tabfocus paste wordpress link image',					
								table_toolbar: "tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol", 
								menubar: false, 
								theme : "modern", 
							//	height : '300px',
							//	width : width,						
								toolbar: 'fontsizeselect fontselect | formats blockformats fontformats fontsizes align | bold italic underline strikethrough superscript subscript | backcolor forecolor | numlist bullist | link image | undo redo | removeformat',
								setup: function (editor) {
									editor.on('change', function () {
										editor.save();
									});
								}
							});	
							usam_load_attachments( $('#send_mail .js-attachments') );		
						});
					}
				break;				
			}				
		},				
		
		save_bulk_actions : function() 
		{				
			var modal_id = 'bulk_actions_'+USAM_Tabs.table;
			$("#"+modal_id).modal('hide');
			usam_active_loader();
			var ids = [];
			var properties = [];		
			var val = '';
			i = 0;	
			$('#'+modal_id+' .js-properties select').each(function()
			{
				val = $(this).val();
				if ( val != '' )
				{ 
					properties[i] = {"value" : val, "key" : $(this).attr('name')};
					$(this).prop('selectedIndex',0);
					i++;
				}
			});			
			$('#'+modal_id+' .js-properties input,#'+modal_id+' .js-properties textarea').each(function()
			{
				val = $(this).val();
				if ( val != '' )
				{ 
					properties[i] = { "value" : val, "key" : $(this).attr('name') };
					i++;
				}
			});				
			i = 0;	
			$('.wp-list-table tbody .check-column input:checkbox:checked').each(function()
			{
				ids[i] = $(this).val();
				$(this).removeAttr("checked");
				i++;
			});
			usam_send({action: 'bulk_actions_'+USAM_Tabs.table, 'properties': properties, 'ids': ids,	nonce: USAM_Tabs.bulk_actions_nonce}, (r) => USAM_Tabs.update_table());			
			return false;	
		},	

		change_signature : function()
		{ 
			usam_active_loader();
			var callback = function(r) 
			{		
				var	tinyMCE_content = tinyMCE.get('email_editor').getContent();	
				$signature = $("<div id='js-content'>"+tinyMCE_content+"</div>").find('.js-signature');				
				if ( $signature.length )
					content = $signature.html( r ).parents('#js-content').html();
				else
					content = "<div class='js-signature'>"+r+"</div>" + tinyMCE_content;
				tinyMCE.get('email_editor').setContent( content );	
			};		
			usam_send({nonce: USAM_Tabs.get_signature_nonce, action: 'get_signature',	id: $(this).val()}, callback);
		},		
		
		add_favorites_page : function( e ) 
		{
			var title = $(".js-tab-title").text();			
			let url = new URL( document.location.href );				
			let url2 = new URL( window.location.origin + window.location.pathname );
			url2.searchParams.set('page', url.searchParams.get('page'));
			url2.searchParams.set('tab', url.searchParams.get('tab'));
			if ( url.searchParams.has('view') )
				url2.searchParams.set('view', url.searchParams.get('view'));
			if ( url.searchParams.has('table') )
				url2.searchParams.set('table', url.searchParams.get('table'));
			if ( url.searchParams.has('n') )
				url2.searchParams.set('n', url.searchParams.get('n'));
			if ( url.searchParams.has('m') )
				url2.searchParams.set('m', url.searchParams.get('m'));				
			var link = url2.toString();
			var t = $(this);
			t.addClass("is-loading");			
			var	handler = function(r)
			{					
				t.removeClass("is-loading");
				if ( t.hasClass("dashicons-star-filled") )
				{
					t.removeClass("dashicons-star-filled").addClass("dashicons-star-empty");
					$("#wp-admin-bar-favorite-page ul #wp-admin-bar-favorite-page-sub-item-"+USAM_Tabs.screen_id).remove();
				}
				else
				{
					t.addClass("dashicons-star-filled").removeClass("dashicons-star-empty");
					$("#wp-admin-bar-favorite-page ul").append("<li id='wp-admin-bar-favorite-page-sub-item-"+USAM_Tabs.screen_id+"' class='usam-favorite-page-link'><a class='ab-item' href='"+link+"'>"+title+"</a></li>");
				}								
			};			
			usam_send({action: 'add_favorites_page', 'title': title,'url': link, 'screen_id': USAM_Tabs.screen_id, nonce: USAM_Tabs.add_favorites_page_nonce}, handler);			 
		},				
		table_select_page_number: function(e) 
		{	
			if ( 13 == e.which )
			{
				e.preventDefault();	 				
				var	data = Object.assign({}, interface_filters.filtersData);
				data.paged = parseInt($(this).val()) || '1';
				USAM_Tabs.table_view( data );
			}
		},	
		
		update_table: function( $table ) 
		{			 
			var	data = Object.assign({}, interface_filters.filtersData);
			USAM_Tabs.table_view( data, $table );
		},
				
		table_sorting: function(e) 
		{ 
			usam_active_loader();
			e.preventDefault();	
			var	data = Object.assign({}, interface_filters.filtersData);
			var query = e.currentTarget.getAttribute('href');
			var $table = $(this).parents('.usam_tab_table');
			if( $table.find('input[name=paged]').length )
				data.paged = parseInt($table.find('input[name=paged]').val());				
			data.order = USAM_Tabs.list_table_query( query, 'order' ) || 'asc';
			data.orderby = USAM_Tabs.list_table_query( query, 'orderby' ) || 'title';
			$table.find('.js-table-order').val(data.order);
			$table.find('.js-table-orderby').val(data.orderby);			
			USAM_Tabs.table_view( data, $table );
		},
		
		table_navigation: function( e ) 
		{ 
			e.preventDefault();
			usam_active_loader();		
			var	data = {}	
			var $table = $(this).parents('.usam_tab_table');
			var query = this.getAttribute( 'href' );
			if ( $(this).hasClass('last-page') )
				data.paged = parseInt($table.find('.total-pages').html().replace(/\D/g,''));			
			else if ( $(this).hasClass('first-page') )
				data.paged = 1;	
			else if ( $(this).hasClass('prev-page') )
				data.paged = USAM_Tabs.list_table_query( query, 'paged' ) || 1;
			else if ( $(this).hasClass('next-page') )
				data.paged = USAM_Tabs.list_table_query( query, 'paged' ) || 1;	
			else				
				data.paged = USAM_Tabs.list_table_query( query, 'paged' ) || 1;
			USAM_Tabs.table_view( data, $table );			
		},
		
		get_table_args: function( data, $table ) 
		{
			data = Object.assign(data, interface_filters.filtersData);
			data.order = $table.find('.js-table-order').val();			
			data.orderby = $table.find('.js-table-orderby').val();
			data.table = $table.find('.js-table-name').val();	
			data.screen_id = USAM_Tabs.screen_id;
			if ( typeof list_args[data.table] !== typeof undefined )
			{
				for (k in list_args[data.table].query_vars) 
				{
					data[k] = list_args[data.table].query_vars[k];
					usam_set_url_attr( k, data[k] );
				}
			}
			return data;
		},	
		
		table_view: function( d, $table )
		{
			var data = Object.assign({}, d);
			if ( typeof $table === typeof undefined || $table === null )
				var $table = $('.usam_tab_table');
			if ( typeof data === typeof undefined || data === null )
				var data = {};			
			var vars = usam_get_url_attrs( location.href );									
			if ( typeof vars['form_name'] !== typeof undefined && typeof vars['id'] !== typeof undefined && typeof vars['form'] !== typeof undefined) 
			{
				data.form_name = vars['form_name'];
				data.id = vars['id'];
				data.form = vars['form'];
				if (typeof vars['subtab'] !== typeof undefined) 
					data.subtab = vars['subtab'];				
			}
			else
			{
				var d = location.href.split("#")[0].split("?");
				history.replaceState( '' , '', d[0] );	
				usam_set_url_attr('page',  USAM_Tabs.page);
				usam_set_url_attr('tab',  USAM_Tabs.tab);
				usam_set_url_attr('table', USAM_Tabs.table);
				for (k in data) 
				{					
					if ( k != 'undefined' )
						usam_set_url_attr( k, data[k] );	
				}					
			}		
			data.tab  = USAM_Tabs.tab;
			data.page = USAM_Tabs.page;
			data = USAM_Tabs.get_table_args( data, $table );
			data.action = 'get_list_table';			
			data.nonce  = USAM_Tabs.get_list_table;				
			usam_send(data, (r) =>
			{
				if ( r.rows.length )
				{
					$('#the-list').html( r.rows );
					$('#the-list').trigger('table_update',[r]);
					usam_lazy_image();
				} 
				if ( r.column_headers.length )
					$('.wp-list-table thead tr').html( r.column_headers );					
				if ( r.column_footer.length )
					$('.wp-list-table tfoot tr').html( r.column_footer );					
				if ( r.pagination.top.length )
					$('.tablenav.top .tablenav-pages').html( $(r.pagination.top).html() );
				if ( r.pagination.bottom.length )
					$('.tablenav.bottom .tablenav-pages').html( $(r.pagination.bottom).html() );					
				if ( typeof r.total_pages !== typeof undefined )
				{				
					if ( r.total_pages > 1 )
						document.querySelectorAll('.tablenav .tablenav-pages').forEach((e) => { e.classList.remove("one-page"); });
					else
						document.querySelectorAll('.tablenav .tablenav-pages').forEach((e) => { e.classList.add("one-page"); });
				}
				let e = new CustomEvent('table-load', {bubbles: true, cancelable: true, detail: data.table});
				document.dispatchEvent(e);				
			}, 'GET');
		},	 
			
		list_table_query: function( url, variable ) 
		{
			var vars = usam_get_url_attrs( url );
			if ( typeof vars[variable] !== typeof undefined ) 
				return vars[variable];
			return false;
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
			var callback = (r)=>
			{
				if ( r )
				{						
					if ( action == 'delete' )
						tr.remove();					
					else if ( typeof r.form_name !== typeof undefined )
					{
						if ( typeof r.id !== typeof undefined )
						{
							let url = new URL( document.location.href );
							for (let k in r) 
								url.searchParams.set(k, r[k]);
							
							if (action == 'act' || action == 'invoice')
								window.open(url.href);
							else
								window.location.replace( url.href );
						}
					}
					else if(  action !== 'download' )
					{
						for (let k in r) 
							usam_set_url_attr( k, r[k] );
						USAM_Tabs.update_table( $table );
					}
				}
				else
					usam_notifi({ 'text': UNIVERSAM.action_error });
			};
			usam_send({nonce: USAM_Tabs.bulkactions_nonce, action: 'bulkactions', a: action, item: table, id: id}, callback);	
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
					ids[i] = parseInt($(this).val());
					i++;
				});				
				var callback = (r) =>
				{ 
					if ( r )
					{						
						if ( typeof r.form_name !== typeof undefined )
						{
							for (let k in r) 
								usam_set_url_attr( k, r[k] );
							window.location.replace( location.href );
						}
						else
							USAM_Tabs.update_table( $table );
					}					
					else
						usam_notifi({'text': UNIVERSAM.action_error});
				};
				usam_send({nonce: USAM_Tabs.bulkactions_nonce, action: 'bulkactions', a: action, item: table, cb: ids}, callback);	
			}			
		},											
				
		open_modal_window : function( e ) 
		{ 
			e.preventDefault();
			if (typeof $(this).data('modal') !== 'undefined')	
			{							
				$('.js-modal').removeClass('button_modal_active');
				$(this).addClass('button_modal_active');
				var type = $(this).data('modal');
				var data = {};	
				if (typeof $(this).data('screen') !== 'undefined')	
				{
					data.template = 'lists';
					data.screen = $(this).data('screen');
				}
				if (typeof $(this).data('list') !== 'undefined')
					data.list = $(this).data('list');			
				if (typeof $(this).data('template') !== 'undefined')
					data.template = $(this).data('template');		
				if (typeof $(this).data('title') !== 'undefined')
					data.title = $(this).data('title');							
				data.id = USAM_Tabs.id;
				
				$('body').on('append_modal', '#'+type, function(){ $('.loader__full_screen').remove(); });	
				$('html').on('loading_modal', 'body', function(){ usam_active_loader(); });	
				$.usam_get_modal(type, data);
			}
		},	
				
		select_customer : function( e ) 
		{
			$(".select_customer").toggleClass('hide'); 
		},	
						
		close_tinymce : function( e ) 
		{		
			if ( USAM_Tabs.including_editing )
			{	
				if ( $(e.target).attr("id") == USAM_Tabs.tinymce_id ) 
					return;					
				if ( $(e.target).closest("#tinymce_block").length ) 
					return;
				if ( $(e.target).closest(".mce-panel").length ) 
					return;
				if ( $(e.target).closest(".mce-widget").length ) 
					return;		
				if ( $(e.target).closest(".mce-container").length ) 
					return;	
				if ( $(e.target).closest("#wp-link-wrap").length ) 
					return;		
				
				var	content = tinyMCE.get(USAM_Tabs.tinymce_id).getContent();		
				tinyMCE.get(USAM_Tabs.tinymce_id).remove();	
				 
				$('#'+USAM_Tabs.tinymce_id).html( content );
				$('#'+USAM_Tabs.tinymce_id).siblings('.usam_change_content').html( content ).show();						
				
				USAM_Tabs.including_editing = false;
			}			
		},
		
		// Получить редактируемый блок текста
		change_content : function( e ) 
		{
			if ( !USAM_Tabs.including_editing )
			{ 			
				e.preventDefault();		
				var height = $(this).height();			
				var width = $(this).width();		
				$(this).hide(); 				
				USAM_Tabs.tinymce_id = $(this).siblings('.description_textarea').attr('id');
				
				$(document).on('click', USAM_Tabs.close_tinymce);			
				tinymce.init({ 					
					remove_script_host: false,
					relative_urls: false,
					branding: false,
					selector:'#'+USAM_Tabs.tinymce_id,
					plugins: 'textcolor lists tabfocus paste wordpress link',					
					table_toolbar: "tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol", 
					menubar: '', 
					theme : "modern", 
					height : height,
					width : width,
					toolbar: 'bold italic | link image | fontsizeselect fontselect | backcolor forecolor | numlist bullist | alignleft aligncenter alignright alignjustify |' 
				});					
				USAM_Tabs.including_editing = true;				
				return false;
			}
		},				
		
		click_show_all_tab : function( ) 
		{	
			$('.main_menu__settings #list_hidden_items li.hidden_tab').each(function(i,elem) 
			{
				$(this).appendTo('#menu_tabs-1').removeClass('hidden_tab').addClass('navigation-tab-1').addClass('navigation-tab').draggable();					
			});	
			$('.main_menu__settings .none_hidden_items').show();			
			usam_send({action: 'show_all_menu_tab', 'page': USAM_Tabs.page, nonce: USAM_Tabs.show_all_menu_tab_nonce});	
		},		
		
		change_checklist_radio : function( ) 
		{
			$(".groups_list").toggleClass('hide');
		},		
		
		event_change_sms : function() 
		{ 
			var length = $(this).val().length;
			$(this).parent().find("#characters").html(length);
		},	
		
		show_description : function(e) 
		{				
			e.preventDefault();
			var description = $(this).siblings(".description_box");
			if ( description.is(":visible") ) 			
				description.hide();
			else 			
				description.show();
		},
		
		hide_description : function() 
		{				
			jQuery('.detailed_description').find('.description_box').hide();			
		},
																	
		checked_all : function( e ) 
		{					
			var checked = $(this).is(':checked');
			$(this).parents('.taxonomy_box').find('.categorydiv input[type="checkbox"]').attr('checked',checked);
		},		
		
		select_name_menu : function() 
		{								
			$(this).siblings('.menu_content').toggleClass('select_menu');
			setTimeout(function () {
				var f = (e) =>{					
					if ( $(e.target).hasClass("menu_content") || $(e.target).hasClass("menu_name") || $(this).siblings('.menu_content').length ) 
						return;
					$('.menu_content').removeClass('select_menu');
					$(document).off('click', f);
				}
				$(document).on("click", f);
			}, 200); 			
		},

		form_submit : function() 
		{				
			var j = 0;
			$('.required').each(function(i,elem) 
			{
				if ( $(this).val() == '' )
				{								
					j++;					
					$(this).addClass('highlight');					
				}
				else
					$(this).removeClass('highlight');				
			});				
			if ( j > 0 )
			{
				$('html, body').animate({ scrollTop: $(".highlight").offset().top }, 500); 
				return false;
			}			
			return true;
		},
								
		event_remind_clicked : function() 
		{					
			var date_remind = $(this).siblings('#date_remind');
			if ( $(this).prop('checked') )
				date_remind.removeClass('hide');
			else
				date_remind.addClass('hide');
		},
							
		// Удалить строку таблицы		
		event_delete_table_row : function( e ) 
		{			
			e.preventDefault();				
			var this_row = $(this).closest('tr');	
			this_row.remove();
		},	
		
		show_subtabs_menu : function() 
		{					
			if ( !USAM_Tabs.start_droppable )
			{
				$(this).children('ul').show();			
				$(this).children('dl').show();		
			}
		},
		
		toggle_menu : function() 
		{			
			$('#usam_page_tabs .main_menu__items').toggle();				
		},
		
		open_settings_menu_menu : function() 
		{			
			var menu = $(this).siblings('.settings-menu-tabnav')			
			if ( menu.find('#list_hidden_items li.hidden_tab').length > 0 )
			{
				menu.find('.none_hidden_items').hide();					
			}
			menu.toggle();
		},		
				
		hide_subtabs_menu : function() 
		{				
			$(this).children('ul').hide();	
			$(this).children('dl').hide();		
		},		
				
		//Загрузить опции помощи
		loading_help_setting : function() 
		{					
			var id = '';
			$(".box_help_setting").addClass('hidden');
			$('.show_help').each(function(i,elem) 
			{
				if ( $(this).prop("checked") )
				{
					id = $(this).attr('id');					
					$(".box_help_setting#"+id).removeClass('hidden');
					$(".box_help_setting#"+id).addClass('show');
				}
				
			});
		},
		
		//Показать описание опции
		show_help_setting_box : function() 
		{					
			var box = $(this).parents('.inside');
			var id = $(this).attr('id');	
			
			box.find(".box_help_setting").addClass('hidden');
			box.find(".box_help_setting#"+id).removeClass('hidden');
			box.find(".box_help_setting#"+id).addClass('show');
		},			
	
		event_form_submitted : function() {
			USAM_Tabs.unsaved_settings = false;
		},

		event_changed : function() 
		{
			USAM_Tabs.unsaved_settings = true;
		},
		
		event_before_unload : function() 
		{
			if (USAM_Tabs.unsaved_settings) {
				return USAM_Tabs.before_unload_dialog;
			}
		},

		// Загрузите вкладку настроек, когда кнопка вкладки нажата
		event_tab_button_clicked : function(e)
		{		
			e.preventDefault();
			var href = $(this).find('a').attr('href');
			USAM_Tabs.load_tab(href);
			return false;
		},

		// Когда нажата кнопка вперед / назад в браузере загрузить нужную вкладку	
		event_pop_state : function(e) 
		{
			if (e.state) {
				USAM_Tabs.load_tab(e.state.url, false);
			}
		},

		/**
		 * Display a small spinning wheel when loading a tab via AJAX	
		 */
		toggle_ajax_state : function(tab_id) 
		{
			var tab_button = $('a[data-tab-id="' + tab_id + '"]');
			tab_button.toggleClass('nav-tab-loading');
		},

		// Используя AJAX загрузить вкладку на страницу настроек.
		load_tab : function(url, push_state) 
		{
			usam_active_loader();
			if (USAM_Tabs.unsaved_settings && ! confirm(USAM_Tabs.ajax_navigate_confirm_dialog)) {
				return;
			}
			if (typeof push_state == 'undefined') {
				push_state = true;
			}
			var attrs = usam_get_url_attrs(url);			
			var tab_id = attrs.tab;
			var data = $.extend({}, attrs, {'action': 'navigate_tab', 'nonce': USAM_Tabs.navigate_tab_nonce, 'page': USAM_Tabs.page, 'tab': tab_id});					
			USAM_Tabs.toggle_ajax_state(tab_id);

			// PushState, чтобы сохранить эту загрузку страницы в историю, и изменить поле адреса браузера
			if (push_state && history.pushState) {
				history.pushState({'url' : url}, '', url);
			}			
			var callback = function(r) 
			{					
				var t = USAM_Tabs;				
				t.unsaved_settings = false;
				t.toggle_ajax_state(tab_id);
				$('#tab_' + USAM_Tabs.tab).replaceWith(r.content);				
				USAM_Tabs.tab = tab_id;
				$('.settings-error').remove();
				$('.navigation-tab-active').removeClass('navigation-tab-active');
				$('[data-tab-id="' + tab_id + '"]').parent('li').addClass('navigation-tab-active');
				$('#usam_tab_page form').attr('action', url);
				let c = 'USAM_Page_'+USAM_Tabs.page
				if ( c[tab_id] !== undefined )				
					c[tab_id].event_init();
			};		
			usam_send(data, callback);		
		},
				
		phone_call : function( call ) 
		{
			var html = '<div class="call_control_panel"><div class="call_control_panel__body"><div class="call_control_panel__phone call_control_panel__text">'+call.phone+'</div><div class="cancel_call icon" data-id='+call.id+' data-gateway='+call.gateway+'></div><div class="call_control_panel__line"></div><div class="call_control_panel__message call_control_panel__text">'+call.message+'</div><div class="call_customer icon"></div></div></div>';
			$('body').append(html);			
		},
		
		click_phone_call : function(e) 
		{	
			var phone = '';
			if ( typeof $(this).data("phones") !== 'undefined' && $(this).data("phones") != '' )
			{ 
				var phones = $(this).data("phones")+",";
				phones = phones.split(',');	
				phone = phones[0];
			}
			else if ( typeof $(this).data("phone") !== 'undefined' && $(this).data("phone") != '' )
			{
				phone = $(this).data('phone');
			}
			if ( phone != '' && $( ".call" ).length == 0 )
			{	
				USAM_Tabs.phone_call({id: 0, 'phone': phone, message: USAM_Tabs.call_status_message});			
				usam_api('phone/call', {phone:phone}, 'GET', function(r)
				{					
					if ( r === null )
					{
						$('.call_control_panel').remove();
						usam_notifi({ 'text': 'Не удается связаться'});
					}
					else	
						$( ".cancel_call" ).attr('data-id', r.id);
				});
			}		
		},
		
		cancel_phone_call : function(e) 
		{ 
			var id = $(this).attr('data-id');
			var gateway = $(this).attr('data-gateway');			
			$(this).parents('.call_control_panel').remove();					
			usam_api('phone/cancel', {id: id, gateway:gateway}, 'GET');			
		},
		
		insert_text : function(e) 
		{				
			e.preventDefault();				
			var text = $(this).attr('data-text');
			if ( jQuery("#email_editor").length )
			{		
				tinyMCE.get('email_editor').insertContent( text );	
			}		
		},			
		
		open_message_send : function(e) 
		{ 	
			e.preventDefault();				
			var html = '';
			if ( typeof $(this).data("emails") !== 'undefined' && $(this).data("emails") != '' )
			{ 
				var emails = $(this).data("emails").split(',');						
				var name = '';
				if ( typeof $(this).data("name") !== 'undefined' && $(this).data("name") != '' )
				{
					name = $(this).data("name")+" ";	
				}
				$.each(emails, function(index, value)
				{	
					if ( value != '' )
						html = html+"<option value='"+value+"'>"+name+value+"</option>";
				});		
			}		
			var el = document.querySelector('.email_property');
			if ( html == '' && el )
				html = "<option value='"+el.innerText+"'>"+el.innerText+"</option>";
			if ( $('#send_mail').length )
			{		
				if ( typeof $(this).data("type") !== 'undefined' || typeof $(this).data("id") !== 'undefined' )
				{
					$('#send_mail #object_type').val( $(this).data("type") );	
					$('#send_mail #object_id').val( $(this).data("id") );	
				}
				else
				{
					if ( html != '' )
						$('#send_mail .js-to-email').html("<select name='to' id='to_email'>"+html+"</select>");				
					else
						$('#send_mail .js-to-email').html("<input id='to_email' type='text' name='to' value=''>");	
				}
				usam_set_height_modal( $('#send_mail') );
			}
			else
			{ 
				var t = $(this);
				t.addClass("is-loading");
				var	data = {action: 'get_email_sending_form', nonce: USAM_Tabs.get_email_sending_form_nonce};	
				if ( $('.mce-tinymce').length )
				{
					data.tinymce_scripts_loaded = true;
				}
				if ( typeof $(this).data("type") !== 'undefined' || typeof $(this).data("id") !== 'undefined')
				{
					data.object_type = $(this).data("type");	
					data.object_id = $(this).data("id");	
				}
				usam_send(data, (r)=>{	
					t.removeClass("is-loading");		
					$('body').append( r );	
					
					if ( html != '' )
						$('#send_mail .js-to-email').html("<select name='to' id='to_email'>"+html+"</select>");						
					
					tinymce.init({ 					
						themes : 'modern',
						skin : 'lightgray',
						remove_script_host: false,
						relative_urls: false,
						branding: false,
						statusbar: false,
						selector:'#email_editor',
						plugins: 'textcolor lists tabfocus paste wordpress link image',					
						table_toolbar: "tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol", 
						menubar: false, 
						theme : "modern", 
						height : '300px',
					//	width : width,						
						toolbar: 'fontsizeselect fontselect | formats blockformats fontformats fontsizes align | bold italic underline strikethrough superscript subscript | backcolor forecolor | numlist bullist | link image | undo redo | removeformat',
						setup: function (editor) {
							editor.on('change', function () {
								editor.save();
							});
						}
					});	
					usam_load_attachments( $('#send_mail .js-attachments') );	
					usam_set_height_modal( $('#send_mail') );
				});		
			}			
		},				
		
		open_sms_send : function(e) 
		{ 	
			e.preventDefault();		
			var html = '';
			if ( typeof $(this).data("phones") !== 'undefined' && $(this).data("phones") != '' )
			{ 
				var name = '';
				if ( typeof $(this).data("name") !== 'undefined' && $(this).data("name") != '' )
				{
					name = $(this).data("name")+" ";	
				}				
				var phones = $(this).data("phones")+",";
				phones = phones.split(',');				
				$.each(phones, function(index, value)
				{	
					if ( value != '' )
						html = html+"<option value='"+value+"'>"+name+value+"</option>";
				});								
			} 
			if ( $('#send_sms').length )
			{
				if ( html != '' )
					$('#send_sms .js-to-sms').html("<select name='to' id='to_phone'>"+html+"</select>");				
				else
					$('#send_sms .js-to-sms').html("<input id='to_phone' type='text' name='to' value=''/>");	
				if ( typeof $(this).data("type") !== 'undefined' || typeof $(this).data("id") !== 'undefined')
				{
					$('#send_sms #object_type').val( $(this).data("type") );	
					$('#send_sms #object_id').val( $(this).data("id") );	
				}	
				usam_set_height_modal( $('#send_sms') );		
			}			
			else
			{ 
				var t = $(this);
				t.addClass("is-loading");
				var	data = {
					action : 'get_sms_sending_form',					
					nonce  : USAM_Tabs.get_sms_sending_form_nonce
				};					
				if ( typeof $(this).data("type") !== 'undefined' || typeof $(this).data("id") !== 'undefined')
				{
					data.object_type = $(this).data("type");	
					data.object_id = $(this).data("id");	
				}						
				var callback = function(r)
				{	
					t.removeClass("is-loading");		
					$('body').append( r );					
					if ( html != '' )
						$('#send_sms .js-to-sms').html("<select name='to' id='to_phone'>"+html+"</select>");	
					
					usam_set_height_modal( $('#send_sms') );
				};		
				usam_send(data, callback);		
			}			
		},

		shipped_document_status : function(e) 
		{		
			usam_send({nonce: USAM_Tabs.bulkactions_nonce, action: 'bulkactions', a: 'status-'+$(this).val(), item: 'shipping_documents', id: $(this).data('id')});	
		},
		
		shipped_document_courier : function(e) 
		{		
			usam_send({nonce: USAM_Tabs.bulkactions_nonce, action: 'bulkactions', a: 'courier', item: 'shipping_documents', id: $(this).data('id'), courier:$(this).val()});	
		},	
		
		delete_manager : function(e) 
		{	
			e.preventDefault();		
			$(this).parents('.user_block').remove();		
		},	
		
		action_item : function(e) 
		{							
			e.preventDefault();			
			var t = e.currentTarget;
			t.classList.add('is-loading');
			action = t.getAttribute("data-action");
			group = t.getAttribute("data-group");
			if ( t.hasAttribute('data-id') )
				id = t.getAttribute("data-id");
			else
				id = USAM_Tabs.id;
			usam_send({nonce: USAM_Tabs.bulkactions_nonce, action: 'bulkactions', a: action, item: group, id: id}, (r) =>			
			{
				t.classList.remove('is-loading');					
				if ( action == 'copy' )
				{					
					for (let k in r) 
						usam_set_url_attr( k, r[k] );
					window.location.replace( location.href );
				}
				else if ( action == 'delete' )
				{					
					let url = new URL( document.location.href );
					url.searchParams.delete("form");
					url.searchParams.delete("form_name");
					url.searchParams.delete("id");
					if ( url.searchParams.has('subtab') )
						url.searchParams.delete("subtab");	
					window.location.replace( url.href );				
				}
				if ( typeof r.reload !== typeof undefined )
					location.reload()
				else if ( typeof r.form_name !== typeof undefined )
				{
					for (let k in r) 
						usam_set_url_attr( k, r[k] );
					window.location.replace( location.href );
				}
			});
		},
		
		get_form_confirmation_delete_item : function(e) 
		{							
			e.preventDefault();	
			var $form = null;
			delete_callback = (e) =>
			{ 
				$table = $(this).parents('.usam_tab_table');			
				if ( $table.length )
				{
					var item = $table.find('.js-table-name').val();							
					var id = USAM_Tabs.list_table_query( $(this).attr('href'), 'cb' ) || '0';
					$(this).parents('tr').remove();
				}
				else
				{
					$form = $(this).parents('.element_form');			
					if ( $form.length )
					{						
						var goto_url = $(this).attr('href');
						var item = $form.attr('data-element');							
						var id = $form.attr('data-id');						
						let url = new URL(location.href);
						if ( !item )
							item = url.searchParams.get('tab');	
					}
					else
						return false;
				}				
				var	callback = (r) =>			
				{ 
					usam_notifi({ 'text': UNIVERSAM.item_delete_text });
					if ( $form !== null && $form.length )
						window.location.replace( goto_url );
				}
				usam_send({action: 'delete', 'id': id, 'item': item, nonce: USAM_Tabs.delete_nonce}, callback);	
			}			
			if ( $('#confirmation_delete_item').length == 0 )
			{
				var	callback = function(r)
				{
					$('body').append( r );
					$('#confirmation_delete_item').modal();					
				};			
				usam_send({action: 'get_form_confirmation_delete', nonce: USAM_Tabs.get_form_confirmation_delete_nonce}, callback);
			}
			else
			{
				$('#confirmation_delete_item').modal();							
			}	
			$('body').on('click', '.js-action-delete-item', delete_callback);					
		},
	});	
})(jQuery);	
USAM_Tabs.init();