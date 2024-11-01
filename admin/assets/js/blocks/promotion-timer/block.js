var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/promotion-timer', {
	title: 'Таймер акции',
	icon: {   		 
		src: 'feedback',
	},
	supports: {
		align: true,
		html: false,
		multiple: true,
	},
	category: 'usam',
	description: 'Добавить таймер акции',	
	edit: function( props ) 
	{		
		var el = wp.element.createElement;
		d = new Date();
		d.setDate(d.getDate()+1);
		d.setHours(0, 0, 0, 0);
		if( props.attributes.settings > 0 )
			return el( 
				'div',	{ className: 'components-placeholder is-large block_content '+props.className },
				el(
					'div',{ className: 'options_block__name' }, 'Количество дней до конца акции'
				),
				el(
					'div', { className: 'options_block__option' },
					el(
						'input', {type:'text', value: props.attributes.day, onChange: (e) => { props.setAttributes({day: Number(e.target.value)}) } },
					),	
				),					
				el(
					'div',{ className: 'options_block__name' }, 'Показать, если показан товара дня'
				),
				el(
					'div', { className: 'options_block__option' },
					el(
						'select',
						{ value: props.attributes.promotion_option, onChange: (e) => { props.setAttributes({promotion_option: e.target.value.toString() }) } },
						el( 'option',{ value: '' }, 'Для товаров дня и акций' ),	
						el( 'option', { value: 'promotion' },'Только для акций'),		
						el( 'option', { value: 'product_day' },'Только для товаров дня'),								
					),
				),					
				el(
					'div', 
					{ className: 'block_tools' },
					el(
						'a', { onClick: (e) => props.setAttributes({settings: 0}) }, 'Показать результат'		
					),
				),
			);		
		else	
		{
			if( document.querySelector('.promotion_timer') ) 
			{		
				new Vue({el: '.promotion_timer'})
			}
			return el(
				'div', 
				{ className:' components-placeholder is-large promotion_timer block_content' },	
				el( 'timer', { ':date': "'"+d.toString()+"'"} ),
				el(
					'div', { className: 'block_tools' },
					el(
						'a',{ onClick: (e) => props.setAttributes({settings: 1}) }, 'Показать настройки'		
					),
				),			
			); 	
		}
	},
	
	save: function( props )
	{ 
		return null;
	},
});