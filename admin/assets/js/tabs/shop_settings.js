(function($)
{
	$.extend(USAM_Page_shop_settings, 
	{				
		init : function() 
		{					
			$(function()
			{			
				USAM_Page_shop_settings.wrapper = $('.tab_'+USAM_Tabs.tab);						
				if ( USAM_Page_shop_settings[USAM_Tabs.tab] !== undefined )				
					USAM_Page_shop_settings[USAM_Tabs.tab].event_init();
				
				USAM_Page_shop_settings.wrapper.
				   on('change','input, textarea, select', USAM_Page_shop_settings.event_settings_changed).						
				   on('submit','#usam-settings-form', USAM_Page_shop_settings.event_settings_form_submitted);					
			});
		},	
	
		event_settings_form_submitted : function() {
			USAM_Page_shop_settings.unsaved_settings = false;
		},

		event_settings_changed : function() {
			USAM_Page_shop_settings.unsaved_settings = true;
		},				
	});	
	
	/**
	 * Бланки
	 */
	USAM_Page_shop_settings.blanks = 
	{		
		event_init : function() 
		{
			if ( $('#edit_form_blanks').length )
			{
				USAM_Page_shop_settings.wrapper
					  //  .on('submit','#element_editing_form ', USAM_Page_shop_settings.blanks.save_form_blanks);
						.on('click','.button_save_close', USAM_Page_shop_settings.blanks.save_form_blanks)
						.on('click','.button_save', USAM_Page_shop_settings.blanks.save_form_blanks)	
						.on('change','#usam_display_setting_blank select', USAM_Page_shop_settings.blanks.change_company_blank);					
			}
		},
		
		iframe_blanks_loaded : function() 
		{ 
			let iframe = $('#edit_form_blanks').contents();				
			iframe.on('click', '#add-columns', USAM_Page_shop_settings.blanks.open_add_columns);		
			$('body').on('click','#add_column_modal_window .system_attributes input[type=checkbox]', USAM_Page_shop_settings.blanks.add_columns); 		
			USAM_Page_shop_settings.blanks.load_sortable();
		},	
		
		load_sortable : function() 
		{ 
			let iframe = $('#edit_form_blanks').contents();				
			iframe.find('#products-table thead').sortable({
				cursor: "move",
				axis:    "x",
				items       : 'th',
				containment : 'parent',	
				containment : 'parent',		
				zIndex      : 9999,	
				cursorAt: { left: 80 },		
				dropOnEmpty: false,					
				forceHelperSize: false,
				forcePlaceholderSize: false
			});			
		},

		change_company_blank : function() 
		{ 
			var callback = function(r)
			{
				$("#edit_form_blanks").contents().find('body').html( r );	
				USAM_Page_shop_settings.blanks.load_sortable();
			};		
			usam_send({action: 'edit_blank', 'company': $(this).val(), 'blank': USAM_Tabs.id, nonce: USAM_Page_shop_settings.edit_blank_nonce}, callback);	
		},	
		
		open_add_columns : function() 
		{ 
			let iframe = jQuery('#edit_form_blanks').contents();		
			iframe.find('#products-table thead input').each(function()
			{ 
				$('#add_column_modal_window .system_attributes input[type=checkbox]#attribute_'+$(this).attr('name')).prop('checked', true);
			});
			usam_set_height_modal( $('#add_column_modal_window') );			
		},	
		
		add_columns : function() 
		{			
			let iframe = jQuery('#edit_form_blanks').contents();	
			var id = $(this).val();				
			if ( $(this).prop('checked') )
			{							
				if ( iframe.find('#products-table thead .column-'+id).length == 0 )
				{											
					var label = $(this).closest(".edit_form__item").find('label').html();
					var th ='<th class="column-'+id+' ui-sortable-handle"><input type="text" class="table_colum_edit" placeholder="'+label+'" value="'+label+'" name="'+id+'"></th>';
					iframe.find('#products-table thead tr').append($(th));				
					iframe.find('#products-table tbody tr').append($('<td></td>'));
					var column = iframe.find('#products-table tbody tr:first td').length;					
					if ( column-4 > 0 )
					{						
						column = column-4;						
						iframe.find('#products-table tfoot tr').each(function()
						{ 
							$(this).find('td:first').attr( 'colspan', column);
						});						
					}					
				}
			}
			else if ( iframe.find('#products-table thead .column-'+id).length )
			{
				iframe.find('#products-table .column-'+id).remove();
			}
		},
			
		save_form_blanks : function() 
		{ 			
			var $close = false;
			if ( $(this).attr('id') == "edit-submit-save-close" )
				$close = true;			
			
			var iframe = jQuery('#edit_form_blanks').contents();					
			var i = 0;			
			var input = {};
			var textarea = {};
			var table = {};		
			iframe.find(".option_form_input").each( function()
			{					
				input[i] = { "value" : $(this).val(), "name" : $(this).attr('name') };				
				++i;
			});		
			iframe.find(".option_form_textarea").each( function()
			{					
				textarea[i] = { "value" : $(this).val(), "name" : $(this).attr('name') };				
				++i;
			});				
			
			i = 0;	
			iframe.find("#products-table:first .table_colum_edit").each( function()
			{								
				table[i] = { "value" : $(this).val(), "name" : $(this).attr('name') };				
				++i;
			});							
			usam_active_loader();
			var data = {
				action        : 'save_blank',
				'input'       : input,		
				'textarea'    : textarea,						
				'table'       : table,						
				'blank'       : jQuery('#edit_form_blanks').data('id'),			
				'company'     : jQuery('#usam_display_setting_blank select').val(),							
				nonce         : USAM_Page_shop_settings.save_blank_nonce
			},									
			callback = function(r)
			{											
				if ( $close )
				{
					$('#element_editing_form ').submit();
					return true;
				}										
			};				
			usam_send(data, callback);	
			return false;			
			
		},		
	};
	
	/**
	 * Шаблоны сообщений
	 */
	USAM_Page_shop_settings.template_messages = 
	{		
		event_init : function() 
		{			
			USAM_Page_shop_settings.wrapper.
			   on('click','.theme .theme-screenshot', USAM_Page_shop_settings.template_messages.event_select_template_messages);
		},
	
		event_select_template_messages : function() 
		{
			//$('.themes .theme').removeClass('active');
		//	$(this).addClass('active');			
		},		
	};
			
	/**
	 * Уведомления о событиях
	 */
	USAM_Page_shop_settings.notification = 
	{		
		event_init : function() 
		{			
			USAM_Page_shop_settings.wrapper
			    .on('change','#check_type', USAM_Page_shop_settings.notification.event_select_check_type)
				.on('click','#usam_conditions .condition-logic', USAM_Page_shop_settings.notification.change_condition_logic);
		},
	
		change_condition_logic : function() 
		{			
			if ( $(this).hasClass("condition_logic_and") ) 
			{
				$(this).removeClass('condition_logic_and');
				$(this).addClass('condition_logic_or');	
				$(this).find('span').html(USAM_Page_shop_settings.text_or);
				$(this).find('input').val('OR');
			}
			else
			{
				$(this).removeClass('condition_logic_or');
				$(this).addClass('condition_logic_and');	
				$(this).find('span').html(USAM_Page_shop_settings.text_and);	
				$(this).find('input').val('AND');					
			}
		},
		
		event_select_check_type : function() 
		{
			var type = $(this).val();			
			var parent = $(this).closest('tr');
			
			parent.find(".check_blok").addClass('hidden').find('.condition_value').attr('disabled', true);
			parent.find("#check_"+type).removeClass('hidden').addClass('show').find('.condition_value').attr('disabled', false);			
		},		
	};
	
	/**
	 * Вкладка "Правила покупки"
	 */
	USAM_Page_shop_settings.purchase_rules = 
	{		
		event_init : function() 
		{				
			USAM_Page_shop_settings.purchase_rules.show_all_taxonomy_box();
			USAM_Page_shop_settings.wrapper.
				on('click', '.inside', USAM_Page_shop_settings.purchase_rules.show_all_taxonomy_box);
		},		
		
		//Показать таксомании
		show_all_taxonomy_box : function() 
		{				
			var id = $("#type_search input:radio:checked").attr('id');	
			if ( id == 'type_search_group' )
			{	
				$("#all_taxonomy").removeClass('hidden').addClass('show');								
			}
			else
			{					
				$("#all_taxonomy").addClass('hidden').removeClass('show');			
			}		
		},
	};
	
	/**
	 * Вкладка Главные
	 */
	USAM_Page_shop_settings.General = 
	{		
		event_init : function() 
		{			
			USAM_Page_shop_settings.wrapper.
				on('click','.usam-select-all', USAM_Page_shop_settings.General.event_select_all).
				on('click','.usam-select-none', USAM_Page_shop_settings.General.event_select_none);
		},

		event_select_all : function() {
			$('#usam-target-markets input:checkbox').each(function(){ this.checked = true; });
			return false;
		},

		event_select_none : function() {
			$('#usam-target-markets input:checkbox').each(function(){ this.checked = false; });
			return false;
		},
	};
	
	/**
	 * Настройка меню
	 */
	USAM_Page_shop_settings.admin_menu = 
	{
		event_init : function() 
		{
			$('body').on('change', '#role', USAM_Page_shop_settings.admin_menu.event_role_changed);
		},	

		event_role_changed : function () 
		{ 
			let url = new URL( document.location.href );	
			let section = url.searchParams.get('section') || 'menu';			
			usam_active_loader();
			var data   = {
				action        : 'get_capabilities',
				'role'        : $(this).val(),						
				'section'     : section,					
				'page'        : USAM_Tabs.page,	
				'tab'         : USAM_Tabs.tab,	
				nonce         : USAM_Page_shop_settings.get_capabilities_nonce
			},									
			callback = function(r)
			{		
				$("#usam_page_tab").html( r );			
			};						
			usam_send(data, callback);	
		},
	};	
})(jQuery);	
USAM_Page_shop_settings.init();

function iframe_blanks_loaded() 
{ 
	USAM_Page_shop_settings.blanks.iframe_blanks_loaded();
}