var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/search', {
	title: 'Поиск на сайте',
	icon: {   		 
		src: 'feedback',
	},
	supports: {
		align: true,
		html: false,
		multiple: true,
	},
	category: 'usam',
	description: 'Добавить форму поиска на сайте',	
	edit: function( props ) 
	{	
		var el = wp.element.createElement;
		return el(
			'div', 
			{ className: props.className+' components-placeholder is-large' },						
			el( 'input', { className: 'search_form__input', placeholder:'Поиск по каталогу' } )
		);
	},
	
	save: function( props )
	{ 
		return null;
	},
});