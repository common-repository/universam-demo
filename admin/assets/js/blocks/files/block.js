var registerBlockType = wp.blocks.registerBlockType;

registerBlockType( 'usam/files', {
	title: 'Папка',
	icon: {   		 
		src: 'portfolio',
	},
	category: 'usam',
	description: 'Опубликуйте файлы выбранной папки',	
	edit: function( props ) 
	{					
		var el = wp.element.createElement;
		function search_folder( e )
		{			
			props.setAttributes({ message: e.target.value });
			if ( e.target.value.length > 1  ) 
			{				
				var data = { 'search' : e.target.value };	
				usam_api('folders', data, 'POST', (r) => {					
					const listItems = r.items.map((item, i) => el('li',{ className: 'ui-menu-item', key: 'autocomplete-'+item.id.toString(), onClick: (e) => updateMessage(item, e) }, item.name));														
					ReactDOM.render(listItems, document.querySelectorAll('#block-'+props.clientId+' .ui-autocomplete')[0]);
				}); 
			}			
		}
		function updateMessage( item, e ) {
			props.setAttributes({ message: item.name, folder_id: parseInt(item.id, 10) });			
		}				
		classNamehidden = props.attributes.folder_id ? 'hidden':'';
		classNameblock = props.attributes.folder_id ? '':'hidden';					
		return el(
			'div', 
			{ className: props.className },					
			el(
				'div', 
				{ className: 'text_search_block '+classNamehidden },
				el(
					'input', { type: 'search', value: props.attributes.message, onChange: search_folder, placeholder:'Введите название папки для публикации' }		
				),
			),
			el(
				'div', 
				{ className: 'ui-autocomplete '+classNamehidden },
			),
			el(
				'div', 
				{ className: classNameblock },
				el( wp.serverSideRender, { block: 'usam/files', attributes: props.attributes } ),			
			),				
		); 		
	},
	
	save: function( props )
	{ 
		return null;
	},
});