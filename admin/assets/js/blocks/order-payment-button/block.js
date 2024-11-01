var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/order-payment-button', {
	title: 'Кнопка оплаты заказа',
	icon: {   		 
		src: 'feedback',
	},
	supports: {
		align: true,
		html: false,
		multiple: true,
	},
	category: 'usam',
	description: '',	
	edit: function( props ) 
	{			
		var el = wp.element.createElement;
		return el( 'a', { href: '#', className: 'usam_modal button quick_order_payment'}, 'Оплатить заказ' );
	},
	
	save: function( props )
	{ 
		return null;
	},
});