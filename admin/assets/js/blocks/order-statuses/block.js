var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/order-statuses', {
	title: 'Статусы заказа',
	icon: {   		 
		src: 'feedback',
	},
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
		multiple: false,
	},
	category: 'usam',
	description: 'Описание статусов заказа',	
	edit: function( props ) 
	{
		var el = wp.element.createElement;
		return el(			
			'div', 
			{ className: props.className },			
			el( wp.serverSideRender, { block: 'usam/order-statuses', attributes: props.attributes } ),
		); 		
	},
	
	save: function( props )
	{ 
		return null;
	},
});