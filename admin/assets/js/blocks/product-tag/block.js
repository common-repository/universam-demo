var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/product-tag', {
	title: 'Метки товаров',
	icon: {   		 
		src: 'feedback',
	},
	supports: {
		align: true,
		html: false,
		multiple: true,
	},
	category: 'usam',
	description: 'Добавить метки товаров',	
	edit: function( props ) 
	{	 			
		var el = wp.element.createElement;
		if(  props.attributes.slugs.length )
		{			
			usam_api('product_tags', {'slug': props.attributes.slugs}, 'POST', (r) => {											
				var listItems = [];
				if ( r.items.length )
					listItems = r.items.map((item, i) => el('div', { className: 'product_tag list_terms__term', key: 'term-'+item.term_id.toString()}, item.name));		
				ReactDOM.render(listItems, document.querySelector('#block-'+props.clientId+' .list_terms'));
			});
		}
		if ( props.attributes.start )
			return el(
				'div', 
				{ className: 'block_content '+props.className },						
				el( wp.serverSideRender, { block: 'usam/product-tag', attributes: props.attributes } ),
				el(
					'div', 
					{ className: 'block_tools' },
					el(
						'a',
						{ onClick: (e) => props.setAttributes({start: 0}) }, 'Выбрать опции'		
					),
				),		
			); 		
		else
		{	
			return el(
				'div', { className: props.className },					
				el(
					'div', { className: 'components-placeholder is-large' },	
					el(								
						'div', { className: 'options_block ' },
						el( 'div', { className: 'components-placeholder__label' }, 'Выберете опции' ),					
						el('div', { className: 'options_block__name' }, 'Выберете метки'),
						el('div', { className: 'autocomplete' },						
							el(
								'input', {type: 'search', className: 'autocomplete__search', placeholder:'Введите название метки', onChange: (e)=>{
									if ( e.target.value.length > 2  ) 
									{							
										usam_api('product_tags', {'search' : e.target.value}, 'POST', (r) => {											
											var listItems = [];
											if ( r.items.length )
												listItems = r.items.map((item, i) => el('div', { className: 'selectlist__list_name', key: 'autocomplete-'+item.term_id.toString(), onClick: (e) => {
													if ( !props.attributes.slugs.includes(item['slug']) )
													{													
														const slugs = props.attributes.slugs.slice();														
														slugs.push(item['slug'].toString());																									
														props.setAttributes({slugs:slugs}) 
													}
													ReactDOM.render([], document.querySelector('#block-'+props.clientId+' .selectlist__lists'));														
													document.querySelector('#block-'+props.clientId+' .autocomplete__search').value = '';														
												}}, item.name));	
											else
												listItems.push( el('div', {className: 'selectlist__list_name'}, "Ничего не найдено") );	
											ReactDOM.render(listItems, document.querySelector('#block-'+props.clientId+' .selectlist__lists'));
										});
									}			
								}}		
							),						
							el(
								'div', { className: 'selectlist__panel' },
								el('div', { className: 'selectlist__lists' }),
							),
						),
						el(
							'div', { className: 'list_terms' }
						)
					)			
				)	
			); 	
		}
	},
	
	save: function( props )
	{ 
		return null;
	},
});