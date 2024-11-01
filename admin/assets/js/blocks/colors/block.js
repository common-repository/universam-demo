var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/colors', {
	title: 'Цвет',
	icon: {   		 
		src: 'color',
	},
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
		multiple: true,
	},
	category: 'usam',
	description: 'Добавляет образец цвета',	
	edit: function( props ) 
	{			
		var el = wp.element.createElement;
		function updateMessage( item, e ) {
			props.setAttributes({ code: item.code.toString() });
		}		
		classNamehidden = props.attributes.show_color_code ? '':'hidden';
		return el(
			'div', 
			{ className: 'classNamehidden' },	
			el(						
				'div',
				{ className: 'options_block' },
				el(
					'div',
					{ className: 'options_block__option' },
					'Код цвета'
				),
				el(						
					'div',
					{ className: 'options_block__option' },
					el(
						'input', { value: props.attributes.color, onChange: (e) => { props.setAttributes({ color: e.target.value.toString() }) } }		
					),							
				),	
				el(
					'div',
					{ className: 'options_block__option' },
					'Размер'
				),				
				el(				
					'div',
					{ className: 'options_block__option' },
					el(
						'input', { value: props.attributes.size, onChange: (e) => { props.setAttributes({ size: parseInt(e.target.value?e.target.value:0) }) } }		
					),							
				),
				el(
					'div',
					{ className: 'options_block__option' },
					'Показывать код цвета'
				),
				el(				
					'div',
					{ className: 'options_block__option' },
					el(
						'select',
						{ value: props.attributes.show_color_code, onChange: (e) => { props.setAttributes({ show_color_code: e.target.value.toString() }) } },
						props.attributes.bol.map((item, i) => el(		
							'option',{ value: item.key, key:'bol'+i}, item.name,	
						)),	
					),							
				),	
				el(
					'div',
					{ className: 'options_block__option '+classNamehidden },
					'Код текста цвета'
				),
				el(						
					'div',
					{ className: 'options_block__option '+classNamehidden },
					el(
						'input', { value: props.attributes.text_color, onChange: (e) => { props.setAttributes({ text_color: e.target.value.toString() }) } }		
					),							
				),					
				el(
					'div',
					{ className: 'options_block__option' },
					'Тип'
				),
				el(				
					'div',
					{ className: 'options_block__option' },
					el(
						'select',
						{ value: props.attributes.option, onChange: (e) => { props.setAttributes({ option: e.target.value.toString() }) } },
						props.attributes.type_options.map((item, i) => el(		
							'option',{ value: item.key, key:'type_options'+i}, item.name,	
						)),	
					),							
				),
			),			
			el(
				'div', 
				{ className: '' },
				el( wp.serverSideRender, { block: 'usam/colors', attributes: props.attributes } ),			
			),				
		); 		
	},
	
	save: function( props )
	{ 
		return null;
	},
});