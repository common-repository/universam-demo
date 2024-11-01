var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/phone', {
	title: 'Телефон сайта',
	icon: {   		 
		src: 'feedback',
	},
	supports: {
		align: true,
		html: false,
		multiple: true,
	},
	category: 'usam',
	description: 'Добавить телефон сайта',	
	edit: function( props ) 
	{			
		var el = wp.element.createElement;
		return el( wp.serverSideRender, {block: 'usam/phone', attributes: props.attributes} );
	},
	
	save: function( props )
	{ 
		return null;
	},
});