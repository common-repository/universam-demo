var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/slider', {
	title: 'Слайдер',
	icon: {   		 
		src: 'slides',
	},
	category: 'usam',
	description: 'Показать слайдер',	
	edit: function( props ) 
	{			
		var el = wp.element.createElement;
		function updateMessage( item, e ) {
			props.setAttributes({ id: parseInt(item.id, 10) });
		}					
		usam_api('sliders', 'GET', (response) => {			
			const listItems = response.map((item, i) => el(
				'div',
				{ className: 'choose_options__option', key: 'choose_slider-'+item.id.toString(), onClick: (e) => updateMessage(item, e) },
				el( 'img', { src: props.attributes.id == item.id ? props.attributes.selected_circle : props.attributes.circle, width:20, height:20  } ),	
				el( 'div', { className: 'choose_options__option_name' }, item.name ),
			));
			document.querySelectorAll('.select_sliders').forEach(function(userItem) {
				ReactDOM.render(listItems, userItem );
			});	
		});
		return el(
			'div', 
			{ className: props.className+' components-placeholder is-large' },	
			el( 'div', { className: 'components-placeholder__label' }, 'Выберете слайдер' ),				
			el( 
				'div',
				{ className: 'choose_options select_sliders' }
			),				
		); 		
	},
	
	save: function( props )
	{ 
		return null;
	},
});