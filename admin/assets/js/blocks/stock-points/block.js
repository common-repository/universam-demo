var registerBlockType = wp.blocks.registerBlockType;

var myMap, myPlacemark;	
registerBlockType( 'usam/stock-points', {
	title: 'Список остатков на складах',
	icon: {   		 
		src: 'admin-site-alt',
	},
	supports: {		
		multiple: false,
	},
	category: 'usam',
	description: 'Вы можете выбрать список отображения остатков на складах',	
	edit: function( props ) 
	{	
		var el = wp.element.createElement;
		if ( props.attributes.settings )
			return el( 
				'div',	{ className: 'components-placeholder is-large block_content '+props.className },
				el(
					'div',{ className: 'options_block__name' }, 'Выдача товара'
				),
				el(
					'div', { className: 'options_block__option' },
					el(
						'select',
						{ value: props.attributes.issuing, onChange: (e) => { props.setAttributes({issuing: Number(e.target.value)}) } },
						el( 'option',{ value: 0 }, 'Не доступные к выдаче' ),	
						el( 'option',{ value: 1 }, 'Доступные к выдаче' ),	
						el( 'option', { value: 2 },'Все'),							
					),	
				),					
				el(
					'div',{ className: 'options_block__name' }, 'Отгрузка товара'
				),
				el(
					'div', { className: 'options_block__option' },
					el(
						'select',
						{ value: props.attributes.shipping, onChange: (e) => { props.setAttributes({shipping: Number(e.target.value)}) } },
						el( 'option',{ value: 0 }, 'Не доступные к отгрузке' ),	
						el( 'option',{ value: 1 }, 'Доступные к отгрузке' ),	
						el( 'option', { value: 2 },'Все'),							
					),		
				),	
				el(
					'div',{ className: 'options_block__name' }, 'Тип пункта'
				),
				el(
					'div', { className: 'options_block__option' },
					el(
						'select',
						{ value: props.attributes.type, onChange: (e) => { props.setAttributes({type: e.target.value}) } },
						el( 'option',{ value: 'shop' }, 'Магазины' ),	
						el( 'option',{ value: 'warehouse' }, 'Склады' ),	
						el( 'option',{ value: 'postmart' }, 'Постаматы' ),	
						el( 'option', { value: 'all' },'Все'),							
					),	
				),	
				el(
					'div',{ className: 'options_block__name' }, 'Местоположение'
				),
				el(
					'div',{ className: 'options_block__option' },
					el(
						'select',
						{ value: props.attributes.location, onChange: (e) => { props.setAttributes({location: Number(e.target.value)}) } },
						el( 'option',{ value: 0 }, 'Вне зависимости от местоположения' ),	
						el( 'option',{ value: 1 }, 'По региону посетителя' ),		
					),		
				),	
				el(
					'div',{ className: 'options_block__name' }, 'Показать цены продажи'
				),
				el(
					'div', { className: 'options_block__option' },
					el(
						'select',
						{ value: props.attributes.type_price, onChange: (e) => { props.setAttributes({type_price: Number(e.target.value)}) } },
						el( 'option',{ value: 0 }, 'Нет' ),	
						el( 'option',{ value: 1 }, 'Да' ),		
					),		
				),	
				el(
					'div', 
					{ className: 'block_tools' },
					el(
						'a',
						{ onClick: (e) => props.setAttributes({settings: 0}) }, 'Показать результат'		
					),
				),
			)				
		else
			return el(			
				'div', 
				{ className: 'block_content '+props.className },
				el( wp.serverSideRender, { block: 'usam/stock-points', attributes: props.attributes } ),
				el(
					'div', 
					{ className: 'block_tools' },
					el(
						'a',
						{ onClick: (e) => props.setAttributes({settings: 1}) }, 'Показать настройки'		
					),
				),
			); 		
	},
	save: function( props )
	{ 
		return null;
	},
});