(function($)
{	
	function category_sort(e, ui)
	{
		var order = $(this).sortable('toArray');			
		usam_send({action: 'set_taxonomy_order', sort_order: order,	nonce: USAM_Taxonomy.set_taxonomy_order_nonce});
	}

	$(function()
	{		
		if ( document.querySelector('#new_term_description') )			
			document.querySelector('.term-description-wrap').remove();
		
		var images_file_frame;
		$('body').on('click', '.taxonomy_thumbnail', function(e) 
		{
			e.preventDefault();	
			var t = $( this );
			var attachment_id = t.data( 'attachment_id' );
			var term_id = t.data( 'id' );		
	
			images_file_frame = wp.media.frames.images_file_frame = wp.media({library: { type: 'image' }, multiple: false});		
			// Pre-select selected attachment
			wp.media.frames.images_file_frame.on('open', function() 
			{			
				var selection = wp.media.frames.images_file_frame.state().get( 'selection' );
				if ( attachment_id > 0 ) 
				{
					attachment = wp.media.attachment( attachment_id );
					attachment.fetch();
					selection.add( attachment ? [ attachment ] : [] );
				}
			});
			
			images_file_frame.on( 'select', function() 
			{				
				attachment = images_file_frame.state().get( 'selection' ).first().toJSON();						
				if ( attachment.sizes.thumbnail )
					var url = attachment.sizes.thumbnail.url;
				else
					var url = attachment.sizes.full.url;					
				usam_send({action: 'set_taxonomy_thumbnail', term_id: term_id, attachment_id: attachment.id, nonce: USAM_Taxonomy.set_taxonomy_thumbnail_nonce});
				t.attr( 'src', url );			
				t.attr( 'data-attachment_id', attachment.id );		
			} ); 
			images_file_frame.open();
		});	
		
		var table = $('body.edit-tags-php .wp-list-table tbody');
		table.find('tbody tr').each(function(){
			var t = $(this),
				id = t.attr('id').replace(/[^0-9]+/g, '');
			t.data('level', USAM_Term_List_Levels[id]);
			t.data('id', id);
		});
		table.usam_sortable_table({stop : category_sort});		
		$('.usam_select_all').on('click', function(e)
		{
			$('input:checkbox', $(this).parent().siblings('.multiple-select') ).each(function(){ this.checked = true; });
			return false;
		});	
		$('.usam_select_none').on('click', function(e)
		{		
			$('input:checkbox', $(this).parent().siblings('.multiple-select') ).each(function(){ this.checked = false; });
			return false;
		});
		$('#term_import').on('click', function(e)
		{		
			e.preventDefault();		
			$('#term_import_window').modal();
		});	
		$('.js-term-status').on('click', function(e)
		{				
			e.preventDefault();		
			var vars = usam_get_url_attrs( location.href );
			var status = $(this).attr("data-status");			
			status = status == 'publish' ? 'hidden' : 'publish';
			$(this).addClass('hide');
			$(this).siblings('.js-term-status_'+status).removeClass('hide');
			var term_id = $(this).parents('tr').attr('id').replace(/[^0-9]/g,"");
			usam_api('term/'+vars['taxonomy']+'/'+term_id, {status:status}, 'POST');
		});				
	});		
})(jQuery);

document.addEventListener("DOMContentLoaded", () => {			
	if( document.getElementById('term-meta-tags') )
	{
		new Vue({		
			el: '#term-meta-tags',
			data() {
				return {					
					data: {meta:{}, postmeta:{}, meta_filter:{}, images:[]},
					id: 0
				};
			},			
			mounted() {
				var vars = usam_get_url_attrs( location.href );
				this.id = vars['tag_ID'];			
				usam_api('term/'+vars['taxonomy']+'/'+this.id, {add_fields:['metas']}, 'GET', (r) => this.data = r);
			}			
		})	
	}
	if( document.getElementById('term_images') )
	{
		new Vue({		
			el: '#term_images',
			data() {
				return {					
					data: {meta:{}, postmeta:{}, meta_filter:{}, images:[], representative_image:0},
					id: 0
				};
			},			
			mounted() {
				var vars = usam_get_url_attrs( location.href );
				this.id = vars['tag_ID'];			
				usam_api('term/'+vars['taxonomy']+'/'+this.id, {add_fields:['images']}, 'GET', (r) => this.data = r);
			},	
			methods: {				
				addMedia(a) 
				{
					for (let i in a)
						this.data.images.push({ID:a[i].id, full:a[i].url});
				},
				deleteMedia(k) 
				{
					this.data.images.splice(k, 1);	
				},	
				allowDrop(e, k) {
					e.preventDefault();
					if( this.oldIndex != k )
					{					
						let v = Object.assign({}, this.data.images[this.oldIndex]);					
						this.data.images.splice(this.oldIndex, 1);	
						this.data.images.splice(k, 0, v );	
						this.oldIndex = k;		
					}
				},
				drag(e, k) {				
					this.oldIndex = k;
					if( e.currentTarget.hasAttribute('draggable') )
						e.currentTarget.classList.add('draggable');	
					else
						e.preventDefault();
				},	
				dragEnd(e, i) {
					e.currentTarget.classList.remove('draggable');
				},
			}
		})	
	}	
	if( document.getElementById('ozon_categories') )
	{
		new Vue({		
			el: '#ozon_categories',
			data() {
				return {					
					categories:[],	
					category:0,	
					application_id:0
				};
			},		
			mounted() {			
				this.application_id = application_id;	
				this.category = ozon_category;	
				usam_api('ozon/'+this.application_id+'/categories', 'GET', (r) => this.categories = r);
			}			
		})			
	}	
})

Vue.component('ozon-categories', {		
	template: '<div><select-list @change="subcategory=$event.id" :lists="lists" :selected="subcategory"></select-list><ozon-categories-contents v-if="subcategory>0 && parentsCategories.length>0" @change="category=$event" :lists="parentsCategories" :selected="selected"/></div>',
	props: {'lists': Array, 'selected': Number|String},
	watch:{
		selected(val, oldVal) 
		{
			if ( val )
				this.subcategory = this.loadSubCategory( this.lists );
				
		},	
		lists(val, oldVal) 
		{
			this.load();
		},
		subcategory(val, oldVal) 
		{
			if ( !this.parentsCategories.length )
				this.$emit('change', val);
		},	
		category(val, oldVal) 
		{
			if ( this.parentsCategories.length )
				this.$emit('change', val);
		}
	},
	computed: {		
		parentsCategories() {	
			var result = this.lists.filter(x => x.id===this.subcategory);	
			return result.length ? result[0].children : [];
		},	
	},
	data() {				
		return {subcategory:0, category:0}
	},
	mounted() 
	{
		this.load();
	},
	methods: {
		load()
		{
			if ( this.selected )
				this.subcategory = this.loadSubCategory( this.lists );
		},
		loadSubCategory( lists )
		{			
			var r = 0;
			for (let i in lists)
			{
				if ( lists[i].id == this.selected )
					return lists[i].id;
				else if ( lists[i].children.length )
				{
					if ( this.loadSubCategory( lists[i].children ) )
						return lists[i].id;
				}
			}
			return r;
		},
	}
})
Vue.component('ozon-categories-contents', {		
	template: '<div><ozon-categories @change="$emit(`change`, $event)" :lists="lists" :selected="selected"/></div>',
	props: {'lists': Array, 'selected': Number|String},
})