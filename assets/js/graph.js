(function($)
{  
	$.graph_pie_chart = function( id, data, name_graph )
	{ 					
		var graph = $("#"+id);
		if ( data.length != 0 )
			graph.show();		
		else
		{
			graph.hide();
			return false;
		}	
		var width_graph = graph.closest('.graph').addClass('graph_pie_chart').width();		
		var margin = 30,
			width = 500,
			height =  500;			

		/*var svg = d3.select("#"+id)
			.attr("width", width + margin.left + margin.right)
			.attr("height", height + margin.top + margin.bottom)					
		  .append("g")
			.attr("transform", "translate(" + margin.left + "," + margin.top + ")");	
		*/	
		// функция для получения цветов
		var color = d3.scale.category10();
		 
		// задаем радиус
		var radius = Math.min(width - 2*margin, height- 2*margin) / 2;
		 	
		// создаем элемент арки с радиусом
		var arc = d3.svg.arc()
			.outerRadius(radius)
			.innerRadius(0);
			 
		var pie = d3.layout.pie()
			.sort(null)
			.value(function(d) { return d.x_data; });
		
		var svg = d3.select("#"+id)
				.attr("class", "axis")
				.attr("width", width)
				.attr("height", height)
				.append("g")
				.attr("transform", 
					"translate(" +(width / 2) + "," + (height / 2 ) + ")");
				
		var g = svg.selectAll(".arc")
		.data(pie(data))
		.enter().append("g")
		.attr("class", "arc");  
		
		if ( name_graph != '' ) 
		{ 
			svg.append("text")
				.attr("class", "title")
				.attr("x", -radius-margin)
				.attr("y", -radius-10)				  
				.text( name_graph );
		}
	
		g.append("path")
		.attr("d", arc)
		.style("fill", function(d) { 		
			return color(d.data.label); 
		});
		 
		g.append("text")
			.attr("transform", function(d)
			{			
				return "translate(" + arc.centroid(d) + ")"; 
			})
			.style("text-anchor", "middle")
			.text(function(d) { return d.data.label; });
	};	
  
	// Горизонтальные бары
	$.graph_horizontal_bars = function( id, data, name_graph )
	{ 
		let graph = $("#"+id);
		if ( data.length != 0 )
			graph.show();		
		else
		{
			graph.hide();
			return false;
		}
		let margin = { top: 0, bottom: 0, left: 0, right: 0 };
		let name_length = 0;
		for (let i = 0; i < data.length; ++i)
		{
			if ( data[i].y_data.length > name_length)
				name_length = data[i].y_data.length;
		}						
		margin.left = 8*name_length+10;		
		name_length = 0;
		let max = 0;		
		for (let i = 0; i < data.length; ++i)
		{		
			if ( data[i].x_data > max )
			{								
				if ( data[i].label.indexOf('description_bar_signature') !== -1 )
				{
					label = $(data[i].label).siblings('.description_bar_signature').text(); 
					name_length = 7*label.length;						
				}
				else
				{
					label = $('<div/>').html(data[i].label).text(); 
					name_length = 10*label.length;	
				}			
				max = data[i].x_data;				
			}			
		}			 
		margin.right = name_length+5;
		var width_graph = graph.closest('.graph').addClass('graph_horizontal_bars').width();
		var count = data.length;
		var bar_thickness = 35;		
		if ( name_graph != '' ) 
			margin.top = 60;
		else
			margin.top = 0;
		var width = width_graph - margin.left - margin.right,
			height = bar_thickness*count+3+ margin.top + margin.bottom;
		var svg = d3.select("#"+id).attr("width", width_graph).attr("height", height + margin.top + margin.bottom).append("g");	

		var x = d3.scale.linear().range([0, width]).domain([0, d3.max(data, function (d) {		
			return d.x_data;
		})]);

		var y = d3.scale.ordinal().rangeRoundBands([height, 0], .1).domain(data.map(function (d) {				
			return d.y_data;
		}));

		var yAxis = d3.svg.axis().scale(y).tickSize(0).orient("left");
		var gy = svg.append("g").attr("class", "y axis").call(yAxis);		
		var bars = svg.selectAll(".bar").data(data).enter().append("g");

		//добавить прямоугольники
		bars.append("rect").attr("class", "bar")
		.attr("y", function (d) {
				return y(d.y_data);
			})
			.attr("height", y.rangeBand()).attr("x", 0).attr("width", function (d) {
				return x(d.x_data);
			});
		//добавьте метку значения справа от каждой панели
		bars.append("foreignObject").attr("class", "bar_signature")
			.attr("y", function (d) {
				return y(d.y_data);
			})		
			.attr("x", function (d) {
				var c = x(d.x_data) + 5;
				return c > 0 ? c : 5;
			})
			.attr("width", function (d) {
				return width_graph - x(d.x_data) - margin.left;
			})	
			.attr("height", function (d) {				
				return y.rangeBand();
			})			
			.html(function (d) {
				return d.label;
			});		
		svg.attr("transform", "translate(" + margin.left+ "," + margin.top + ")");	
		if ( name_graph != '' ) 
		{ 
			svg.append("text").attr("class", "title").attr("x", -margin.left).attr("y", -26).text( name_graph );
		}				
	};	
		
	$.vertical_bars = function( id, data, name_graph )
	{
		var graph = $("#"+id);
		if ( data.length != 0 )
			graph.show();		
		else
		{
			graph.hide();
			return false;
		}
		var word_length = 100;
		var width_graph = graph.closest('.graph').addClass('vertical_bars').width();			
		if ( name_graph != '' ) 
		{ 
			var margin_top = 60;
		}
		else
		{
			var margin_top = 60;
		}
		var name_length = 0;
		jQuery.each(data, function(index, val) 
		{								
			if ( val.y_data.length>name_length)
				name_length = val.y_data.length;				
		});					
		var text_length = 6*name_length+10;
		var margin = {top: margin_top, right:30, bottom: 40, left: text_length},
			width = width_graph - margin.left - margin.right,
			height = 400 - margin.top - margin.bottom;
			margin_graph = margin.left-margin.right;	
		
		var svg = d3.select("#"+id)
			.attr("width", width + margin.left + margin.right)
			.attr("height", height + margin.top + margin.bottom)					
			.append("g")
			.attr("transform", "translate(" + margin.left + "," + margin.top + ")");	
	
		var count_word = data.length;
		var display_word = width/word_length;
		display_word = Math.round(display_word).toFixed(0);
		var k=1;
		if ( display_word <= count_word )
		{
			k = count_word/display_word;
			k =	Math.ceil(k);		
		}					
		var x = d3.scale.ordinal().rangeRoundBands([0, width], .1, .3);		
		var y = d3.scale.linear().range([height, 0]);			
	
		var xAxis = d3.svg.axis().scale(x).orient("bottom");	
		var yAxis = d3.svg.axis().scale(y).orient("left").ticks(8, "");	

		/*функция масштабирования значений по оси X*/	
		var i = k-1;
		var j = 0;
		var xdata = [];			
		jQuery.each(data, function(index, d) 
		{								
			i++;								
			if ( k==i )
			{				
				i = 0;											
				xdata[j] = d.y_data; 
				j++;
			}				
		});	
		if ( k == 1 )
		{
			margin_graph = 0;
		}	
		var xScale = x.domain(xdata);
		/*функция масштабирования значений по оси Y*/
		var yScale = y.domain([0, d3.max(data, function(d) { return d.x_data; })]);

		if ( name_graph != '' )
		{
			svg.append("text")
			.attr("class", "title")
			.attr("x", -margin.left)
			.attr("y", -26)				  
			.text( name_graph );
		}
		svg.append("g")
			.attr("class", "x axis")						
			.attr("transform", "translate("+margin_graph+"," + height + ")") //двигать оси
			.call(xAxis)
			.selectAll(".tick text");
	
// вывод значений y
		  svg.append("g")
			.attr("class", "y axis")				  
			.call(yAxis);

			 // создаем набор вертикальных линий для сетки   
		d3.selectAll("#"+id+" g.x g.tick")
			.append("line") // добавляем линию
			.classed("grid-line", true) // добавляем класс	
		//	.attr("style", 'stroke: #cccccc')
			.attr("x1", 0)
			.attr("y1", 0)
			.attr("x2", 0)
			.attr("y2", - (height));
			 
		// рисуем горизонтальные линии 
		d3.selectAll("#"+id+" g.y g.tick")
			.append("line")   
			.classed("grid-line", true)
			.attr("x1", 0)
			.attr("y1", 0)
			.attr("x2", width-margin.right)
			.attr("y2", 0);

		var xScale = x.domain(data.map(function(d) { return d.y_data; }));
		var labelsContainers = svg.selectAll(".bar")		
			.data(data)				 
			.enter().append("rect")
			.attr("class", "bar")
			.attr("x", function(d) { return x(d.y_data); })
			.attr("width", x.rangeBand())
			.attr("y", function(d) { return y(d.x_data); })
			.attr("height", function(d) { return height - y(d.x_data); })			
			.on("mouseenter", function (d, i) {
				svg.select("#label" + i).style("display", "block");
			})
			.on("mouseleave", function (d, i) { 
				svg.select("#label" + i).style("display", "none"); 
			});	
			
		/*Ниже код для добавления всплывающей подсказки*/
		var labelsContainers = svg.selectAll("g.label").data(data).enter().append("g").attr("class", "label")
			.attr("transform", function (d) {
				var lInitialX = xScale(d.y_data);
				var lX = lInitialX + xScale.rangeBand() / 2;
				var lY = yScale(d.x_data);
				return "translate(" + lX + ", " + lY + ")";
			})
			.attr("id", function (d, i) { return "label" + i; })
			.style("display", "none");
		labelsContainers.append("polygon")
			.attr("points", "0,0 -5,-10 -70,-10 -70,-50 70,-50 70,-10 5,-10");
		labelsContainers.append("text")
			.attr("id", function (d, i) { return "date" + i; })
			.attr("x", "0")
			.attr("y", function (d) {
				return -35;
			})
			.style("text-anchor", "middle")
			.html(function (d) { return d.label[0]; });
		labelsContainers.append("text")
			.attr("id", function (d, i) { return "value" + i; })
			.attr("x", "0")
			.attr("y", function (d) {
				return -15;
			})
			.style("text-anchor", "middle")
			.html(function (d) { return d.label[1]; }); 
	};		
})(jQuery);
