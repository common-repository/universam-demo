document.addEventListener("DOMContentLoaded", () => {
	new Vue({
		el: '.calendar_view',		
		data() {
			return {
				cell: 0,	
				day_cell_width: 0,
				week_cell_width: 0,
				
				loading : true,	
				updatedEvents : false,					
				displayCells : [],
				cells : [],
				events : [],
				day_cells : [],		
				week_cells : [],
				tab : 'month',		
				current_date : '',				
				
				month_events : [],	
				day_events : [],	
				week_events : [],				
				
				event_holder_class : 'usam_event_holder',
				event_class : 'usam_event',
				event_title_class : 'usam_event_title',	
			
				cel_width  : 0,
				cel_height : 0,	
				calendars  : []
			};
		},	
		computed: {		
			selectedCalendars() {	
				var calendars = [];
				for (k in this.calendars) 		
					if ( this.calendars[k].checked )
						calendars.push(this.calendars[k].id);
				return calendars;
			},
		},
		watch: {					
			tab(v, old) 
			{
				usam_send({action: 'save_tab_calendar', tab: v, nonce: USAM_Calendar.save_tab_calendar_nonce});	
				this.calculate_cells();
				this.updatedEvents = true;				
			},
		},
		mounted() {					
			usam_api('calendars', {type:'all'}, 'POST', (r) => {			
				this.calendars = r.items;
				for (k in this.calendars)
					this.$watch(['calendars', k].join('.'), this.selectCalendar, {deep:true});
			})			
			this.calulate_task_tab_day();
			this.calulate_task_tab_month();				
			if (USAM_Calendar.tab=='month' || USAM_Calendar.tab=='week' || USAM_Calendar.tab=='day')
				this.tab = USAM_Calendar.tab;									
			this.get_events();	
			new_event.$on('add_event', (e) => {				
				this.events.push(e);
			})		
		},
		methods: {
			selectCalendar() 
			{	
				usam_api('calendars/user', {calendars:this.selectedCalendars}, 'POST');
			},				
			startDay() 
			{
				const d = new Date();
				d.setDate(1);
				d.setDate(d.getDate() - 3);				
				return local_date(d, "Y-m-d", false)+" 00:00:00";
			},
			endDay() 
			{
				const d = new Date(this.startDay());
				d.setDate(d.getDate() + 41);				
				return local_date(d, "Y-m-d", false)+" 00:00:00";
			},
			get_events() 
			{  
				this.loading = true;
				usam_api('events', {date_query: [{column: 'end', after:this.startDay(), inclusive: true}, {column: 'start', before:this.endDay(), inclusive: true}], orderby: 'length_event', order:'DESC'}, 'POST', (r) => {					
					this.events	= r.items;	
					this.calculate_cells();
					this.loading = false;
					this.updatedEvents = true;
				});	
			},	
			calculate_cells() 
			{
				var current_date = new Date();	
				this.cells = [];
				this.displayCells = [];
				switch ( this.tab ) 
				{
					case 'month':
						var d = new Date(this.startDay());
						for(var i = 0; i <= 5; i++) 
						{
							let cells = [];
							for(var j = 0; j <= 6; j++)
							{
								o = {date:new Date(local_date(d, "Y-m-d", false)+" 00:00:00"), title:d.getDate(), current: d.getDate() == current_date.getDate() && d.getMonth() == current_date.getMonth() && d.getFullYear() == current_date.getFullYear(), count:0};
								cells.push(o);
								this.cells.push(o);
								d.setDate(d.getDate() + 1);						
							}
							Vue.set(this.displayCells, i, cells);							
						}
					break;
					case 'week':							
						var d = new Date();
						d.setDate(current_date.getDate()-(current_date.getDay()||6));
						for(var i = 0; i <= 6; i++) 
						{									
							o = {date:new Date(local_date(d, "Y-m-d", false)+" 00:00:00"), title:d.getDate(), current: d.getDate() == current_date.getDate() && d.getMonth() == current_date.getMonth() && d.getFullYear() == current_date.getFullYear(), count:0};
							Vue.set(this.displayCells, i, o);
							this.cells.push(o);
							d.setDate(d.getDate() + 1);	
						}							
					break;
					case 'day':							
						var d = new Date();
						o = {date:new Date(local_date(d, "Y-m-d", false)+" 00:00:00"), title:d.getDate(), current: d.getDate() == current_date.getDate() && d.getMonth() == current_date.getMonth() && d.getFullYear() == current_date.getFullYear(), count:0};
						Vue.set(this.displayCells, 0, o);
						this.cells.push(o);				
					break;						
				}				
			},				
			
			display_events() 
			{ 			
				switch ( this.tab ) 
				{
					case 'day':				
						this.cel_width = jQuery(".countent_tabs #tab-day .row_1").width();	
						this.cel_height = jQuery(".countent_tabs .usam_day ").height();	
					break; 
					case 'week':							
						this.cel_width = jQuery(".countent_tabs .usam_days-tbl-grid").width();	
						this.cel_height = jQuery(".countent_tabs .usam_days-tbl-grid").height();		
					break;
					case 'month':	
						this.cel_width = jQuery("#tab-month .usam_day").width();	
						this.cel_height = jQuery("#tab-month .usam_day").height();						
					break;				
				}			
				for (k in this.events)
				{						
					Vue.set(this.events[k], 'active', 0);
					Vue.set(this.events[k], 'show', 0);
					switch ( this.tab ) 
					{
						case 'day':		
							this.day_time_processing(k, 0);
						break;
						case 'week':		
							for (i in this.cells) 
								this.day_time_processing(k, i);								
						break;
						case 'month':		
							this.display_events_month(k); 
						break;
					}		
				}	
			},
					
			display_events_month( k )
			{			
				n = this.month_events.length;				
				var event_date_from = new Date( this.events[k].start );		
				var event_date_to = new Date( this.events[k].end );			
				var 
				event_left = 0,
				event_top = 0;			
					
				var event_start	= false,
					number_from,
					number_to,	
					start_x  = 0,
					start_y  = 0,				
					event_longer_calendar = false;
				// Найдем начальные точки и узнаем можно ли это событие вывести	
				for (i in this.cells)
				{	
					var d = this.cells[i].date;		
					if ( event_start == false  )
					{
						if ( d.getDate() == event_date_from.getDate() && d.getMonth() == event_date_from.getMonth() && d.getFullYear() == event_date_from.getFullYear() )	
						{
							event_start	= true;
							number_from = i;
						}
						else if ( i == 0 && d.getDate() >= event_date_from.getDate() && d.getMonth() >= event_date_from.getMonth() && d.getFullYear() >= event_date_from.getFullYear() )	
						{
							event_start	= true;
							number_from = i;
						}
					}					
					if ( event_start )
					{
						if ( this.cells[i].count > 3 ) 
						{
							this.cells[i].count++;	
							event_start	= false;	
							if ( this.cells[i].count == 5 )
							{	
								start_x = i%7;							
								start_y = i/7;
								start_y = Math.floor(start_y);									
								//this.place_block({id:0, title:'<div class="usam_task_day_more" data-count_tasks="">'+USAM_Calendar.message_many_event+'</div>'}, start_x, start_y, 4, start_x );						
							}
							break;
						} 
						if ( d.getDate() == event_date_to.getDate() && d.getMonth() == event_date_to.getMonth() && d.getFullYear() == event_date_to.getFullYear())	
						{
							number_to = i;
							break;
						}
						if ( i == 35 )
						{  //Задание длиньше, чем календарь
							number_to = i;
							event_longer_calendar = true;
						}
					}
				}
				if ( !event_start )
					return false;
			
				var event_start	= false,
				x = 0,
				y = 0; 
				for (i in this.cells)
				{					
					if ( number_from == i )
					{
						event_start	= true;
						start_x = x;
					}
					if ( event_start )
					{
						this.cells[i].count++;				
						if ( number_to == i )
						{ 
							this.place_block( k, start_x, y, this.cells[i].count, x );								
							break;
						}						
					}
					if ( x == 6 )
					{					
					//	if ( event_start )
						//	this.place_block({id:0, title:'<div class="usam_task_day_more" data-count_tasks="">'+USAM_Calendar.message_many_event+'</div>'}, start_x, y, this.cells[i].count, x );
						x = 0;
						start_x = x;
						y++;
					}
					else
						x++;
				}				
			},			
			
			place_block( k, start_x, start_y, y_offset, current_x ) 
			{		
				var
				height_day = jQuery("#tab-month .usam_days-grid-table tbody").height(),
				width_table = jQuery("#tab-month .usam_days-grid-table tbody").width(),
				width_k = (width_table-(this.cel_width*7))/7,
				height_k = (height_day-(this.cel_height*6))/6,
				height_header = jQuery("#tab-month .usam_days-grid-table .usam_day-title").height();						
				var left = start_x*(this.cel_width+width_k)-1;					
				if ( left > 0 )
					left = left + start_x;				
				Vue.set(this.events[k], 'left', left);
				Vue.set(this.events[k], 'top', start_y*(this.cel_height+height_k)+height_header*y_offset+y_offset);
				Vue.set(this.events[k], 'width', (current_x-start_x+1)*(this.cel_width+width_k)-15);
				Vue.set(this.events[k], 'show', 1);
			},				
					
			day_time_processing( k, i ) 
			{
				n = this.week_events.length;
				var d = this.cells[i].date,
					event_date_from = new Date( this.events[k].start ),		
					event_date_to = new Date( this.events[k].end ),	
					min_shift1	= event_date_from.getMinutes() < 30 ? 3 :24,	
					min_shift2	= event_date_to.getMinutes() == 0 ? 0 : 20,	
					hours = 0,
					width_header = 0,
					height_header = jQuery(".usam_days-grid-table thead").height();	
					if ( jQuery(".usam_time").length > 0 )
						width_header = jQuery(".usam_time").width();
				if ( d.getDate() == event_date_from.getDate() && d.getMonth() == event_date_from.getMonth() && d.getFullYear() == event_date_from.getFullYear() )	
				{ 	
					/* day_events : [],	
				week_events : [], */
					if ( event_date_from.getDate() == event_date_to.getDate() )
						hours = event_date_to.getHours() - event_date_from.getHours();
					else
						hours = 24 - event_date_from.getHours();	

console.log("hours"+hours);
console.log("min_shift1"+min_shift1);
console.log("cel_height"+this.cel_height);
console.log('height='+this.cel_height * hours-min_shift1-3);

					Vue.set(this.events[k], 'left', i*(this.cel_width+3)+3);
					Vue.set(this.events[k], 'top', this.cel_height*event_date_from.getHours()+height_header+min_shift1);
					Vue.set(this.events[k], 'width', Math.floor((this.cel_width-20)/this.cells[i].count));
					Vue.set(this.events[k], 'height', this.cel_height * hours-min_shift1-3);	
					Vue.set(this.events[k], 'show', 1);
				}
			},
			
			week_event_mouseover : function( k ) 
			{
				for (i in this.week_events)		
				{
					this.week_events[i].active = i==k?1:0;
					Vue.set(this.week_events, i, this.week_events[i]);
				}
			},	
			
			week_event_mouseleave : function( ) 
			{
				for (i in this.week_events)		
				{
					this.week_events[i].active = 0;
					Vue.set(this.week_events, i, this.week_events[i]);
				}
			},		

			day_event_mouseover : function( k ) 
			{
				this.day_events[k]['active'] = 1;
			},		

			month_event_mouseover : function( k ) 
			{
				for (i in this.month_events)		
				{
					this.month_events[i].active = i==k?1:0;
					Vue.set(this.month_events, i, this.month_events[i]);
				}		
			},	
			
			month_event_mouseleave : function( ) 
			{
				for (i in this.month_events)		
				{
					this.month_events[i].active = 0;
					Vue.set(this.month_events, i, this.month_events[i]);
				}
			},	
					
			calulate_task_tab_day : function() 
			{
				var width_elem,	_width;				
				jQuery('#tab-day .usam_table_day_calendar .usam_tasks_cell').each(function(i,elem) 
				{				
					width_elem = USAM_Calendar.day_cell_width/jQuery(this).find('.usam_task').length-8;				
					jQuery(this).find('.usam_task').each(function(j,elem_task) 
					{	
						jQuery(this).css({width: width_elem}); 				
					});
				});					
			},
			
			calulate_task_tab_month : function() 
			{
				var top;			
				jQuery('.usam_day_cell').each(function(i,elem) 
				{				
					jQuery(this).find('.usam_task').each(function(j,elem_task) 
					{					
						top = j*20+20;			
						jQuery(this).css({top: top}); 				
					});
				});					
			},
			
			calulate_task_tab_week() 
			{
				var width_elem,	_width;			
				jQuery('#tab-week .usam_table_day_calendar .usam_tasks_cell').each(function(i,elem) 
				{				
					width_elem = USAM_Calendar.week_cell_width/jQuery(this).find('.usam_task').length-8;				
					jQuery(this).find('.usam_task').each(function(j,elem_task) 
					{	
						jQuery(this).css({width: width_elem}); 				
					});
				});					
			},

			// Событие при нажатие на ячейку времени в календаре
			open_window_add_event_day( hour, m ) 
			{	
				hour = parseInt(hour) - 1;
				m = parseInt(m);	
				if ( m == '00')
					end_time = hour+':30:00';
				else
					end_time = (hour+1)+':00:00';
				d = new Date();
				var start = local_date(d, "Y-m-d", false)+" "+hour+':'+m+":00";
				var end = local_date(d, "Y-m-d", false)+" "+end_time;
				this.open_window_add_event( start, end );
			},
			
			open_window_add_event_week( day, hour, m ) 
			{
				hour = parseInt(hour) - 1;
				m = parseInt(m);	
				if ( m == '00')
					end_time = hour+':30:00';
				else
					end_time = (hour+1)+':00:00';
				d = new Date( this.day_cells[day.k].date );
				start = local_date(d, "Y-m-d", false)+" "+hour+':'+m+":00";
				end = local_date(d, "Y-m-d", false)+" "+end_time;
				this.open_window_add_event( start, end );
			},		
			
			open_window_add_event_month( cell ) 
			{
				start = local_date(cell.date, "Y-m-d", false)+" 00:00:00";
				end = local_date(cell.date, "Y-m-d", false)+" 23:59:59";		
				this.open_window_add_event( start, end );	
			},
					
			open_window_add_event(start, end) 
			{			
				Vue.set(new_event.event, 'type', 'task');
				Vue.set(new_event.event, 'start', start);
				Vue.set(new_event.event, 'end', end);
				new_event.show_modal();				
			},		
					
			openEvent( event ) 
			{		
				Vue.set(new_event, 'event', event);		
				new_event.show_modal();
			},			
		},
		updated()
		{
			if ( this.updatedEvents )
			{
				this.display_events();
				this.updatedEvents = false;
			}
		}
	})
})