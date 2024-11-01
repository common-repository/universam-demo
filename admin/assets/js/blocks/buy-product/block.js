var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/buy-product', {
	title: 'Добавить в корзину',
	icon: {   		 
		src: 'cart',
	},
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
		multiple: true,
	},
	category: 'usam',
	description: 'Добавить кнопку В корзину',	
	edit: function( props ) 
	{	
		var el = wp.element.createElement;
		if ( props.attributes.product_id )
			return el(
				'div', 
				{ className: 'block_content '+props.className },						
				el( wp.serverSideRender, { block: 'usam/buy-product', attributes: props.attributes } ),	
				el(
					'div', 
					{ className: 'block_tools' },
					el(
						'a',
						{ onClick: (e) => props.setAttributes({product_id: 0 }) }, 'Выбрать другой товар'		
					),
				),		
			); 		
		else
			return el(
				'div', 
				{ className: 'block_content '+props.className },					
				el(
					'div', 
					{ className: 'autocomplete' }, 
					el(
						'div', 
						{ className: 'checklist__search_block'}, 
						el(
							'input',
							{ type: 'search', className: 'autocomplete__search', placeholder:'Введите название товара', onChange: (e)=>{
								if ( e.target.value.length > 2  ) 
								{
									usam_api('products', {'search' : e.target.value}, 'POST', (r) => {		
										var listItems = [];
										if ( r.items.length )
											listItems = r.items.map((item, i) => el('div', { className: 'selectlist__list_name', key: 'autocomplete-'+item.ID.toString(), onClick: (e) => props.setAttributes({product_id: parseInt(item.ID, 10) }) }, item.post_title));	
										else
											listItems.push( el('div', {className: 'selectlist__list_name'}, "Ничего не найдено") );	
										ReactDOM.render(listItems, document.querySelector('#block-'+props.clientId+' .selectlist__lists'));					
									});
								}			
							}}		
						),
					),
					el(
						'div', 
						{ className: 'selectlist__panel' },
						el(
							'div', 
							{ className: 'selectlist__lists' },
						),
					),
				)
			); 		
	},
	
	save: function( props )
	{ 
		return null;
	},
});