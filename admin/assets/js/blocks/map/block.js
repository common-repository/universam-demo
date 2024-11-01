var registerBlockType = wp.blocks.registerBlockType;
var active = true;
var myMap, myPlacemark;	
registerBlockType( 'usam/map', {
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
		if ( active )
		{
			active = false;
			ymaps.ready(function()
			{ 		
				myMap = new ymaps.Map(document.getElementById('usam_map_block'), {yandexMapDisablePoiInteractivity: false, center: [props.attributes.latitude, props.attributes.longitude], controls:["zoomControl"], zoom: props.attributes.zoom}, {suppressMapOpenBlock: true});
				myMap.behaviors.disable('scrollZoom'); 								
				usam_api('points_delivery', 'GET', (r) => {								
					for( var i = 0; i < r.points.length; i++) 
					{ 
						myPlacemark = new ymaps.Placemark([r.points[i].latitude, r.points[i].longitude], { 
							hintContent: r.points[i].title, 
							balloonContentHeader: r.points[i].title, 
							balloonContent: r.points[i].description 
						}); 
						myMap.geoObjects.add( myPlacemark ); 
					}
				} ); 
			}) 
		}		
		return el('div', {className: props.className},	
			el('div', {className: 'usam_map js-map', id:'usam_map_block'}),	
		); 		
	},
	
	save: function( props )
	{ 
		return null;
	},
});