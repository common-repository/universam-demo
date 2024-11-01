var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/share', {
	title: 'Кнопка поделиться',
	icon: {   		 
		src: 'feedback',
	},
	supports: {
		align: true,
		html: false,
		multiple: true,
	},
	category: 'usam',
	description: 'Добавить кнопки поделиться',	
	edit: function( props ) 
	{
		var el = wp.element.createElement;
		return el(
			'div', 
			{ className: props.className+' share_buttons' },		
			el( 'img', { src: props.attributes.twitter, width:20, height:20 } ),	
			el( 'img', { src: props.attributes.vk, width:20, height:20 } ),
		);
	},
	
	save: function( props )
	{ 
		return null;
	},
});