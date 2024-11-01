var registerBlockType = wp.blocks.registerBlockType,
	webformcode;

registerBlockType( 'usam/webform', {
	title: 'Веб-форма',
	icon: {   		 
		src: 'feedback',
	},
	supports: {
		align: ['wide', 'full'],
		html: false,
		multiple: true,
	},
	category: 'usam',
	description: 'Добавить веб-форму',	
	edit: function( props ) 
	{
		var el = wp.element.createElement;
		if ( props.attributes.code )
		{ 
			var vueInitialState = {};
            Object.keys( props.attributes ).forEach( function (key) {
                vueInitialState[key] = props.attributes[key];
            });			
			var id = 'webform_'+ props.attributes.code+'_'+ props.clientId;	
			if ( webformcode == props.attributes.code )
				return el( 'div', {id: id} );
			webformcode = props.attributes.code;			
			usam_api('webform/'+props.attributes.code, 'GET', (r) =>
			{				
				document.getElementById(id).innerHTML = r.template;	
				if ( document.getElementById(id) )			
				{
					new Vue({
						el: '#'+id, 
						mixins: [webform],
						data: function () {
							return vueInitialState
						},
						watch: {
							code(newValue) {
								setAttributes({code:newValue});
							}
						},
						mounted() {				
							this.loadProperties();
						},
					});	
				}						
			});
			return el( 'div', {id: id} );
		}
		else
		{					
			usam_api('webforms', 'GET', (r) => {
				const listItems = r.map((item, i) => el(
					'div',
					{ className: 'choose_options__option', key: 'select_webform-'+item.code.toString(), onClick: (e) => props.setAttributes({code: item.code.toString() }) },
					el( 'img', { src: props.attributes.code == item.code ? props.attributes.selected_circle : props.attributes.circle, width:20, height:20  } ),	
					el( 'div', { className: 'choose_options__option_name' }, item.title ),
				));			
				document.querySelectorAll('.select_webforms').forEach((userItem)=>{
					ReactDOM.render(listItems, userItem );
				});			
			});
			var classNameIsLarge = props.attributes.code != '' ? '':'components-placeholder is-large';
			return el(
				'div', 
				{ className: props.className+' '+classNameIsLarge },	
				el( 'div', { className: 'components-placeholder__label'}, 'Выберете веб-форму' ),
				el( 'div', { className: 'choose_options select_webforms' } ),
			); 	
		}
	},
	
	save: function( props )
	{ 
		return null;
	},
});