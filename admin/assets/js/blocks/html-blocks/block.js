var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/html-blocks', {
	title: 'HTML блоки',
	icon: {   		 
		src: 'feedback',
	},
	supports: {
		align: true,
		html: false,
		multiple: true,
	},
	category: 'usam',
	description: 'Добавить html блоки',	
	edit: function( props ) 
	{
		const setAttributes = props.setAttributes
		var el = wp.element.createElement;		
		var id = 'html-blocks_'+ props.clientId;	
		if ( document.getElementById(id) )
		{
			new Vue({
				el: '#'+id, 
				template: '<select-list-api v-model="items" multiple=1 route="htmlblocks"></select-list-api>',
				watch: {
					items(newValue) {
						setAttributes({items:newValue});
					}
				},
				data() {
					return {               
						items:[],
					};
				}, 
			});	
		}
		if( props.attributes.settings == 0 )
		{
			return el(
				'div', { className: 'components-placeholder is-large block_content '+props.className },
				el( wp.serverSideRender, { block: 'usam/html-blocks', attributes: props.attributes } ),
				el(
					'div', { className: 'block_tools' },
					el(
						'a',{ onClick: (e) => props.setAttributes({settings: 1}) }, 'Показать настройки'		
					),
				),
			);		
		}
		else
		{
			return el(
				'div', { className: props.className+' components-placeholder is-large block_content' },	
				el( 'div', { className: 'components-placeholder__label'}, 'Выберете HTML блоки' ),				
				el( 'div', {id: id } ),
				el(
					'div', 
					{ className: 'block_tools' },
					el(
						'a', { onClick: (e) => props.setAttributes({settings: 0}) }, 'Показать результат'		
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