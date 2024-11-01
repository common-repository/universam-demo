(function($)
{
	$.extend(USAM_Page_orders, 
	{	
		init : function() 
		{					
			$(function()
			{					
				USAM_Page_orders.wrapper = $('.tab_'+USAM_Tabs.tab);				
				if ( USAM_Page_orders[USAM_Tabs.tab] !== undefined )				
					USAM_Page_orders[USAM_Tabs.tab].event_init();
							
				USAM_Page_orders.wrapper
					.on('click', '#button-compare_invoices', USAM_Page_orders.compare_invoices)
					.on('change', '.js-order-status', USAM_Page_orders.event_order_status_change)	
					.on('click', '.js-canceled-order', USAM_Page_orders.event_order_status_change)
					.on('click', '.js-show-status-selection', USAM_Page_orders.event_show_status_selection);
			});
		},	
		
		event_show_status_selection : function(e) 
		{								
			var el = e.currentTarget.closest(".js-order-id");
			el.querySelector(".description_reason_cancellation").classList.add('hide');
			el.querySelector(".js-order-status").classList.remove('hide');
		},		
		
		event_order_status_change : function(e) 
		{								
			var el = e.currentTarget.closest(".js-order-id");
			var order_id = el.getAttribute('order_id');			
			var el_status = el.querySelector(".js-order-status")
			var data = {status:el_status.value}			
			if ( el_status.value == 'canceled' )
			{				
				var reason = el.querySelector('.js-reason-cancellation').value;
				if ( reason == '' )
				{					
					el.querySelector(".description_reason_cancellation").classList.remove('hide');
					el_status.classList.add('hide');
					return;
				}		
				data.cancellation_reason = reason;
				el.querySelector(".description_reason_cancellation").classList.add('hide');
				el_status.classList.remove('hide');
			}
			usam_api('order/'+order_id, data, 'POST');
		},	
		
		compare_invoices : function(e) 
		{	
			e.preventDefault();
			$.usam_get_modal("compare_invoices");
		}
	});	
})(jQuery);	
USAM_Page_orders.init();


document.addEventListener("DOMContentLoaded", () => {	
	if( document.querySelector('.orders_grid') )
	{
		new Vue({		
			el: '.orders_grid',
			mixins: [grid_view, data_filters],			
			data() {
				return {		
					object_type:'order'		
				};
			},	
			beforeMount() {	
				usam_api('statuses', {type:'order'}, 'POST', (r) => { 					
					this.columns = r;
					this.request_counter++;						
					this.dataProcessing();	
					this.scrollGrid();
				});	
				this.requestData();
			},		
			methods: {
				requestData( data )
				{
					if ( data == undefined )
						data = {};	
					else
						usam_active_loader();					
					this.items = [];					
					data.add_fields = 'last_comment';
					data.fields = ['properties','customer', 'manager'];
					data.count = 1000;
					data.status_type = 'unclosed';
					usam_api('orders', data, 'POST', (r) => { 
						for (let k in r.items)
						{
							r.items[k].checked = false;
							r.items[k].emails = this.getConnections( 'email', r.items[k].properties );
							r.items[k].phones = this.getConnections( 'mobile_phone', r.items[k].properties );
						}
						this.items = r.items;						
						this.request_counter++;
						this.dataProcessing();							
					});									
				},				
				saveStatus(id, status) {				
					usam_api('order/'+id, {status:status}, 'POST');	
				}
			}
		})	
	}	
	else if( document.querySelector('.leads_grid') )
	{
		new Vue({		
			el: '.leads_grid',
			mixins: [grid_view, data_filters],			
			data() {
				return {		
					object_type:'lead'		
				};
			},	
			beforeMount() {			
				usam_api('statuses', {type:'lead'}, 'POST', (r) => { 					
					this.columns = r;
					this.request_counter++;						
					this.dataProcessing();	
					this.scrollGrid();
				});	
				this.requestData();
			},		
			methods: {
				requestData( data )
				{ 	
					if ( data == undefined )
						data = {};	
					else
						usam_active_loader();					
					this.items = [];
					data.add_fields = 'last_comment';
					data.fields = ['properties','customer', 'manager'];
					data.count = 1000;
					data.order = 'DESC';
					data.orderby = 'date';
					data.status_type = 'unclosed';
					usam_api('leads', data, 'POST', (r) => {
						for (let k in r.items)
						{
							r.items[k].checked = false;
							r.items[k].emails = this.getConnections( 'email', r.items[k].properties );
							r.items[k].phones = this.getConnections( 'mobile_phone', r.items[k].properties );
						}
						this.items = r.items;						
						this.request_counter++;
						this.dataProcessing();
					});									
				},	
				saveStatus(id, status) {				
					usam_api('lead/'+id, {status:status}, 'POST');	
				}				
			}
		})	
	}	
	else if( document.getElementById('compare_invoices_importer') )
	{ 
		new Vue({
			el: '#compare_invoices_importer',
			mixins: [importer],		
			data() {
				return {					
					found:[],
					not_found:[],
					customer_id:0				
				};
			},
			methods: {
				startImport()
				{
					var handler = (r) => 
					{
						this.found = r.found;
						this.not_found = r.not_found;
					};
					usam_active_loader();
					usam_send({action: 'compare_invoices', nonce: USAM_Importer.compare_invoices_nonce, file: this.file.name, type: USAM_Importer.rule_type, columns: this.value_name, customer_id: this.customer_id, template_id: this.template_id}, handler);				
				},	
				change_customer(e)
				{
					this.customer_id = e.id;
				},
			}
		})
	}
})