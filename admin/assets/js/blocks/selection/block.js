var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/selection', {
	title: 'Подборки товаров',
	icon: {   		 
		src: 'feedback',
	},
	supports: {
		align: true,
		html: false,
		multiple: true,
	},
	category: 'usam',
	description: 'Добавить подборки товаров',	
	edit: function( props ) 
	{	
		var el = wp.element.createElement;
		return el( wp.serverSideRender, {block: 'usam/selection', attributes: props.attributes} );
	},
	
	save: function( props )
	{ 
		return null;
	},
});