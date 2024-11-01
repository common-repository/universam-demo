var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/reviews', {
	title: 'Отзывы',
	icon: {   		 
		src: 'admin-comments',
	},
	category: 'usam',
	description: 'Показать отзывы сайта',	
	edit: function( props ) 
	{					
		var el = wp.element.createElement;
		function search( event )
		{			
			props.setAttributes({ message: event.target.value });				
		}
		function updateMessage( item, event ) {
			props.setAttributes({ message: item.name, product_id: parseInt(item.id, 10) });			
		}		
		classNamehidden = props.attributes.selected ? '':'';
		classNameblock = props.attributes.selected ? '':'hidden';	
		return el(
			'div', 
			{ className: props.className },					
			el(
				'div',
				{ className: classNamehidden },	
				el(
					'div',
					{ className: 'components-placeholder is-large' },	
					el(								
						'div',
						{ className: 'options_block ' },
						el( 'div', { className: 'components-placeholder__label' }, 'Выберете опции отзывов' ),					
						el(
							'div',
							{ className: 'options_block__option' },
							'Количество'
						),
						el(						
							'div',
							{ className: 'options_block__option options_block__interval' },
							el(
								'input',
								{ value: props.attributes.per_page, onChange: (e) => { props.setAttributes({ per_page: parseInt(event.target.value?event.target.value:0) }) } }		
							),							
						),		
						el(
							'div',
							{ className: 'options_block__option' },
							'Номер страницы, откуда брать отзывы'
						),
						el(						
							'div',
							{ className: 'options_block__option options_block__interval' },
							el(
								'input',
								{ value: props.attributes.page_id, onChange: (e) => { props.setAttributes({ page_id: parseInt(event.target.value?event.target.value:0) }) } }		
							),							
						),
						el(
							'div',
							{ className: 'options_block__option' },
							'Ответ менеджера сайта'
						),
						el(
							'div',
							{ className: 'options_block__option' },
							el(
								'select',
								{ value: props.attributes.hide_response, onChange: (e) => { props.setAttributes({ hide_response: parseInt(event.target.value) }) } },
								el( 'option',{ value: 1 }, 'Показать' ),	
								el( 'option', { value: 0 },'Скрыть'),							
							),	
						),
						el(
							'div',
							{ className: 'options_block__option' },
							'Суммарный рейтинг'
						),
						el(
							'div',
							{ className: 'options_block__option' },
							el(
								'select',
								{ value: props.attributes.summary_rating, onChange: (e) => { props.setAttributes({ summary_rating: parseInt(event.target.value) }) } },
								el( 'option',{ value: 1 }, 'Показать' ),	
								el( 'option', { value: 0 },'Скрыть'),							
							),	
						),						
					/*	el(
							'div',
							{ className: 'options_block__option' },
							el(
								'button',
								{ className: 'components-button is-primary', onClick: (e) => { props.setAttributes({ selected: 1 }) } },
								'Публиковать выбранные товары'
							),	
						),		*/					
					),		
				),					
			),							
		/*	el(
				'div', 
				{ className: classNameblock },
				el( wp.serverSideRender, { block: 'usam/selected-products', attributes: {order: props.attributes.order, orderby: props.attributes.orderby, brand: props.attributes.brand, category: props.attributes.category, tag: props.attributes.tag, to_price: props.attributes.to_price, from_price: props.attributes.from_price, selected: props.attributes.selected} } ),			
			),		*/		
		); 		
	},
	
	save: function( props )
	{ 
		return null;
	},
});