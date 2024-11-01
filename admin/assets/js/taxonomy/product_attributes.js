(function($)
{
	$(function()
	{	
		$('.box_category')
			.on('click', 'a#delete_category', delete_category)
			.on('click', 'a#add_category', add_category_box_display);
		
		$('body')
			.on('click', '#display_category_window #modal_action', add_category)	
			.on('change', '#parent', settings_product_attribute)
			.on('add_table_layer', '.table_rate', add_table_layer);
			
						
		$('#add_new_term').hide();			

		jQuery('#parent option:contains("   ")').remove();
		jQuery('#parent').mousedown(function(){
			jQuery('#parent option:contains("   ")').remove();
		});			
	});	
	
	var add_table_layer = function( e ) 
	{		
		var select = $('#type_product_attributes').val();				
		if ( select == 'COLOR' || select == 'COLOR_SEVERAL' )
			jQuery('.js-ready-options-code').wpColorPicker( );
	};	
		
	var settings_product_attribute = function() 
	{		
		var parent = $(this).val();	
		if ( parent == '-1' )
			$('#add_new_term').hide();
		else
			$('#add_new_term').show();	
	};	
	
	var is_numeric = function() 
	{				
		if (this.value.match(/[^0-9]/g)) 
		{
			this.value = this.value.replace(/[^0-9]/g, '');
		}		
	};		
	
	// Скрыть / показать добавление новой категории
	var add_category_box_display = function(e) 
	{	
		e.preventDefault();		
		$('#display_category_window').modal();	
		
		var category_ul = jQuery("#display_category_window .categorychecklist");	
		if ( category_ul.html() == '' )
		{
			var handler = function(r) 
			{			
				category_ul.append(r);	
			};			
			usam_send({action: 'display_category_in_attributes_product', nonce: USAM_Product_Attributes.display_category_in_attributes_product_nonce, term_id: USAM_Product_Attributes.term_id}, handler);
		}
	};
	
	
	var add_category = function() 
	{		
		var a = '<a href="" id="delete_category">'+USAM_Product_Attributes.text_delete+'</a>';		
		if ( $('.box_category #no_data').length )
			$('.box_category #no_data').remove();
		
		$('#display_category_window .categorychecklist input:checked').each(function()
		{				
			var val = $(this).val();
			
			if ( !$('.box_category #in-usam-category-'+val).length )
			{
				var input = $(this).attr('type','hidden').closest('.selectit').html();	
				$(".product_attributes_category .categories table").append( '<tr><td>'+input+a+'</td></tr>');
			}
		});	
		$('#display_category_window').modal('hide');	
	};
	
	var delete_category = function(e) 
	{		
		e.preventDefault();
		$(this).closest('tr').remove();	
	};	
})(jQuery);

document.addEventListener("DOMContentLoaded", () => {
	if( document.getElementById('ready_options') )
	{
		new Vue({
			data() {
				return {					
					default_:{id:0, code:'',value:'', slug:'',sort:'', cb:false},
					main:0,
					page:1,
					count:0,
					size:100,
					rows:[],
					delete_rows:[],					
					type:'',
					id:0,
					load:false,
				};
			},	
			el: '#ready_options',
			watch: {
				page:function (val, oldVal) 
				{ 
					this.load = true;
					this.requestData();
				},
			},
			computed: {				
				cb() {			
					return this.rows.filter(x => x.cb);	
				},	
			},
			mounted() 
			{  		
				let url = new URL( document.location.href );	
				this.id = url.searchParams.get('tag_ID');		
				this.requestData();
				this.type = document.getElementById('type_product_attributes').value;
				document.getElementById('type_product_attributes').addEventListener('change', (e) =>{				
					this.type = e.target.value;					
				})				
//is_numeric	
			},
			methods: {									
				requestData() 
				{
					usam_api('attribute_values', {attribute_id:this.id,paged:this.page,count:this.size, order:'asc', orderby:'sort'}, 'POST', (r) => {	
						this.load = false;
						this.count = r.count;
						this.rows = r.items;	
						if ( !r.items.length && this.page < 2 )
						{
							for ( i = 0; i < 3; i++) 	
								this.rows.push(Object.assign({}, this.default_));
						}
					});
				},
				combine()
				{			
					var ids = [];
					for (let i = this.rows.length; i--;) 
						if( this.main == this.rows[i].id )
							this.rows[i].cb = false;	
						else if( this.rows[i].cb )
						{
							ids.push(this.rows[i].id);
							this.rows.splice(i, 1);
						}
					usam_api('attribute_values/combine', {attribute_id:this.id,ids:ids,main:this.main}, 'POST', (r) => usam_admin_notice(r, 'ready'));					
					this.main = 0;
				},				
				del(item)
				{			
					this.delete_rows.push(item);	
				},					
			}
		})
	}	
});	