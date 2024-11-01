(function($)
{
	$.extend(USAM_Page_reports, 
	{	
		init : function() 
		{					
			$(function()
			{					
				USAM_Page_reports.wrapper = $('.tab_'+USAM_Tabs.tab);				
				if ( typeof usam_data_graph !== "undefined" )
				{
					USAM_Page_reports.wrapper
						.on('click', '.wp-list-table thead .manage-column', USAM_Page_reports.select_colum_graph)
						.on('table_update', '#the-list', USAM_Page_reports.load_table_list);
					USAM_Page_reports.graph( usam_data_graph, usam_name_graph );
				}
			});
		},		

		load_table_list : function( e, data ) 
		{
			$("#graph").html('');			
			USAM_Page_reports.graph( data.data_graph, data.name_graph );	
		},	
		
		select_colum_graph : function( e ) 
		{
			if ( $(this).hasClass("column-date") )
				return false;
			
			if ( $(".wp-list-table tbody tr.no-items").length != 0 )
				return false;
			
			var usam_name_graph = $(this).text();
			var usam_data_graph = [];	
			
			var id = $(this).attr('id');	
			var i = $(".wp-list-table tbody .column-"+id).length-1;
			var label = [];
			var message = $(".wp-list-table thead .column-primary").text();
			$(".wp-list-table tbody .column-"+id).each(function(index, elem) 
			{							
				number = parseFloat($(this).text().replace(".","").replace(",","."));
				var column_date = $(this).closest('tr').find(".column-date");
				column_date.find('.toggle-row').remove();
				label = [];
				label[0] = message+": "+column_date.text();				
				label[1] = usam_name_graph+": "+$(this).text();				
				usam_data_graph[i] = {'y_data': column_date.text(), 'x_data': number, 'label': label};					
				i--;
			});							
			$("#graph").html('');			
			USAM_Page_reports.graph( usam_data_graph, usam_name_graph );				
		},		
		
		graph : function( usam_data_graph, usam_name_graph ) 
		{	
			if ( usam_data_graph.length != 0 )
			{		
				$.vertical_bars( 'graph', usam_data_graph, usam_name_graph );		
				$("#graph").show();
			}
			else
				$("#graph").hide();
		},		
	});	
})(jQuery);	
USAM_Page_reports.init();