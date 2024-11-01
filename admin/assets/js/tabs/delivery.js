document.addEventListener("DOMContentLoaded", () => {
if( document.querySelector('.delivery_documents_grid') )
{ 
	new Vue({		
		el: '.delivery_documents_grid',
		mixins: [grid_view, data_filters],
		data() {
			return {						
				statuses:[],		
			};
		},		
		beforeMount: function () {					
			usam_api('statuses', {type:'shipped'}, 'POST', (r) => this.statuses = r);					
			usam_api('couriers', 'GET', (r) => { 					
				r.items.unshift({id:0, user_id:0, appeal: USAM_Tabs.not_assigned_message, online: ''});
				this.columns = r.items;				
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
				data.fields = ['property_types','status_data'];
				data.add_fields = ['order_number'];
				data.count = 1000;
				data.status_type = 'unclosed';
				data.storage_pickup = 0;
				usam_api('shippeds', data, 'GET', (r) => { 
					for (let k in r.items)
						r.items[k].checked = false;
					this.items = r.items;
					this.request_counter++;
					this.dataProcessing();							
				});									
			},
			dataProcessing()
			{ 
				if ( this.request_counter > 1 )
				{ 			
					let item, sum, number;
					for (let k in this.columns)
					{	
						item = [];
						sum = 0;
						number = 0;
						for (let i in this.items)
						{		
							if ( this.items[i].courier==this.columns[k].user_id )
							{									
								this.sum += this.items[i].totalprice;
								sum += this.items[i].totalprice;
								number++;
							}
						}
						Vue.set(this.columns[k], 'sum', sum);
						Vue.set(this.columns[k], 'formatted_sum', to_currency( sum, '' ));
						Vue.set(this.columns[k], 'current_number', number);			
					}								
				}											
			},				
			dropStatusDelivery(e, k) {			
				e.preventDefault();
				document.querySelectorAll('.drop_area').forEach((el) => {el.classList.remove('hover_drop');});
				this.draggable = false;
				if ( this.selectedItems.length )
				{
					for (let i in this.items)
						if ( this.items[i].checked )
						{
							this.changeStatus(k, i);
							this.items[i].checked = false;
						}
				}
				else
					this.changeStatus(k, e.dataTransfer.getData("item"));
			},
			changeStatus(k, i) {
				let old_k = '';	
				for (let j in this.columns)
					if (this.columns[j].user_id == this.items[i].courier)
					{
						old_k = j;
						break;
					}	
				if ( old_k === '' )
					return false;
				if ( this.statuses[k].internalname != this.items[i].status )
				{	
					usam_api('shipped/'+this.items[i].id, {status:this.statuses[k].internalname}, 'POST');	
					this.columns[old_k].number--;
					this.columns[old_k].current_number--;						
					this.columns[old_k].sum -= this.items[i].totalprice;
					this.columns[old_k].formatted_sum = to_currency(this.columns[old_k].sum, '');					
					this.sum -= this.items[i].totalprice;
					this.items.splice(i, 1);					
				}
			},	
			changeСolumn(k, i)
			{
				let old_k = '';	
				for (let j in this.columns)
					if (this.columns[j].user_id == this.items[i].courier)
					{
						old_k = j;
						break;
					}	
				if ( old_k === '' )
					return false;
				if ( this.columns[k].user_id != this.items[i].courier )
				{	
					var data = {courier:this.columns[k].user_id};
					let currentDate = new Date();
					let dateDelivery = new Date( this.items[i].date_delivery );	
					if ( currentDate > dateDelivery || !this.items[i].date_delivery )
					{
						currentDate.setHours( currentDate.getHours()+2 );
						this.items[i].date_delivery = local_date(currentDate, "Y-m-d H:i:s");
						data.date_delivery = this.items[i].date_delivery;
					}
					if ( (this.items[i].status == 'pending' || this.items[i].status == 'packaging') && this.columns[k].user_id )
					{
						data.status = 'expect_tc';
						this.items[i].status = 'expect_tc';					
						for (let k in this.statuses)
						{
							if ( this.statuses[k].internalname == 'expect_tc' )
							{
								this.items[i].status_name = this.statuses[k].name;
								this.items[i].status_color = this.statuses[k].color;
							}
						}
					}
					usam_api('shipped/'+this.items[i].id, data, 'POST');	
					this.columns[old_k].number--;
					this.columns[old_k].current_number--;						
					this.columns[old_k].sum -= this.items[i].totalprice;
					this.columns[old_k].formatted_sum = to_currency(this.columns[old_k].sum, '');					
					this.columns[k].sum += this.items[i].totalprice;
					this.columns[k].formatted_sum = to_currency(this.columns[k].sum, '');		
					this.columns[k].number++;
					this.columns[k].current_number++;							
					this.items[i].courier = this.columns[k].user_id;
				}
			}
		}
	})	
}
})	