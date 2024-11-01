var registerBlockType = wp.blocks.registerBlockType;	
var active = true;
var myMap, myPlacemark;	
registerBlockType( 'usam/point-receipt-products', {
	title: 'Пункты выдачи товара',
	icon: {   		 
		src: 'admin-site-alt',
	},
	supports: {		
		multiple: false,
	},
	category: 'usam',
	description: 'Пункты выдачи товара',	
	edit: function( props ) 
	{		
		var el = wp.element.createElement;
		if ( props.attributes.settings )
		{
			return el('div', {className: 'components-placeholder is-large block_content '+props.className},
					el('div', {className: 'options_block__name'}, 'Пункты с учетом местоположения посетителя'),
					el('div', {className: 'options_block__option'},
						el(
							'select', { value: props.attributes.location, onChange: (e) => { props.setAttributes({location: Number(e.target.value)}) } },
							el( 'option',{ value: 0 }, 'Не доступные к выдаче' ),	
							el( 'option',{ value: 1 }, 'Доступные к выдаче' ),	
							el( 'option', { value: 2 },'Все'),							
						),
					),	
					el('div',{ className: 'options_block__name' }, 'Тип пункта'),
					el('div', { className: 'options_block__option' },
						el(
							'select', { value: props.attributes.type, onChange: (e) => { props.setAttributes({type: e.target.value}) } },
							el( 'option',{ value: 'shop' }, 'Магазины' ),	
							el( 'option',{ value: 'warehouse' }, 'Склады' ),	
							el( 'option',{ value: 'postmart' }, 'Постаматы' ),	
							el( 'option', { value: 'all' },'Все'),							
						),	
					),
					el('div', {className: 'options_block__name'}, 'Собственник складов'),
					el('div', {className: 'options_block__option'},
						el(
							'select', { value: props.attributes.owner, onChange: (e) => { props.setAttributes({owner: e.target.value}) } },
							el( 'option',{ value: '' }, 'Только свои склады' ),	
							el( 'option',{ value: 'all' }, 'Все' ),							
						),	
					),
					el('div', { className: 'block_tools' },
						el('a', { onClick: (e) => props.setAttributes({settings: 0}) }, 'Показать результат'),
					)
				)						
		}
		else
		{
			if ( active )
			{ 
				active = false;
				ymaps.ready(function()
				{ 							
					usam_api('points_delivery', 'GET', (r) => {								
						myMap = new ymaps.Map(document.getElementById('usam_map_block'), {yandexMapDisablePoiInteractivity: false, center: [r.latitude, r.longitude], controls:["zoomControl"], zoom: props.attributes.zoom}, {suppressMapOpenBlock: true});
						myMap.behaviors.disable('scrollZoom'); 	
						for (k in r.points)
						{ 
							myPlacemark = new ymaps.Placemark([r.points[k].latitude, r.points[k].longitude], { 
								hintContent: r.points[k].title, 
								balloonContentHeader: r.points[k].title, 
								balloonContent: r.points[k].map_description,
								number: r.points[k].id
							});
							myMap.geoObjects.add( myPlacemark ); 
						}	
					}); 
				}) 
			}		
			return el('div', { className: 'block_content '+props.className },
				el('div', { className: 'usam_map js-map', id:'usam_map_block' }, ),
				el('div', { className: 'block_tools' },
					el('a', { onClick: (e) => props.setAttributes({settings: 1}) }, 'Показать настройки'),
				),				
			); 	
		}		
	},
	
	save: function( props )
	{ 
		return null;
	},
});