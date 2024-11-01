var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/region-selection', {
	title: 'Кнопка выбор региона',
	icon: {   		 
		src: 'feedback',
	},
	supports: {
		align: true,
		html: false,
		multiple: true,
	},
	category: 'usam',
	description: 'Добавить кнопку выбор региона',	
	edit: function( props ) 
	{			
		var el = wp.element.createElement;
		return el( wp.serverSideRender, { block: 'usam/region-selection', attributes: props.attributes } );
	},
	
	save: function( props )
	{ 
		return null;
	},
});