var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/search-order', {
	title: 'Информация о заказе',
	icon: {   		 
		src: 'feedback',
	},
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
		multiple: false,
	},
	category: 'usam',
	description: 'Поиск информации о заказе для клиента',	
	edit: function( props ) 
	{
		var el = wp.element.createElement;
		return el(
				'div', 
				{ className: 'search_info '+props.className },	
				el(
				'div', { className: 'search_info__search '+props.className },
					el( 'h2', { className: 'title'}, 'Информация по вашему заказ' ),
					el( 'div', { className: 'search_info__keyword' },
						el( 'input', { className: 'search_info__input option-input', placeholder:'Введите номер заказа' } ),
					),
				)
			);
	},
	
	save: function( props )
	{ 
		return null;
	},
});