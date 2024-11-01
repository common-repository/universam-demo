var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/stock-level', {
	title: 'Уровень товарного запаса',
	icon: {   		 
		src: 'feedback',
	},
	supports: {
		align: true,
		html: false,
		multiple: true,
	},
	category: 'usam',
	description: 'Добавить уровень товарного запаса',	
	edit: function( props ) 
	{
		var el = wp.element.createElement;
		return el(
			'div', 
			{ className: 'stock_level' },	
			el( 'div', { className: 'stock_level_column'}),				
			el( 'div', { className: 'stock_level_column'}),		
			el( 'div', { className: 'stock_level_column'}),		
		); 
	},
	
	save: function( props )
	{ 
		return null;
	},
});