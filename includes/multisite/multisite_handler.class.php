<?php
class USAM_Multisite_Handler 
{
	function __construct( ) 
	{			
		if ( !usam_is_license_type('FREE') )
		{
			//add_filter('wp_insert_post_empty_content', [&$this, 'insert_post_empty_content'], 10, 2 );	
			if ( !is_main_site() )
			{ 
				add_action('admin_menu', [&$this, 'admin_menu'], 999);
			//	add_filter( 'map_meta_cap', [&$this, 'map_meta_cap'], 10, 100);
			//	add_filter( 'user_has_cap', [&$this, 'user_has_cap'], 10, 4); 
			}
			else
			{ 
				add_action('usam_insert_product', [&$this, 'insert_post'], 10, 3);
				add_action('usam_edit_product', [&$this, 'edit_product'], 10, 3);
				add_action('usam_add_images_links', [&$this, 'insert_media'], 10, 2);				
				add_action( 'created_term', [&$this, 'created_term'], 10, 3);
				add_action( "edited_term", [&$this, 'created_term'], 10 , 3 );
				add_action( 'usam_property_insert', [&$this, 'insert_property']);
				add_action( 'usam_property_group_insert', [&$this, 'property_group_insert']);
				add_action( 'set_object_terms', [&$this, 'set_object_terms'], 10 , 6 );
			}
		}
	}	
	
	function set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids )
	{
		global $wpdb;
		remove_action( 'set_object_terms', [$this, 'set_object_terms'], 10 , 6 );
		$sites = get_sites(['site__not_in' => [0,1]]);	
		foreach( $sites as $site )
		{		
			switch_to_blog( $site->blog_id );				
			$id = usam_get_post_id_multisite( $object_id );
			if ( $id )
			{	
				if ( $taxonomy != 'usam-product_type' )
				{
					$numeric = true;
					foreach( $terms as $k => $term )
					{
						if ( !is_numeric($term) )
						{
							$numeric = false;
							break;
						}
						else
							$terms[$k] = (int)$term;	
					}
					if ( $numeric && $terms )
					{							
						$terms = $wpdb->get_col("SELECT multisite_term_id FROM ".usam_get_table_db('linking_terms_multisite')." WHERE term_id IN (".implode(',', $terms).")");
						if ( !$terms )
							continue;
						$terms  = array_map( 'intval', $terms );						
					}
				}
				wp_set_object_terms( $id, $terms, $taxonomy, $append );		
			}
		}
		switch_to_blog( 1 );	
		add_action('set_object_terms', [&$this, 'set_object_terms'], 10 , 6 );		
	}
	
	function insert_property($t)
	{
		global $wpdb;
		$data = $t->get_data();
		remove_action( 'usam_property_insert', [$this, 'insert_property']);
		$sites = get_sites(['site__not_in' => [0,1]]);	
		foreach( $sites as $site )
		{		
			switch_to_blog( $site->blog_id );		
			if ( !usam_get_properties(['fields' => 'id', 'number' => 1, 'code' => $data['code']]) )
				usam_insert_property( $data );
		}
		switch_to_blog( 1 );	
		add_action('usam_property_insert', [&$this, 'insert_property']);
	}
	
	
	function property_group_insert($t)
	{
		global $wpdb;
		$data = $t->get_data();
		remove_action( 'usam_property_group_insert', array($this, 'property_group_insert'));
		$sites = get_sites(['site__not_in' => [0,1]]);	
		foreach( $sites as $site )
		{		
			switch_to_blog( $site->blog_id );	
			if ( !usam_get_property_groups(['fields' => 'id', 'number' => 1, 'code' => $data['code']]) )
				usam_insert_property_group( $data );
		}
		switch_to_blog( 1 );	
		add_action('usam_property_group_insert', [&$this, 'property_group_insert']);
	}
	
	function insert_media( $product_id, $data )
	{
		global $wpdb;		
		remove_action( 'usam_add_images_links', [&$this, 'insert_media'], 10, 2);
		
		$attachments = usam_get_product_images( $product_id );
		$attach = [];
		foreach( $attachments as $attachment )
		{
			$attach[$attachment->ID] = get_attached_file( $attachment->ID );
		}
		$thumbnail_id = get_post_thumbnail_id( $product_id );	
		
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$sites = get_sites(['site__not_in' => [0,1]]);	
		foreach( $sites as $site )
		{						
			switch_to_blog( $site->blog_id );			
			$id = usam_get_post_id_multisite( $product_id );
			if ( $id )
			{	
				$multisite_attachments = [];
				foreach( usam_get_product_images( $id ) as $attachment )
				{
					$multisite_attachments[$attachment->ID] = $attachment;
				}
				if ( !$multisite_attachments && !$attachments )
					continue;
								
				$attachment_multisite = $wpdb->get_results("SELECT * FROM ".usam_get_table_db('linking_posts_multisite')." WHERE ID IN (".implode(',', array_keys($attach)).")");
				$attachment_site_post_ids = [];				
				foreach( $attachment_multisite as $attachment )
				{
					$attachment_site_post_ids[$attachment->ID] = $attachment->multisite_post_id;
				}		
				foreach( $attachments as $attachment )
				{				
					if ( !isset($attachment_site_post_ids[$attachment->ID]) || !isset($multisite_attachments[$attachment_site_post_ids[$attachment->ID]]) )
					{
						$filepath = $attach[$attachment->ID];		
						$upload_dir = wp_upload_dir( );					
						$filename = basename($filepath);
						$newfile = $upload_dir['path'].'/'.wp_unique_filename( $upload_dir['path'], $filename );		
						if ( copy($filepath, $newfile) ) 
						{		
							$attachment_id = media_handle_sideload(['name' => $filename, 'tmp_name' => $newfile], $id, $attachment->post_title, (array)$attachment );						
							if( !is_wp_error($attachment_id) )
							{
								$attachment_site_post_ids[$attachment->ID] = $attachment_id;
								$result = $wpdb->insert(usam_get_table_db('linking_posts_multisite'), ['ID' => $attachment->ID, 'multisite_post_id' => $attachment_id], ['ID' => '%d', 'multisite_post_id' => '%d']);
							}
						}
					}
					else
					{
						$attachment_site = $multisite_attachments[$attachment_site_post_ids[$attachment->ID]];
						if ( $attachment_site->menu_order != $attachment->menu_order || $attachment_site->ID != $id)
							wp_update_post(['ID' => $attachment_site->ID, 'post_parent' => $id, 'menu_order' => $attachment->menu_order]);
					}
				}	
				if ( $thumbnail_id && isset($attachment_site_post_ids[$thumbnail_id]) )
				{
					set_post_thumbnail( $id, $attachment_site_post_ids[$thumbnail_id] );				
				}
			}			
		}		
		switch_to_blog( 1 );
		add_action('usam_add_images_links', [&$this, 'insert_media'], 10, 2);
	}
					
	function insert_post($product_id, $data, $attributes)
	{
		global $wpdb;		
		remove_action( 'usam_edit_product', array($this, 'insert_post'), 10, 3 );
		remove_action( 'usam_insert_product', array($this, 'insert_post'), 10, 3 );
		remove_action( 'set_object_terms', [$this, 'set_object_terms'], 10 , 6 );
		$sites = get_sites(['site__not_in' => [0,1]]);		
		$new_data = $data;
				
		foreach( $sites as $site )
		{		
			switch_to_blog( $site->blog_id );	
			if ( !empty($data['post_parent']) )
			{
				$new_data['post_status'] = 'publish';	
				$new_data['post_parent'] = usam_get_post_id_multisite( $data['post_parent'] );
				if ( !$new_data['post_parent'] )
					continue;
			}
			if ( !empty($new_data['tax_input']) )
			{
				$terms = [];
				foreach( $new_data['tax_input'] as $taxonomy => $new_terms )
				{
					if ( $taxonomy == 'usam-product_type' )
						$terms[$taxonomy] = $new_terms;
					elseif ( $new_terms )
						$terms[$taxonomy] = $wpdb->get_col("SELECT multisite_term_id FROM ".usam_get_table_db('linking_terms_multisite')." WHERE term_id IN (".implode(',', $new_terms).")");
				}
				$new_data['tax_input'] = $terms;
			}					
			$product = new USAM_Product( $new_data );				
			$multisite_post_id = $product->insert_product( $attributes );				
			if ( $wpdb->insert(usam_get_table_db('linking_posts_multisite'), ['ID' => $product_id, 'multisite_post_id' => $multisite_post_id], ['ID' => '%d', 'multisite_post_id' => '%d']) )
				wp_cache_set( $product_id, $multisite_post_id, 'usam_post_id_multisite_'.$site->blog_id );
		}	
		switch_to_blog( 1 );	
		add_action('set_object_terms', [&$this, 'set_object_terms'], 10 , 6 );		
		add_action('usam_edit_product', [&$this, 'insert_post'], 10, 3);
		add_action('usam_insert_product', [&$this, 'insert_post'], 10, 3);
	}
	
	function edit_product($product_id, $data, $attributes)
	{
		global $wpdb;		
		remove_action( 'usam_edit_product', array($this, 'insert_post'), 10, 3 );
		remove_action( 'usam_insert_product', array($this, 'insert_post'), 10, 3 );
		remove_action( 'set_object_terms', [$this, 'set_object_terms'], 10 , 6 );
		
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		
		$sites = get_sites(['site__not_in' => [0,1]]);	
		
		$attachments = usam_get_product_images( $product_id );
		$attach = [];
		foreach( $attachments as $attachment )
		{
			$attach[$attachment->ID] = get_attached_file( $attachment->ID );
		}
		$thumbnail_id = get_post_thumbnail_id( $product_id );	
		
		$data['tax_input'] = [];
		foreach(['usam-category', 'usam-brands', 'usam-catalog', 'usam-category_sale', 'usam-variation'] as $taxonomy )
			$data['tax_input'][$taxonomy] = wp_get_post_terms($product_id, $taxonomy, ['fields' => 'ids']);
	
		$new_data = $data;
		foreach( $sites as $number => $site )
		{		
			if ( (usam_is_license_type('SMALL_BUSINESS') || usam_is_license_type('LITE')) && $number == 2 )
				break;
		
			switch_to_blog( $site->blog_id );
			$multisite_post_id = usam_get_post_id_multisite( $product_id );
			if ( !empty($data['post_parent']) )
			{
				$new_data['post_parent'] = usam_get_post_id_multisite( $data['post_parent'] );
				if ( !$new_data['post_parent'] )
					continue;
			}
			if ( !empty($new_data['tax_input']) )
			{
				$terms = [];
				foreach( $new_data['tax_input'] as $taxonomy => $new_terms )
				{
					if ( $taxonomy == 'usam-product_type' )
						$terms[$taxonomy] = $new_terms;
					elseif ( $new_terms )
						$terms[$taxonomy] = $wpdb->get_col("SELECT multisite_term_id FROM ".usam_get_table_db('linking_terms_multisite')." WHERE term_id IN (".implode(',', $new_terms).")");
				}
				$new_data['tax_input'] = $terms;
			}		
			if ( !$multisite_post_id )
			{							
				$product = new USAM_Product( $new_data );				
				$multisite_post_id = $product->insert_product( $attributes );				
				if ( $wpdb->insert(usam_get_table_db('linking_posts_multisite'), ['ID' => $product_id, 'multisite_post_id' => $multisite_post_id], ['ID' => '%d', 'multisite_post_id' => '%d']) )
					wp_cache_set( $product_id, $multisite_post_id, 'usam_post_id_multisite_'.$site->blog_id );
			}
			else
			{					
				$product = new USAM_Product( $multisite_post_id );		
				$product->set( $new_data ); //update_product
				$product->set_terms( );	
			}					
			$multisite_attachments = [];
			foreach( usam_get_product_images( $multisite_post_id ) as $attachment )
			{
				$multisite_attachments[$attachment->ID] = $attachment;
			}
			if ( !$multisite_attachments && !$attachments )
				continue;
							
			$attachment_site_post_ids = [];			
			if ( $attach )
			{
				$attachment_multisite = $wpdb->get_results("SELECT * FROM ".usam_get_table_db('linking_posts_multisite')." WHERE ID IN (".implode(',', array_keys($attach)).")");					
				foreach( $attachment_multisite as $attachment )
				{
					$attachment_site_post_ids[$attachment->ID] = $attachment->multisite_post_id;
				}
			}
			foreach( $attachments as $attachment )
			{				
				if ( !isset($attachment_site_post_ids[$attachment->ID]) || !isset($multisite_attachments[$attachment_site_post_ids[$attachment->ID]]) )
				{
					$filepath = $attach[$attachment->ID];		
					$upload_dir = wp_upload_dir( );					
					$filename = basename($filepath);
					$newfile = $upload_dir['path'].'/'.wp_unique_filename( $upload_dir['path'], $filename );	
					if ( copy($filepath, $newfile) ) 
					{		
						$attachment_id = media_handle_sideload(['name' => $filename, 'tmp_name' => $newfile], $multisite_post_id, $attachment->post_title, (array)$attachment );						
						if( !is_wp_error($attachment_id) )
						{
							$attachment_site_post_ids[$attachment->ID] = $attachment_id;
							if ( $wpdb->insert(usam_get_table_db('linking_posts_multisite'), ['ID' => $attachment->ID, 'multisite_post_id' => $attachment_id], ['ID' => '%d', 'multisite_post_id' => '%d']))
							wp_cache_set( $attachment->ID, $attachment_id, 'usam_post_id_multisite_'.$site->blog_id );
						}
					}
				}
				else
				{
					$attachment_site = $multisite_attachments[$attachment_site_post_ids[$attachment->ID]];
					if ( $attachment_site->menu_order != $attachment->menu_order || $attachment_site->ID != $multisite_post_id)
						wp_update_post(['ID' => $attachment_site->ID, 'post_parent' => $multisite_post_id, 'menu_order' => $attachment->menu_order]);
				}
			}	
			if ( $thumbnail_id && isset($attachment_site_post_ids[$thumbnail_id]) )
			{
				set_post_thumbnail( $multisite_post_id, $attachment_site_post_ids[$thumbnail_id] );				
			}
		}	
		switch_to_blog( 1 );	
		add_action('usam_edit_product', [&$this, 'insert_post'], 10, 3);
		add_action('usam_insert_product', [&$this, 'insert_post'], 10, 3);	
		add_action('set_object_terms', [&$this, 'set_object_terms'], 10 , 6 );				
	}
			
	function created_term($term_id, $tt_id, $taxonomy)
	{				
		global $wpdb;
		
		$term = get_term( $term_id, $taxonomy );
		if ( !$term || is_wp_error($term) )
			return false;
		
		remove_action( 'created_term', array($this, 'created_term'), 10, 3 );
		remove_action( 'edited_term', array($this, 'created_term'), 10, 3 );
		
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		
		$attachment_id = (int)get_term_meta($term_id, 'thumbnail', true);
		$filepath = '';
		if ( $attachment_id )
		{
			$attachment = get_post( $attachment_id );
			if ( $attachment )
				$filepath = get_attached_file( $attachment_id );	
			else
				$attachment_id = 0;
		}
		$sites = get_sites(['site__not_in' => [0,1]]);	
		foreach( $sites as $site )
		{		
			switch_to_blog( $site->blog_id );				
			$id = $wpdb->get_var( "SELECT multisite_term_id FROM ".usam_get_table_db('linking_terms_multisite')." WHERE term_id = {$term_id}");	
			if ( $term->parent )
				$parent = $wpdb->get_var( "SELECT multisite_term_id FROM ".usam_get_table_db('linking_terms_multisite')." WHERE term_id = {$term->parent}");
			else
				$parent = 0;				
			if ( !$id )
			{	
				$new_term = term_exists( $term->name, $taxonomy, $parent );
				if ( !$new_term )
				{
					$new_term = wp_insert_term( $term->name, $taxonomy, ['description' => $term->description, 'slug' => $term->slug, 'parent' => $parent] );			
					if( !is_wp_error($new_term) )
						$id = $new_term['term_id'];	
				}
				else
					$id = $new_term['term_id'];				
				if ( $id )
					$wpdb->insert(usam_get_table_db('linking_terms_multisite'), ['term_id' => $term->term_id, 'multisite_term_id' => $id], ['term_id' => '%d', 'multisite_term_id' => '%d']);
				$site_attachment_id = 0;
			}	
			else
			{
				wp_update_term($id, $taxonomy, ['parent' => $parent]);
				$site_attachment_id = (int)get_term_meta($id, 'thumbnail', true);
			}
			if ( $attachment_id )
			{
				$thumbnail_id = usam_get_post_id_multisite( $attachment_id );
				if ( !$thumbnail_id )
				{	
					$upload_dir = wp_upload_dir( );					
					$filename = basename($filepath);
					$newfile = $upload_dir['path'].'/'.wp_unique_filename( $upload_dir['path'], $filename );		
					if ( copy($filepath, $newfile) ) 
					{		
						$thumbnail_id = media_handle_sideload(['name' => $filename, 'tmp_name' => $newfile], 0, $attachment->post_title, (array)$attachment );						
						if( !is_wp_error($thumbnail_id) )
						{
							if ( $wpdb->insert(usam_get_table_db('linking_posts_multisite'), ['ID' => $attachment_id, 'multisite_post_id' => $thumbnail_id], ['ID' => '%d', 'multisite_post_id' => '%d']) )
								wp_cache_set( $attachment_id, $thumbnail_id, 'usam_post_id_multisite_'.$site->blog_id );
						}
							
					}
				}
				if ( !is_wp_error($thumbnail_id) && $site_attachment_id != $thumbnail_id )
					update_term_meta( $id, 'thumbnail', $thumbnail_id );
			}
			else
			{
				update_term_meta( $id, 'thumbnail', 0 );
				update_term_meta( $id, 'images', [] );
			}
			if ( $id )
			{
			//	$id = $wpdb->get_results( "SELECT multisite_term_id FROM ".$wpdb->termmeta." WHERE term_id = {$term_id}");					
			}
		}
		switch_to_blog( 1 );	
		add_action( 'created_term', [&$this, 'created_term'], 10, 3);
		add_action( 'edited_term', [&$this, 'created_term'], 10, 3);
	}
	
	function map_meta_cap($caps, $cap, $user_id, $args) 
	{
		if ( $cap == 'edit_products' )
			$caps[] = 'do_not_allow';	
		return $caps; 
	}
	
	function user_has_cap($allcaps, $caps, $args, $t) 
	{
		if ( $cap == 'edit_products' )
			$capabilities[$cap] = false;	
		return $capabilities; 
	}
		
	function admin_menu() 
	{ 	
		remove_submenu_page('edit.php?post_type=usam-product', 'post-new.php?post_type=usam-product'); 
	}

	function insert_post_empty_content($maybe_empty, $postarr)
	{
		if ( is_main_site() )
			$maybe_empty;
		else
			return true;
	}
}
new USAM_Multisite_Handler();
?>