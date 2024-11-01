var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/basket', {
	title: 'Корзина товаров',
	icon: {   		 
		src: 'welcome-add-page',
	},
	supports: {
		align: ['wide', 'full'],
		html: false,
		multiple: false,
	},
	category: 'usam',
	description: 'Добавить корзину товаров',	
	edit: function( props ) 
	{
		var el = wp.element.createElement;
		return el(
			'div', 
			{ className: props.className },	
			el( 'div', { className: 'components-placeholder__label'}, 'Подпись корзины' ),
			el(
				'div',
				{ className: 'options_block__option' },
				el(
					'select',
					{ value: props.attributes.signature, onChange: (e) => { props.setAttributes({ signature: e.target.value.toString() }) } },
					el( 'option',{ value: 1 }, 'Да' ),	
					el( 'option', { value: 0 },'Нет'),							
				),	
			),	
			el( 'div', { className: 'components-placeholder__label'}, 'Вид корзины' ),
			el(
				'div',
				{ className: 'options_block__option' },
				el(
					'select',
					{ value: props.attributes.basket_view, onChange: (e) => { props.setAttributes({ basket_view: e.target.value.toString() }) } },
					el( 'option',{ value: 'table' }, 'Таблица товаров' ),	
					el( 'option', { value: 'icon' },'Иконка и итог'),							
				),	
			),
		); 		
	},
	
	save: function( props )
	{ 
		return null;
	},
});