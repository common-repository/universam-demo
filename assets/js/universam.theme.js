var basket={}, popup_addtocart = {}, webforms={}, pickup_points={}, media_viewer={};

function usam_update_cart_counters( c ) 
{
	let d = document.querySelector('.js-basket-icon');					
	if ( d )
	{
		if ( c.products.length )
			d.classList.add('items_basket');
		else
			d.classList.remove('items_basket');
	}
	let counter = {'number-goods-basket':c.products.length, 'number_items_basket':c.number_goods_message, 'total_number_items_basket':c.number_items_message, 'basket-subtotal':c.subtotal.currency, 'basket-total':c.total.currency}
	var el;
	for (let k in counter)
	{
		el = document.querySelectorAll('.js-'+k);
		if ( el )
			el.forEach((e)=>{ e.innerHTML = counter[k]; })			
	}
	el = document.querySelectorAll('.js_basket_counter');
	if ( el )
		el.forEach((e)=>{ 
			e.innerHTML = c.number_items;
			e.classList.add('site_counter');			
		})
	d = document.querySelector('.is-loading-basket')
	if ( d )
		d.classList.remove("is-loading-basket");
}

function AddToBasket( r, e ) 
{
	if ( r.result )
	{		
		usam_update_cart_counters( r.basket );
		for (let k in basket)
			basket[k].preparationData( r );					
		if ( /basket/.test(location.href) === false && /checkout/.test(location.href) === false )	
		{
			if ( r.popup == 'sidebar' )						
				basket.sidebar.show = 1;	
			else if ( r.popup == 'popup' )
			{
				var el = e.target.closest('.js-product-add');						
				if ( el )
					el = el.getBoundingClientRect();	
				else							
					el = e.target.getBoundingClientRect();	
				clientWidth = document.documentElement.clientWidth
				left = el.left;						
				if ( left+300 > clientWidth )
					left = clientWidth-330;							
								
				popup_addtocart.product = r.product;
				popup_addtocart.show = 1;
				popup_addtocart.left = left;
				popup_addtocart.top = document.documentElement.scrollTop+el.top;
			}
			else if ( r.popup == 'info' )
				usam_notifi({'text': r.notification});
		}
	}					
	else
		usam_notifi({'text': r.notification, type:'error'});
}

function usam_update_product_list( list, post_id, handler, ev )
{ 
	if ( ev != undefined )
		ev.currentTarget.classList.toggle('list_selected');	
	usam_api('list/post', {list:list, post_id: post_id}, 'POST', (r) =>	{
		document.querySelectorAll(".js-"+list+"-counter").forEach((v)=>{
			var counter = parseInt(v.innerText);
			if ( r == 'deleted' )
				counter--;
			else if ( r == 'add' )
				counter++;
			v.innerHTML = counter;	
		})		
		if ( handler != undefined )
			handler(r, ev);		
	});
}

function usam_update_seller_list( list, seller_id, handler, e )
{
	if ( e != undefined )
	{
		e.currentTarget.classList.toggle('list_selected');
		e.currentTarget.classList.add("is-loading");
	}
	usam_api('list/seller', {list:list, seller_id: seller_id}, 'POST', (r) =>	{	
		if ( e != undefined )
			e.currentTarget.classList.remove("is-loading")	
		document.querySelectorAll(".js-"+list+"-counter").forEach((el)=>{
			var counter = parseInt(el.innerText);
			if ( r == 'deleted' )
				counter--;
			else if ( r == 'add' )
				counter++;
			el.innerHTML = counter;	
		})		
		if ( handler != undefined )
			handler(r);		
	});
}
					
function usam_product_variation_change( e ) 
{	
	var p = e.closest('.js-product');
	if ( !p )
		return;
	var id = p.getAttribute('product_id');		
	var allSelected = true;	
	if( p.querySelector(".js-product-variations-"+id) ) 
	{ 		
		var variations = {};
		var value = '';	
		var vargrp_id = e.getAttribute('vargrp_id');
		var el;
		p.querySelectorAll(".js-product-variations-"+id+" .select-variation[vargrp_id='"+vargrp_id+"']").forEach((el)=>{
			el.classList.remove('select-variation');
		})
		e.classList.add('select-variation');		
		p.querySelectorAll(".js-product-variations-"+id+" .select-variation").forEach((el)=>{
			value = el.tagName == 'SELECT' ? el.value : el.getAttribute('variation_id');
			vargrp_id = el.getAttribute('vargrp_id');
			if ( typeof value !== typeof undefined && value != '' && value != 0 ) 
				variations[vargrp_id] = value;			
			let eln = p.querySelector(".js-product-variations-"+id+" .js-name-selected-variation-"+vargrp_id);
			if ( eln )
				eln.innerHTML = el.getAttribute('variation_name');
			
		})
		vargrp_id = 0;
		ok = false;
		p.querySelectorAll(".js-product-variations-"+id+" .js-product-variation").forEach((el)=>{
			if ( vargrp_id && vargrp_id != el.getAttribute('vargrp_id') )
			{  
				if ( !ok )
				{
					allSelected = false;
					return false;
				}
				ok = false;
			}
			vargrp_id = el.getAttribute('vargrp_id');
			if ( el.classList.contains('select-variation') ) 
				ok = true;
		})		
		if ( allSelected )
		{				
			var loading = e.closest('div');
			loading.classList.add("is-loading");
			usam_api('product/'+id+'/variation', {variations : variations}, 'POST', (r) =>
			{
				loading.classList.remove("is-loading");
				if ( !r.variation_product_id )
					return false;								
				let c = ['price', 'oldprice', 'discount', 'sku'];
				for (let k of c)
				{					
					el = p.querySelector('.js-'+k);						
					if ( el )
					{							
						if ( k == 'price' )
						{														
							let price = r.numeric_price;
							p.querySelectorAll('.js-option').forEach((o)=>{				
								if ( o.checked )
									price += parseFloat(o.getAttribute('price'));								
							});	
							el.innerHTML = to_currency(price);							
							el.setAttribute('price', r.numeric_price);
						}
						else
							el.innerHTML = r[k];
					}
				}		
				if ( r.thumbnail !== undefined ) 
				{
					el = p.querySelector('.js-thumbnail');
					if ( el )
					{
						el.src = r.thumbnail.src;
						el.width = r.thumbnail.width;
						el.height = r.thumbnail.height;
					}
				}
			});
		}
	}	
}

function open_webform(e)
{ 
	e.preventDefault();	
	var a = e.currentTarget;
	var type = a.getAttribute('data-modal');		
	var data = {page_id: USAM_THEME.page_id};	
	if( a.hasAttribute('data-id') )
		data.id = a.getAttribute('data-id');	
	document.querySelectorAll('.usam_modal').forEach((l)=>{
		l.classList.remove('button_modal_active');
	})
	a.classList.add('button_modal_active');				
	jQuery.usam_get_modal(type, data);
}
function event_menu_close(e)
{
	if ( jQuery(e.target).closest(".js-menu").length ) 
		return;			
	if ( jQuery(e.target).closest(".js-toggle-menu").length ) 	
		return;		
	e.preventDefault();
	document.querySelectorAll('.js-toggle-menu').forEach((l)=>{
		l.classList.remove('active');
	})	
	document.querySelectorAll('.js-menu').forEach((l)=>{
		l.classList.remove('show_menu');
	})
	var b = document.querySelector('.usam_backdrop');
	!b || b.remove();
	jQuery(document).off('click', event_menu_close); 
}

function quick_product_view( id )
{			
	if ( !id )
		return;
	var modal_type = 'quick_view_'+id;
	if ( jQuery('#'+modal_type).length ) 
		jQuery('#'+modal_type).modal();
	else
	{
		var	callback = (r) =>
		{				
			jQuery('body').append( jQuery(r) );		
			usam_set_height_modal( jQuery('#'+modal_type) );		
		};
		usam_send({action: 'get_modal', modal: 'quick_view', nonce: UNIVERSAM.get_modal, product_id: id}, callback);
	}
}

function update_product_rating( id, n ) {
	if ( getCookie('product_rating_'+id) )
		return;
	setCookie('product_rating_'+id, id);
	usam_api('product/'+id+'/rating', {rating:n}, 'POST');	
}
	
function usam_map_display(id) 
{		
	var myMap, myPlacemark, zoom = 14;
	if ( typeof usam_map.zoom !== typeof undefined )
		zoom = usam_map.zoom;
	if ( typeof usam_map.points !== typeof undefined )
	{
		myMap = new ymaps.Map(id, {yandexMapDisablePoiInteractivity: false, center: [usam_map.latitude, usam_map.longitude], controls:["zoomControl"], zoom: zoom}, {suppressMapOpenBlock: true}); 												
		myMap.behaviors.disable('scrollZoom'); 
		for (let k in usam_map.points) 
		{ 
			myPlacemark = new ymaps.Placemark([usam_map.points[k].latitude, usam_map.points[k].longitude], { 
				hintContent: usam_map.points[k].title, 
				balloonContentHeader: usam_map.points[k].title, 
				balloonContent: usam_map.points[k].map_description 
			}); 
			myMap.geoObjects.add( myPlacemark ); 
		}	
	}
	else
	{		
		usam_api( usam_map.route, {location_id:usam_map.location_id}, 'GET', (r) => {
			myMap = new ymaps.Map(id, {yandexMapDisablePoiInteractivity: false, center: [r.latitude, r.longitude], controls:["zoomControl"], zoom: zoom}, {suppressMapOpenBlock: true}); 												
			myMap.behaviors.disable('scrollZoom'); 				
			for (let k in r.points) 
			{
				myPlacemark = new ymaps.Placemark([r.points[k].latitude, r.points[k].longitude], { 
					hintContent: r.points[k].title, 
					balloonContentHeader: r.points[k].title, 
					balloonContent: r.points[k].map_description
				}); 
				myMap.geoObjects.add( myPlacemark ); 
			}		
		}); 					
	}
}
	
jQuery(document).ready(function ($)
{		
	var $slides = $('.js-product-slides');
	if ( $slides.length )
	{		
		if ( window.screen.width > 769 )
		{
			$(".ProductZoom").each(function() 
			{
				$(this).zoom({'url':$(this).attr('href')});
			})	
		}			
		var args = {autoplay:false, items:1, loop:false, nav:false, dots:false, loadedClass:'', autoWidth:false, dotsContainer: '.js-product-gallery'}
		var next = $slides.attr("next")
		var prev = $slides.attr("prev");
		if ( typeof next !== typeof undefined && typeof prev !== typeof undefined )
		{
			args.navText = ['<img src="'+prev+'">', '<img src="'+next+'">']		
			args.nav = true;
		}
		$slides.owlCarousel(args).on('changed.owl.carousel', function(e) 
		{		
			$('.js-product-gallery .current').removeClass('current');
			$('.js-product-gallery .owl-item').eq(e.item.index).addClass("current");
		})
		$('body').on( "click", ".js-product-gallery img", function(e)
		{
			e.preventDefault();	
			i = $(this).parent().index();
			$slides.trigger('to.owl.carousel', [i, 300]);
			$('.js-product-gallery .owl-item').eq(i).addClass("current");
		});	
		$('.js-product-gallery').on({'initialized.owl.carousel': (e) => $('.js-product-gallery .owl-item').eq(0).addClass("current")}).owlCarousel({autoplay:false, nav:false, autoWidth:true, dots:false})					
		$('body').on('product_variation_change', '.js-product', function(e, data)	
		{ 						
			var img = $(".js-product-gallery img.js-product-image-"+data.variation_product_id);					
			if ( img.length )		
			{
				i = img.parent().index();
				$slides.trigger('to.owl.carousel', [i, 300]);	
				$('.js-product-gallery .owl-item').eq(i).addClass("current");
			}
		});	
	}
	$('.js-product-slides img').show();			
	if ( $(".js-reviews-attachments").length )
	{
		$(".js-reviews-attachments").owlCarousel({autoplay:false, autoWidth:true, loop:false, nav:true, dots:false, responsive:{200:{items:2},600:{items:3},1024:{items:5}} });
	}
	$(".js-carousel").each(function() 
	{
		$(this).owlCarousel({autoplay:true, autoplayTimeout:4000, autoWidth:true, loop:false, nav:false, dots:false});
    });	

	if ( jQuery(".js-carousel-products").length )
		jQuery('.js-carousel-products').owlCarousel({autoplay:false, autoWidth:false, loop:false, nav:true, dots:false, responsive:{200:{items:2},600:{items:3},1024:{items:5}} });
	
	if ( $(".js-date-picker").length )
		$( ".js-date-picker" ).datepicker({dateFormat:"dd.mm.yy", numberOfMonths:1, hideIfNoPrevNext:true});
	$('body').on("click", ".js_button_plus", function() 	
	{							
		var t = $(this);
		var $quantity = $(this).siblings(".js-quantity");		
		if ( $quantity.length )
		{
			var quantity = parseFloat( $quantity.val() );
			var step = 1;	
			if ( typeof $quantity.attr('step') !== typeof undefined ) 		
			{				
				step = parseFloat($quantity.attr('step'));
				step = step <= 0 ? 1 : step;
				quantity = quantity + step; 
				if ( !Number.isInteger(quantity) )	
					quantity = quantity.toFixed(1);
			}
			else
				quantity = quantity + step;			
			var max_stock = $quantity.attr('max');	
			if ( typeof max_stock !== typeof undefined && max_stock !== false ) 
			{				
				max_stock = parseFloat(max_stock);	
				if ( max_stock < quantity )		
					return false;
			}
			$quantity.val(quantity);
			var product_add = t.attr('product_add');	
			if ( typeof product_add !== typeof undefined && product_add !== false ) 		
			{	
				t.addClass("is-loading");
				var id = t.attr('product_id');
				var data = {product_id: id, quantity: step};			
				var unit_measure = t.parents('.js-product').find('.js-unit-measure').attr('unit_measure');	
				if ( typeof unit_measure !== typeof undefined ) 
					data.unit_measure = unit_measure;								
				usam_api('basket/new_product', data, 'POST', (r) =>
				{
					t.removeClass("is-loading");					
					if ( r.result )
					{
						usam_update_cart_counters( r.basket );		
						for (let k in basket)
							basket[k].preparationData( r );						
					}									
				});	
			}
		}
	});	
	
	$('body').on("click", ".js_button_minus", function() 	
	{	
		var t = $(this);
		var $quantity = $(this).siblings(".js-quantity");		
		var quantity = parseFloat( $quantity.val() );		
		
		var step = $quantity.attr('step');	
		if ( typeof step !== typeof undefined && step !== false )
		{				
			step = step <= 0 ? 1 : step;
			quantity = quantity - parseFloat(step);
			if ( !Number.isInteger(quantity) )	
				quantity = quantity.toFixed(1);
		}
		else
			quantity = quantity - 1;
						
		if ( quantity < 0 )			
			return false;	
		
		$quantity.val(quantity);	
		var product_remove = t.attr('product_remove');	
		if ( typeof product_remove !== typeof undefined && product_remove !== false ) 		
		{	
			t.addClass("is-loading");
			var product_id = t.attr('product_id');
			var data   = {action: 'update_product_quantity_basket',	product_id: product_id, quantity: quantity};	
			var unit_measure = t.parents('.js-product').find('.js-unit-measure').attr('unit_measure');	
			if ( typeof unit_measure !== typeof undefined ) 
				data.unit_measure = unit_measure;				
			usam_api('basket/new_product', data, 'POST', ( r ) =>
			{
				t.removeClass("is-loading");					
				if ( r.result )
				{ 							
					usam_update_cart_counters( r.basket );		
					for (let k in basket)
						basket[k].preparationData( r );						
				}									
			});	
		}		
	});	
	
	$('body').on('change', '.js-product-variation', function(e)	
	{	
		usam_product_variation_change( e.currentTarget );
	});
	
	$('body').on('click', '.js-product-variation', function(e)	
	{	
		if ( $(this).prop("tagName") != 'SELECT' )
			usam_product_variation_change( e.currentTarget );
	});
		
	$('.js-change-variation').on('click', function() 
	{ 
		var var_id = $(this).attr('variation_id');
		var el = document.querySelector('#product-variation-'+var_id +'.js-product-variation');
		if ( el ) 
			usam_product_variation_change( el );		
	});
	
	document.querySelectorAll('.js-option').forEach((e)=>{				
		e.addEventListener('change', (l) => {			
			let p = l.target.closest('.js-product').querySelector('.js-price');			
			if ( !p )
				return;
			let price = parseFloat(p.getAttribute('price'));
			document.querySelectorAll('.js-option').forEach((el)=>{				
				if ( el.checked )
					price += parseFloat(el.getAttribute('price'));	
			});	
			p.innerHTML = to_currency(price);			
		})
	});
	
	$('body').on('click', '.js-product-add', function(e)	
	{	
		e.preventDefault();		
		var id = e.currentTarget.getAttribute('product_id');
		var data = {product_id : parseInt(id), quantity:1};
		var p = e.target.closest('.js-product');
		if( p )
		{
			var q = p.querySelector('.js-quantity');
			if( q ) 	
				data.quantity = q.value;
			
			var m = p.querySelector('.js-unit-measure');
			if( m ) 	
				data.unit_measure = m.getAttribute('unit_measure');
		}
		var allSelected = true;
		if( document.querySelector(".js-product-variations-"+id+" .js-product-variation") ) 
		{ 
			allSelected = false;
			data.variations = {};			
			document.querySelectorAll(".js-product-variations-"+id+" .js-product-variation").forEach((el) => {
				value = 0;
				if ( el.tagName == 'SELECT' )
				{						
					v = el.value;
					if ( typeof v !== typeof undefined && v != '' && v != 0 ) 
						value = v;		
					else
					{
						allSelected = false;
						return false;
					}
				}					
				else
				{
					if ( el.classList.contains('select-variation') && typeof el.getAttribute("variation_id") !== typeof undefined )
						value = el.getAttribute("variation_id");
				}
				value = parseInt(value);
				if ( el.hasAttribute("vargrp_id") && value != 0 )
				{
					data.variations[el.getAttribute("vargrp_id")] = value;
					allSelected = true;					
				}				
			});			
		}
		if ( allSelected )
		{						
			e.currentTarget.classList.add("is-loading");
			var handler = (r) =>
			{
				e.currentTarget.classList.remove("is-loading");
				AddToBasket(r, e);								
			};
			let items = [];
			document.querySelectorAll('.js-option').forEach((el,i)=>{				
				if ( el.checked )
					items[i] = {product_id: el.getAttribute('product_id'), quantity: data.quantity};
			});
			if ( items.length )
			{
				items.push(data);
				usam_api('basket/new_products', {items:items}, 'POST', handler);				
			}
			else
				usam_api('basket/new_product', data, 'POST', handler);
		}
		else
			usam_notifi({'text': USAM_THEME.message_choose_options, type:'error'});
	});
		
	$('body').on('click', '.js-seller-list', function(e)
	{			
		e.preventDefault();	
		var product_id = e.currentTarget.getAttribute('seller');
		var list = e.currentTarget.getAttribute('sellerlist');		
		usam_update_seller_list(list, product_id, undefined, e);
	});	
		
	make('.js-product-compare', 'click', function(e)
	{			
		e.preventDefault();	
		var product_id = e.currentTarget.closest('.js-product').getAttribute('product_id');
		usam_update_product_list('compare', product_id, undefined, e);
	});		
		
	make('.js-product-desired', 'click', function(e)
	{	
		e.preventDefault();	
		var el = e.currentTarget.closest('.js-product');
		var product_id = el.getAttribute('product_id');
		if ( document.querySelector('#wishlist_content') )	
		{			
			el.remove();
			if ( !document.querySelector('#wishlist_content .js-product') )	
				document.querySelector('#wishlist_content .empty_page').classList.remove('hide');	
			usam_update_product_list('desired', product_id);
		}
		else
			usam_update_product_list('desired', product_id, undefined, e);
	});
	
	$('body').on('click', '.js-subscribe-for-newsletter', function(e)
	{	
		e.preventDefault();	
		var t = $(this);		
		var $email = $(this).siblings('input');
		var email = $email.val();		
		if ( email != '' )
		{			
			t.addClass("is-loading");			
			usam_api('subscribe', {email: email}, 'POST', ( r ) =>
			{
				t.removeClass("is-loading");
				if ( r.result )
					usam_notifi({'text': USAM_THEME.message_subscribe});
				else
					usam_notifi({'text': USAM_THEME.message_unsubscribe});			
				if ( r.result )
					t.parents('.subscribe_for_newsletter').html('');	
			});
		}		
	});		
	$('body').on('click', '#add_item_button', function(e) 
	{	
		$(".add_item_form").slideToggle();		
		$(this).toggleClass('active');		
		$(".add_item_form").find("input:text:visible:first").focus();
	});		
		
	$('body').on('click', '.js-cookie-notice-close', function(e)
	{	
		e.preventDefault();	
		$(this).parent().remove();
		let date = new Date(Date.now() + 8640000);
		date = date.toUTCString();
		document.cookie = "cookienotice=1; path=/; expires=" + date;
		usam_send({action: 'close_cookie_notice'});
	});
	
	$('body').on('click', '.js-user-list', function()	
	{					
		var communication = $(this).data('communication');
		var list_id = $(this).data('list_id');
		var status = 2;
		var t = $(this);
		if ( $(this).prop("checked") )
			status = 1;
		var	callback = function(r)
		{		
			var text = status==1?USAM_THEME.message_subscribe:USAM_THEME.message_unsubscribe;
			usam_notifi({'text': text});
		};					
		usam_send({action: 'update_subscribe', communication: communication, list_id: list_id,	status: status}, callback);	
	});	
	
	$(document).on('click', ".button_show_all_text", function(e) 	
	{	
		e.preventDefault();
		$(this).siblings('.js-reduced-text').toggleClass('reduced_text');	
		text = $(this).attr('text');
		text2 = $(this).html();
		$(this).attr('text', text2).html(text);
		return false;
	});	
	
	$('body').on('click', '.js-toggle-menu', function(e)
	{		
		e.preventDefault();			
		var menu_id = $(this).data("menu_id");		
		var $menu = $("#"+menu_id);
		if( typeof $menu.attr('backdrop') !== 'undefined' )
		{  
			if ( $menu.attr('backdrop') == '1' )
				add_backdrop();
		}		
		if ( $menu.hasClass('show_menu') )
		{
			var b = document.querySelector('.usam_backdrop');
			!b || b.remove();
			$(this).removeClass('active');			
			$menu.removeClass('show_menu');
		}
		else
		{
			$(".js-menu").removeClass('show_menu');
			$menu.addClass('show_menu');
			$(this).addClass('active');					
		}
		setTimeout(function () {
			$(document).on("click", event_menu_close);
		}, 1); 
	});	
	
	$('body').on('click', '.js-close-menu', function()	
	{
		$(".show_menu").removeClass('show_menu');
		var b = document.querySelector('.usam_backdrop');
		!b || b.remove();
		$(document).off('click', event_menu_close);
	});	
		
	$('.js-mobile-menu .menu-item-has-children > a').on('click', function(e) 
	{
		var item = $(this).parent();
		if ( item.length )
		{
			e.preventDefault();
			item.toggleClass("is-active");
			return false;
		}
	});
	
	make('.js-enlarge-photo', 'click', usam_enlarge_photo);
	
	make('.open_product_media_viewer', 'click', (e) => { 
		e.preventDefault();
		if ( typeof product !== typeof undefined )
		{			
			var id = e.currentTarget.id.replace(/[^0-9]/g,"");
			media_viewer.image_key = 0;
			media_viewer.images = product.images;			
			for (let k in media_viewer.images) 
			{
				if ( id == media_viewer.images[k].id )
				{
					media_viewer.image_key = Number(k);
					break;
				}
			}
			media_viewer.title = product.post_title;
			media_viewer.open = true;
		}		
	})	
	make('.open_reviews_media_viewer', 'click', (e) => {
		e.preventDefault();	
		var src = e.currentTarget.getAttribute('src')
		media_viewer.images = [];
		usam_api('reviews', {page_id:USAM_THEME.page_id, media:true, add_fields:['media']}, 'POST', (r) =>
		{
			media_viewer.title = typeof product !== typeof undefined ? product.post_title : '';
			media_viewer.image_key = 0;
			for (let k in r.items) 
				media_viewer.images = media_viewer.images.concat(r.items[k].images);
			
			for (let k in media_viewer.images) 
			{
				if ( src == media_viewer.images[k].small )
				{
					this.image_key = k;
					break;
				}
			}
			media_viewer.open = true;
		});	
	})
	make('.js-quick-view-open', 'click', (e) => {
		e.preventDefault();				
		var id = e.currentTarget.closest('.js-product').getAttribute('product_id');		
		quick_product_view( id );
	})
			
	$('.js-open-image').on('click', function(e)
	{
		e.preventDefault();
		if(this.tagName == 'img') 
			var img = $(this);
		else
			var img = $(this).find('img');
		var src = img.attr("src");
		$("body").append( '<div class="view_picture"><img src="'+src+'"/></div>' );		
		$('.view_picture').addClass('active'); 
	});	
	
	$('body').on('click', '.js-product-unit', function(e)
	{
		var unit_measure = e.target.getAttribute('unit_measure')		
		var box = e.target.closest('.selection_list');
		var p = e.target.closest('.js-product');	
		var q = p.querySelector('.js-quantity');				
		box.querySelector('.js-product-unit.hide').setAttribute('quantity', q.value);		
		box.querySelectorAll('.js-product-unit').forEach((el) => { el.classList.remove('hide'); });		
		var um = box.querySelector('.js-unit-measure');
		um.innerHTML = e.target.innerHTML;
		um.setAttribute('unit_measure', unit_measure);
		
		e.target.classList.add('hide');			
			
		p.querySelector('.js-price').innerHTML = e.target.getAttribute('unit_price');
		
		q.setAttribute('max', e.target.getAttribute('max'));
		var step = 1
		if ( e.target.hasAttribute('step') )
		{
			var step = e.target.getAttribute('step')
			q.setAttribute('step', step);
		}
		if ( e.target.hasAttribute('quantity') )
			q.value = e.target.getAttribute('quantity');
		else
			q.value = step;
	});		
	
	$('body').on('click', '.term_arrow', function(e)
	{
		e.preventDefault();
		e.currentTarget.closest('li').classList.toggle('select_category');
	});		
	
	document.querySelectorAll('a.js-move-block').forEach((el) => {
		el.addEventListener('click', (e) => { 
			e.preventDefault();
			let href = e.currentTarget.getAttribute('href');
			let str = href.split('#');
			usam_scroll_to_element("#"+str[1]);
		})
	})
	document.querySelectorAll('.slide_video').forEach((el) => {
		el.addEventListener('click', (e) => { 
			e.preventDefault();
			var el = e.currentTarget.querySelector('[video]');
			if ( el )
			{
				var url = el.getAttribute('video')+ "?autoplay=1&mute=1&autohide=1&rel=0";
				e.currentTarget.querySelector('.usam_banner_content').innerHTML = '<iframe frameborder=0 src="'+url+'"/>';
			}
		})
	})	
	
	if( $(".js-lzy-products-group").length )
	{ 	
		graphObserver = new IntersectionObserver((entries, Ob) => { 
			entries.forEach((e) => {
				if (e.isIntersecting)
				{
					const lazy = e.target;
					var	data   = {
						action     : 'get_products_group',
						title      : $(lazy).attr('data-title'),
						query      : $(lazy).attr('data-query'),
						template   : $(lazy).attr('data-template'),
						post_meta_cache : $(lazy).attr('data-post_meta_cache'),						
						number     : $(lazy).attr('data-number'),
						product_id : $(lazy).parents('.js-product').attr('product_id'),
					};
					usam_send(data, (r) =>
					{		
						if ( r )
						{
							$(lazy).html(r).removeClass('js-lzy-products-group');
							$(lazy).find('.header_tab a.tab:first').tab('show');
							var c = $(lazy).find(".js-carousel-products")							
							if ( c.length )
								c.owlCarousel({autoplay:false, autoWidth:false, loop:false, nav:true, dots:false, responsive:{200:{items:2},600:{items:3},1024:{items:5}} });							
						}
						else
							$(lazy).remove();
					});	
					Ob.unobserve(lazy);
				}
			})
		}, {rootMargin:'0px 0px 100px 0px'});
		document.querySelectorAll('.js-lzy-products-group').forEach((v) => {
			graphObserver.observe(v);
		}) 						
	}
	
	if( $(".js-html-blocks").length )
	{ 	
		graphObserver = new IntersectionObserver((entries, Ob) => { 
			entries.forEach((e) => {
				if (e.isIntersecting)
				{
					const lazy = e.target;					
					usam_api('htmlblock/'+$(lazy).attr('data-id'), {product_id:$(lazy).parents('.js-product').attr('product_id')}, 'GET', (r) => {
						if ( r )
						{
							$(lazy).html(r).removeClass('js-html-blocks');
							$(lazy).find('.header_tab a.tab:first').tab('show');
							var c = $(lazy).find(".js-carousel-products")							
							if ( c.length )
								c.owlCarousel({autoplay:false, autoWidth:false, loop:false, nav:true, dots:false, responsive:{200:{items:2},600:{items:3},1024:{items:5}} });							
						}
						else
							$(lazy).remove();
					});	
					Ob.unobserve(lazy);
				}
			})
		}, {rootMargin:'0px 0px 100px 0px'});
		document.querySelectorAll('.js-html-blocks').forEach((v) => { graphObserver.observe(v); }) 						
	}
	
	$(".js-youtube").each(function() 
	{
		$(this).css('background-image', 'url(http://i.ytimg.com/vi/' + this.id + '/sddefault.jpg)');
		$(this).append($('<div/>', {'class': 'play'}));
		$(document).on('click', '#'+this.id, (e) =>
		{
			$(e.target).remove();
			var iframe_url = "https://www.youtube.com/embed/" + this.id + "?autoplay=1&mute=1&autohide=1&rel=0";
			if ( $(this).data('params') ) 
				iframe_url+='&'+$(this).data('params');
			var iframe = $('<iframe/>', {'frameborder': '0', 'src': iframe_url })
			$(this).replaceWith(iframe);
		});
	});	

	$('body').on('click', '.js-feedback', function(e)
	{	
		e.preventDefault();
		var id = e.currentTarget.getAttribute('href')
		var code = id.replace('#webform_', '');	
		var el = e.currentTarget.closest('.js-product');
		var page_id = USAM_THEME.page_id;
		if( e.currentTarget.hasAttribute('data-id') )
			page_id = Number(e.currentTarget.getAttribute('data-id'));	
		else if( el && el.hasAttribute('product_id') )
			page_id = Number(el.getAttribute('product_id'));		
		var m = document.getElementById('webform_modal_'+code);				
		if ( m && m.getAttribute('page_id') === page_id ) 
			jQuery('#webform_modal_'+code).modal();
		else
		{ 			
			usam_api('webform/'+code, {page_id:page_id}, 'GET', (r) =>
			{
				if( r.modal !== undefined )
				{
					document.querySelector('body').insertAdjacentHTML('beforeend', r.modal);
					document.querySelector('#webform_modal_'+code+' .modal-body').innerHTML = r.template;					
					usam_set_height_modal( jQuery('#webform_modal_'+code) );	
					document.getElementById('webform_modal_'+code).setAttribute("page_id", page_id);				
					
					webforms[code] = new Vue({el: document.querySelector('#webform_modal_'+code+' '+id), mixins: [webform]});	
					webforms[code].preparationData( r );
					webforms[code].page_id = page_id;
					webforms[code].code = code;					
					if( typeof e.currentTarget.hasAttribute('data-object') !== 'undefined' && typeof e.currentTarget.hasAttribute('data-object_id') !== 'undefined' )
					{
						let object = e.currentTarget.getAttribute('data-object');			
						let object_id = Number(e.currentTarget.getAttribute('data-object_id'));		
						webforms[code].object[object] = object_id;	
					}
				}
			});
		}	
	});
});

var webform = {		
	mixins: [edit_properties],
	data() {
		return {				
			data:{},
			code:'',				
			page_id:0,			
			object:{},	
			send:false,	
			message_result:''
		};
	},		
	methods: {
		loadProperties()
		{			
			usam_api('webform/'+this.code, 'GET', (r) => this.preparationData( r ));		
		},		
		preparationData( r )
		{
			this.properties = this.propertyProcessing(r.properties);
			this.data = r.webform;
			for (k in this.properties)					
				this.$watch(['properties', k].join('.'), this.propertyChange, {deep:true});			
			this.propertyGroups = r.groups;		
		},			
		scrollTop()
		{
			document.querySelector(".js-webform").scrollIntoView({behavior:'smooth', block:'start'});
		},
		next(e)
		{ 	
			e.preventDefault();	
			if ( !this.propertiesVerification( this.main_groups[this.step].code ) )
			{
				this.scrollTop();
				this.step++;
				var data = {};
				for (k in this.properties)
				{
					data[this.properties[k].code] = this.properties[k].value;
				}
				usam_api('webform/'+this.code, {data:data}, 'PUT');		
			}			
		},
		send_form( )
		{ 
			if ( !this.propertiesVerification() && this.confirm )
			{
				data = {};
				for (k in this.properties)
					data[this.properties[k].code] = this.properties[k].value;
				this.send = true;
				var p = this.page_id?this.page_id:USAM_THEME.page_id;
				var	args = {data:data, page_id: p, object: this.object};			
				var el = document.querySelector('#webform_'+this.code+' .js-quantity');
				if ( el )
					args.quantity = this.quantity;			
				usam_api('webform/'+this.code, args, 'POST', this.processSendResponse);
			}			
		},	
		processSendResponse(r) 		
		{ 		
			this.send = false;				
			this.message_result = r;
		}	
	}				
}

document.addEventListener("DOMContentLoaded", () => {	
	if( document.querySelector('.promotion_timer') )
	{
		new Vue({el:'.promotion_timer'})					
	}
	if( document.querySelector('.js-map') )
	{
		ymaps.ready( () => {
			document.querySelectorAll('.js-map').forEach((e) => {
				e.classList.remove('js-map');
				usam_map_display( e.id );				
			})
		})
	}
	
	make('.usam_modal', 'click', open_webform)
	document.querySelectorAll('.js-webform').forEach((e) => {
		var code = e.id.replace('webform_', '');
		e.classList.remove('js-webform');
		if ( code )
		{
			webforms[code] = new Vue({el: e, mixins: [webform]});	
			webforms[code].code = code;						
			webforms[code].loadProperties();			
		}
	})	
	
	document.querySelectorAll('.menu-item-has-children > a .toggle_level').forEach((v)=>{	
		v.addEventListener('click', (e) => {		
			var el = e.currentTarget.closest('.menu-item-has-children');
			if ( el )
			{
				e.preventDefault();
				el.classList.toggle("is-active");					
			}
		})
	})		
	usam_api('popups', {page_id: USAM_THEME.page_id}, 'GET', (r) =>
	{		
		for (let i = 0; i < r.webforms.length; i++)
		{
			var data = r.webforms[i];
			if ( getCookie('webformmodal_'+data.code) )
				return;			
			setTimeout(() => { 
				if ( document.getElementById('webform_modal_'+data.code) ) 
					jQuery('#webform_modal_'+data.code).modal();
				else
				{					
					document.querySelector('body').insertAdjacentHTML('beforeend', data.modal);
					document.querySelector('#webform_modal_'+data.code+' .modal-body').innerHTML = data.template;					
					usam_set_height_modal( jQuery('#webform_modal_'+data.code) );					
					
					webforms[data.code] = new Vue({el: document.querySelector('#webform_modal_'+data.code+' '+'.js-webform'), mixins: [webform]});	
					webforms[data.code].code = data.code;						
					webforms[data.code].loadProperties();
				}	
				setCookie('webformmodal_'+data.code, 1);				
			}, (data.actuation_time-1)*1000);
		}		
		for (let i = 0; i < r.banners.length; i++) 
		{
			let  data = r.banners[i];
			if ( getCookie('bannermodal_'+data.id) )
				return;		
			setTimeout(() => {
				if ( document.getElementById('banner_modal_'+data.id) ) 
					jQuery('#banner_modal_'+data.id).modal();
				else
				{				
					document.querySelector('body').insertAdjacentHTML('beforeend', data.modal);
					usam_set_height_modal( jQuery('#banner_modal_'+data.id) );
				}
				setCookie('bannermodal_'+data.id, 1);				
			}, (data.actuation_time-1)*1000);						
		}
	});
	
	if( document.getElementById('chat_clients') )
	{ 
		new Vue({
			el: '#chat_clients',
			data() {
				return {
					display_chat: false,	
					contact_form: {},
					confirm: true,		
					send_contactform: false,	
				};
			},				
			mixins: [chat],
			computed:
			{  
				emptySender(){
					return !Object.keys(this.sender).length || this.sender.appeal === '';
				}
			},
			mounted() 
			{
				if ( this.$refs['number_messages'] !== undefined )
					this.loadDialog();
			},
			methods: {				
				sendContactForm() 
				{ 
					this.send_contactform = true;
					if (this.confirm && this.contact_form.message!='' && this.contact_form.name!='')
						usam_api('chat/contactform', this.contact_form, 'POST', this.updateChatData);
				},				
				openChat(e) 
				{
					e.preventDefault();
					this.startUpdate = true;
					this.display_chat = true;	
					setTimeout(() => {						
						var onclick = (e) => {					
							if ( e.target.closest('#chat_clients') != null )
								return false;
							document.removeEventListener("click", onclick);	
							this.display_chat = false;							
						}
						document.addEventListener("click", onclick);
					}, 1); 	
					if ( !this.id )
						this.loadDialog();
				}
			}
		})	
	}	
	if( document.getElementById('site_search') )
	{ 
		var siteSearchBlock = new Vue({		
			el: '#site_search',
			mixins: [site_search]
		});
	}
	if( document.getElementById('popup_addtocart') )
	{ 
		popup_addtocart = new Vue({		
			el: '#popup_addtocart',	
			mixins: [handler_checkout],
			watch: {
				show(val, oldVal) 
				{ 
					if ( val == 1 )
						setTimeout( ()=> { document.addEventListener("click", this.popup_close); }, 10); 
				},				
			},
			data() {
				return { left:0, top:0, show:0, product:{}};
			},
			methods: {
				popup_close(e)
				{
					if ( e.target.closest('#popup_addtocart') ) 	
						return;		
					e.preventDefault();		
					this.popup_hide();
				}
			}
		})
	}
	if( document.getElementById('sidebar_addtocart') )
	{ 
		basket.sidebar = new Vue({		
			el: '#sidebar_addtocart',	
			mixins: [handler_checkout],
			watch: {
				show:function (val, oldVal) 
				{
					if ( val == 1 )
					{
						setTimeout( ()=> { document.addEventListener("click", this.popup_close); }, 10); 
						add_backdrop();
					}
					else
						this.popup_hide();
				},				
			},						
			data() {
				return { show:0, product:{}	};
			},
			methods: {
				popup_close(e)
				{
					if ( e.target.closest('#sidebar_addtocart') ) 	
						return;
					e.preventDefault();		
					this.popup_hide();						
				}
			}
		})
	}
	if( document.getElementById('widget_basket') )
	{ 
		basket.sidebar = new Vue({		
			el: '#widget_basket',	
			mixins: [handler_checkout],							
			data() {
				return { show:0	};
			},
			mounted() {
				this.loadProperties();			
			}
		})
	}
	if( document.getElementById('add_product') )
	{
		new Vue({		
			el: '#add_product',		
			mixins: [add_product],
			created() 
			{
				this.loadCategories(); 
				this.loadAttributes(); 			
			}
		})
	}	
	document.querySelectorAll('.product-stock-level').forEach((el)=>{ 
		var p = el.closest('.js-product');
		if ( p )
		{			
			var product_id = p.getAttribute('product_id');	
			new Vue({		
				el: el,							
				data() {
					return {id:0, storages:[], send:false, loaded:false, open:false};
				},		
				mounted() {
					this.id = product_id;					
				},
				methods: {
					load()
					{
						this.$refs.productStockLevel.show = true;
						if ( !this.send && this.id )
						{							
							var location = 0;
							if ( this.$el.hasAttribute('location') )
								location = this.$el.getAttribute('location');
							this.send = true;								
							usam_api('product/'+this.id+'/balances', {location:location, issuing:1, in_stock:0}, 'GET', (r)=> {
								this.storages = r;
								this.loaded = true;	
							});
						}						
					}
				}
			})
		}
	})
	document.querySelectorAll('.product-subscription').forEach((el)=> new Vue({el: el})	)		
	if( document.getElementById('tracking') )
	{
		productSubscriptionModal = new Vue({		
			el: '#modal-product-subscription',		
			data() {
				return { show:0, id:0, email:'', subscription:0, notifications_email:0, notifications_sms:0, setting:false, info:false};
			},
			mounted() {
				usam_api('contact/0', {add_fields:['properties','groups']}, 'GET', (r) => {
					this.notifications_email = r.notifications_email
					this.notifications_sms = r.notifications_sms
				});
			},
			methods: {		
				showModal() {
					this.$refs.productSubscription.show = true;
				},
				saveNotifications() {
					usam_api('contact/0', {notifications_email:this.notifications_email?1:0, notifications_sms:this.notifications_sms?1:0}, 'POST', (r) => this.info = 1);
				},	
				saveContact() {
					if ( this.email )
						usam_api('contact/0', {email:this.email}, 'POST', (r) => this.setting = true );
				},			
			}
		})
	}
})

Vue.component('product-subscription', {
	props:{
		id:{type:Number, required:false, default:0},
		email:{type:String, required:false, default:''},
		subscription:{type:Number, required:false, default:0}
	},
	data() {
		return {
			status:this.subscription,								
		};				
	},		
	watch:{
		subscription(v, old) 
		{		
			this.status = v;
		},
		status(v, old) 
		{				
			usam_update_product_list('subscription', this.id);
			this.$emit('change', v);
			if ( !this.email && v && productSubscriptionModal !== undefined)
			{
				Vue.set(productSubscriptionModal, 'id', this.id);
				Vue.set(productSubscriptionModal, 'email', this.email);		
				Vue.set(productSubscriptionModal, 'subscription', this.subscription);		
				productSubscriptionModal.showModal();
			}
		},
	}
})

Vue.component('web-form', {
	props:{
		formcode:{type:String, required:true},		
	},
	mixins: [webform],		
	mounted() {
		this.code = this.formcode;
		this.loadProperties();			
	}
})

var store_lists = {	
	data() {
		return {
			storages:[],
			old_storages:[],
			selected:false,
			map:null,
			initial:true,
			tab:'list',
			screen:{},
			display_search:false,	
			isLoading:false,
			timerId: 0,
			Observer:{}								
		};				
	},	
	mounted() 
	{			
		this.Observer = new IntersectionObserver((entries, imgObserver) => {
			entries.forEach((e) => {
				if (e.isIntersecting)
				{ 
					if ( this.old_storages.length && this.isLoading == false )
					{
						var i = 0;					
						for (k in this.old_storages)
						{							
							this.storages.push(this.old_storages[k]);
							this.old_storages.splice(k, 1);									
							i++;
							if ( i > 10 )
								break;
						}							
					}
				}
			})
		}, {rootMargin: '0px 0px 100px 0px'});
		document.querySelectorAll('#load-store-list').forEach((v)=>{ this.Observer.observe(v); })
	},
	methods: {	
		search_enter(e)
		{
			clearInterval(this.timerId);
			var code = e.keyCode ? e.keyCode : e.which;				
			v = e.target.value;	
			if (code == 13)
				this.load_data({search:v});
			else if ( v.length > 0 )
				this.timerId = setTimeout(this.load_data, 1200, {search:v});
		},
		initial_map(center)
		{
			this.map = new ymaps.Map('stores_map', {yandexMapDisablePoiInteractivity: false, center:center, controls:["zoomControl"], zoom: 12}, {suppressMapOpenBlock: true});
			this.map.behaviors.disable('scrollZoom'); 
		},
		click_pickup(k)
		{
			if ( this.storages[k] !== undefined )
				this.selected = k;
			let hover = getComputedStyle(document.documentElement).getPropertyValue('--main-hover-color');
			for ( i = 0; i < this.map.geoObjects.getLength(); i++) 
			{
				let color = i==k?hover:'#1e98ff';
				let point = this.map.geoObjects.get(i);
				point.options.set({preset: 'islands#icon', iconColor: color});
				if( i==k )
					this.map.panTo(point.geometry.getCoordinates(), {duration: 1000});
			}
		},
		close_pickup_points(e)
		{ 
			e.preventDefault();		
			this.parent.modal('select_pickup');
		}
	}		
}

var eventBus = new Vue();

var handler_checkout = {		
	data() {
		return {		
			page: 'basket',		
			tab: 'customer',
			coupon: '',	
			pointsArgs:null,
			timerId:null,			
			agree:1,				
			personal_data:1,
			codeError:false,
			errors:[],		
			errors_codes:{personal_data:0,agree:0,license:[]},
			license:[],	
			propertyGroups:[],			
			properties:{},
			types_payers:[],
			companies:[],
			loaded:true,
			send:false,		
			send:false,
			startVerification:false,					
			basket:null,
			selected:{shipping:0, storage_pickup:0, payment:0, company:0, type_payer:0, coupon:'', bonuses:0, address:0},
			address:{street:'',house:'',flat:'',floor:''},
			shipping_method:{},
			payment_method:{},
			customer:{bonuses:0},			
			recipient:0,
			cross_sells:'',
			gifts:[],
			selectAll:false			
		};
	},
	mixins: [edit_properties],
	watch: {
		personal_data(val, oldVal) 
		{ 
			this.errors_codes.personal_data = !val;
		},	
		selectAll(val, oldVal) 
		{ 
			for (k in this.basket.products)
				this.basket.products[k].cb = val;
		},		
		agree(val, oldVal) 
		{ 
			this.errors_codes.agree = !val;
		},	
		page(val, oldVal) 
		{ 
			usam_scroll_to_element('#basket');
			history.pushState(null, null, window.location.pathname.replaceAll(oldVal, this.page));
		}
	},
	mounted() {
		eventBus.$on('change_basket', (r) => {
			this.startVerification = true;
			this.preparationData( r );
		})
	  },
	methods: {		
		loadProperties()
		{ 
			if ( typeof basket_data !== typeof undefined )
				this.dataFormatting( basket_data );
			else
			{
				this.loaded = false;
				usam_api('basket', 'GET', this.dataFormatting);
			}
		},	
		dataFormatting(r)
		{ 		
			this.loaded = true;
			this.selected = r.selected;
			this.coupon = this.selected.coupon;			
			for (k in this.selected)
			{
				if (k == 'shipping')
					this.$watch('selected.'+k, this.update_shipping);
				else if (k == 'type_payer')
					this.$watch('selected.'+k, ()=> this.send_api( this.selected ));
				else
					this.$watch('selected.'+k, ()=> this.update());
			}
			this.preparationData( r );			
		},
		save_address()
		{ 				
			if ( this.address.street == '' || this.address.house == '' )
				return false;			
			let data = Object.assign({}, this.address);
			data.floor = parseInt( data.floor );
			if ( isNaN(data.floor) ) 
				data.floor = 0;						
			if ( this.selected.address )
				usam_api('address/'+this.selected.address, data, 'POST');
			else		
				usam_api('addresses', data, 'POST', (r) => this.selected.address = r.id);	
		},		
		update_shipping(val, oldVal)
		{
			this.shipping_method = this.get_method(this.basket.shipping_methods, this.selected.shipping);		
			if ( this.shipping_method.delivery_option )
				this.selected.storage_pickup = this.shipping_method.storage_pickup;
			this.update();
		},
		update( buy )
		{ 
			var data = {};
			Object.assign(data, this.selected);
			data.buy = !Number.isInteger(buy) ? 0 : buy;
			data.checkout = {};			
			for (k in this.properties)
				data.checkout[this.properties[k].code] = this.properties[k].value;			
			this.send_api( data );
		},
		send_api( data )
		{
			if( this.send || this.startVerification )
				return false;
			
			this.send = true;
			usam_api('basket', data, 'POST', (r) => {					
				this.send = false;
				if ( data.buy && r.order_id )
				{						
					var url = new URL( document.location.href );
					url.searchParams.append('submit_checkout', 1);									
					window.location.replace( url.toString() );
				}
				else			
					this.updateData( r );
			});
		},
		get_method( methods, selected )
		{
			for( k in methods )
			{
				if (methods[k].id == selected)
					return methods[k];
			}
			return {};
		},
		updateData( r )
		{
			if ( typeof r.error_message !== typeof undefined )		
				for( k in r.error_message )
					usam_notifi({'text': r.error_message[k], type:'error'});	
			this.preparationData( r );			
			usam_update_cart_counters( r.basket );
			eventBus.$emit('change_basket', r);
		},
		preparationData( r )
		{
			this.send = false;	
			this.startVerification = true;
			for (k in this.properties)
			{
				unwatch = this.properties[k].unwatch;
				unwatch();
			}
			this.handleData( r );						
		},
		handleData( r )
		{
			var new_prop = false;
			r.properties = this.propertyProcessing(r.properties);			
			this.properties = r.properties;
			for (k in this.properties)
				this.properties[k].unwatch = this.$watch(['properties', k].join('.'), this.updateBasket, {deep:true});
			this.propertyGroups = r.groups;			
			for (k in r.basket.products)
			{
				r.basket.products[k].cb = false;
				r.basket.products[k].confirm = '';
			}			
			this.basket = r.basket;			
			this.customer = r.customer;
			this.shipping_method = this.get_method(this.basket.shipping_methods, this.selected.shipping);
			this.payment_method = this.get_method(this.basket.payment_methods, this.selected.payment);							
		},					
		updateBasket(p, t)
		{ 
			if ( !this.startVerification )
			{
				this.propertyChange(p, t);			
				if ( !p.error && (p.field_type == 'postcode' || p.field_type == 'company' || p.field_type == 'location') )
				{ 			
					clearInterval(this.timerId);
					this.timerId = setTimeout(this.update, 10);
				}
			}
		},			
		verification()
		{ 
			var error = false;			
			this.startVerification = true;
			var i = 0;
			for (k in this.basket.agreements)
			{
				if ( !this.license.includes(this.basket.agreements[k].ID) )
				{
					error = true;
					Vue.set(this.errors_codes.license, i, this.basket.agreements[k].ID);
					i++;						
				}
			}
			if ( !this.agree )
			{
				this.errors_codes.agree = 1;
				error = true;
			}
			if ( !this.personal_data )
			{
				error = true;
				this.errors_codes.personal_data = 1;				
			}
			if ( this.propertiesVerification() )
				error = true;	

			this.startVerification = false;			
			return !error;
		},		
		buy(e)
		{
			if ( this.verification() )		
				this.update(1);
		},	
		deleteSelected(e)
		{
			var ids = [];
			for (let k in this.basket.products)
			{
				if ( this.basket.products[k].cb )
				{
					ids.push( this.basket.products[k].id );
					this.basket.products.splice(k, 1);
				}
			}
			if ( ids.length )
				usam_api('basket/products', {items: ids}, 'DELETE', this.updateData);	
		},		
		clear(e)
		{
			e.preventDefault();			
			this.send = true;
			usam_api('basket/clear', 'GET', this.updateData);
		},
		updateProducts()
		{			
			var p = [];
			var i = 0;
			for (k in this.basket.products)
			{
				p[i] = {id:this.basket.products[k].id, quantity:this.basket.products[k].quantity};
				i++;
			}			
			this.send = true;
			usam_api('basket/products', {products:p}, 'POST', this.updateData);
		},
		plus(k)
		{
			if ( this.send )		
				return false;			
			var g = parseFloat(this.basket.products[k].quantity) + this.basket.products[k].step_quantity;				
			if( this.basket.products[k].stock < g )
				return false;
			this.basket.products[k].quantity = g;			
			clearInterval(this.timerId);			
			this.timerId = setTimeout(this.updateProducts, 800);	
		},
		minus(k)
		{ 
			if ( this.send )
				return false;
			var g = parseFloat(this.basket.products[k].quantity) - this.basket.products[k].step_quantity;					
			if ( g <= 0 )		
				return false;
			
			this.basket.products[k].quantity = g;			
			clearInterval(this.timerId);			
			this.timerId = setTimeout(this.updateProducts, 800);	
		},		
		remove(k)
		{
			this.send = true;
			usam_api('basket/product', {id:this.basket.products[k].id}, 'DELETE', this.updateData);		
			this.basket.products.splice(k, 1);
		},	
		compare(k)
		{	
			this.userList(k, 'compare');
		},
		desired(k)
		{	
			this.userList(k, 'desired');
		},	
		userList(k, list)
		{	
			usam_update_product_list(list, this.basket.products[k].id);
			this.basket.products[k][list] = !this.basket.products[k][list];
		},		
		quick_view( id )
		{			
			quick_product_view( id );
		},
		add_gift( id )
		{
			this.send = true;
			usam_api('basket/new_product', {gift:1, product_id: id}, 'POST', this.updateData);
		},
		popup_hide()
		{			
			this.show = 0;
			var b = document.querySelector('.usam_backdrop');
			!b || b.remove();
			document.removeEventListener("click", this.popup_close);
		},
		is_type_payer_company()
		{
			for (let k in this.types_payers)
			{					
				if ( this.types_payers[k].type=='company' && this.types_payers[k].id == this.selected.type_payer )
					return true;
			}
			return false;
		},		
		select_pickup(e)
		{
			this.pointsArgs = {paged:1}
			for (let k in this.properties)
				if (this.properties[k].field_type == 'location' && this.properties[k].value)
				{
					this.pointsArgs.location_id=this.properties[k].value;
					break;
				}					
			this.$refs['modalpickup'].show = true;
		},
		selectedPickup(e)
		{
			this.basket.selected_storage_address = e.title;			
			this.selected.storage_pickup = e.id;	
		},
		updateProductRating(p, n)
		{
			if ( !p.myrating )
			{
				p.myrating = n;
				update_product_rating( p.product_id, n );
			}
		}
	},
	updated()
	{	
		this.startVerification = false;
	}
} 

var site_search = {	
	data() {
		return {				
			popular:[],	
			results:[],
			keyword:'',		
			isLoading:false,		
			active:false,					
			timerId: 0,		
			products: null,	
			categories: [],	
			category: 0,				
			categorylists: [],	
			count: 0,
			load_popular: false,				
		};
	},	
	watch: {
		category(val, oldVal) 
		{ 
			this.autocomplete_search();
		},	
	},	
	mounted() {
		this.keyword = keyword_search;			
	},
	methods: {
		get_popular() 
		{ 
			if ( this.load_popular )
				return;	
			d = new Date();
			var monthnum = d.getMonth()+1; 
			var year = d.getFullYear();
			usam_api('searching_results', {orderby:'count=desc,sum_results=desc', count:10, groupby:'phrase', fields:'phrase', monthnum:monthnum, year:year, conditions:[{key:'number_results',value:0,compare:'>',type:'NUMERIC'}]}, 'POST', (r) => {
				this.load_popular = true;
				this.popular = r.items;					
			});
		},				
		popular_word(k)
		{
			this.keyword=this.popular[k];
			this.autocomplete_search();
		},
		toggleActive(e) 
		{
			if ( this.active )				
				document.removeEventListener("click", this.removeActive);
			else
			{
				setTimeout( ()=>{ 
					this.$refs['search'].focus();
					document.addEventListener("click", this.removeActive ) 
				}, 100);				
			}
			this.active = !this.active;
		},
		focus(e) 
		{
			this.get_popular();
			this.active = true;	
			setTimeout(()=>{ document.addEventListener("click", this.removeActive ) }, 500);
		},
		removeActive(e)
		{ 
			if ( e.target.closest('.search_form') == null )
			{		
				this.active = false;			
				document.removeEventListener("click", this.removeActive);
			}
		},
		go_page_search(k)
		{ 
			if ( typeof this.$refs.more_results !== typeof undefined )
				window.location.replace( this.$refs.more_results.href );
		},
		searchPaste(e)
		{
			this.timerId = setTimeout( this.autocomplete_search, 100 );
		},
		autocomplete_change(e)
		{
			clearTimeout(this.timerId);				
			this.timerId = setTimeout( this.autocomplete_search, 100 );
		},
		autocomplete_select(list, k)
		{				
			this.filters[k].search=list.name; 
			this.filters[k].checked=list.id;
			document.querySelectorAll('.js-checklist-panel').forEach((el) => {el.hidden = true;});
		},
		autocomplete_search()
		{
			clearTimeout(this.timerId);	
			if ( this.keyword.length > 1)
			{
				for (let i in this.results) 
				{
					if (this.results[i].keyword == this.keyword)
					{
						this.products = this.results[i].products;
						this.categories = this.results[i].categories;
						this.count = this.results[i].count;
						return true;
					}
				}
				this.isLoading = true;		
				var data = {search: this.keyword, add_fields:'small_image,price_currency,sku,old_price', count:10, orderby:'default', order:'default', status:'publish', post_parent:0}
				if ( this.category )
					data.category = this.category;
				this.results.unshift({categories:[], products:[], count:0, keyword:this.keyword});
				usam_api('products', data, 'POST', (r) => {						
					this.products = r.items;
					this.count = r.count;	
					this.isLoading = false;	
					this.results[0].products = r.items;
					this.results[0].count = r.count;
				});				
				usam_api('categories', {search: this.keyword, count:10}, 'POST', (r) => {
					var reg = new RegExp(this.keyword.replace(/([.?*+^$[\]\\(){}|-])/g, ''), 'gi');
					this.categories = [];					
					for (let i in r.items) 
					{							
						r.items[i].name = r.items[i].name.replace(reg, '<span class="search_panel__category_word">$&</span>');	
						Vue.set(this.categories, i, r.items[i]);	
						this.results[0].categories = r.items[i];
					}			
				});
			}
		},	
		change_category(e)
		{
			this.category = e.id;	
		},		
	}
};

var add_product = {
	data() {
		return {
			id:0,
			defaultProduct:{post_title:'', post_content:'', post_status:'draft', not_limited:0, stock:0, under_order:0, category:0, price:'', images:[], attributes:{}},			
			mandatory: {post_title: true, post_content: true, category: true, price: true},
			imageViewing:0,
			product:{},			
			selectedCategories:[],
			categories:[],
			attributes:[],			
			searchAttributes:'',
			tab:'product',
			loaded: true,
			productLoaded: false,
			categoriesLoaded: false,
			codeError: false			
		};
	},
	computed: {		
		parentsCategories: function () {	
			return this.categories.filter(x => x.parent===0);
		},			
		importantAttribute: function () {	
			if ( this.product.attributes == undefined )
				return [];
			const asArray = Object.entries(this.product.attributes);
			const filtered = asArray.filter(x => x[1].important);			
			return Object.fromEntries(filtered);
		},
		mandatoryProperty: function () {	
			const asArray = Object.entries(this.mandatory);
			const filtered = asArray.filter(x => x[1]===false);			
			return Object.keys(Object.fromEntries(filtered));
		},		
		attributesColumns: function () {	
			var a = [];
			var s = this.searchAttributes.toLowerCase();
			for (let k in this.defaultProduct.attributes)
			{
				if ( this.searchAttributes == '' || this.defaultProduct.attributes[k].name.toLowerCase().includes(s)  )
					a.push(this.defaultProduct.attributes[k]);	
			}
			if ( !a.length )
				return [];
			let col = a.length/3;
			let i = 0;
			let letter = '';
			let columns = [];
			let column = 0;
			let attrs = [];			
			for (let k in a)
			{				
				letter = a[k].name.slice(0, 1 );
				if ( col < i )
				{
					column++;
					i = 0;
				}
				i++;
				if ( typeof attrs[column] === typeof undefined )
					attrs[column] = {};
				if ( typeof attrs[column][letter] === typeof undefined )
					attrs[column][letter] = [];						
				attrs[column][letter][attrs[column][letter].length] = a[k];							
			}
			return attrs;
		},
	},
	mounted() {		
		let url = new URL( document.location.href );
		if ( url.searchParams.get('id') )  
			this.id = url.searchParams.get('id');	
		this.loadAttributes();	
	},
	methods: {	
		loadProduct()
		{
			this.selectedCategories = [];
			usam_api('product/'+this.id, {add_fields:['sku','category','images','price','edit_attributes','stock','under_order']}, 'GET', this.loadingData);	
		},
		loadingData(r)
		{
			if ( !this.categoriesLoaded )
				setTimeout(this.loadingData, 100, r);
			else
				this.processProduct( r );
		},
		propertyProcessing(p)
		{		
			for (k in p)
			{
				if ( p[k].field_type=='COLOR_SEVERAL' || p[k].field_type=='M' )
					p[k].search = '';
				else if ( p[k].field_type=='rating' )
					p[k].hover = 0;					
				p[k].error = 0;	
				p[k].verification = false;
			}
			return p;
		},	
		processProduct(r)
		{
			this.productLoaded = true;
			if( r.category.length )
			{
				this.buildListСategories( r.category[0] );
				r.category = r.category[0].term_id;							
			}
			else
				r.category = 0;		
			if ( r.not_limited )
				r.stock = '';
			
			r.attributes = this.propertyProcessing(r.attributes);	
			this.product = r;
			for( let k in this.defaultProduct )
			{
				if( this.product[k] === undefined )
					Vue.set(this.product, k, this.defaultProduct[k]);				
			}	
			this.mandatoryPropertyProduct();
		},		
		defaultPropertyProduct()
		{			
			for( let k in this.defaultProduct )
				Vue.set(this.product, k, this.defaultProduct[k]);		
			this.selectedCategories = [];
			this.mandatoryPropertyProduct();
		},
		mandatoryPropertyProduct()
		{
			for (let k in this.mandatory)
			{ 
				this.$watch('product.'+k, ()=>{
					this.mandatory[k] = this.product[k]!=='' && this.product[k]!==0 ? true : false;
					this.codeError = false;
					for (let i in this.mandatory)
					{
						if ( !this.mandatory[i] )
						{
							this.codeError = i;
							break;
						}
					}					
				});
			}
		},
		loadCategories()
		{			
			usam_api('categories', {orderby: 'sort', order: 'ASC', count: 0}, 'POST', (r) => {
				for( let i in r.items )
				{
					r.items[i].depth = this.gerDepth(r.items, r.items[i], 0);					
					r.items[i].children = 0;
					for( let j in r.items )
					{
						if( r.items[i].parent == r.items[j].term_id )
							r.items[i].parent_name = r.items[j].name;
						if( r.items[i].term_id == r.items[j].parent )
							r.items[i].children++;						
					}
				}
				this.categories = r.items.sort((item1, item2) => { 				
					if ( item1['depth'] === item2['depth'] )
						return 0;
					else
						return item1['depth'] > item2['depth'] ? 1 : -1;
				})
				this.categoriesLoaded = true;
			});	
		},
		loadAttributes()
		{								
			usam_api('product_attributes', {orderby:'name', order:'ASC', count:0, add_fields:'options'}, 'POST', (r) => {				
				if( r.items.length )
				{
					r.items = this.propertyProcessing(r.items);
					for (let k in r.items)
					{
						r.items[k].value = '';
						r.items[k].related_ids = [];
						for (let i in r.items[k].related_categories)
							r.items[k].related_ids[i] = r.items[k].related_categories[i].term_id;
						Vue.set(this.defaultProduct.attributes, r.items[k].slug, r.items[k]);
					}				
				}
				let url = new URL( document.location.href );
				if ( url.searchParams.get('tab') )  
					this.tab = url.searchParams.get('tab');
			});	
		},		
		gerDepth(items, item, depth)
		{				
			depth++;
			if ( item.parent == 0 )
				return depth;				
			for (let i in items)
			{
				if ( items[i].term_id == item.parent )
					depth = this.gerDepth(items, items[i], depth);
			}
			return depth;
		},
		changeCategory(item)
		{
			this.product.category = 0;		
			if ( item.parent )
			{
				for (let i in this.selectedCategories)
				{
					if ( item.parent == this.selectedCategories[i] )
						this.selectedCategories.splice( Number(i)+1 );
				}
			}
			else
				this.selectedCategories = [];
		},
		openCategory(item)
		{				
			if ( this.selectedCategories.includes(item.term_id) )
				return false;								
			for (let i in this.selectedCategories)
			{
				if ( item.parent == this.selectedCategories[i] )
					this.selectedCategories.splice( Number(i)+1 );
			}
			this.selectedCategories.push(item.term_id);	
			this.product.category = item.children > 0 ? 0 : item.term_id;			
		},
		buildListСategories( term )
		{
			this.selectedCategories.unshift( term.term_id );	
			for( let i in this.categories )
			{				
				if( this.categories[i].term_id == term.parent )
					this.buildListСategories( this.categories[i] );
			}			
		},
		changeProductMeta( e )
		{
			this.product.productmeta[e.code] = e.id;
		},	
		imageDelete(k)
		{
			Vue.delete(this.product.images, k);
		},
		imageTurn(k)
		{
			usam_api('image/'+this.product.images[k].ID, {rotate:90}, 'POST');
		},	
		imageMain(k)
		{ 
			for( let i in this.product.images )
			{
				this.product.images[i].thumbnail = i==k;
				Vue.set(this.product.images, i, this.product.images[i]);
			}
		},			
		imageDrop(e)
		{
			e.target.nextElementSibling.click();
		},			
		imageUpload(e)
		{	
			for (var i = 0; i < e.target.files.length; i++)
			{						
				let n = this.product.images.length;
				this.product.images.push({load:true, percent:0, error:''});					
				var formData = new FormData();
				formData.append('file', e.target.files[i]);			
				usam_form_save( formData, ( r ) => {
					r.load = false;	
					r.thumbnail = n == 0;
					Vue.set(this.product.images, n, r);
				}, (p) => {				
					this.product.images[n].percent = p.loaded*100/p.total;	
				}, 'product/0/images' );
			}
		},
		getDataSave()
		{					
			this.codeError = false;
			for (let k in this.mandatory)
			{
				if ( !this.product[k] )
				{
					this.codeError = k;
					this.mandatory[k] = false;
				}
			}
			if ( this.codeError )
				return false;
			var data = {attributes:{}, image_gallery:[]};			
			for (let i in this.product.images)
			{
				data.image_gallery[i] = this.product.images[i].ID;
				if ( this.product.images[i].thumbnail )
					data.thumbnail_id = this.product.images[i].ID;
			}
			for (let k in this.product)
			{
				if ( k == 'price' && this.product[k] === '' )
					data.price = 0;
				else if ( k !== 'images' && k !== 'attributes' )
					data[k] = this.product[k];
			}
			for (let k in this.product.attributes)
			{
				if ( this.product.attributes[k].field_type == 'COLOR_SEVERAL' || this.product.attributes[k].field_type == 'M' )
				{
					data.attributes[this.product.attributes[k].slug] = [];
					for (let i in this.product.attributes[k].options)
					{
						if ( this.product.attributes[k].options[i].checked )
							data.attributes[this.product.attributes[k].slug].push(this.product.attributes[k].options[i].id);						
					}
				}
				else
					data.attributes[this.product.attributes[k].slug] = this.product.attributes[k].value;
			}			
			return data;
		},
		addProduct(e)
		{						
			var data = this.getDataSave();
			if ( data )
				usam_api('product', this.getDataSave(), 'POST', (r) => { 
					this.id = r;
					usam_notifi({text: USAM_THEME.message_saved})					
				});
		},
		updateProduct(e)
		{
			this.saveProduct(this.id, this.getDataSave());
		},
		saveProduct( id, data )
		{
			if ( data )
				usam_api('product/'+id, data, 'POST', () => usam_notifi({text: USAM_THEME.message_saved}));
		},
	}
}
if( document.getElementById('tracking') )
{
	new Vue({		
		el: '#tracking',		
		data() {
			return {tracking:'', history:{operations:[]}, tab:'search', tab:'search', no_data:false};
		},
		mounted() 
		{
			let url = new URL( document.location.href );	
			this.tracking = url.searchParams.get('track_id');
			if ( this.tracking )
				this.search();
		},
		methods: {
			search()
			{
				if ( this.tracking )
				{					
					usam_api('tracking/'+this.tracking, 'GET', (r) =>
					{
						if ( r.operations != undefined && r.operations.length )
						{
							this.no_data=false;
							this.history=r;
							this.tab='list';
						}
						else
							this.no_data=true;
							
					});
				}
			},	
			back()
			{		
				this.tab = 'search';
				this.tracking = '';
			}			
		}
	})
}
if( document.getElementById('search_my_order') )
{
	new Vue({		
		el: '#search_my_order',		
		data() {
			return {id:'', order:[], tab:'search', no_data:false};
		},
		mounted: function () 
		{
			let url = new URL( document.location.href );	
			this.id = url.searchParams.get('id');
			if ( this.id )
				this.search();
		},
		methods: {
			search()
			{ 
				if ( this.id )
				{					
					usam_api('order/status/'+this.id, 'GET', (r) =>
					{
						if ( r.id != undefined )
						{
							this.no_data=false;
							this.order = r;
							this.tab = 'list';
						}
						else
							this.no_data=true;
					});
				}
			},	
			back()
			{
				this.tab = 'search';
				this.id = '';
			}
		}
	})
}
if( document.getElementById('sets') )
{
	new Vue({		
		el: '#sets',		
		data() {
			return {sets:[], tab:0, buy:false};
		},
		watch:{				
			sets:{
				handler:function (val, oldVal) 
				{
					let totalprice = 0, n = 0, number = 0;					
					for (let k in this.sets)
					{
						totalprice = 0;
						number = 0;
						for (let i in this.sets[k].categories)
						{ 	
							let hidden = 0;	
							for (let j in this.sets[k].categories[i].products)
							{
								let p = this.sets[k].categories[i].products[j];					
								if ( p.status )
								{
									number++;
									n = p.price.value * p.quantity;									
								}
								else
								{
									n = 0;									
									hidden++;
								}
								p.total = {value: n, currency: to_currency(n)};													
								totalprice += n;
								this.sets[k].categories[i].products[j] = p;														
							}	
							this.sets[k].categories[i].hidden = hidden;							
						}	
						this.sets[k].number_products = number;								
						this.sets[k].totalprice = {value: totalprice, currency: to_currency(totalprice)};	
					}
				}, 
				deep: true
			}
		},
		mounted() 
		{				
			usam_api('sets', 'GET', (r) => {						
				this.loaded = true;				
				for (let k in r.items)
				{
					r.items[k].number_products = 0;
					for (let i in r.items[k].categories)
					{
						r.items[k].categories[i].all = 0;												
						r.items[k].categories[i].show_description = 0;
					}
					
				}
				this.sets = r.items;
			});	
		},
		methods: {				
			open_set(k)
			{		
				this.tab = k;
			},
			quick_view( id )
			{
				quick_product_view( id );
			},
			go_checkout(e)
			{		
				e.preventDefault();				
				if ( this.buy )
					return false;
				this.buy = true;
				var data = [];
				for (let i in this.sets[this.tab].categories)
				{ 	
					for (let j in this.sets[this.tab].categories[i].products)
					{
						let p = this.sets[this.tab].categories[i].products[j];
						if ( p.status )
							data.push({product_id:p.ID, quantity:p.quantity});					
					}
				} 
				if ( data.length )
				{
					var url = e.currentTarget.href;
					var handler = (r) => {						
						location.href = url;
					} 
					usam_api('basket/new_products', {items:data, clear:1}, 'POST', handler);
				}				
			},			
			plus(i,j)
			{				
				let p = this.sets[this.tab].categories[i].products[j];
				var g = parseFloat(p.quantity);
				g = g + p.unit;	
				if( p.stock < g )
					return false;
				this.sets[this.tab].categories[i].products[j].quantity = g;	
			},
			minus(i,j)
			{
				let p = this.sets[this.tab].categories[i].products[j];
				var g = parseFloat(p.quantity);
				g = g - p.unit;			
				if ( g <= 0 )		
					return false;				
				this.sets[this.tab].categories[i].products[j].quantity = g;	
			},
		}
	})
}
if( document.getElementById('media-viewer') )
{
	media_viewer = new Vue({		
		el: '#media-viewer',			
		data() {
			return {
				images:[],
				open:false,
				zoom:false,
				image_key:0,	
				fullScreen:false,		
				title:'',				
			};
		},		
		watch: {
			open() {				
				this.zoom = false;
			}
		},
		created() 
		{			
			this.setScreen();			
			document.addEventListener("webkitfullscreenchange", this.setScreen);
			document.addEventListener("mozfullscreenchange", this.setScreen);
			document.addEventListener("fullscreenchange", this.setScreen);	
		},
		methods: {				
			setScreen(e)
			{
				this.fullScreen = document.fullscreenElement?true:false;
			},
			fullScreenChange(e)
			{
				if (document.fullscreenElement)
					document.exitFullscreen();
				else
					document.documentElement.requestFullscreen();
			}		
		}
	})
}	
if( document.getElementById('compare_products_content') )
{
	new Vue({		
		el: '#compare_products_content',
		data() {
			return {
				numberSlide: 0,
				translate: 0,				
				category:0,
				categories:[],
				products:[],				
				product_attributes:[],
				attributes:[]
			};
		},
		watch: {
			category:function (val, oldVal)
			{ 							
				this.numberSlide = 0;
				setTimeout(()=>{
					this.$refs.slider.init();
				}, 200);
			}						
		},
		computed: {
			groups() {
				var r = [];
				for (let j in this.attributes)
				{
					if ( this.attributes[j].parent === 0 )
					{
						for (let k in this.product_attributes[this.categories[this.category].term_id])
						{							
							if ( this.attributes[j].term_id==this.product_attributes[this.categories[this.category].term_id][k].parent )
							{
								r.push(this.attributes[j]);
								break;
							}
						}
					}
				}
				return r;
			},
		},
		created() 
		{ 
			if ( typeof products !== typeof undefined && products.length )
			{
				this.product_attributes = product_attributes;
				this.products = products;				
				window.addEventListener('scroll', this.scrollPage);
				this.categories = categories;	
				for (let j in attributes)
					attributes[j].hide = false;
				this.attributes = attributes;
			}
		},
		methods: {			
			scrollPage(e)
			{
				var b = document.querySelector('.js-compare-products-fixed');
				var l = document.getElementById('compare_products_lists').getBoundingClientRect();
				if ( l.top < 200 )
				{
					if( !b.classList.contains('is-active') )
						b.classList.add('is-active');
				}
				else if( b.classList.contains('is-active') && l.top > 0 )
					b.classList.remove('is-active');
			},
			del(id)
			{
				usam_update_product_list('compare', id);
				for (let i in this.products)	
				{
					if ( this.products[i].product_id == id )
					{
						for (let j in this.categories)	
						{
							let n = this.categories[j].products.indexOf(id);
							if (n !== -1)
							{
								this.categories[j].products.splice(n, 1);
								if ( !this.categories[j].products.length )
								{
									this.categories.splice(j, 1);
									if ( this.categories.length && this.category == j )
										this.category = 0;
								}
							}
						}						
						this.products.splice(i, 1);					
						break;
					}
				}
				if ( this.products.length )
				{
					for (let i in this.product_attributes[this.category])
					{
						for (let j in this.product_attributes[this.category][i].values )
						{
							if ( this.product_attributes[this.category][i].values[j].product_id == id )
								this.product_attributes[this.category][i].values.splice(j, 1);
						}
					}
				}
				else
				{
					this.disableDragging();
					window.removeEventListener('scroll', this.scrollPage);
					this.product_attributes = [];
				}
			},			
		}		
	})
}


Vue.component('store-lists', {	
	props:{
		args:{required:false, default:null}
	},
	data() {
		return {
			storages:[],
			old_storages:[],
			selected:false,
			map:null,
			initial:true,
			tab:'list',
			screen:{},
			display_search:false,	
			isLoading:false,
			timerId: 0,
			Observer:{}								
		};				
	},									
	watch:{
		selected(v, old) 
		{		
			if( v !== old )
				this.$emit('change', this.storages[this.selected]);
		},
		args(v, old) 
		{
			if( JSON.stringify(v) !== JSON.stringify(old) )
				this.load_data( v );
		}
	},
	mounted() 
	{
		if( this.args !== null )
			this.load_data( this.args );
		this.Observer = new IntersectionObserver((entries, imgObserver) => {
			entries.forEach((e) => {
				if (e.isIntersecting)
				{ 
					if ( this.old_storages.length && this.isLoading == false )
					{
						var i = 0;					
						for (k in this.old_storages)
						{							
							this.storages.push(this.old_storages[k]);
							this.old_storages.splice(k, 1);									
							i++;
							if ( i > 10 )
								break;
						}							
					}
				}
			})
		}, {rootMargin: '0px 0px 100px 0px'});
		document.querySelectorAll('#load-store-list').forEach((v)=>{ this.Observer.observe(v); })
	},
	methods: {	
		load_data( data )
		{ 
			this.screen = window.screen;
			if ( !this.isLoading )
			{
				this.isLoading = true;
				usam_api('points_delivery', data, 'GET', (r) =>
				{ 
					if ( r.points )
					{						
						this.old_storages = [];
						this.storages = [];
						for (k in r.points)
						{		
							if ( k > 10 )	
								this.old_storages[k] = r.points[k];
							else
								this.storages[k] = r.points[k];	
						}						
						if ( !this.selected )
						{ 
							for (k in this.storages)
							{
								if ( this.storages[k].id == r.selected )
								{
									this.selected = k;
									break;
								}
							}	
						}								
						if ( this.storages.length>5 && this.display_search == false)
							this.display_search = true;			

						if ( typeof ymaps !== typeof undefined )
						{	
							setTimeout(()=>{								
								if ( this.initial )
								{  
									this.initial = false;
									this.initial_map([r.latitude, r.longitude]);
								}
								else
								{
									this.map.setCenter([r.latitude, r.longitude]);
									this.map.geoObjects.removeAll();	
								}
								for (k in r.points)
								{ 
									myPlacemark = new ymaps.Placemark([r.points[k].latitude, r.points[k].longitude], { 
										hintContent: r.points[k].title, 
										balloonContentHeader: r.points[k].title, 
										balloonContent: r.points[k].map_description,
										number: r.points[k].id
									});
									myPlacemark.events.add('click', (e) => {						
										var id = parseInt(e.get('target').properties._data.number);	
										this.selected = false;										
										for (k in this.storages)
										{					
											if ( id == this.storages[k].id )
											{												
												this.selected = parseInt(k);
												break;
											}
										}
										if ( this.selected === false )
											for (k in this.old_storages)
											{					
												if ( id == this.old_storages[k].id )
												{
													this.storages.push(this.old_storages[k]);
													this.old_storages.splice(k, 1);			
													this.selected = this.storages.length-1;
													break;
												}
											}				
										setTimeout(()=>{
											var top = this.$refs['lists'].querySelector('.js-select-store:nth-child('+this.selected+')').offsetTop;	
											this.$refs['lists'].scrollTo(0, top);	
										},10);										
									});
									this.map.geoObjects.add( myPlacemark ); 					
								}								
							},50);							
						}									
					}		
					this.isLoading = false;
				});
			}
		},
		search_enter(e)
		{
			clearInterval(this.timerId);
			var code = e.keyCode ? e.keyCode : e.which;				
			v = e.target.value;	
			if (code == 13)
				this.load_data({search:v});
			else if ( v.length > 0 )
				this.timerId = setTimeout(this.load_data, 1200, {search:v});
		},
		initial_map(center)
		{
			this.map = new ymaps.Map(this.$refs['map'], {yandexMapDisablePoiInteractivity: false, center:center, controls:["zoomControl"], zoom: 12}, {suppressMapOpenBlock: true});
			this.map.behaviors.disable('scrollZoom'); 
		},		
		click_pickup(k)
		{
			if ( this.storages[k] !== undefined )
				this.selected = k;
			let hover = getComputedStyle(document.documentElement).getPropertyValue('--main-hover-color');
			for ( i = 0; i < this.map.geoObjects.getLength(); i++) 
			{
				let color = i==k?hover:'#1e98ff';
				let point = this.map.geoObjects.get(i);
				point.options.set({preset: 'islands#icon', iconColor: color});
				if( i==k )
					this.map.panTo(point.geometry.getCoordinates(), {duration: 1000});
			}
		},
		close_pickup_points(e)
		{ 
			e.preventDefault();		
			this.$parent.closeModal();
		}
	},
	updated() {
		this.$nextTick(function () {					
			setTimeout(()=>{	
				var l = this.$refs['lists'];
				if( l )
				{ 				
					var height = (window.innerHeight) ? window.innerHeight :  document.documentElement.clientHeight || document.body.clientHeight || 0;	
					var m = this.$parent.$el;
					if ( m.offsetHeight > height )
					{				
						height = height-(m.offsetHeight-l.offsetHeight)-20;
						l.setAttribute("style", "height:"+height+"px;");	
						this.$refs['map'].setAttribute("style", "height:"+height+"px;");
						if ( this.display_search )
							height -= 56;
						height -= 30;
						l.setAttribute("style", "height:"+height+"px;");
					}						
				}	
			},10);						
		})						
	}
});
