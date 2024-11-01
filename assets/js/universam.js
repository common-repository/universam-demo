Vue.use(VueMask.VueMaskPlugin);
Vue.mixin({
   data() {
		return {
			$screen:{}
		};
	},
	created() {
		window.addEventListener("resize", this.onResize);
		this.onResize();
	},
	methods: {
        onResize() {
			this.$screen = {width: document.documentElement.clientWidth, height: document.documentElement.clientHeight}
		},
		localDate(date, format, brief) {
            return local_date(date, format, brief);
        },
        get_attachment_title(title) {
            return usam_get_attachment_title(title);
        },
    }
});

(function($)
{   	
	$.usam_get_modal = function(modal_type, data, handler)
	{ 				
		if ( jQuery('#'+modal_type).length )
			usam_set_height_modal( $('#'+modal_type) );
		else
		{ 
			if ( data == undefined )
				data = {};
			$('body').trigger( 'loading_modal' );
			add_backdrop();
			data.action = 'get_modal';
			data.nonce  = UNIVERSAM.get_modal;
			data.modal = modal_type;			
			usam_send(data, (r) =>
			{
				var m = document.querySelector('.modal.in');
				var b = document.querySelector('.usam_backdrop');
				!b || b.remove();
				jQuery('body').append( jQuery(r) );	

				if ( modal_type == 'availability_by_warehouses' )
				{
					setTimeout(()=> {
						new Vue({el:'#availability_by_warehouses_vue'})
					}, 300);
				}
				else if( modal_type == 'regions_search' )
				{
					setTimeout(()=>{				
						new Vue({		
							el:'#regions_search .region_selection',
							data() {
								return {
									location_id:0,
								}
							},
							watch:{
								location_id(val, oldVal) 
								{
									let url = new URL( document.location.href );
									url.searchParams.set('locid', val);
									window.location.replace(url.href);
								},
							},
							methods: {				
								change_location(e)
								{ 						
									this.location_id = e.id;
								}	
							}
						})
					}, 300);
				}	 
				if ( m )
					$('#'+modal_type).css({'z-index': 100001});
				$('#'+modal_type).trigger( 'append_modal' );
				usam_set_height_modal( $('#'+modal_type) );					
				if ( handler != undefined )
					handler();
			});
		}			
	};
})(jQuery);

function usam_item_remove( options ) 
{
	var defaults = {		
		button: '<span class="usam_notifi_action">Отменить</span>',
		item_delete_text: UNIVERSAM.item_delete_text,
		handler:null,
		el:null
	},
	options = Object.assign(defaults, options);
	if ( options.el !== null )
	{
		var el = options.el,
		el_parent = el.parentNode,
		i = Array.prototype.slice.call( el_parent.children ).indexOf( el );
		if ( el_parent.children.length - 1 == i )
		{
			var where = 'afterend';	
			i--;
		}
		else
			var where = 'beforeBegin';
		options.el.remove();	
	}
	let n = usam_notifi({text: options.item_delete_text, buttons: options.button, handler: options.handler});			
	n.querySelector('.usam_notifi_action').addEventListener('click', (e) => {
		e.target.closest('.usam_notifi').remove();
		options.callback( options.data );	
		if ( options.el !== null )
		{
			if ( el_parent.children.length )
				el_parent.children[i].insertAdjacentHTML(where, el.outerHTML);		
			else
				el_parent.innerHTML = el.outerHTML;	
		}
	})
}

function usam_notifi( options ) 
{	
	var defaults = {delay: 5000, text: '', type: 'info', buttons: '', position:'top', x: 25, y: 35, class_message: 'usam_notifi_message', class_button: 'usam_notifi_actions', handler:null};
	if ( typeof USAM_THEME !== typeof undefined && typeof USAM_THEME.notifi !== typeof undefined)
		defaults = Object.assign(defaults, USAM_THEME.notifi);
	options = Object.assign(defaults, options);
	content = '';
	if ( options.buttons )
		content = '<div class="'+options.class_button+'">'+options.buttons+'</div>';
	if ( options.text )
		content = '<div class="'+options.class_message+'">'+options.text+'</div>'+content;	
	if ( !content )
		return false;
	let div = document.createElement('div');
	div.className = "usam_notifi usam_notifi_animate usam_notifi_"+options.type;
	if ( options.position == 'top' )
	{
		var last_e = document.querySelector(".usam_notifi:last-child");	
		if ( last_e !== null )
		{
			var r = last_e.getBoundingClientRect();
			options.y += r.top+20;
		}
		div.style.top = options.y+'px';
		div.style.right = options.x+'px';
		div.style.bottom = 'auto';
	}
	else if ( options.position == 'bottom' )
	{
		div.style.bottom = options.y+'px';
		div.style.right = options.x+'px';
	}
	div.innerHTML = '<div class="usam_notifi_content">'+content+'<div class="usam_notifi_close"></div></div>';		
	document.body.append(div);
	div.addEventListener('click', (e) => { 
		if ( !e.target.classList.contains('usam_notifi_close') ) 
		{
			if ( e.target.closest('.usam_notifi') || e.target.classList.contains('usam_notifi') ) 
				return false;
		}
		e.target.closest('.usam_notifi').remove(); 
	});
	setTimeout(() => { 
		if ( options.handler !== null )
			options.handler();
		var r = div.getBoundingClientRect();
		div.remove(); 
		var n = document.querySelectorAll('.usam_notifi');
		if ( n !== null )
		{
			n.forEach((el) => { el.style.top = (parseInt(el.style.top,10) - parseInt(r.top+20,10))+'px'	});		
		}
	}, options.delay);		
	return div;
};

function usam_send(data, handler, method)
{
	if ( method == undefined )
		method = 'POST';
	data['usam_ajax_action'] = data['action'];
	data['action'] = 'usam_ajax';			
	var success = function(r)
	{ 
		if ( document.querySelector('.loader__full_screen') )
			document.querySelector('.loader__full_screen').remove();			
		if ( r !== null )
		{
			if ( typeof r.messages !== typeof undefined )
			{
				for (const message of r.messages)
				  usam_notifi({'text': message});	
				delete r.messages;				
			}					
			if ( typeof r.errors !== typeof undefined )
			{
				for (const message of r.errors)
				  usam_notifi({'text': message, type:'error'});	
				return;				  
			}
			if ( typeof r.download !== typeof undefined ) 
			{ 
				let a = document.createElement('a');
				a.className = "js-download";
				a.setAttribute('href', r.download);					
				a.setAttribute('download', r.title);		
				document.body.append(a);
				let el = document.querySelector('.js-download');
				el.click();
				el.remove();
				delete r.download;
			}
		}
		if ( handler != undefined )
			handler( r );
	};
	jQuery.ajax({type: method, url: ajaxurl, data: data, success: success, dataType: 'json'});
};	

function usam_api(route, data, method, handler)
{
	if ( data === "GET" || data === "POST" || data === "DELETE")
	{		
		if ( method != undefined )
			handler = method;
		method = data;		
		data = {};
	} 
	var admin = document.querySelector('#adminmenu')?true:false;	
	var d = {method: method, headers: {'Content-Type': 'application/json','X-WP-Nonce': usamSettings.nonce,'X-WP-Admin': admin}}
	if( method == 'GET' )
		route += '?' + new URLSearchParams(data);
	else
		d.body = JSON.stringify(data);		
	fetch(usamSettings.resturl+route, d)
	.then(r => r.json())
	.then(r => {
		if ( document.querySelector('.loader__full_screen') )
			document.querySelector('.loader__full_screen').remove();
		if ( typeof r.messages !== typeof undefined )
		{
			for (const message of r.messages)
				usam_notifi({'text': message});
			delete r.messages;			
		}
		if ( typeof r.redirection !== typeof undefined )
			document.location.href = r.redirection;
		if ( typeof r.download !== typeof undefined ) 
		{ 
			let a = document.createElement('a');
			a.className = "js-download";
			a.setAttribute('href', r.download);					
			a.setAttribute('download', r.title);		
			document.body.append(a);
			let el = document.querySelector('.js-download');
			el.click();
			el.remove();
			delete r.download;
		}
		if ( typeof r.errors !== typeof undefined )
		{
			if ( Array.isArray(r.errors) )
			{
				for (const message of r.errors)
				  usam_notifi({'text': message, type:'error'});
			}
			else
				usam_notifi({'text': r.errors, type:'error'});	
			return;				  
		}
		if ( handler != undefined )
			handler( r );
	})
	.catch((r) => { 
		usam_api_response_handler_fail( r );
	});
};

function wp_api(route, data, method, handler)
{ 
	if ( data === "GET" || data === "DELETE" )
	{		
		if ( method != undefined )
			handler = method;
		method = data;		
		data = {};
	} 
	var d = {method: method, headers: {'Content-Type': 'application/json','X-WP-Nonce': usamSettings.nonce}}
	if( method == 'GET' )
		route += '?' + new URLSearchParams(data);
	else
		d.body = JSON.stringify(data);		
	fetch(usamSettings.wp_resturl+route, d)
	.then(r => r.json())
	.then(r => {
		if ( document.querySelector('.loader__full_screen') )
			document.querySelector('.loader__full_screen').remove();	
		if ( typeof r.errors !== typeof undefined )
		{
			if ( Array.isArray(r.errors) )
			{
				for (const message of r.errors)
				  usam_notifi({'text': message, type:'error'});
			}
			else
				usam_notifi({'text': r.errors, type:'error'});	
			return;				  
		}
		if ( handler != undefined )
			handler( r );
	})
	.catch((r) => { 
		usam_api_response_handler_fail( r );
	});
}; 

function usam_api_response_handler_fail( r )
{
	if ( r.status != 200 )
	{
		if ( typeof r.responseJSON !== typeof undefined )
			console.log( r.responseJSON.message );
		else
			console.log(r);
	}
}

function usam_form_save( data, handler, onprogress, route )
{
	var url = route !== undefined ? usamSettings.resturl+route : ajaxurl;
	var admin = document.querySelector('#adminmenu')?true:false;
	var xhr = new XMLHttpRequest();
	xhr.open("POST", url);
	xhr.setRequestHeader("X-WP-Nonce", usamSettings.nonce);
	xhr.setRequestHeader("X-WP-Admin", admin);	
	xhr.responseType = 'json';	
	xhr.onload = (e)=>{
		var r = xhr.response;		
		if ( r === 0 ) 
		{
			usam_notifi({'text': 'Перезагрузите страницу и попробуйте еще раз', type:'error'});
			return;
		}
		if ( typeof r.messages !== typeof undefined )
		{
			for (const message of r.messages)
			  usam_notifi({'text': message});	
			delete r.messages;
		}				
		if ( typeof r.errors !== typeof undefined )
		{
			for (const message of r.errors)
			  usam_notifi({'text': message, type:'error'});	
			return;				  
		}
		if ( handler != undefined )
			handler( r );
	}
	if ( onprogress !== undefined )
		xhr.upload.onprogress = onprogress;	
	xhr.send(data);
	return xhr;
};

function sortable( options )
{
	var defaults = {rootEl: null, onUpdate: null, onProcess: null, handle: ''};
	options = Object.assign(defaults, options);
	options.rootEl = document.querySelector(options.rootEl);
	if ( !options.rootEl )
		return;
	var dragEl;	 
	var currentDragEl;   
	var dragElIndex;
	[].slice.call(options.rootEl.children).forEach(function (el){
		el.draggable = true;
	});
	function _onDragOver(e)
	{
	   e.preventDefault();
	   e.dataTransfer.dropEffect = 'move';      
	   var target = e.target;   
	   if( target && target !== dragEl )
	   { // Сортируем				
			let el;
			if( target.getAttribute('draggable') )
				el = target.nextSibling || target;	
			else if( target.closest('[draggable="true"]') )
				el = target.closest('[draggable="true"]').nextSibling;				
			if ( options.onProcess != null && el )
			{ 
				let k = Array.prototype.slice.call( el.parentNode.children ).indexOf( el );
				if ( k != dragElIndex )
				{							
					options.onProcess(k, dragElIndex);
					dragElIndex = k;					
				}
			}
			else
				options.rootEl.insertBefore(dragEl, el);	
	   }
	}
   
   function _onDragEnd(e)
   {
		e.preventDefault();
	  
		dragEl.classList.remove('ghost');
		options.rootEl.removeEventListener('dragover', _onDragOver, false);
		options.rootEl.removeEventListener('dragend', _onDragEnd, false);
		if ( options.onUpdate != null )
			options.onUpdate(dragEl, dragElIndex);
   }
   
	options.rootEl.onmousedown = function(e) {
		currentDragEl = e.target;
	};

   options.rootEl.addEventListener('dragstart', function (e)
   {
		if ( options.handle !== null && !currentDragEl.closest(options.handle) )
			e.preventDefault();					
		dragEl = e.target; // Запоминаем элемент который будет перемещать	
		dragElIndex = Array.prototype.slice.call( dragEl.parentNode.children ).indexOf( dragEl );
	   // Ограничиваем тип перетаскивания
		e.dataTransfer.effectAllowed = 'move';
		e.dataTransfer.setData('Text', dragEl.textContent);
		options.rootEl.addEventListener('dragover', _onDragOver, false);
		options.rootEl.addEventListener('dragend', _onDragEnd, false);
		setTimeout(function (){ dragEl.classList.add('ghost'); }, 0)
   }, false);
}

function usam_get_mini_loader( )
{	
	html = '<div id="circle-loader" class="circle_loader"><div class="circle_loader__circle circle_loader__circle1"></div><div class="circle_loader__circle circle_loader__circle2"></div><div class="circle_loader__circle circle_loader__circle3"></div><div class="circle_loader__circle circle_loader__circle4"></div><div class="circle_loader__circle circle_loader__circle5"></div><div class="circle_loader__circle circle_loader__circle6"></div><div class="circle_loader__circle circle_loader__circle7"></div><div class="circle_loader__circle circle_loader__circle8"></div><div class="circle_loader__circle circle_loader__circle9"></div><div class="circle_loader__circle circle_loader__circle10"></div><div class="circle_loader__circle circle_loader__circle11"></div><div class="circle_loader__circle circle_loader__circle12"></div></div>';
	return html;
}

function usam_scroll_to_element(id)
{				
	var el = document.querySelector(id);
	if ( el )
		el.scrollIntoView({behavior: 'smooth', block: 'start'})
}

function usam_addScript( src ) 
{		
	var script = document.createElement('script');
	script.src = UNIVERSAM.js_url+src;
	script.defer = script.type = 'text/javascript';
	document.getElementsByTagName('head')[0].appendChild( script );
	return script;
};

function usam_set_height_modal( modal ) 
{
	modal.modal();	
	modal.on('shown.bs.modal', function (e)
	{
		var w = document.querySelectorAll('#'+modal.attr('id')+' .modal-scroll');	
		if ( !w )				
			w = document.querySelectorAll('#'+modal.attr('id')+'.modal-scroll');			
		w.forEach((el) => {
			if ( el.hasAttribute('data-resize') )
			{
				if ( el.getAttribute('data-resize') == 0)
					return;
			}
			var window_top = el.getBoundingClientRect().top+document.querySelector('#'+modal.attr('id')).getBoundingClientRect().top*2;	
			var height = jQuery(window).height()-window_top;
			if ( window_top + el.offsetHeight > screen.height )
				el.setAttribute("style", "height:"+height+"px; overflow-y:auto;");
			else
				el.setAttribute("style", "max-height:"+height+"px; overflow-y:overlay; overflow-x:hidden;");
		});
	});
};
			
function usam_get_attachment_title( attachment_title ) 
{
	var ext = attachment_title.replace(/^.*[\.]/ig, ''),
		filename = attachment_title.replace(/^.*[\/\\]/ig, '');	
	if ( filename.length > 12 )
		filename = filename.substr(0, 12)+"..."+ext;
	return filename;
};

function formatFileSize(bytes) 
{
	if (typeof bytes !== 'number')
		return '';
	if (bytes >= 1000000000)
		return (bytes / 1000000000).toFixed(2) + ' GB';
	if (bytes >= 1000000)
		return (bytes / 1000000).toFixed(2) + ' MB';
	return (bytes / 1000).toFixed(2) + ' KB';
}	
   
function usam_set_url_attr(prmName, val)
{
	var res = '';
	var d = location.href.split("#")[0].split("?");
	var query = d[1];
	if(query) 
	{
		var params = query.split("&");
		for(var i = 0; i < params.length; i++)
		{
			keyval = params[i].split("=");
			if(keyval[0] != prmName) 
			{
				res += params[i] + '&';
			}
		}
	}
	if ( Array.isArray(val) )
		res += prmName + '=' + val.join(',');
	else
		res += prmName + '=' + val;
	history.replaceState( '' , '', d[0] + '?' + res );
	return false;
};	

function usam_get_url_attrs(url)
{
	var vars = [], hash;	
    var hashes = url.slice(url.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
};

function usam_copy_clipboard(e, text)
{
	var range = document.createRange();
    range.selectNode(e);
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(range);
    document.execCommand("copy");
    window.getSelection().removeAllRanges();
	if ( text != undefined )
		usam_notifi({'text': text});
}

function add_backdrop()
{
	let div = document.createElement('div');
	div.className = "usam_backdrop";
	document.body.append(div);
}

function validate_email(email) {
    var re = /[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/;
    return re.test(String(email).toLowerCase());
}

function validateURL(url) 
{
	try {
		new URL(url);
		return true;
	} 
	catch {
		return false;
	}
}

function getCookie(name) {
	let matches = document.cookie.match(new RegExp("(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"));
	return matches ? decodeURIComponent(matches[1]) : undefined;
}

function setCookie(name, value, options = {}) 
{
	options = {path: '/', ...options };
	if (options.expires instanceof Date) {
		options.expires = options.expires.toUTCString();
	}
	let updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value);

	for (let optionKey in options) 
	{
		updatedCookie += "; " + optionKey;
		let optionValue = options[optionKey];
		if (optionValue !== true)
			updatedCookie += "=" + optionValue;
	}
	document.cookie = updatedCookie;
}

function to_currency( v, c, d ) 
{
	if ( v === undefined )
		return '';
	c = c === undefined ? currency : c;
	d = d === undefined ? 2 : d;
	if ( typeof v == 'string' )
		v = Number(v);	
	return v.toFixed(d).toString().replace('.', decimal_separator).replace(/\B(?=(\d{3})+(?!\d))/g, thousands_separator)+" "+c;
}

function local_date(date, format, brief) 
{	
	if ( date === '' )
		return '';
	brief = brief !== undefined ? brief : true;	
	brief = format == "Y-m-d H:i:s" ? false : brief;
	let currentDate = new Date();	
	if ( typeof date != Object )
		date = new Date( date );	
	
	format = format === undefined ? 'd.m.Y H:i' : format;
	if ( format.includes('H:i') && brief && date.getMonth() == currentDate.getMonth() && date.getDate() == currentDate.getDate())
		format = 'H:i';	
	fmt = t => ("" + t).padStart(2, '0');	
	let m = date.getMonth()+1;
	format = format.replace('H', fmt(date.getHours()));
	format = format.replace('i', fmt(date.getMinutes()));
	format = format.replace('s', fmt(date.getSeconds()));	
	format = format.replace('d', fmt(date.getDate()));	
	format = format.replace('j', date.getDate());
	format = format.replace('m', fmt(m));
	format = format.replace('F', fmt(m));
	format = format.replace('M', fmt(m));	
	if ( currentDate.getFullYear() == date.getFullYear() && brief )
	{
		format = format.replace(/[.-/\s]Y/gim, '');
		format = format.replace(/Y/i, '');
		format = format.replace(/^[\.\-/\s]/ig,'');		
	}
	else
	{
		let y = date.getFullYear().toString();
		format = format.replace('Y',y);
		format = format.replace('y', y.substr(2));
	}
	return format;
}

function make( name, action, f) 
{	
	jQuery(document).on(action, name, f);
}

function usam_enlarge_photo(e)
{
	e.preventDefault();				
	let el = e.currentTarget;
	let src = el.tagName == 'IMG' ? el.getAttribute('src') : el.querySelector('img').getAttribute('src');
	let b = document.querySelector('.view_picture img');
	if ( !b )
	{
		let d = document.createElement('div');
		d.classList.add('view_picture');
		d.classList.add('is-active');			
		d.innerHTML = '<svg class="view_picture__close" shape-rendering="geometricPrecision" xmlns="http://www.w3.org/2000/svg"><use xlink:href="'+svgicon+'#close"></use></svg><img src="'+src+'">';
		document.body.appendChild(d);
	}
	else
	{
		document.querySelector('.view_picture').classList.add('is-active');
		b.setAttribute('src', src);
	}
	var f = (ev)=> {			
		document.querySelector('.view_picture').classList.remove('is-active');
		document.removeEventListener("click", f);
	};
	setTimeout(() => { document.addEventListener("click", f); }, 30);
}

function usam_lazy_image() 
{ 		
	const imageObserver = new IntersectionObserver((entries, imgObserver)=>{
		entries.forEach((e) => {
			if (e.isIntersecting)
			{
				if ( e.target.tagName == 'IMG' )
					e.target.src = e.target.dataset.src;
				else
					e.target.style.backgroundImage = "url("+e.target.dataset.src+")";
				e.target.classList.remove("js-lzy-img");				
				imgObserver.unobserve(e.target);
			}
		})
	}, {rootMargin: '0px 0px 50px 0px'});
	document.querySelectorAll('.js-lzy-img').forEach((v) => imageObserver.observe(v)) 
}	
document.addEventListener("DOMContentLoaded", usam_lazy_image);

const obsIMG = new MutationObserver(mutationList =>  
	mutationList.filter(e => e.type === 'childList').forEach(e => { 
		if( e.target.querySelector('.js-lzy-img') )
			usam_lazy_image();	
	})
 );  
obsIMG.observe(document,{childList:true});  

var data_filters = {
	data() {
		return {					
			filtersData:{},
			screen_id:'',
			filter_id:0,
			search:'',
			filters:[],		
			groupby_date:'',
			period:'',
			daterange:{},		
			page_sorting:'',				
			sort_options:{},
			addList:false,
			loadFiltersNow:false,
			filterSearch:false,
			window:'default'
		};
	},
	mounted() {		
		if ( typeof USAM_Admin !== typeof undefined )
			this.screen_id = USAM_Admin.screen_id;
		if ( typeof filtersSettings !== typeof undefined && filtersSettings[this.window] !== undefined )
		{
			for (let j in filtersSettings[this.window])
				this[j] = filtersSettings[this.window][j];
		}		
		let url = new URL( document.location.href );	
		if ( url.searchParams.has('filter_id') )
			this.filter_id = url.searchParams.get('filter_id');
		if ( url.searchParams.has('s') )
			this.search = url.searchParams.get('s');		
	},				
	methods: {
		table_view( data ) 
		{									
			usam_active_loader();	
			USAM_Tabs.table_view( data, jQuery('.usam_tab_table') );
		}	
	}
}
				

Vue.component('interface-filters', {
	props:{
		ifilters:{type:Array, required:false, default:[]},
		screen_id:{type:String, required:false, default:''},
		filter_id:{type:Number, required:false, default:0},
		s:{type:String, required:false, default:''},
		range:{type:Object, required:false, default:()=>{ return {start:'', end:''} }},		
		period:{type:String, required:false, default:''},
		groupby_date:{type:String, required:false, default:''},
		page_sorting:{type:String, required:false, default:''},
		sort_options:{type:Object, required:false, default:{}},	
		loadFiltersNow:{type:Boolean, required:false, default:false},
		filterSearch:{type:Boolean, required:false, default:false},
		addList:{type:Boolean, required:false, default:false},		
	},	
	data() {
		return {
			search:this.s,
			filters:[],	
			selectedFilters:[],
			daterange:this.range,
			dateperiod:'',
			timerId:0,			
			request:true,
			filter_name:'',
			show_filters:false,				
			selected_filter:{},
			sorting:'',		
			searchHiddenFilters:'',	
			save_filters:null,
			changeDate:false,
			typeObjects: 'companies',
			objectsCRMQuery: {
				companies: 'company',
				contacts: 'contact',
				orders: 'order',
				leads: 'lead',
				invoices: 'invoice',
				suggestions: 'suggestion',
				contracts: 'contract',
				products: 'product'
			},
			propertyKey:null
		};
	},	
	watch:{
		ifilters(v, oldVal) 
		{			
			this.filters = v;			
			for (let k in this.filters)
				this.$watch(['filters', k, 'show'].join('.'), this.saveFilters);
			this.loadFiltersSettings();				
		},
		range(v, oldVal) 
		{
			if( Object.keys(v).length )
			{
				if( v.start )
					this.daterange.start = v.start;
				if( v.end )
					this.daterange.end =  v.end;
			}
		},		
		period(v, oldVal) 
		{
			this.dateperiod = v;
		},
		page_sorting(v, oldVal) 
		{			
			this.sorting = v;
		},
		s(v, oldVal) 
		{			
			this.search = v;
			this.cursorEndField( this.$refs.search );
		}		
	},	
	computed:
	{
		filtersData() 
		{ 
			let	d = {};				
			let code = '';
			var r = [];
			for (let k in this.filters)
			{
				if( !this.filters[k].show )
					continue;				
				code = this.filters[k].code;
				if ( this.filters[k].type=='numeric' || this.filters[k].type=='date' )
				{ 		
					if ( this.filters[k].from !== '' )
						d[code] = this.filters[k].from;
					if ( this.filters[k].to !== '' )
					{
						if ( this.filters[k].from === '' )
							d[code] = '|'+this.filters[k].to;	
						else
							d[code] += '|'+this.filters[k].to;
					}
					if ( this.filters[k].from || this.filters[k].to )
						r.push(this.filters[k]);
				}
				else if ( this.filters[k].type=='checkbox' )
				{
					d[code] = this.filters[k].value?1:0;
					if ( this.filters[k].value )
						r.push(this.filters[k]);
				}
				else if ( this.filters[k].type=='string' || this.filters[k].type=='string_meta' )
				{
					if( Array.isArray(this.filters[k].value) && this.filters[k].value.length || !Array.isArray(this.filters[k].value) && this.filters[k].value !== '' || this.filters[k].checked == 'exists' || this.filters[k].checked == 'not_exists' )
					{						
						d['v_'+code] = this.filters[k].value;
						d['c_'+code] = this.filters[k].checked;
						r.push(this.filters[k]);
					}						
				}										
				else if ( this.filters[k].type=='select' )
				{
					if ( this.filters[k].value )
					{
						d[code] = this.filters[k].value;					
						r.push(this.filters[k]);
					}
				}
				else if( this.filters[k].type=='objects' || this.filters[k].type=='autocomplete' || this.filters[k].type=='counterparty' )
				{
					if( this.filters[k].value.length )
						r.push(this.filters[k]);
					if ( this.filters[k].options.length )
					{
						if( this.filters[k].type=='objects' )
							r.push(this.filters[k]);
						for (let i in this.filters[k].options)
						{
							if( this.filters[k].type=='objects' )
								code = 'o_'+this.filters[k].options[i].object_type;
							else if( this.filters[k].type=='counterparty' )
								code = this.filters[k].request;
							if( d[code] === undefined )
								d[code] = [this.filters[k].options[i].id];	
							else
								d[code].push(this.filters[k].options[i].id);
						}
					}
				}
				else if( this.filters[k].type=='checklists' )
				{
					if ( this.filters[k].value.length )
					{
						r.push(this.filters[k]);
						d[code] = this.filters[k].value;
					}
				}
				else if ( this.filters[k].type=='period' )
				{
					if ( this.filters[k].period )
					{
						r.push(this.filters[k]);
						d[code] = this.filters[k].period;
						if ( this.filters[k].period == 'month' || this.filters[k].period == 'quarter' || this.filters[k].period == 'year' )
						{
							d[this.filters[k].period] = this.filters[k].interval;	
							d['year'] = this.filters[k].year;	
						}
						else if ( this.filters[k].period == 'year' )
							d[this.filters[k].period] = this.filters[k].year;	
					}			
				}
			}			
			this.selectedFilters = r;
			if( this.groupby_date ) 			
				d['groupby_date'] = this.groupby_date;					
		
			if ( this.dateperiod )							
				d['period'] = this.dateperiod;	
			else
			{
				if( this.daterange.start )
					d['date_from'] = this.daterange.start;
				if( this.daterange.end )
					d['date_to'] = this.daterange.end;
			}		
			if ( this.search )	
				d.s = this.search;	
			this.$emit('calculation', d);
			return d;
		},
	},
	methods: {		
		changeDateRange(e)
		{		
			if( this.daterange.start != e.start || this.daterange.end != e.end )
			{
				this.daterange = e
				this.dateperiod = '';
				this.timeoutFilterPageData();				
			}
		},		
		timeoutFilterPageData()
		{
			clearTimeout(this.timerId);
			this.timerId = setTimeout(() => {
				if( !this.changeDate )
					this.filterPageData();
				else
					this.timeoutFilterPageData();
			}, 1000 );			
		},
		loadFiltersSettings()
		{		
			let load = this.filtersData.length>0;	
			if ( this.filter_id )
				this.loadFilterTemplates();					
			if ( this.loadFiltersNow || load )
				this.prepareFilters();				
		},		
		loadFilterTemplates()
		{
			if ( this.save_filters !== null || !this.screen_id )
				return;
			this.save_filters = [];			
			usam_api('admin/filters', {screen_id:this.screen_id}, 'GET', (r) => { 
				this.save_filters = r;	
				for (let k in this.save_filters)
				{
					if( this.save_filters[k].id == this.filter_id )
					{
						this.selected_filter = {name: this.save_filters[k].name, k: k};	
						break;
					}
				}
			});
		},
		loadSelectedFilters( selected )
		{ 	
			let s;
			for (let k in this.filters)
			{
				s = selected[this.filters[k].code];
				if ( this.filters[k].type=='numeric' || this.filters[k].type=='date' )
				{ 
					let from = '';
					let to = '';
					if ( s !== undefined )
					{
						n = s.split('|');							
						from = n[0];
						if ( n[1] !== undefined )
							to = n[1];
					}
					Vue.set(this.filters[k], 'from', from);
					Vue.set(this.filters[k], 'to', to);
				}
				else if ( this.filters[k].type=='checkbox' )
					this.filters[k].value = s !== undefined?s:0;
				else if ( this.filters[k].type=='string' || this.filters[k].type=='string_meta' )
				{						
					if( selected['v_'+this.filters[k].code] === undefined )
						this.filters[k].value = '';
					else
						this.filters[k].value = selected['v_'+this.filters[k].code];
					if( selected['c_'+this.filters[k].code] === undefined )
						this.filters[k].checked = 0;
					else
						this.filters[k].checked = selected['c_'+this.filters[k].code];
					
				}
				else if ( this.filters[k].type=='select')							
					this.filters[k].value = s !== undefined?s:0;
				else if ( this.filters[k].type=='checklists' || this.filters[k].type=='autocomplete' || this.filters[k].type=='counterparty' || this.filters[k].type=='objects' )
				{
					if( s === undefined )
						s = [];
					this.filters[k].value = s;				
				}
				else if ( this.filters[k].type=='period' )
				{				
					Vue.set(this.filters[k], 'period', s);
					Vue.set(this.filters[k], 'interval', selected.interval !== undefined?selected.interval:'');	
					Vue.set(this.filters[k], 'year', selected.year !== undefined?selected.year:'');	
				}		
			}
		},	
		sidebar(type, k) {
            this.propertyKey = k;
            this.$refs['modal' + type].show = !this.$refs['modal' + type].show;
        },
		selectObjects(e) {
			this.filters[this.propertyKey].value.push(e.id);
			e.object_type = this.objectsCRMQuery[this.typeObjects];
			this.filters[this.propertyKey].options.push(e);
		},
		clearSearch(k)
		{					
			this.filters[k].search='';			
			this.$refs['filter_search'+k][0].focus();					
		},			
		getCheckedOptions( filter )
		{					
			let options = [];
			for (let i = 0; i < filter.options.length; i++)	
			{
				if ( filter.value.includes(filter.options[i].id) )
					options.push(filter.options[i]);
			} 
			return options;
		},
		open_filter(k, e)
		{					
			e.preventDefault();
			this.selected_filter = {name: this.save_filters[k].name, k: k};	
			this.loadSelectedFilters( this.save_filters[k].setting );				
			this.prepareRequestData(this.save_filters[k].setting);					
		},
		cancelFilter(e)
		{ 
			e.preventDefault();
			this.selected_filter = {};
			this.reset_filters();
			this.prepareRequestData();		
		},				
		startFilter(e) 
		{ 
			e.preventDefault();
			if ( this.filterSearch )
				this.search = this.$refs.search.textContent;
			this.filterPageData();	
		},		
		reset_filters()
		{ 
			this.selectedFilters = [];
			this.daterange = {start:'', end:''};
			for (let k in this.filters)
				this.reset_filter(k);
		},			
		cancel_selected_filter(i, code)
		{ 				
			this.$delete(this.selectedFilters,i);
			for (let k in this.filters)
				if( this.filters[k].code == code )
				{					
					this.reset_filter(k);
					break;
				}							
			this.prepareRequestData( this.filtersData );	
		},	
		reset_filter(k)
		{				
			if ( this.filters[k].type=='numeric' || this.filters[k].type=='date' )
			{					
				this.filters[k].from = '';
				this.filters[k].to = '';				
			}
			else if ( this.filters[k].type=='checkbox' && this.filters[k].type=='select' )
				this.filters[k].value = '';
			else if ( this.filters[k].type=='string' || this.filters[k].type=='string_meta' )
			{
				this.filters[k].value = '';
				this.filters[k].checked = '';
			}
			else if ( this.filters[k].type=='checklists' )
				this.filters[k].value = [];
			else if ( this.filters[k].type=='autocomplete' || this.filters[k].type=='objects' || this.filters[k].type=='counterparty' )
				this.filters[k].options = [];
			else if ( this.filters[k].type=='period' )
				this.filters[k].period = '';
			
			let url = new URL( document.location.href );
			if ( this.filters[k].type=='string' || this.filters[k].type=='string_meta' )
			{
				url.searchParams.delete('v_'+this.filters[k].code);
				url.searchParams.delete('c_'+this.filters[k].code);					
			}	
			else if( this.filters[k].type=='objects' )
				url.searchParams.delete('o_'+this.filters[k].options[i].object_type);
			else if( this.filters[k].type=='counterparty' )
				url.searchParams.delete(this.filters[k].request);
			else
				url.searchParams.delete(this.filters[k].code);
			history.pushState({'url' : url.href}, '', url.href);	
		},
		delete_filter( k )
		{ 					
			var id = this.save_filters[k].id;
			this.save_filters.splice(k, 1);
			usam_api('admin/filters', {id:id}, 'DELETE');
		},
		add_filter(e)
		{ 					
			e.preventDefault();
			if ( this.filter_name == '' || !this.screen_id )
				return false;
			usam_api('admin/filters', {name:this.filter_name, filters: this.filtersData, screen_id: this.screen_id}, 'POST', (r) => this.save_filters.push(r));	
			this.filter_name = '';
		},
		filterPageData()
		{ //Фильтровать данные
			clearTimeout(this.timerId);
			var	data = this.filtersData;
			if ( this.sorting )
			{					
				if ( document.querySelector('.js-table-orderby') )
				{
					sort = this.sorting.split('-');					
					document.querySelector('.js-table-order').value = sort[1];
					document.querySelector('.js-table-orderby').value = sort[0];				
				}
				data.page_sorting = this.sorting;
				let str = data.page_sorting.split('-');
				data.orderby = str[0];
				data.order = str[1];				
			}
			this.prepareRequestData( data );
		},	
		addOptions(k, e)		
		{
			this.filters[k].value.push(e.id);
			Vue.set(this.filters[k].options, this.filters[k].options.length, e);
		},
		deleteOptions(p, i)		
		{
			p.value.splice(p.value.indexOf(p.options[i]), 1);
			this.$delete(p.options,i)
		},
		prepareRequestData( data )
		{
			if ( data === undefined ) 
				data = {};
			this.show_filters = false;		
			document.removeEventListener("click", this.close_filters);
			if ( jQuery(".js-open-filters").length )
				this.scrollTop();			
			this.$emit('change', data);
		},					
		cursorEndField(e) 
		{		
			var range = document.createRange();
			range.selectNodeContents(e);
			range.collapse(false);
			var sel = window.getSelection();
			sel.removeAllRanges();
			sel.addRange(range);			
		},	//при вставке скопированного
		search_paste(e) 
		{ 
			e.preventDefault();
			var clipboardData = e.clipboardData || window.clipboardData;
			e.target.innerHTML = clipboardData.getData('Text');
			this.cursorEndField( e.target );
		},						
		search_item(e) {
			if (e.inputType === 'insertParagraph')
				e.preventDefault();
			e.target.innerHTML = this.$refs.search.textContent;
			this.cursorEndField( e.target );
		},
		search_enter(e) 
		{			
			if (e.code === 'Enter')
			{
				e.preventDefault();
				this.search = this.$refs.search.textContent;
				this.filterPageData();
			}				 
		},
		search_button(e) 
		{
			this.search = this.$refs.search.textContent;
			this.filterPageData();				 
		},
		select_period(e) 
		{
			this.dateperiod = '';					
			this.filterPageData();	
		},	
		selectFilterDate( period, e ) 
		{		
			e.preventDefault();
			this.dateperiod = period;		
			ms = 86400000;  // миллисекунд в сутках
			var end = currentDate = new Date; 		
			switch ( this.dateperiod ) 
			{				
				case 'today':							
					start = currentDate;  // текущая дата		
				break;		
				case 'yesterday':							
					start = end = new Date(currentDate - ms); 
				break;		
				case 'last_7_day':							
					start = new Date(currentDate - (7*ms)); 
				break;	
				case 'last_30_day':							
					start = new Date(currentDate - (30*ms)); 					
				break;			
				case 'last_365_day':							
					start = new Date(currentDate - (365*ms)); 
				break;					
				case 'last_1825_day':							
					start = new Date(currentDate - (1825*ms)); 
				break;
				case 'last_3650_day':							
					start = new Date(currentDate - (3650*ms)); 
				break;				
			}
			this.daterange = {start: this.localDate(start, 'Y-m-d', false)+' 00:00:00', end: this.localDate(end, 'Y-m-d H:i:s', false)}
			this.filterPageData();	
		},					
		filter_focus(e)
		{
			document.querySelectorAll('.js-checklist-panel').forEach((el) => {el.hidden = true;});
			var el_box = e.target.closest(".checklist");	
			var el_list = el_box.querySelector('.js-checklist-panel');				
			el_list.hidden = false;
			var f = (ev)=> {
				let el = ev.target.closest('.checklist');
				if ( el == null || el.id != el_box.id )
				{
					el_list.hidden = true;
					document.removeEventListener("click", f);							
				}
				else
					return false;
			};
			setTimeout( ()=>{ document.addEventListener("click", f ) }, 500);		
		},
		close_filters(e)
		{
			if( !e.target.classList.contains("button_delete") && e.target.closest('.js-page-filters') == null && e.target.closest('.modalSidebar') == null )
			{ 
				this.show_filters = false;
				document.removeEventListener("click", this.close_filters);
			}
		},
		open_filters(e)
		{
			this.show_filters = this.show_filters?false:true;
			if ( this.show_filters )						
				setTimeout( ()=>{ document.addEventListener("click", this.close_filters) }, 500);
			this.prepareFilters();
		},
		sortable(k, i)
		{						
			let v = structuredClone(this.filters[i]);
			this.filters.splice(i, 1);	
			this.filters.splice(k, 0, v );
		
		},			
		toggleFilter(k)
		{
			this.filters[k].show=!this.filters[k].show;
			this.saveFilters();
		},
		saveFilters()
		{
			let codes = [];
			for (let k in this.filters)
			{
				if ( this.filters[k].show )
					codes[k] = this.filters[k].code;	
			}
			usam_api('filters', {filters: codes, screen_id: this.screen_id}, 'PUT');
		},
		filtersRestore()
		{
			for (let k in this.filters)
				this.filters[k].show = this.filters[k].show_default;
			usam_api('filters', {screen_id: this.screen_id}, 'PUT');
			this.scrollTop();
		},
		scrollTop()
		{
			document.querySelector(".js-open-filters").scrollIntoView({behavior:'smooth', block:'start'});
		},
		prepareFilters()
		{		
			if ( !this.request )
				return false;
			this.loadFilterTemplates();
			sortable({rootEl:'.js-filters', onUpdate:this.saveFilters, onProcess:this.sortable, handle: '.js-drag'});			
			var data = {};
			let code = '';
			for (let k in this.filters)
			{
				code = this.filters[k].code;				
				if ( this.filters[k].type=='checklists' ) 
					data[code] = this.filters[k].query !== undefined?this.filters[k].query:k;
				else if ( this.filters[k].type=='select' ) 
				{ 
					if ( !this.filters[k].options.length ) 
						data[code] = this.filters[k].query !== undefined?this.filters[k].query:k;
				}
				else if ( this.filters[k].type=='autocomplete' || this.filters[k].type=='counterparty' ) 
				{ 							
					if( this.filters[k].value.length )
					{
						usam_api(this.filters[k].request, {include:this.filters[k].value, fields:'autocomplete'}, 'POST', (r) => {
							for (let i in r.items)
								this.filters[k].options.push(r.items[i]);	
						});
					}
				}
			}	
			this.request = false;
			if ( Object.keys(data).length )
				this.loadFilters( data );
		},
		loadFilters( data )
		{	
			usam_api('filters', data, 'POST', (r) => {
				for (let k in r)
				{					
					for (let i in this.filters)
					{						
						if (this.filters[i].code == k)
						{
							var options = [];
							for (let j in r[k])								
								options.push(typeof r[k][j] === "string" || typeof r[k][j] === "number" ? {id:j, name:r[k][j], checked:false} : r[k][j]);	
							if ( this.filters[i].type=='checklists' )
								Vue.set(this.filters[i], 'checked', this.filters[i].value.length);
							Vue.set(this.filters[i], 'options', options);
							break;
						}
					}
				}	
			});
		}				
	}
})

var edit_properties = {
	data() {
		return {		
			step: 0,	
			propertyGroups:[],		
			properties:{},
			codeError:false,
			confirm:true			
		};
	},
	computed: {
		main_groups() {
			return this.propertyGroups.filter((i) => i.parent_id === 0);
		},
	},
	methods: {
		propertiesVerification( group )
		{
			if ( typeof group === typeof undefined )
				group = 'all';	
			this.codeError = false;
			for (let k in this.properties)
			{
				if ( this.properties[k].group == group || group == 'all' )
				{
					if ( this.propertyVerification( this.properties[k] ) )
						this.codeError = 'property_verification';
				}
			}
			return this.codeError;
		},
		propertyChange(p, t)
		{			
			this.propertyVerification(p);			
			this.codeError = p.error ? 'property_verification' : false;
		},
		propertyVerification(p) 
		{										
			if ( p.mandatory && p.value === '' && p.field_type=='rating' )
				return false;
			var error = false;
			var verification = p.field_type=='COLOR_SEVERAL' || p.field_type=='M' || p.field_type=='checkbox' ? p.value.length==0 : !p.value;
			if ( p.mandatory )
				error = verification;
			if ( !error && p.value )
			{
				var r = '';
				switch ( p.field_type ) 
				{			
					case 'mobile_phone':						
						r = /^[\d\+][\d\(\)\ -]{4,14}\d$/;
					break;
					case 'pass':			
						if ( p.value.toString().length < 6 )
							error = 'pass_smalllength';							
					break;
					case 'email':
						r = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
						p.value = p.value.replace(/[\s+/+?+,+\\]/g, '');
					break;			
				}				
				if ( r )
					error = !r.test(p.value);
			}
			p.error = error;
			p.verification = !verification && !p.error;
			return error;
		},		
		check_group(code)
		{
			for (k in this.properties)
			{
				if ( this.properties[k].group==code )
					return true;
			}
			return false;
		},	
		propertyProcessing(p)
		{		
			for (k in p)
			{
				if ( p[k].field_type=='click_show' )
					p[k].show = 0;
				else if ( p[k].field_type=='rating' )
					p[k].hover = 0;
				if ( p[k].value === undefined )
					p[k].value = null;	
				p[k].error = false;	
				p[k].verification = false;
			}
			return p;
		},	
		preparationData(r)
		{ 
			this.propertyGroups = r.groups;
			this.properties = this.propertyProcessing(r.properties);	
			delete r.groups;
			delete r.properties;			
		//	for (k in this.properties)
		//		this.$watch(['properties', k].join('.'), this.propertyChange, {deep:true});
		},
		change_company(e)
		{				
			this.properties[e.code].value = e.name;
			for (k in this.properties)
			{
				if ( typeof e[k] !== typeof undefined )
				{
					this.properties[k].value = e[k];					
					if ( this.properties[k].field_type=='location' )
						this.properties[k].search = e._name_legallocation;
				}
			}
		},
		fileDelete(k, i)
		{
			if ( this.properties[k].type == 'file' )
			{
				Vue.delete(this.properties[k], 'file');
				this.properties[k].value = 0;
			}
			else
			{				
				this.properties[k].value = this.properties[k].value.filter((n) => n !== this.properties[k].files[i]);
				Vue.delete(this.properties[k].files, i);
			}
		},
		fileDrop(e, k)
		{ 
			e.preventDefault();
			e.currentTarget.classList.remove('over');
			this.fileUpload( e.dataTransfer.files, k );
		},		
		fileAttach(e)
		{ 			
			let el = e.target.querySelector('input[type="file"]');			
			if ( el )
				el.click();			
			else if ( e.currentTarget.nextElementSibling )
				e.currentTarget.nextElementSibling.click();	
		},			
		aDrop(e) {					
			e.preventDefault();			
			e.currentTarget.classList.add('over');			
		},		
		fileChange(e, k)
		{	
			if (!e.target.files[0] ) 
				return;
			this.fileUpload( e.target.files, k );		
		},
		fileUpload(files, k)
		{	
			if ( this.properties[k].field_type == 'files' )
			{  
				for (var i = 0; i < files.length; i++)
				{ 
					let n = this.properties[k].files.length;
					Vue.set(this.properties[k].files, n, {load:true, percent:0, error:'', title: usam_get_attachment_title(files[i].name), size:formatFileSize(files[i].size)});
					usam_form_save( this.formFileData( files[i], k ), ( r ) => {
						if ( r.status == 'success' )
						{ 
							r.load = false;							
							Vue.set(this.properties[k].files, n, r);
							this.properties[k].value.push(r.id);
						}
						else
							this.properties[k].files[n].error = r.error_message;
					}, (e)=> this.properties[k].files[n].percent = e.loaded*100/e.total, 'upload' );	
				}
			}
			else
			{
				Vue.set(this.properties[k], 'file', {load:true, percent:0, error:'', title:usam_get_attachment_title(files[0].name), size:formatFileSize(files[0].size)});
				usam_form_save( this.formFileData( files[0], k ), ( r ) => { 
					if ( r.status == 'success' )
					{ 
						r.load = false;							
						Vue.set(this.properties[k], 'file', r);
						this.properties[k].value = r.id;
					}
					else
						this.properties[k].file.error = r.error_message;
				}, (e)=> this.properties[k].file.percent = e.loaded*100/e.total, 'upload' );		
			}		
		},
		formFileData(f, k)
		{
			var fData = new FormData();				
			fData.append('file', f);
			fData.append('property', this.properties[k].id);	
			fData.append('type', 'property');	
			return fData;
		},
		prev(e)
		{
			e.preventDefault();	
			document.body.scrollIntoView({behavior:'smooth',  block:'start'});
			this.step--;
		},
		localDate(d, f)
		{
			return local_date(d, f, false);
		},
		modal(k)
		{			
			this.$refs['modal'+k][0].show = !this.$refs['modal'+k][0].show;
		},
		typeProperty( type )
		{			
			for (let k in this.properties)
			{
				if( this.properties[k].field_type==type )
					return this.properties[k].value;				
			}
			return '';
		},
		getConnections( type )
		{
			let v = {};
			for (let k in this.properties)
			{
				if( this.properties[k].field_type==type && this.properties[k].value )
				{
					let code = this.properties[k].hidden?this.properties[k].private:this.properties[k].value;
					v[code] = this.properties[k].value;
				}
			}
			return v;
		},
		getPropertiesGroup(g) {
			const asArray = Object.entries(this.properties);
			const filtered = asArray.filter(x => x[1].group===g && x[1].value!=='');
			return Object.fromEntries(filtered);
		},
		getValues()
		{
			var data = {};			
			for (let k in this.properties)
				data[k] = this.properties[k].value;	
			return data;
		}
	}
}

Vue.component('select-list-api', {		
	template: '<select-list @change="selected=$event.id" :lists="lists" :selected="selected" :multiple="multiple"></select-list>',
	props:{		
		route:{type:String, required:true},
		value:{required:false},
		multiple:{type:[Number,String], required:false, default:0}
	},
	data() {
		return {
			selected:this.value,
			lists:[],
		}
	},
	watch:{
		value(v, old) { this.selected = v },
		selected(v, old) { this.$emit('input',v); },
		route(v, old) { 
			if( v !== old )
				this.load(); 
		},		
	},	
	mounted() 
	{  
		this.load();
	},
	methods: {
		load()
		{
			usam_api(this.route, {orderby:'id', order:'desc', count:1000, fields:'autocomplete'}, 'POST', (r) => this.lists = r.items);
		}		
	}
})

Vue.component('select-list', {
template: '<div class="selectlist" :class="[multiple?`selectlist_multiple`:`selectlist_simple`]"><div class="selectlist__selected" @click="open" ref="selected"><span class="selectlist__selected_wrapper"><slot name="title" :items="items" :titles="titles"><span class="selectlist_none" v-show="noData">{{none}}</span><span v-show="!noData" class="selectlist__selected_title" v-for="(item, k) in titles"><span v-html="item.name"></span><span v-show="multiple" class="selectlist__selected_delete" @click="remove(item)"></span></span></slot></span></div><div class="selectlist__panel" ref="panel" hidden><div class="selectlist__search_selected" v-if="showSearch"><input type="text" ref="search" class="selectlist__search" placeholder="Поиск" v-model="s"></div><div class="selectlist__lists"><label class="selectlist__list_name" :class="[item.checked?`active`:``]" v-for="(item, k) in items" @click="list_click(k, $event)" v-show="!s || item.name && item.name.toLowerCase().includes(s.toLowerCase())"><input type="checkbox" v-model="item.checked" v-show="multiple"><span v-html="item.name"></span></label></div></div></div>',
	props:{
		lists:{type:[Array,Object], required:true, default:() => ([])},
		selected:{required:false, default:''},
		none:{type:String, required:false, default:''},
		hidden:{required:false, default:0},
		search:{type:Number,required:false, default:1},
		multiple:{type:[Number,String], required:false, default:0}
	},
	data() {				
		return {items:[], s:'', showSearch:true}
	},	
	computed: {
		titles() {
			return this.items.filter(x => x.checked);
		},
		noData()
		{
			return !this.titles.length;
		},
	},
	watch:{
		selected(val, oldVal) 
		{			
			this.load();
		},
		lists(val, oldVal) 
		{
			this.load();
		}
	},
	mounted() 
	{  
		this.load();		
	},
	methods: {
		load()
		{			
			this.items = [];				
			if( this.none && !this.multiple )
				this.items.push({id:'', name:this.none, checked:this.selected===''});
			for (k in this.lists)
			{				
				let item = ( typeof this.lists[k] === "string" || typeof this.lists[k] === "number" ) ? {id:k, name:this.lists[k]} : this.lists[k];
				item.checked = false;			
				if ( this.selected !== '' )
				{ 
					if ( this.multiple )
						item.checked = Array.isArray(this.selected) ? this.selected.includes(item.id) : false;
					else if ( item.id == this.selected )
						item.checked = true;
				}
				this.items.push(structuredClone(item));
			}
			this.showSearch = this.items.length < 8 ? false : this.search;
		},
		open(e)
		{ 
			if ( this.multiple && (e.target.closest('.selectlist__selected_title') || e.target.classList.contains("selectlist__selected_title")) )
				return false;
			var h = this.$refs.panel.hidden;
			document.querySelectorAll('.selectlist__panel').forEach((el) => el.hidden = true);			
			this.$emit("openselectlist", true);
			this.$refs.panel.hidden = !h;	
			if ( this.showSearch )
				this.$refs.search.focus();			
			this.$refs.selected.classList.toggle('active');
			if ( !this.$refs.panel.hidden )
			{				
				setTimeout( ()=>{ document.addEventListener('click', this.listener); }, 50);
				if ( this.showSearch )
					this.$refs.search.focus();	
			}
		},
		listener(e)
		{ 
			if ( e.target.classList.contains('selectlist') || e.target.closest('.selectlist') )
				return false;
		
			this.$refs.panel.hidden = true;
			this.$refs.selected.classList.remove('active');
			document.removeEventListener('click', this.listener, false);
		},
		remove(item)
		{ 
			var ids = [];	
			for (k in this.items)
			{					
				if ( this.items[k].id == item.id )
					this.items[k].checked = false;
				if( this.items[k].checked )
					ids.push(this.items[k].id);
			}		
			this.$emit('change',{id:ids});			
		},				
		list_click(k, e)
		{		
			e.preventDefault();
			if ( !this.multiple )
			{
				for (i in this.items)
				{
					if ( this.items[i].checked )
						this.items[i].checked = false;
				}										
				this.items[k].checked = true;
			}	
			else
				this.items[k].checked = this.items[k].checked?false:true;			
			if ( this.multiple )
			{
				var ids = [];	
				for (k in this.items)
				{					
					if( this.items[k].checked )
						ids.push(this.items[k].id);
				}
				this.$emit('change',{id:ids});
			}
			else
			{
				for (k in this.items)
				{					
					if( this.items[k].checked )
					{
						this.$emit('change',this.items[k]);
						break;
					}
				}				
				this.$refs.panel.hidden = true;	
				this.$refs.selected.classList.remove('active');
				document.removeEventListener('click', this.listener, false);
			}
		}
	}
})


Vue.component('check-list', {
	props:{		
		selected:{required:false, default:null},
		lists:{type:[Array,Object], required:true, default:() => ([])},				
		color:{required:false, default:false},
		placeholder:{type:String, required:false, default: 'Поиск'},
	},
	template: '<div class="checklist"><div class="checklist__wap"><div v-if="items.length>7" class="checklist__search_block"><input type="search" class="checklist__search" :class="[search!==``?`active`:``]" :placeholder="placeholder" v-model="search"/><a class="button_delete checklist__search_delete" v-show="search!==``&&items.length" @click="search=``"></a></div><div class="checklist__lists"><label class="selectit" v-for="item in items" :class="[item.checked?`active`:``]" v-show="!search || item.name.toLowerCase().includes(search.toLowerCase())"><input type="checkbox" v-model="item.checked" :value="item.id" :style="color?`background:`+item.code:``">&nbsp;<span class="selectit_name" v-html="item.name"></span></label></div></div></div>',
	data() {				
		return {items: [], search:'', unwatch:null}
	},
	mounted() 
	{
		this.load();
		this.$watch('selected', this.update, {deep:true});
		this.$watch('lists', this.update, {deep:true});
	},
	methods: {
		load()
		{ 	
			var items = []
			for (k in this.lists)
			{
				let item = ( typeof this.lists[k] === "string" || typeof this.lists[k] === "number" ) ? {id:k, name:this.lists[k]} : structuredClone(this.lists[k]);	
				if ( item.checked === undefined )
				{ 
					item.checked = false;
					if ( this.selected !== null )
						item.checked = this.selected.includes(item.id);	
				}
				items.push(structuredClone(item));
			}
			this.items = structuredClone(items);			
			this.unwatch = this.$watch('items', this.change, {deep:true});			
		},
		update()
		{
			this.unwatch(); this.load();
		},
		change()
		{
			var ids = [];	
			for (k in this.items)
				if ( this.items[k].checked )
					ids.push(this.items[k].id);
			this.$emit('change',ids);
		}
	}
})

Vue.component('check-block', {		
	template: '<div class="checkblock"><label class="checkblock__title"><input type="checkbox" v-model="all"/><slot name="title"></slot></label><check-list :lists="lists" :selected="selected" @change="selected=$event"/></div>',
	props:{		
		lists:{type:[Array,Object], required:true, default:() => ([])},
		value:{required:false},
	},
	data() {
		return {
			all:false,	
			selected:this.value,
		}
	},	
	watch:{
		value(v, old) { this.selected = v },
		selected(v, old) { this.$emit('input',v); },
		all(v, old) 
		{
			var ids = [];
			if( v )
			{
				for (let i in this.lists)
					ids.push(this.lists[i].id)
			}
			this.selected = ids;	
		}
	},
})

Vue.component('progress-circle', {		
	template: '<div class="active_circle" :style="{backgroundImage:style}"><div class="active_circle__border"><span class="active_circle__prec">{{p}}%</span></div></div>',
	props: ['percent'],
	data() {
		return {
			p: 0,
			style: '',					
		}
	},	
	watch:{
		percent(val, oldVal) 
		{
			this.p = Math.round(val);			
			var v = val*3.6;
			if ( val*3.6 <= 180)
				this.style = "linear-gradient("+Math.round(v+90)+"deg, transparent 50%, #7db1c9 50%),linear-gradient(90deg, #7db1c9 50%, #01799c 50%)";
			else
				this.style = "linear-gradient("+Math.round(v-90)+"deg, transparent 50%, #01799c 50%),linear-gradient(90deg, #7db1c9 50%, #01799c 50%)";
		}
	},
})

Vue.component('sort-block', {
	template: '<div :class="classes"><slot name="body" :allowDrop="allowDrop" :drag="drag" :dragEnd="dragEnd" :drop="drop"/></div>',
	props:{
		classes:{type:String, required:false, default:'sort-block'},
	},
	data() {
		return {
			index:'',		
		}
	},		
	methods: {
		allowDrop(e, k) {		
			e.preventDefault();			
			if ( this.index !== k )
			{				
				this.$emit('change', this.index, k);
				this.index = k;		
			}
		},
		drag(e, k) {				
			this.index = k;
			if ( e.target.hasAttribute('draggable') )
				e.currentTarget.classList.add('draggable');	
			else
				e.preventDefault();
		},	
		dragEnd(e, k) {				
			e.currentTarget.classList.remove('draggable');
		},
		drop(e, k) {
			e.preventDefault();					
		}
	}
})

Vue.component('selector', {		
	template: '<div class="selector"><div class="selector__item" v-for="list in items" @click="$emit(`input`,list.id)" :class="[value==list.id?`active`:``,`selector__item_`+list.id]" v-html="list.name"></div></div>',
	props: {items: {type:Array, required:false, default:()=>[{id:0, name:'Нет'},{id:1, name:'Да'}]}, value: Number|String},
})

Vue.component('selector-multiple', {		
	template: '<div class="selector"><div class="selector__item" v-for="(list, k) in items" @click="change(k)" :class="[selected==list.id?`active`:``]" v-html="list.name"></div></div>',
	props: {items: Array, selected: {type:Array, required:true, default: []}},
	data(){	return { ids: this.selected } },
	watch: {		
		selected(val, oldVal) 
		{
			this.ids = val;
		},
	},	
	methods:{
		change(k){
			this.ids.push(this.items[k].id);
			this.$emit('change',ids);		
		}
	}
})


Vue.component('paginated-list',{	
	props:{
		paged:{type:Number, required:false, default:1},
		count:{type:Number, required:false},		
		size:{type:Number, required:false, default:20},
		moreon:{required:false, default:false},
		disable:{required:false, default:false},
		links:{required:false, default:false}
	},
	data(){
		return {
			page:this.paged,
			more:this.moreon,	
			disableMore:false,			
			Observer:false
		}
	},
	watch: {		
		moreon(v, old) 
		{
			if( v!==old && v )
				this.more = v;
		},		
		paged(v, old) 
		{
			if( v!==old && v )
				this.page = v;
		},
		page(v, old) 
		{
			if( v!==old )
			{
				this.$emit('change', v);
				if( this.links )
					this.setUrl();
			}
		},
		more(v, old) 
		{				
			if( v!==old )
				this.$emit('change-more', v);
			if( v )
				this.loadingMore();
			else if( this.Observer )
				this.Observer.unobserve( this.$refs.more );
		},
	},			
	methods:{
		setUrl(){
			var url = this.getUrl( this.page );
			history.pushState({'url' : url}, '', url);
		},
		getUrl( p ){
			var url = new URL( document.location.href );
			if( url.href.includes("/page/") )
			{
				if( p == 1 )
					return document.location.pathname.replace(/\/page\/[0-9]+/gi, "");
				else
					url = new URL( p+document.location.search, document.location.href );
			}
			else
			{
				if( p == 1 )
					return document.location.href;
				else
				{
					var s = url.origin+document.location.pathname;
					s = s.replace(/\/+$/, '');
					url = new URL( "page/"+p+document.location.search, s+'/' );	
				}
			}
			return url.href;
		},
		nextPage(e){
			e.preventDefault();
			if( this.page != this.pageCount )
				this.page++;			
		},		
		prevPage(e){
			e.preventDefault();
			if ( this.page != 1 )
				this.page--;
		},
		setPage(n, e){
			e.preventDefault();			
			this.page = n;
		},		
		loadingMore() 
		{ 		
			if( this.$refs.more )
			{
				this.Observer = new IntersectionObserver((el, o) => {
					el.forEach((e) => {
						if ( this.page != this.pageCount && !this.disable && !this.disableMore )
						{
							this.page++;
							this.disableMore = true;							
							setTimeout(()=> this.disableMore = false, 100)
						}						
					})
				}, {rootMargin: '0px 0px 50px 0px'});	
				this.Observer.observe(this.$refs.more);
			}
		},
	},
	computed:
	{
		pageCount(){
			if ( this.count )
				return Math.ceil(this.count/this.size);
			else
				return 1;
		},
		paginatedData(){
			let array = [];
			if ( this.page > 2 )
			{
				let max = this.page+2;
				if ( max >= this.pageCount )
					max = this.pageCount-1;	
				for ( i = this.page-2; i <= max; i++) 
					array.push(i);				
			}
			else
			{
				let max = this.pageCount-1 > 3 ? 3 : this.pageCount-1;
				array = Array.from({length: max}, (_, i) => i + 1);	
			}			
			return array
		}
	},
	template: '#paginated-list'
});

Vue.component('autocomplete', {
	inheritAttrs: false,
	template: '<div class="autocomplete"><div class="checklist__search_selected"><input type="text" class="autocomplete__search" :class="[search?`active`:``,$attrs.class]" :placeholder="placeholder" ref="search" v-model="search" @keydown="change"><a class="checklist__search_delete" v-show="search!=``" @click="clear"></a><span v-if="isLoading" class="loading_process"></span></div><div class="selectlist__panel" hidden ref="selection"><div class="selectlist__lists"><div v-for="list in lists" @click="select(list)" class="selectlist__list_name" v-html="list.title"></div><a v-show="more && this.lists.length" @click="loadMore" class="autocomplete__more">...</a><div v-if="lists.length==0" class="selectlist__list_name autocomplete__none" v-html="none"></div></div></div></div>',
	props:{
		selected:{required:false, default:''},
		uniqueid:{required:false, default:''},
		clearselected:{required:false, default:0},
		placeholder:{type:String, required:false, default:'Поиск'},
		code:{required:false, default: ''},
		request:{type:String, required:true, default: ''},
		none:{type:String, required:false, default:'Нет данных'},
		objectname:{type:String, required:false, default:'name'},
		query:{type:Object, required:false, default:() => ({})}
	},
	data() {
		return {lists:[], isLoading:false, eventListener:false,	more:true, paged:1,	search:''}
	},
	watch: {
		selected(val, oldVal) 
		{
			this.search = val;
		}		
	},
	mounted() 
	{		
		if ( this.uniqueid )
		{
			var data = Object.assign({}, this.query);
			data.include = [this.uniqueid];			
			usam_api(this.request, data, 'POST', (r) => this.search = r.count>0?r.items[this.uniqueid]:'');
		}
		else
			this.search = this.selected;
	},
	methods: {
		change(e)
		{
			clearTimeout(this.timerId);
			this.paged = 1;
			this.more = true;
			this.timerId = setTimeout( this.load, 500, e );					
		},		
		clear()
		{				
			this.search=''; 
			this.$refs.search.focus();
		},		
		select( l )
		{
			if ( this.clearselected )
				this.clear();
			else
				this.search=l[this.objectname];
			l.code = this.code;
			this.$emit('change', l);
		},
		loadMore(e)
		{				
			this.paged++;
			this.load(e);
		},
		load( e )
		{	
			let l = {value:this.search};
			l.code = this.code;
			this.$emit('keydown', l);			
			if ( this.search.length < 2 || !this.more )
				return;						
			this.isLoading = true;	
			if ( this.paged == 1 )
			{
				this.lists = [];				
				if ( !this.eventListener )
				{
					this.eventListener = true;
					var el_box = e.target.closest(".autocomplete__more");
					var f = (ev)=> { 
						let el = ev.target.closest('.autocomplete__more');						
						if ( el == null )
							this.$refs['selection'].hidden = true;
						else if ( el.isEqualNode(el_box) )
							this.$refs['selection'].hidden = false;
						else
							return false;
					};
					setTimeout(()=>{ document.addEventListener("click", f ) }, 500);
				}
			}
			var data = Object.assign({}, this.query);
			data.search = this.search;	
			data.fields = 'autocomplete';
			data.paged = this.paged;
			usam_api(this.request, data, 'POST', (r) => {
				var reg = new RegExp(this.search, 'gi');				
				for (let i in r.items) 
				{					
					if ( typeof r.items[i] == 'object' )
						r.items[i].title = r.items[i][this.objectname].replace(reg, '<span class="autocomplete__word">$&</span>');
					else
						r.items[i] = {id:i, title:r.items[i], name:r.items[i].replace(reg, '<span class="autocomplete__word">$&</span>')};
					this.lists.push(r.items[i]);					
				}		
				this.isLoading = false;		
				this.more = r.count>r.items.length;					
				this.$refs['selection'].hidden = false;										
			});
		},	
	}
})

Vue.component('site-slider',{
	template: '<div :class="classes"><slot name="body" :items="items" :prev="prev" :next="next" :enable="enable" :zoom="zoom" :changeZoom="changeZoom" :scrollStart="scrollStart" :scrollEnd="scrollEnd" :n="n"/><slot name="buttons" :items="items" :prev="prev" :next="next" :enable="enable" :zoom="zoom" :changeZoom="changeZoom" :scrollStart="scrollStart" :scrollEnd="scrollEnd" :n="n"/></div>',
	props:{
		items:{type:Array, required:false},
		number:{type:Number, required:false, default:0},
		amount:{type:Number, required:false, default:0},
		mouse:{type:Boolean, required:false, default:true},
		touch:{type:Boolean, required:false, default:true},
		type:{type:String, required:false, default:'scroll'}, //transform
		classes:{type:String, required:false, default:'site-slider'},
	},
	watch: {
		number() {			
			var d = this.n - this.number
			this.n = this.number;
			var p = 0;
			if( this.type == 'scroll' )
			{
				if ( d )
					p = -this.slideWidth*this.amount;
				else
					p = this.slideWidth*this.amount;
			}
			else if( this.type == 'transform' )
				p = this.slideWidth*this.n;				
			this.changePosition(p);
		},
		n(val, old) {
			if ( this.slides )
				this.scrollEnd = this.slides.scrollWidth<=(this.slideWidth*this.n+this.slides.offsetWidth)
		},		
	},
	computed:
	{
		scrollStart()
		{
			return !this.n;
		}
	},
	data() {
		return {slides:null, scroll:null, mouseDown:false, x:0, startX:0, scrollLeft:0, enable:false, n:0, zoom:false, totalSlides:0, slideWidth:0, scrollEnd:true}
	},	
	mounted () {
		this.init();	
		window.addEventListener('resize',() => {
			if ( this.enable == false )
				this.disable();
			this.init();						
		})
	},	
	methods: {
		init()		
		{			
			this.n = this.number;
			this.slides = this.$el.querySelector(".slider-slides");
			this.slides = this.slides ? this.slides : this.$el
			this.zoom = window.innerWidth < 1023;			
			if( this.slides.scrollWidth >= this.slides.offsetWidth )
			{				
				this.scrollEnd = this.slides.scrollWidth<=(this.slideWidth*this.n+this.slides.offsetWidth)
				this.enable = true;				
				if ( this.mouse )
				{
					this.slides.addEventListener('mousedown', this.start, false);
					this.slides.addEventListener('mousemove', this.move, false);
					this.slides.addEventListener('mouseup', this.stop, false);
					this.slides.addEventListener('mouseleave', this.stop, false);						
				}
				if ( this.touch )
				{
					this.slides.addEventListener('touchstart', this.start, false);
					this.slides.addEventListener('touchmove', this.move, false);	
					this.slides.addEventListener('touchend', this.stop, false);					
				}
				if ( this.amount )
				{
					var lists = Array.from(this.slides.childNodes).filter((i) => i.nodeName=='DIV')
					this.totalSlides = lists.length
					if ( this.totalSlides )
						this.slideWidth = lists[0].clientWidth;
				}
				if ( this.n )
					this.nextSlide();
				else
					this.changePosition(0);
			}
			else
				this.enable = false;
		},		
		stop(e)
		{
			if ( this.amount && this.mouseDown )
			{
				var x = this.x - (e.type == 'touchend' ? e.changedTouches[0].pageX : e.pageX);
				if ( Math.abs(x) > 10 )
				{
					if ( x > 0 )
						this.nextSlide();
					else
						this.prevSlide();
				}
			}
			this.mouseDown = false;				
		},		
		move(e)
		{ 
			if ( this.mouseDown && this.enable )
				this.changePosition(this.scrollLeft - ((e.type == 'touchmove' ? e.changedTouches[0].pageX : e.pageX) - this.slides.offsetLeft - this.startX));
		},
		start(e)
		{  
			if ( this.enable )
			{
				this.mouseDown = true;				
				this.x = e.type == 'touchstart' ? e.changedTouches[0].pageX : e.pageX;
				this.startX = this.x - this.slides.offsetLeft;			
				this.scrollLeft = this.slides.scrollLeft;
			}
		},
		changePosition(p)
		{
			if ( this.type == 'scroll' )
				this.slides.scrollLeft = p;
			else if ( this.type == 'transform' )
				this.slides.style.transform = "translate3d(-"+p+"px, 0px, 0px)";
			this.$emit('changeposition', p);
		},
		prev(e)
		{
			e.preventDefault();
			this.prevSlide();
			return false
		},
		next(e)
		{
			e.preventDefault();
			this.nextSlide();
			return false
		},		
		nextSlide()
		{			
			var max = this.items.length - 1;	
			if ( this.n != max )
			{ 
				var n = this.n + this.amount
				this.$emit('change', n);
				this.n = n > max ? max : n;				
				var p = 0;
				if ( this.type == 'scroll' )
					p = this.slideWidth*this.amount;
				else  if ( this.type == 'transform' )
					p = this.slideWidth*this.n;
				this.changePosition(p);
			}			
		},
		prevSlide()
		{
			if ( this.n != 0 )
			{
				var n = this.n - this.amount
				this.$emit('change', n);
				this.n = n < 0 ? 0 : n;	
				var p = 0;
				if ( this.type == 'scroll' )
					p = -this.slideWidth*this.amount;
				else if ( this.type == 'transform' )
					p = this.slideWidth*this.n;
				this.changePosition(p);				
			}
		},
		disable(e)
		{
			if ( this.mouse )
			{
				this.slides.removeEventListener('mousedown', this.start, false);
				this.slides.removeEventListener('mousemove', this.move, false);
				this.slides.removeEventListener('mouseup', this.stop, false);
				this.slides.removeEventListener('mouseleave', this.stop, false);	
			}
			if ( this.touch )
			{
				this.slides.removeEventListener('touchstart', this.start, false);
				this.slides.removeEventListener('touchmove', this.move, false);	
				this.slides.removeEventListener('touchend', this.stop, false);					
			}
		},		
		changeZoom()
		{
			if (  window.innerWidth > 1023 )
				this.zoom = !this.zoom;
		}
	}
});


Vue.component('image-zoom',{
	props:{
		imgNormal:{type:String, required:true},
		imgZoom:{type:String, required:false, default:''},
		scale:{type:Number, required:false, default:0},
		disabled:{type:Boolean, required:false, default:false},
	},
	template: `<div class="image_zoom" :class="{zoomed}" @touchstart="zoom" @touchmove="move" @mousemove="move" @mouseenter="zoom" @touchend="unzoom" @mouseleave="unzoom"><img class="normal" ref="normal" :src="imgNormal"><img class="zoom" ref="zoom" :src="imgZoom || imgNormal" draggable="false" @dragstart="dragstart"></div>`,
	data(){
		return {
			scaleFactor: 1,
			resizeCheckInterval: null,
			start: false,
		}
	},
	watch: {
		disabled(val, old) {
			if ( val )
				this.start = !val;
		},
		start() {			
			var zoom = this.$refs.zoom
			zoom.style.width = zoom.width + "px"
			zoom.style.height = zoom.height + "px"
			this.$emit('change',this.start);
		}
	},
	computed:
	{
		zoomed(){
			return this.start && !this.disabled;
		}
	},
	mounted() {
		if (this.$props.scale) {
			this.scaleFactor = parseInt(this.$props.scale)
			this.$refs.zoom.style.transform = "scale(" + this.scaleFactor + ")"
		}
		this.init()
		this.initEventResized()
	},
	methods: {   
		pageOffset(el) {
			var rect = el.getBoundingClientRect(),
			scrollLeft = window.pageXOffset || document.documentElement.scrollLeft,
			scrollTop = window.pageYOffset || document.documentElement.scrollTop;			
			return {y: rect.top + scrollTop, x: rect.left + scrollLeft}
		},
		dragstart(e) {
			e.preventDefault();
		},
		zoom(e) {
			if (!this.disabled) this.start = true
		},
		unzoom() {
			if (!this.disabled) this.start = false
		},
		move(e) {
			if (!this.disabled) this.start = true		
			var offset = this.pageOffset(this.$el);		
			var zoom = this.$refs.zoom
			var normal = this.$refs.normal			
			if ( e.type == 'touchmove' )
			{
				var touch = e.touches[0];
				var relativeX = touch.clientX - offset.x + window.pageXOffset
				var relativeY = touch.clientY - offset.y + window.pageYOffset	
			}
			else
			{
				var relativeX = e.clientX - offset.x + window.pageXOffset
				var relativeY = e.clientY - offset.y + window.pageYOffset	
			}			
			var normalFactorX = relativeX / normal.offsetWidth
			var normalFactorY = relativeY / normal.offsetHeight
			var x = normalFactorX * (zoom.offsetWidth * this.scaleFactor - normal.offsetWidth)
			var y = normalFactorY * (zoom.offsetHeight * this.scaleFactor - normal.offsetHeight)
			zoom.style.left = -x + "px"
			zoom.style.top = -y + "px"
			zoom.style.width = zoom.width + "px"
			zoom.style.height = zoom.height + "px"
		},
		init() {    
			var promises = [this.$refs.zoom, this.$refs.normal].map(function(image) 
			{
				return new Promise(function(resolve, reject) {
					image.addEventListener("load", resolve)
					image.addEventListener("error", reject)
				})
			})
			var component = this
			Promise.all(promises).then(function() {
				component.$emit("loaded")
			})
		},
		initEventResized() {
			var normal = this.$refs.normal
			var previousWidth = normal.offsetWidth
			var previousHeight = normal.offsetHeight
			this.resizeCheckInterval = setInterval(() => {
				if ((previousWidth != normal.offsetWidth) || (previousHeight != normal.offsetHeight)) {
					previousWidth = normal.offsetWidth
					previousHeight = normal.offsetHeight
					this.$emit("resized", {width: normal.width, height: normal.height, fullWidth: normal.naturalWidth,	fullHeight: normal.naturalHeight})
				}
			}, 1000)
		}
	},	
	updated() {
		this.init()
	},
	beforeDestroy() {
		this.resizeCheckInterval && clearInterval(this.resizeCheckInterval)
	}
})

var chat = {
  	data() {
		return {
			messages:[],
			dialogs:{},						
			sender:{},
			recipient:{},
			manager:{},			
			id:0,
			containerHeight:0,
			loaded:false,
			loadMore: true,
			scroll: true,
			message: '',
			new_message: false,		
			startUpdate: false,	
			unreadMessages:0,
			timeoutId:false
		};
	},
	methods: {		
		updateChat() {	
			var data = {dialog_id: this.id, read_ids:[]};
			var c = this.$el.querySelector("#chat_messages");
			if ( c )				
				for (var i = 0; i < this.messages.length; i++)
				{
					if ( this.messages[i].status == 1 && this.sender.id != this.messages[i].contact_id )
					{
						var el = c.querySelector('[message_id="'+this.messages[i].id+'"]');						
						if ( c.scrollTop <= el.offsetTop && c.scrollTop+c.clientHeight >= el.offsetTop + el.offsetHeight )
							data.read_ids.push(this.messages[i].id);
					}
				}
			if ( this.messages.length )
				data.message_id = this.messages[0].id;	
			usam_api('chat/messages', data, 'POST', this.updateChatData);
		},		
		sentMessage() {			
			var message = this.message;
			if ( message != '' ) 
			{		
				document.querySelector('#textarea-message').style.height='auto';			
				this.message = '';
				var data = {message: message, dialog_id: this.id, update:true};
				if ( this.messages.length )
					data.message_id = this.messages[this.messages.length-1].id;
				var d = new Date();									
				var massage = {id: 0, user: this.recipient.name, contact_id: this.recipient.id, date_insert:local_date(d), message: message, status : 1}				
				usam_api('chat/message', data, 'POST', (r) =>
				{					
					this.id = r.dialog_id;
					this.scroll = true;
					this.updateItems(r.items);
				});
			}	
		},
		loadMoreMessages() 
		{ 
			if ( this.messages.length > 19 )
			{ 		
				const ob = new IntersectionObserver((el, o) => { 
					el.forEach((e) => {
						if (e.isIntersecting && this.loadMore)
						{ 									
							this.loadMore = false;						
							var data = {'dialog_id': this.id, order:'ASC'};
							if ( this.messages.length )
								data.from_id = this.messages[0].id;
							usam_api('chat/messages', data, 'POST', (r) =>
							{
								if ( r.items.length )
								{
									this.containerHeight = this.$el.querySelector("#chat_messages").scrollHeight;
									for (var i = 0; i < r.items.length; i++)
										this.messages.unshift(r.items[i]);
									this.loadMore = true;																			
								}																		
							});
						}
					})
				});			
				document.querySelectorAll('.js-load-more-chat-messages').forEach((v) => {
					ob.observe(v);
				}) 	
			}
		},		
		autoTextarea(e) 
		{			
			if ( e.target.scrollHeight > 50 )
				e.target.style.height=(e.target.scrollHeight+1)+'px';
		},
		newLine(e) {
			let c = e.target.selectionStart;
			e.target.setRangeText("\n", c, c, "end");
			this.text = e.target.value;
			this.autoTextarea(e);
		},
		localDate(d, f)
		{
			return local_date(d, f, true);
		},
		loadDialog( id ) {			
			this.messages = [];
			this.loaded = false;
			var data = {}
			if ( id !== undefined )
				data.dialog_id = id;
			usam_api('chat/messages', data, 'POST', this.updateChatData);
		},
		updateChatData(r){ 
			this.recipient = r.recipient || {};
			this.sender = r.sender || {};			
			this.manager = r.manager || {};
			this.unreadMessages = r.unread || 0;
			this.loaded = true;			
			if ( !this.id  )
				this.id = r.dialog.id						
			if ( r.items.length )
			{
				var c = this.$el.querySelector("#chat_messages");	
				if ( c )
					this.scroll = c.scrollHeight === c.scrollTop + c.clientHeight;				
				this.updateItems(r.items);
			}			
		},
		updateItems(items) {
			if ( !items.length )
				return;	
			var l = this.messages.length;
			for (var k = 0; k < this.messages.length; k++)
			{
				for (i in items)
				{	
					if ( items[i].id == this.messages[k].id )
					{
						Vue.set(this.messages, k, items[i]);
						delete items[i];
					}
				}
			}
			if ( items.length )
			{
				var n = false; 
				for (i in items)
				{
					if ( l && !items[i].my )
						n = true;
					this.messages.push(items[i]);
				}
				if ( n )
				{
					this.new_message = true;						
					window.setTimeout(() => this.new_message = false, 3000);
				}
			}			 
		},		
		setManager() {
			this.saveDialog({manager_id: 0});
		},		
		saveDialog(data) {
			usam_api('chat/dialog/'+this.id, data, 'POST', (r) => {
				this.sender = r.sender;				
				this.manager = r.manager;		
				this.updateItems(r.items);
			});
		},
		modal(k)
		{
			this.$refs['modal'+k].show = !this.$refs['modal'+k].show;
		}
	},
	watch:{
		id(val, oldVal) 
		{
			if ( val == 0 )
				this.messages = [];
		},
		startUpdate(val, oldVal) 
		{
			if ( val )
				this.timeoutId = window.setInterval(this.updateChat, 5000);
			else
				clearTimeout(this.timeoutId);		
		}	
	},	
	updated()
	{	
		if ( this.id )
		{
			var c = this.$el.querySelector("#chat_messages");	
			if ( c )
			{
				if ( this.containerHeight )
				{
					c.scrollTop = c.scrollHeight-this.containerHeight;	
					this.containerHeight = 0;
				}
				else if ( this.scroll )
				{	
					c.scrollTop = c.scrollHeight;
					this.scroll = false;
				}
			}
		}				
		this.loadMoreMessages();
	}
}

Vue.component('datetime-picker', {
	props:{
		value:{required:true},		
		placeholder:{type:String, required:false, default:'дд.мм.гггг чч:мм'},
		mindate:{required:false},
	},
	template: '<v-date-picker :value="value" @input="$emit(`input`, $event)" :min-date="mindate" mode="dateTime" :model-config="{type:`string`,mask:`YYYY-MM-DD HH:mm:ss`}" is24hr :attributes="[{key:`today`, highlight:{color:`green`, fillMode:`outline`}, dates:new Date()}]"><template v-slot="{inputValue, inputEvents}"><input class="datetime_picker" type="text" :value="inputValue" v-on="inputEvents" :placeholder="placeholder"></template></v-date-picker>',
})

Vue.component('date-picker', {
	props:{
		value:{required:true},		
		placeholder:{type:String, required:false, default:'дд.мм.гггг'},
	},
	template: '<v-date-picker :value="value" @input="$emit(`input`, $event)" :model-config="{type:`string`,mask:`YYYY-MM-DD HH:mm:ss`}" :input-debounce="800" :attributes="[{key:`today`, highlight:{color:`green`, fillMode:`outline`}, dates:new Date()}]"><template v-slot="{inputValue, inputEvents}"><input class="date_picker" type="text" :value="inputValue" v-on="inputEvents" :placeholder="placeholder"></template></v-date-picker>',
})

Vue.component('modal-window', {
	template: '#modal-window',
	props:{
		backdrop:{type:Boolean, required:false, default:true},
	},
	watch:{
		show(v, old) 
		{
			if ( this.backdrop )
			{
				if ( v )
					add_backdrop();
				else
				{
					var b = document.querySelector('.usam_backdrop');
					!b || b.remove();
				}
			}
			if( v )
			{
				setTimeout(()=> { 
					document.addEventListener('click', this.close);
					this.maxHeight();
				}, 381);
			}
			else				
				document.removeEventListener('click', this.close, false);	
			setTimeout(() => this.animation=v, 380);
		}
	},		
	data() {
		return {
			show: false,
			animation: false		
		}
	},	
	methods: {		
		closeModal() {
			this.show = false
		},
		maxHeight() {			
			var el = this.$el.querySelector('.modal-scroll');			
			if ( el )
			{
				var window_top = el.getBoundingClientRect().top+this.$el.getBoundingClientRect().top*2;	
				var height = document.documentElement.clientHeight-window_top;
				if ( window_top + el.offsetHeight > screen.height )
					el.setAttribute("style", "height:"+height+"px; overflow-y:auto;");
				else
					el.setAttribute("style", "max-height:"+height+"px; overflow-y:overlay; overflow-x:hidden;");
			}
		},
		close(e)
		{
			if ( e.target.closest('.modal') || e.target.classList.contains("modal") )
				return false;
			this.show = false;
			document.removeEventListener('click', this.close, false);
		},
	}
})

Vue.component('hint-window', {
	props:{
		open:{type:Boolean, required:false, default:false},
	},
	template: '#hint-window',	
	watch:{
		show(v, old) 
		{			
			if( v )
			{
			//	this.$refs['hint'].style.margin = '0 0 0 '+(this.$refs['hint'].parentNode.firstElementChild.clientWidth)+'px';
				setTimeout(()=> document.addEventListener('click', this.close), 1);	
			}
			else				
				document.removeEventListener('click', this.close, false);	
		},
		open(v, old) 
		{			
			this.show = v
		}
	},		
	data() {
		return {
			show: this.open	
		}
	},	
	methods: {
		close(e)
		{
			if ( e.target.closest('.hint') || e.target.classList.contains("hint") )
				return false;
			this.show = false;			
			document.removeEventListener('click', this.close, false);
		},
	}
})

Vue.component('timer', {
	template: `<div class="clock"><div class="clock_timer"><div v-for="(n, i) in trackers" :class="[(i+1)%3 !== 0?'clock_number':'clock_divider']"><div v-if="(i+1)%3 !== 0" class="clock_number_before" :class="{'active':!n.active}"><div class="clock_number_wap"><div class="clock_number_up"><div class="clock_number_up_shadow"></div><div class="clock_number_up_inn">{{n.down}}</div></div><div class="clock_number_down"><div class="clock_number_down_shadow"></div><div class="clock_number_down_inn">{{n.down}}</div></div></div></div><div v-if="(i+1)% 3 !== 0" class="clock_number_active" :class="{'active':!n.active}"><div class="clock_number_wap"><div class="clock_number_up"><div class="clock_number_up_shadow"></div><div class="clock_number_up_inn">{{n.up}}</div></div><div class="clock_number_down"><div class="clock_number_down_shadow"></div><div class="clock_number_down_inn">{{n.up}}</div></div></div></div><span class="clock_divider_dot top" v-if="(i+1) % 3 === 0"></span><span class="clock_divider_dot bottom" v-if="(i+1) % 3 === 0"></span></div></div></div>`,	
	props: ['date'],	
	data() {
		return {
			time: 0,
			Seconds: 0,			
			trackers:[],
			oldTrackers:[],
			timer: null,
		}
	},
	watch: {
		time(time) {
			if (time === 0) {
				this.stopTimer()
			}
		}
	},
	mounted() {
		this.Seconds = Math.floor((new Date(Date.parse(this.date)) - new Date())/1000);			
		this.preparation();
		this.startTimer()
	},
	destroyed() {
		this.stopTimer()
	},
	methods: {
		preparation() {
			this.oldTrackers = structuredClone(this.trackers);
			this.trackers = [];				
			var day = this.Seconds / (60 * 60 * 24);
			if( day > 1 )
				this.formatTracker(Math.floor(day), 'day');
			this.formatTracker(Math.floor((this.Seconds / (60 * 60)) % 24), 'hours');
			this.formatTracker(Math.floor((this.Seconds / 60) % 60), 'minutes');
			this.formatTracker(Math.floor(this.Seconds % 60), 'seconds');			
			setTimeout(() => this.animation(), 100);
		},
		formatTracker(d, k) {			
			d = d<=9?'0'+d.toString():d.toString();
			let i = Number(d[0])+1;
			i = i > 6 ? 0 : i;
			let j = Number(d[1])+1;
			j = j > 9 ? 0 : j;				
			var n = this.trackers.length;			
			var a = this.oldTrackers.length && this.oldTrackers[n].up!=d[0];			
			Vue.set(this.trackers, n, {up:d[0], down:i, current_up:d[0], current_down:i, active:a});
			n = this.trackers.length;
			a = this.oldTrackers.length && this.oldTrackers[n].up!=d[1];
			Vue.set(this.trackers, n, {up:d[1], down:j, current_up:d[1], current_down:j, active:a});
			if( k != 'seconds' )
			{
				n = this.trackers.length;
				Vue.set(this.trackers, n, {});
			}
		},		
		animation() {
			for (i in this.trackers)
				if( this.trackers[i].active )
				{
					this.trackers[i].active = false;
					this.trackers[i].up = this.trackers[i].current_up;
					this.trackers[i].down = this.trackers[i].current_down;
				}
		},
		startTimer() {
			this.timer = setInterval(() => {
				this.Seconds--				
				this.preparation();
			}, 1000)
		},				
		stopTimer() {
			clearTimeout(this.timer)
		},
	} 
})

Vue.component('range-slider', {
	template: '<div class="range_slider"><div class="track-highlight" ref="trackHighlight"></div><div class="track" ref="_vpcTrack"></div><span class="range_slider_min" ref="track1"></span></div>',
	props: ['min', 'max', 'value'],
	data() {
		return {
			selected: this.value,
			step: 1,
			isDragging: false,
			timerId: false
		}
	},
	watch: {		
		value(val, oldVal) 
		{			
			this.selected = val;	
			this.recalculate();			
		},	
		selected(val, oldVal) 
		{
			clearInterval(this.timerId);
			this.timerId = setTimeout(() => this.$emit('change',val), 10);
		},		
		min(val, oldVal) 
		{			
			this.recalculate();
		},
		max(val, oldVal) 
		{			
			this.recalculate();
		},
	},
	computed:{
		totalSteps() { 
			return (this.max - this.min) / this.step;		  
		},		
		percentPerStep() { 
			return 100 / this.totalSteps;		  
		}
	},
	mounted() 
	{
		this.recalculate();
		['mouseup', 'mousemove'].forEach(type => { document.body.addEventListener(type, (e) => {
				if( this.isDragging )
					this[type](e)			
			})
		});		
		['mousedown', 'mouseup', 'mousemove', 'touchstart', 'touchmove', 'touchend'].forEach(type => {			
			this.$refs.track1.addEventListener(type, (e) => {
				e.stopPropagation();
				this[type](e);
			},{passive: true});			
		})
		this.$refs._vpcTrack.addEventListener('click', (e) => {
			e.stopPropagation();
			this.setClickMove(e);		  
		})
		this.$refs.trackHighlight.addEventListener('click', (e) => {
			e.stopPropagation();
			this.setClickMove(e);		  
		})
	},
	methods: {		
		recalculate()
		{			
			if ( this.min !== 'undefined' && this.selected < this.min ) 
				this.selected = this.min;			
			this.$refs.track1.style.left = this.valueToPercent(this.selected) + '%';		
			this.setTrackHightlight();
		},
		moveTrack(e)
		{ 
			moveInPct = (e.clientX-this.$refs._vpcTrack.getBoundingClientRect().left) / this.getPercentInPx();			
			if( moveInPct<=0 ) 
			{
				value = this.min;
				moveInPct = 0;
			}
			else if( moveInPct>=100 ) 
			{
				value = this.max;
				moveInPct = 100;
			}
			else		
				value = Math.round(( moveInPct / this.percentPerStep * this.step ) + this.min);	
					
			if( this.max < value || this.min > value ) 
				return;
			this.selected = value;	
			this.$refs['track1'].style.left = moveInPct + '%';
			this.setTrackHightlight()			
		},
		mousedown(e)
		{
			if(this.isDragging) return;
			this.isDragging = true;
		},
		touchstart(e){
			this.mousedown(e)
		},
		mouseup(e){
			if(!this.isDragging) return;
			this.isDragging = false;
		},
		touchend(e){
			this.mouseup(e)
		},
		mousemove(e){
			if(!this.isDragging) return;  		
			this.moveTrack(e);
		},
		touchmove(e){
			this.mousemove(e.changedTouches[0]);
		},
		valueToPercent(value){	
			return ((value - this.min) / this.step) * this.percentPerStep;
		},
		setTrackHightlight(){
			this.$refs.trackHighlight.style.width = this.valueToPercent(this.selected) + '%';
		},
		getPercentInPx(){
			let oneStepInPx = this.$refs._vpcTrack.offsetWidth / this.totalSteps;
			return oneStepInPx / this.percentPerStep;
		},
		setClickMove(e)
		{
			let track1Left = this.$refs.track1.getBoundingClientRect().left;			
			if(e.clientX < track1Left)
				this.moveTrack(e)			
		}
	}
})


Vue.component('teleport', {
	template: '<div :class="classes"><slot/></div>',
	props: {
		to: {type: String, required: true},
		where:{type: String, default: 'after'},
		disabled: Boolean,
	},
	data() {
		return {
			nodes: [],
			waiting: false,
			observer: null,
			parent: null,
		}
	},
	watch: {
		to: 'maybeMove',
		where: 'maybeMove',
		disabled(value) {
			if (value) {
				this.disable();    
				this.$nextTick(() => { this.teardownObserver(); });
			} 
			else {
				this.bootObserver();
				this.move();
			}
		},
	},
	mounted() {
		this.nodes = Array.from(this.$el.childNodes);
		if (!this.disabled)
			this.bootObserver();
		this.maybeMove();
	},
	beforeDestroy() {
		this.nodes = this.getComponentChildrenNode();
		this.disable();
		this.teardownObserver();
	},
	computed: {
		classes() {
			if (this.disabled)
				return ['teleporter'];
			return ['teleporter', 'hidden'];
		},
	},
  methods:{
    maybeMove() {
      if (!this.disabled) {
        this.move();
      }
    },
    move() {
		this.waiting = false;
		this.parent = document.querySelector(this.to);
		if (!this.parent) 
		{
			this.disable();
			this.waiting = true;
			return;
		}
		if (this.where === 'before')
			this.parent.prepend(this.getFragment());
		else
			this.parent.appendChild(this.getFragment());
    },
    disable() {
      this.$el.appendChild(this.getFragment());
      this.parent = null;
    },
    getFragment() {
      const fragment = document.createDocumentFragment();
      this.nodes.forEach(node => fragment.appendChild(node));
      return fragment;
    },
    onMutations(mutations) 
	{
      let shouldMove = false;
      for (let i = 0; i < mutations.length; i++) {
        const mutation = mutations[i];
        const filteredAddedNodes = Array.from(mutation.addedNodes).filter(node => !this.nodes.includes(node));

        if (Array.from(mutation.removedNodes).includes(this.parent)) {
          this.disable();
          this.waiting = !this.disabled;
        } else if (this.waiting && filteredAddedNodes.length > 0) {
          shouldMove = true;
        }
      }

      if (shouldMove) {
        this.move();
      }
    },
    bootObserver() {
      if (this.observer) {
        return;
      }

      this.observer = new MutationObserver(mutations => this.onMutations(mutations));
      this.observer.observe(document.body, {childList:true, subtree:true, attributes:false, characterData:false});

      if (this.childObserver)
        return;
		this.childObserver = new MutationObserver(mutations => {
			const childChangeRecord = mutations.find(i => i.target === this.$el);
			if (childChangeRecord) 
			{
			  // Remove old nodes before update position.
				this.nodes.forEach((node) => node.parentNode && node.parentNode.removeChild(node));
				this.nodes = this.getComponentChildrenNode();
				this.maybeMove();
			}
		});

      this.childObserver.observe(this.$el, {childList:true, subtree:false, attributes:false, characterData:false});
    },
    teardownObserver() {
      if (this.observer) {
        this.observer.disconnect();
        this.observer = null;
      }
      if (this.childObserver) {
        this.childObserver.disconnect();
        this.childObserver = null;
      }
    },
    getComponentChildrenNode() {
		return this.$vnode.componentOptions.children.map((i) => i.elm).filter((i) => i);
    },
  },
})

Vue.component('attachment', {
	template: '<div class="usam_attachments" @drop="fileDrop" @dragover="aDrop"><div class="usam_attachments__file" v-if="Object.keys(file).length"><a class="usam_attachments__file_delete delete" @click="fileDelete"></a><div class="attachment_icon"><img v-show="file.icon !== undefined" :src="file.icon"/><progress-circle v-show="load" :percent="percent"></progress-circle></div><div class="attachment__file_data"><div class="filename">{{file.title}}</div><div v-if="error" class="attachment__file_data__error" :class="[error?`loading_error`:``]">{{error}}</div><div v-else class="filesize"><a class="attachment__file_data_download" download :href="file.url" title ="Сохранить этот файл себе на компьютер" target="_blank">Скачать</a>{{file.size}}</div></div></div><div class ="attachments__placeholder" @click="fileAttach" v-else><div class="attachments__placeholder__text">Перетащите или нажмите, чтобы прикрепить файл</div><div class="attachments__placeholder__select"><span class="dashicons dashicons-paperclip"></span>Выбрать файл</div></div><input type="file" @change="fileChange"></div>',
	props:{
		value:{required:false, default:0},
		attachment:{type:Object, required:false, default:() => ({})},
		args:{type:Object, required:false, default:() => ({})}
	},
	data() {
		return {
			file:this.attachment, load:false, percent:0, error:''
		}
	},
	methods: {
		fileDelete(k, i)
		{
			this.file = {};
			this.$emit('input', 0);
		},
		fileDrop(e)
		{ 
			e.preventDefault();
			e.currentTarget.classList.remove('over');
			this.fileUpload( e.dataTransfer.files );
		},		
		fileAttach(e)
		{ 			
			let el = e.target.querySelector('input[type="file"]');			
			if ( el )
				el.click();			
			else if ( e.currentTarget.nextElementSibling )
				e.currentTarget.nextElementSibling.click();	
		},			
		aDrop(e) {					
			e.preventDefault();			
			e.currentTarget.classList.add('over');			
		},		
		fileChange(e)
		{	
			if (!e.target.files[0] ) 
				return;
			this.fileUpload( e.target.files );		
		},
		fileUpload(files)
		{	
			this.load = true;
			this.percent = 0;
			this.error = '';			
			this.file = {title:usam_get_attachment_title(files[0].name), size:formatFileSize(files[0].size)};
			usam_form_save( this.formFileData( files[0] ), ( r ) => { 
				if ( r.status == 'success' )
				{ 
					this.load = false;
					this.file = r;
					this.$emit('input', r.id);
				}
				else
					this.error = r.error_message;
			}, (e)=> this.percent = e.loaded*100/e.total, 'upload' );	
		},
		formFileData(f)
		{
			var fData = new FormData();				
			fData.append('file', f);
			for (k in this.args)	
				fData.append(k, this.args[k]);	
			return fData;
		},
	}
})