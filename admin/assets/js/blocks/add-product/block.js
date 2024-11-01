var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/add-product', {
	title: 'Добавить товар',
	icon: {   		 
		src: 'welcome-add-page',
	},
	supports: {
		align: ['wide', 'full'],
		html: false,
		multiple: false,
	},
	category: 'usam',
	description: 'Добавить товар',	
	edit: function( props ) 
	{
		var el = wp.element.createElement;
		var vueInitialState = {};
		Object.keys( props.attributes ).forEach( (key) => {
			vueInitialState[key] = props.attributes[key];
		});
		if( document.getElementById('add_product') )
		{ 
			new Vue({		
				el: '#add_product',		
				mixins: [add_product],				
				created: function () 
				{
					this.loadCategories(); 
					this.loadAttributes(); 			
				}
			})
		}
		return el( wp.serverSideRender, { block: 'usam/add-product', attributes: props.attributes } );
	},
	
	save: function( props )
	{ 
		return null;
	},
});