var get_input = function( name, value ) 
{		
	return '<input type="hidden" name="'+Basket_Conditions.attr_name+"["+Basket_Conditions.index+"]"+'['+name+']" value="'+value+'"/>';
};

// Получить блок условий
var get_condition_blok = function( type, html ) 
{							
	i = Basket_Conditions.index;
	return '<div id ="row-'+i+'" class = "condition-block condition-'+type+'"><div class = "condition-wrapper">'+html+'</div><a class="button_delete" href="#"></a></div>';	
};

// Получить оператор сравнения условий
var get_logic_blok = function(  logic_operator ) 
{						
	name = logic_operator == 'OR'?Basket_Conditions.text_or:Basket_Conditions.text_and;
	_class = logic_operator == 'OR'?'condition_logic_or':'condition_logic_and';
	html = '<div id ="row-'+Basket_Conditions.index+'" class = "condition-block condition-logic '+_class+'"><span>'+name+'</span>'+get_input( 'logic_operator', logic_operator )+'</div>';
	Basket_Conditions.index++;
	return html;
};	

var get_html_expression = function( property, logic, value, property_title, logics_title, value_title ) 
{							
	var input = get_input( 'type', 'simple' )+get_input( 'property', property )+get_input( 'logic', logic )+get_input( 'value', value );
	html = input+'<div class = "expression-wrapper"><div class = "expression property_expression">'+property_title+'</div><div class = "expression logics_expression">'+logics_title+'</div><div class = "expression value_expression">'+value_title+'</div></div>';	
	Basket_Conditions.index++;
	return html;
};

// Вставка правил
var insert_rules = function( html ) 
{
	if ( html != '' )
	{
		Basket_Conditions.div_conditions.append( html );		
		usam_notifi({ 'text': Basket_Conditions.text_add_item });	
	}
};

(function($)
{		
	var logics_display = function() 
	{	
		var box = $(this).parents('tr').attr('id');		
		if ( $("#"+box+' #logics .variability_logic .selected').length >0 )
		{		
			var property = $(this).data('property');
			var properties = [];
			
			$("#"+box+' #logics .variability_logic .selected').hide();
			$("#"+box+' #logics .variability_logic .selected').each(function()
			{
				var t = $(this);
				properties = t.data('properties').split(',');				
				for (index = 0; index < properties.length; ++index) 
				{						
					if ( properties[index] == property )
					{
						t.show();
						break;
					}
				}
			});
		}
	};
	
	var save_condition_cart = function() 
	{	
		var modal_id = $('#'+Basket_Conditions.modal_id);
		var type_properties = modal_id.find('#type_properties').val();
		var group = modal_id.find('.condition_group #'+type_properties);
		var property = group.find('#properties #select_property option:selected').val();
		var logic = group.find('#logics #select_logic option:selected').val();
		logic = logic == 'undefined'?'':logic;
		var value = group.find('#property_value').val();	
		if ( logic || type_properties == 'products' || type_properties == 'group' || type_properties == 'group_product' || type_properties == 'user_data' )
		{						
			var property_title = '';		
			var logics_title = '';
			var id = '';
			var html = '';	
			var html_logic = '';				
			Basket_Conditions.attr_name = "c";
			if ( Basket_Conditions.id_row != '' )
			{
				attr_name = '';
				Basket_Conditions.wind_contaner.parents('.condition-block').each(function(index, elem) 
				{							
					attr_name = "["+elem.id.replace(/[^0-9]/gim,'')+"]" + attr_name;
				});		
				Basket_Conditions.attr_name += attr_name+"["+Basket_Conditions.id_row+"]";					
			}
			Basket_Conditions.index = Basket_Conditions.div_conditions.children('.condition-block').length;	
			if ( Basket_Conditions.index > 0 )
				html_logic = get_logic_blok( 'AND' );		
			else
				Basket_Conditions.index = 0;					
			var tab = modal_id.find('#'+type_properties);

			if ( tab.find("#logics #select_logic option:selected").length >0 )
				logics_title = tab.find("#logics #select_logic option:selected").text();			

			switch ( type_properties ) 
			{
				case 'product_attributes':	
				case 'product':							
					if ( property && value )
					{							
						property_title = tab.find('#select_property option:selected').text();						
						var html_expression = get_html_expression( property, logic, value, property_title, logics_title, value );						
						html = html + get_condition_blok( 'simple', html_expression );
					}			
				break;				
				case 'group':						
					var add_conditions = '<div class = "title_group">'+Basket_Conditions.text_group+'<a href="#condition_cart_window" id = "usam_modal" class = "add_condition_cart_item" data-toggle="modal" data-type="condition_cart_window">'+Basket_Conditions.text_add_conditions+'</a></div><div class = "conditions"></div>';						
					html = html + get_condition_blok( 'group', add_conditions+get_input( 'type', 'group' ) );
				break;	
				case 'group_product':							
					var add_conditions = '<div class = "title_group">'+Basket_Conditions.text_group+'<a href="#condition_cart_item_window" id = "usam_modal" class = "add_condition_cart_item" data-toggle="modal" data-type="condition_cart_item_window">'+Basket_Conditions.text_add_conditions+'</a></div><div class = "conditions"></div>';						
					html = html + get_condition_blok( 'group_product', add_conditions+get_input( 'type', 'group_product' ) );
				break;									
				// Для всей корзины
				case 'products': // Добавить контейнер с условиями для элементов корзины								
					var add_conditions = '<div class = "title_group">'+Basket_Conditions.text_product+'<a href="#condition_cart_item_window" id = "usam_modal" class = "add_condition_cart_item" data-toggle="modal" data-type="condition_cart_item_window">'+Basket_Conditions.text_add_conditions+'</a></div><div class = "conditions"></div>';			
						
					html = html + get_condition_blok( 'basket_products', add_conditions+get_input( 'type', 'products' ) );		
				break;				
				case 'cart':					
				case 'order_property':									
					if ( property && value )
					{										
						property_title = tab.find('#select_property option:selected').text();	
						
						var html_expression = get_html_expression( property, logic, value, property_title, logics_title, value );						
						html = html + get_condition_blok( 'simple', html_expression );			
					}
				break;		
				case 'user_data':					
					property_title = tab.find('#select_property option:selected').text();	
						
					var html_expression = get_html_expression( property, logic, '', property_title, logics_title, '' );						
					html = html + get_condition_blok( 'simple', html_expression );	
				break;			
				case 'user':
				case 'location':
					var autocomplete = group.find('.js-autocomplete-'+type_properties);
					value = autocomplete.val();					
					property_title = autocomplete.siblings().val();						
			
					var html_expression = get_html_expression( type_properties, logic, value, property_title, logics_title, '' );						
					html = html + get_condition_blok( 'simple', html_expression );						
				break;					
				case 'roles':
				case 'weekday':	
				case 'selected_shipping':
				case 'selected_gateway':
				case 'type_price':
				case 'type_payer':				
					property_title = modal_id.find('#type_properties option:selected').text();						
					var name_value_expression = '';	
					var html_logic_blok = '';	
					tab.find('#all_taxonomy input:checked').each(function()
					{	
						$(this).attr( "checked", false );						
						name_value_expression = $(this).parents('.selectit').find('span').html();	
						
						var html_expression = get_html_expression( type_properties, logic, $(this).val(), property_title, logics_title, name_value_expression );						
						html = html + html_logic_blok + get_condition_blok( 'simple', html_expression );	
						html_logic_blok = get_logic_blok( 'AND' );							
					});		
				break;		
				case 'terms':				
				case 'cart_terms':										
					var name_value_expression = '';	
					var html_logic_blok = '';	
					property_title = tab.find('#select_property option:selected').text();			
					tab.find('#all_taxonomy input:checked').each(function()
					{	
						$(this).attr( "checked", false );		
						name_value_expression = $(this).parents('.selectit').text();	
						
						var html_expression = get_html_expression( property, logic, $(this).val(), property_title, logics_title, name_value_expression );						
						html = html + html_logic_blok + get_condition_blok( 'simple', html_expression );	
						html_logic_blok = get_logic_blok( 'AND' );	
					});		
				break;					
			}		
			if ( html != '' )				
				insert_rules( html_logic + html );
		}
	};
	
	var default_option = function() 
	{			
		var type = $(this).data('type');
		var id_windows = $('#'+type+' #type_properties').val();

		$('#properties input').attr('checked',false);
		$('#logics input').attr('checked',false);
		$('#property_value').val('');		
		
		$('#logics .variability_logic .selected').hide();	
		$('.condition_group table tr').hide();
		$('.condition_group table tr#'+id_windows).show();	
		
		$('#condition_cart_item_window table tr#'+id_windows).show();		
		
		Basket_Conditions.modal_id = $(this).data('type');
			
		if ( $(this).closest('.condition-block').length )
		{
			Basket_Conditions.wind_contaner = $(this).closest('.condition-block');			
			Basket_Conditions.id_row = $(this).closest('.condition-block').attr('id').replace(/[^0-9]/gim,'');
			Basket_Conditions.div_conditions = Basket_Conditions.wind_contaner.children('.condition-wrapper').children('.conditions');
		}
		else
		{
			Basket_Conditions.wind_contaner = $('.container_condition');
			Basket_Conditions.div_conditions = Basket_Conditions.wind_contaner;
			Basket_Conditions.id_row = '';
		}				
	};
	
	var show_help_box = function() 
	{					
		var box = $(this).parents('tr');
		var id = $(this).attr('id');
		box.find("#help_container .help").removeClass('show');
		box.find("#help_container .help").addClass('hidden');
		box.find("#help_container .help-"+id).removeClass('hidden');
		box.find("#help_container .help-"+id).addClass('show');			
	};
	
	var type_properties = function() 
	{				
		var id = $(this).val();			
		$('.condition_group table tr').hide();
		$('.condition_group table tr#'+id).show();
	};
	
	var change_condition_logic = function() 
	{				
		if ( $(this).hasClass("condition_logic_and") ) 
		{
			$(this).removeClass('condition_logic_and');
			$(this).addClass('condition_logic_or');	
			$(this).find('span').html(Basket_Conditions.text_or);
			$(this).find('input').val('OR');
		}
		else
		{
			$(this).removeClass('condition_logic_or');
			$(this).addClass('condition_logic_and');	
			$(this).find('span').html(Basket_Conditions.text_and);	
			$(this).find('input').val('AND');					
		}
	};
	
	// Удалить условие
	var delete_properties = function(e) 
	{					
		e.preventDefault();			
		var expression = $(this).closest('.condition-block');		
		var container = expression.parent('div');		
		var size = container.children().length;
		var i = expression.index();			
		if ( size > 1 && i != 0 )
		{					
			expression.prev().remove();				
		}
		else if ( size > 1 )
		{
			expression.next().remove();	
		}
		expression.remove();
	};	
		
	var change_terms = function() 
	{						
		var select = $(this).val();				
		var block = $(this).closest('td');		

		block.find('.taxonomy_box').hide();		
		block.find('#group-'+select).show()		
	};	
	
	var open_import_products = function(e) 
	{						
		e.preventDefault();
		jQuery.usam_get_modal('import_product_discount_rule');
		load_import_products();
	};	
	
	var load_import_products = (e) =>
	{
		new Vue({
			el: '#product_discount_importer',
			mixins: [importer],
			methods: {
				startImport()
				{
					var handler = (r) => 
					{	
						Basket_Conditions.wind_contaner = jQuery('.container_condition');
						Basket_Conditions.div_conditions = Basket_Conditions.wind_contaner;										
						Basket_Conditions.index = Basket_Conditions.div_conditions.children('.condition-block').length;
						jQuery('#import_product_discount_rule').modal('hide');	
						var html = Basket_Conditions.index?get_logic_blok( 'OR' ):'',
						html_expression = '';	
						let k = false;
						for (let i in this.value_name)
						{
							if ( this.value_name[i] == 'sku' || this.value_name[i] == 'barcode' )
							{
								k = this.value_name[i];
								for (let j in r)
								{
									if ( r[j][i] )
									{
										html_expression = get_html_expression(k, 'equal', r[j][i], Basket_Conditions.text_product_property[k], Basket_Conditions.text_expression, r[j][i]);
										html = html+get_condition_blok( 'simple', html_expression )+get_logic_blok( 'OR' );									
									}
								}
								break;
							}					
						}
						if ( k )
							insert_rules( html );
					};						
					usam_active_loader();				
					usam_api('importer/file/data', {file: this.file.name, file_settings: this.file_settings}, 'POST', handler);		
				}	
			}
		})
	}
	
	jQuery(document).ready(function()
	{				
		Basket_Conditions.attr_name = "c";
		jQuery('body')
			.on('click','.condition-logic', change_condition_logic)
			.on('click','#usam_modal', default_option)
			.on('click','#properties input', logics_display)
			.on('click','#condition_cart_item_window #save_action', save_condition_cart)
			.on('click','#condition_cart_window #save_action', save_condition_cart)				
			.on('change','#cart_terms #select_property', change_terms)
			.on('change','#terms #select_property', change_terms)
			.on('change','#type_properties', type_properties)			
			.on('click','container_containing_help .show_help', show_help_box)
			.on('click','.container_condition .button_delete', delete_properties)
			.on('click','#import_products', open_import_products);			
			
		var select = jQuery('#cart_terms #select_property').val();
		jQuery('#cart_terms .taxonomy_box').hide();		
		jQuery('#cart_terms #group-'+select).show();
		
		select = jQuery('#terms #select_property').val();
		jQuery('#terms .taxonomy_box').hide();		
		jQuery('#terms #group-'+select).show();				
		
		jQuery("#help_container .help").addClass('hidden');			
	});
})(jQuery);			
