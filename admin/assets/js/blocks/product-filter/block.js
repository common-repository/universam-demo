var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/product-filter', {
	title: 'Фильтр товаров в каталоге',
	icon: {   		 
		src: 'filter',
	},
	supports: {
		align: true,
		html: false,
		multiple: true,
	},
	category: 'usam',
	description: 'Фильтр товаров в каталоге',	
	edit: function( props ) 
	{					
		var el = wp.element.createElement;
		classNamehidden = '';
		let args = {storages:'Фильтр по магазинам', individual_price:'Выбор цен компаний', range_price:'Фильтр цены', product_rating:'Рейтинг товара'};	
		return el(
			'div', 
			{ className: props.className },					
			el(
				'div',
				{ className: classNamehidden },	
				el(
					'div',
					{ className: 'components-placeholder is-large' },	
					el( 'div', { className: 'components-placeholder__label' }, 'Выберете опции' ),						
					Object.keys(args).map((k, i) => el(	
						'div',
						{ className: 'options_row', key:'options_row_'+i },	
						el(
							'label',
							{ className: 'options_checkbox' },	
						
							el(	
								'input',
								{className: 'components-text-control__input', type: 'checkbox', defaultChecked: props.attributes[k], onChange: (e) => props.setAttributes({[k]: e.target.checked }) }		
							),		
							el(							
								'span', {className: 'options_checkbox__title'}, args[k]
							),		
						),								
					)),	
					el(
						'div',
						{ className: 'options_block__name' },
						'Фильтр по вложенным категориям'
					),
					el(
						'div',
						{ className: 'options_block__option' },
						el(
							'select',
							{ value: props.attributes.categories, onChange: (e) => { props.setAttributes({ categories: e.target.value.toString() }) } },
							el( 'option',{ value: '' }, 'Не показывать' ),	
							el( 'option', { value: 'no_hierarchy' },'Без иерархии'),		
							el( 'option', { value: 'hierarchy' },'С иерархией'),								
						),	
					),		
					el(
						'div',
						{ className: 'options_block__name' },
						'Активировать кнопкой'
					),
					el(
						'div',
						{ className: 'options_block__option' },
						el(
							'select',
							{ value: props.attributes.filter_activation, onChange: (e) => { props.setAttributes({ filter_activation: e.target.value.toString() }) } },
							el( 'option', { value: 'button' },'Кнопкой применить'),		
							el( 'option', { value: 'auto' },'Автоматически'),								
						)
					)			
				)					
			)				
		)
	},
	
	save: function( props )
	{ 
		return null;
	},
});