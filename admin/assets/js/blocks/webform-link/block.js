var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/webform-link', {
	title: 'Кнопка веб-формы',
	icon: {   		 
		src: 'feedback',
	},
	supports: {
		align: true,
		html: false,
		multiple: true,
	},
	category: 'usam',
	description: 'Добавить кнопку веб-формы',	
	edit: function( props ) 
	{
		var el = wp.element.createElement;
		usam_api('webforms', 'GET', (r) => {
			const listItems = r.map((item, i) => el(
				'div',
				{ className: 'choose_options__option', key: 'select_webforms_link-'+item.code.toString(), onClick: (e) => props.setAttributes({ code: item.code.toString() }) },
				el( 'img', { src: props.attributes.code == item.code ? props.attributes.selected_circle : props.attributes.circle, width:20, height:20  } ),	
				el( 'div', { className: 'choose_options__option_name' }, item.title ),
			));
			document.querySelectorAll('.select_webforms_link').forEach((u)=> ReactDOM.render(listItems, u ));	
		});
		if ( props.attributes.code )
			return el( wp.serverSideRender, { block: 'usam/webform-link', attributes: props.attributes } );
		else
		{
			return el(
				'div', 
				{ className: props.className+' components-placeholder is-large' },	
				el( 'div', { className: 'components-placeholder__label'}, 'Выберете веб-форму' ),				
				el( 'div', { className: 'choose_options select_webforms_link' } )
			); 	
		}
	},
	
	save: function( props )
	{ 
		return null;
	},
});