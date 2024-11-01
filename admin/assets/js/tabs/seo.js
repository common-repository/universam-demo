(function($)
{
	$.extend(USAM_Page_seo, 
	{				
		init : function() 
		{					
			$(function()
			{						
				USAM_Page_seo.wrapper = $('.tab_'+USAM_Tabs.tab);						
				if ( USAM_Page_seo[USAM_Tabs.tab] !== undefined )				
					USAM_Page_seo[USAM_Tabs.tab].event_init();	
				USAM_Page_seo.wrapper
					.on('click', '#add-keywords',USAM_Page_seo.add_keywords)				
			});
		},
		
		add_keywords : function()
		{ 
			var t = $(this);	
			var keywords = document.querySelector('#keywords').value;
			if ( keywords )
			{
				document.querySelector('#keywords').value = '';
				usam_active_loader();
				usam_send({nonce: USAM_Tabs.bulkactions_nonce, action: 'bulkactions', a: 'add_keywords', item: 'keywords', keywords: keywords});	
			}
		},
		
		load_table_list : function( e, data ) 
		{
			$("#graph").html('');			
			USAM_Page_seo.graph( data.data_graph, data.name_graph );	
		},	
		
		graph : function( data, name_graph ) 
		{				
			var width_graph = jQuery('.usam_tab_table').width();
					
			var margin = {top: 40, right: 10, bottom: 40, left: 70},
				width = width_graph - margin.left - margin.right,
				height = 500 - margin.top - margin.bottom;

			var x = d3.scale.ordinal()
				.rangeRoundBands([0, width], .1, .3);		
				
			var y = d3.scale.linear()
				.range([height, 0]);

			var xAxis = d3.svg.axis()
				.scale(x)
				.orient("bottom")
				.ticks(5);
		
			var yAxis = d3.svg.axis()
				.scale(y)
				.orient("left")
				.ticks(5, "");

			var svg = d3.select("#graph")
				.attr("width", width + margin.left + margin.right)
				.attr("height", height + margin.top + margin.bottom)					
			    .append("g")
				.attr("transform", "translate(" + margin.left + "," + margin.top + ")");			

			var xScale = x.domain(data.map(function(d) { return d.y_data; }));
			var yScale = y.domain([0, d3.max(data, function(d) { return d.x_data; })]);

			svg.append("text")
				  .attr("class", "title")
				  .attr("x", -70)
				  .attr("y", -26)				  
				  .text( name_graph );

			svg.append("g")
				.attr("class", "x axis")			  
				.attr("transform", "translate(0," + height + ")")
				.call(xAxis)
				.selectAll(".tick text");
	// вывод значений y
			svg.append("g")
				.attr("class", "y axis")				  
				.call(yAxis);

				  // создаем набор вертикальных линий для сетки   
			d3.selectAll("g.x g.tick")
				.append("line") // добавляем линию
				.classed("grid-line", true) // добавляем класс	
				//	.attr("style", 'stroke: #cccccc')
				.attr("x1", 0)
				.attr("y1", 0)
				.attr("x2", 0)
				.attr("y2", - (height));
			 
			// рисуем горизонтальные линии 
			d3.selectAll("g.y g.tick")
				.append("line")   
				.classed("grid-line", true)
				.attr("x1", 0)
				.attr("y1", 0)
				.attr("x2", width)
				.attr("y2", 0);			
			// функция, создающая по массиву точек линии
			var line = d3.svg.line()
				.x(function(d){return xScale(d.y_data) + xScale.rangeBand() / 2;})
				.y(function(d){return yScale(d.x_data);});
			// добавляем путь
			svg.append("g").append("path")
				.attr("d", line(data))
				.style("stroke", "steelblue")
				.style("stroke-width", 2);
		},		
	});
	
	USAM_Page_seo.seo_tools = 
	{		
		not_send_form : true,
		
		event_init : function() 
		{					
			USAM_Page_seo.wrapper
				.on('click', '#button_replaced',USAM_Page_seo.seo_tools.text_replacement)
				.on('click', '#save-button',USAM_Page_seo.seo_tools.save_seo);				
		},		
			
		save_seo : function() 
		{							
			usam_active_loader();
			var products = {};
			var send = false;	
			$(".product_title input:text.change_made").each( function()
			{						
				product_id = $(this).data('product_id'),
				product_title = $('#product_title_'+product_id),
				products[product_id] = {
					"product_title": product_title.val()
				};				
				product_title.removeClass("change_made");		
				send = true;				
			});				
			$(".post_excerpt textarea.change_made").each( function()
			{						
				product_id = $(this).data('product_id'),		
				product_excerpt = $('#product_excerpt_'+product_id);	
				if ( typeof products[product_id] !== "undefined" )
					products[product_id].product_excerpt = product_excerpt.val();
				else
					products[product_id] = {
						"product_excerpt": product_excerpt.val()
					};				
				product_excerpt.removeClass("change_made");	
				send = true;						
			});	
			$(".post_content textarea.change_made").each( function()
			{						
				product_id = $(this).data('product_id'),			
				post_content = $('#product_content_'+product_id);	
				if ( typeof products[product_id] !== "undefined" )
					products[product_id].product_content = post_content.val();
				else
					products[product_id] = {
						"product_content": post_content.val()
					};				
				post_content.removeClass("change_made");	
				send = true;						
			});	
			if ( send )
			{
				usam_send({action: 'seo_title_product_save', 'products': products, nonce: USAM_Page_seo.seo_title_product_save_nonce});			
			}
			return false;	
		},
		
		text_replacement : function() 
		{					
			var where  = $('#where_replace').val();
			var what   = $('#what_replaced').val();	
			var how_to = $('#how_to_replace').val();
			if ( what != '' )
			{		
				switch ( where ) 
				{
					case 'title':
						var box = $(".product_title input:text");
					break;
					case 'charact':
						var box = $(".post_content textarea");
					break;
					case 'desc':
						var box = $(".post_excerpt textarea");
					break;
				}				
				box.each( function()
				{	
					var text = $(this).val();					
					var new_text = text.replace(what, how_to);
					if ( new_text != text )
						$(this).addClass( "change_made" );
					$(this).val(new_text);				
				});	
			}			
		},	
	};
	
	USAM_Page_seo.search_engines = 
	{				
		event_init : function() 
		{				
			USAM_Page_seo.wrapper
				.on('click','.dashicons-plus', USAM_Page_seo.search_engines.click_add_keyword)
				.on('click','.dashicons-no', USAM_Page_seo.search_engines.click_delete_keyword);				
		},
		
		click_add_keyword : function() 
		{ 
			var t = $(this);
			var keyword = t.parents('tr').find('.column-query_text').text();	
			t.removeClass('dashicons-plus' ).addClass( 'dashicons-no' );			
			var callback = function(r)
			{	
				t.attr("data-id",r);
			};			
			usam_send({action: 'add_keyword', 'keyword': keyword, nonce: USAM_Page_seo.add_keyword_nonce}, callback);		
		},
		
		click_delete_keyword : function() 
		{ 
			var t = $(this);
			var id = t.data('id');
			t.removeClass( 'dashicons-no' ).addClass('dashicons-plus' ).attr("data-id",0);
			var post_data   = {
				action        : 'delete_keyword',
				'id'          : id,								
				nonce         : USAM_Page_seo.delete_keyword_nonce
			};			
			usam_send(post_data);		
		},
	};		
	
	USAM_Page_seo.positions = 
	{				
		event_init : function() 
		{				
			USAM_Page_seo.wrapper
				.on('table_update','#the-list', USAM_Page_seo.load_table_list);
				
			if ( typeof usam_data_graph !== "undefined" )
			{ 
				USAM_Page_seo.graph( usam_data_graph, usam_name_graph );				
				$("#graph").show();
			}
		},
	};	
})(jQuery);
USAM_Page_seo.init();

document.addEventListener("DOMContentLoaded", () => {			
	var meta = {		
		data() {
			return {					
				tab:'main',
				metas: {pages:[],terms:[]}
			};
		},			
		mounted() {							
			usam_api('seo/metas', 'GET', (r) => this.metas = r);
			document.querySelector('#action-submit').addEventListener("click", this.save );
		},	
		methods: {				
			blur(type,i, k)
			{	
				var str = this.$refs[k][i].innerHTML;
				this.metas[type][i][k] = str.replace(/<[^>]+>/g,'');		
			},									
			save( e )
			{							
				e.preventDefault();
				var data = {};
				for (let t in this.metas)
				{
					data[t] = {};					
					for (let i in this.metas[t])
					{
						data[t][this.metas[t][i].slug] = {};
						for(k of ['title', 'description', 'opengraph_title', 'opengraph_description', 'noindex']) 
							data[t][this.metas[t][i].slug][k] = this.metas[t][i][k];	
					}					
				}
				usam_api('update/seo/metas', {metas:data}, 'POST', (r) =>
				{ 
					usam_admin_notice(r);
					document.querySelector('.js-result-save').classList.remove('hide');		
					setTimeout(() => { document.querySelector('.js-result-save').classList.add('hide');},9000);	
				});
			},
			insert(text, key){ 			
				var s = window.getSelection();
				if ( typeof s.baseNode.innerHTML !== typeof undefined )
				{					
					if( !s.baseNode.classList.contains("shortcode_editor") ) 
						return;
				}
				else if( !s.baseNode.parentNode.classList.contains("shortcode_editor") ) 
					return;
				var range = s.getRangeAt(0); //startPosition = ptr.selectionStart;
				range.deleteContents();			
				range.insertNode(document.createTextNode(' %'+key+'% '));
			},
		}
	}
	if( document.getElementById('tab_section_virtual_pages') )
	{
		new Vue({
			el: '#tab_section_virtual_pages',
			mixins: [meta]
		}) 
	}
	else if( document.getElementById('tab_section_taxonomies') )
	{
		 new Vue({
			el: '#tab_section_taxonomies',
			mixins: [meta]
		})
	}
	else if( document.getElementById('tab_section_post_types') )
	{
		 new Vue({
			el: '#tab_section_post_types',
			mixins: [meta]
		})
	}	
	else if( document.getElementById('tab_section_robots') )
	{
		new Vue({		
			el: '#tab_section_robots',
			data() {
				return {					
					robots:'',			
				};
			},	
			mounted() 
			{
				usam_api('seo/robots', 'GET', this.handler);
			},
			methods: {	
				save(){
					usam_api('seo/robots', {robots:this.robots}, 'POST', usam_admin_notice);
				},
				handler(r){
					this.robots = r;
				},
				get_default(){				
					usam_api('seo/robots/default', 'GET', this.handler);
				},				
			}
		})	
	}
})