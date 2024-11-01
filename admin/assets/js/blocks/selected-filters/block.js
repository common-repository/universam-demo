var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/selected-filters', {
	title: 'Выбранные фильтры',
	icon: {   		 
		src: 'filter',
	},
	supports: {
		align: true,
		html: false,
		multiple: true,
	},
	category: 'usam',
	description: 'Выбранные фильтры',	
	edit: function( props ) 
	{					
		var el = wp.element.createElement;
		return el(
			'div', 
			{ className: props.className+' selected_catalog_filters' },					
			el(
				'div', { className: 'selected_catalog_filters__item'}, 'Пример фильтра',	
			),	
			el(
				'div', { className: 'selected_catalog_filters__item'}, 'Пример фильтра',
			),			
		); 		
	},
	
	save: function( props )
	{ 
		return null;
	},
});