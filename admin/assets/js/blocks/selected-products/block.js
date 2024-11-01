var registerBlockType = wp.blocks.registerBlockType,
	loaded = false,
	taxonomies = [],
	terms = {};

registerBlockType( 'usam/selected-products', {
	title: 'Выбранные товары',
	icon: {   		 
		src: 'cart',
	},
	terms_categories: {},
	category: 'usam',
	description: 'Отображение выбранных товаров',	
	edit: function( props ) 
	{	
		var el = wp.element.createElement;
		var a = {categories:'category', brands:'brands', product_tags:'product_tag', selections:'selection', catalogs:'catalog'};
		function updateMessage( e, item ) {			
			e.target.closest('.selectlist__panel').classList.add('hidden');	
			document.querySelector('#block-'+props.clientId+' #block_'+ item.taxonomy).value = item.name;			
			let parents = {};			
			parents[item.taxonomy.replace('usam-', '')] = item['slug'];			
			props.setAttributes(parents);					
		}			
		var classNamehidden = props.attributes.selected ? '':'';
		var classNameblock = props.attributes.selected ? '':'hidden';			
		function search( e, request )
		{
			terms['usam-'+a[request]] = e.target.value;
			if ( e.target.value.length > 2  ) 
			{
				usam_api(request, {search: e.target.value, name_format:'path'}, 'GET', (r) => {
					const listItems = r.items.map((item, i) => el('div',{className: 'selectlist__list_name', key: 'autocomplete-'+item.term_id.toString(), onClick: (e) => updateMessage(e, item) }, item.name));
					e.target.nextSibling.classList.remove('hidden');
					ReactDOM.render(listItems, e.target.nextSibling.querySelector('.selectlist__lists') );					
				});
			}			
		}			
		if ( !loaded )
		{	
			for (k in a)
			{	
				terms['usam-'+a[k]] = '';
				if ( props.attributes[a[k]] )
				{
					var slug = [];
					slug[0] = props.attributes[a[k]];
					usam_api(k, {slug: slug}, 'GET', (r) => terms[r.items[0].taxonomy] = r.items[0].name);
				}
			}				
			let handler = (r) => {					
				let i = 0;
				for (k in a)
				{
					for (j in r)
					{						
						if ( r[j].name === 'usam-'+a[k] || r[j].name === 'product_tag' )
						{
							taxonomies[i] = {name: r[j].name, label: r[j].label, request: k};							
							delete r[j];
							i++;
							break;							
						}						
					}						
				}
				props.setAttributes({loaded:!props.attributes.loaded});					
			}	
			usam_api('taxonomies', {output:'objects', object_type:'usam-product'}, 'GET', handler );		
			loaded = true;			
		}
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
						el( 'div', { className: 'components-placeholder__label' }, 'Выберете опции товаров' ),					
						el(
							'div', { className: 'options_block__name' }, 'Количество в строке'
						),
						el(						
							'div',
							{ className: 'options_block__option options_block__interval' },
							el(
								'input', {className: 'components-text-control__input', value: props.attributes.column, onChange: (e) => { props.setAttributes({column: parseInt(e.target.value?e.target.value:0) }) } }		
							),							
						),		
						el(
							'div', { className: 'options_block__name' }, 'Количество'
						),
						el(						
							'div', { className: 'options_block__option options_block__interval' },
							el(
								'input', {className: 'components-text-control__input', value: props.attributes.limit, onChange: (e) => { props.setAttributes({limit: parseInt(e.target.value?e.target.value:0) }) } }		
							),							
						),							
						el(
							'div', { className: 'options_block__name' }, 'Диапазон цен'
						),
						el(						
							'div',
							{ className: 'options_block__option options_block__interval' },
							el(
								'input', {className: 'components-text-control__input', value: props.attributes.from_price, onChange: (e) => { props.setAttributes({ from_price: parseFloat(e.target.value?e.target.value:0) }) } }		
							),
							el(
								'input', {className: 'components-text-control__input', value: props.attributes.to_price, onChange: (e) => { props.setAttributes({ to_price: parseFloat(e.target.value?e.target.value:0) }) } }	
							),
						),		
						el(
							'div', { className: 'options_block__name' }, 'Остаток'
						),
						el(						
							'div',
							{ className: 'options_block__option options_block__interval' },
							el(
								'input', {className: 'components-text-control__input', value: props.attributes.from_stock, onChange: (e) => { props.setAttributes({ from_stock: parseFloat(e.target.value?e.target.value:0) }) } }		
							),
							el(
								'input', {className: 'components-text-control__input', value: props.attributes.to_stock, onChange: (e) => { props.setAttributes({ to_stock: parseFloat(e.target.value?e.target.value:0) }) } }	
							),
						),	
						el('div', { className: 'options_block__taxonomy' },
							taxonomies.map((n, i) => el( 'div', {key: 'taxonomy-'+i.toString(),},
								el('div', { className: 'options_block__name' }, n.label),
								el('div', { className: 'selectlist' },
									el('input',{id: 'block_'+n.name, type: 'text', defaultValue: terms[n.name], onChange: event => search(event, n.request), placeholder:'Введите название' }),
									el('div', { className: 'selectlist__panel hidden' }, el( 'div', { className: 'selectlist__lists' }) ),
								),
							)),
						),							
						el('div',{ className: 'options_block__name' }, 'Сортировка'),
						el(
							'div',
							{ className: 'options_block__option' },
							el( 
								'select',
								{ value: props.attributes.orderby, onChange: (e) => { props.setAttributes({ orderby: e.target.value.toString() }) } },
								props.attributes.sorting_options.map((item, i) => el(		
									'option',{ value: item.id, key:'orderby'+i}, item.name,	
								)),		
							),
						),			
						el(
							'div',
							{ className: 'options_block__name' },
							'Направление сортировки'
						),
						el(
							'div',
							{ className: 'options_block__option' },
							el(
								'select',
								{ value: props.attributes.order, onChange: (e) => { props.setAttributes({ order: e.target.value.toString() }) } },
								el( 'option',{ value: 'ASC' }, 'По возрастанию' ),	
								el( 'option', { value: 'DESC' },'По убыванию'),							
							),	
						),	
						el(
							'div',
							{ className: 'options_block__name' },
							'Вариант отображения товара'
						),
						el(
							'div',
							{ className: 'options_block__option' },
							el(
								'select',
								{ value: props.attributes.view_type, onChange: (e) => { props.setAttributes({ view_type: e.target.value.toString() }) } },
								el( 'option',{ value: 'grid' }, 'Плиткой' ),	
								el( 'option', { value: 'list' },'Списком'),							
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