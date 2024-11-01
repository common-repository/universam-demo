<?php
// Класс форм объектов
require_once( USAM_FILE_PATH .'/admin/includes/form.class.php' );		
class USAM_View_Form extends USAM_Form
{	
	protected $page = null;	
	protected $tab = null;		
	protected $subtab = null;
	protected $form_name = null;	
	protected $table = null;			
	protected $list_table = null;	
	protected $tabs = [];	
	protected $ribbon = null;		
	protected $not_exist = false;		
	protected $header_title = '';		
	protected $header_content = '';		
	
	public function __construct( $args = [] ) 
	{ 
		if ( isset($args['id']) )
			$this->id = sanitize_title($args['id']);	
		elseif ( isset($_REQUEST['id']) )
			$this->id = sanitize_title($_REQUEST['id']);	
	
		if ( isset($args['page']) )
			$this->page = $args['page'];			
		elseif ( isset($_REQUEST['page']) )
			$this->page = sanitize_title($_REQUEST['page']);	
		
		if ( isset($args['tab']) )
			$this->tab = $args['tab'];	
		elseif ( isset($_REQUEST['tab']) )
			$this->tab = sanitize_title($_REQUEST['tab']);		
			
		if ( isset($args['table']) )
			$this->table = $args['table'];	
		elseif ( isset($_REQUEST['table']) )
			$this->table = sanitize_title($_REQUEST['table']);		
		else
			$this->table = $this->tab;
			
		if ( isset($args['form_name']) )
			$this->form_name = sanitize_title($args['form_name']);	
		elseif ( isset($_REQUEST['form_name']) )
			$this->form_name = sanitize_title($_REQUEST['form_name']);
		
		$this->get_data_tab();
		$this->tabs = apply_filters( 'usam_view_form_tabs_'.$this->form_name, $this->tabs, $this->data );		
		if( !empty($this->tabs) )
		{
			$this->subtab = $this->tabs[0]['slug'];		
			if ( !empty($args['subtab']) )
				$subtab = $args['subtab'];	
			elseif ( isset($_REQUEST['subtab']) )
				$subtab = sanitize_title($_REQUEST['subtab']);
			else 
				$subtab = '';
			if ( $subtab )
				foreach ( $this->tabs as $menu )
				{
					if ( $menu['slug'] == $subtab )
					{
						$this->subtab = $menu['slug'];
						break;					
					}			
				}	
		}				
		parent::__construct( $args );
	}	

	protected function main_content_cell_1( ) { }	
	protected function main_content_cell_2( ) { }	
	protected function main_content_cell_3( ) { }	
	
	protected function header_view()
	{	
		?>		
		<div class = "header_main">
			<div id='header_main_content' class = "header_main_content">
				<div class = "main_content_cell"><?php $this->main_content_cell_1(); ?></div>
				<div class = "main_content_cell"><?php $this->main_content_cell_2(); ?></div>
				<div class = "main_content_cell"><?php $this->main_content_cell_3(); ?></div>					
			</div>	
			<?php
			if ( !empty($this->header_content) ) 
			{
				?>
				<h4 class="header_content_name"><?php echo esc_html($this->header_title); ?></h4>
				<div class = "main_content_footer">						
					<?php echo esc_html($this->header_content); ?>									
				</div>	
			<?php } ?>
		</div>	
		<?php	
	}	

	protected function toolbar_buttons( ) 
	{ 
		if ( $this->id != null )
		{							
			$this->display_toolbar_buttons();
			if ( $this->change )
			{
				?><div class="action_buttons__button"><a href="<?php echo add_query_arg(['form' => 'edit']); ?>" class="edit_button"><?php _e('Изменить','usam'); ?></a></div><?php
			}
			$this->delete_button();
		}
	}
	
	protected function form_attributes( ) { }
	
	protected function form_class( ) { }
	
	// Отображение элемента таблицы	
	public function display()
    {							
		?>
		<div id="view_form_<?php echo $this->form_name; ?>" <?php $this->form_attributes(); ?> class="element_form form_<?php echo $this->form_name; ?> <?php echo $this->form_class(); ?>" data-id="<?php echo $this->id; ?>" data-element="<?php echo $this->table; ?>">
			<?php
			if ( empty($this->data) || $this->not_exist )
			{			
				?><h2 class="tab_title form_toolbar"><span class="form_title_go_back"><a href="<?php echo $this->get_url_go_back(); ?>" class="go_back"><span class="dashicons dashicons-arrow-left-alt2"></span></a><span class="form_title"><?php $this->title_tab_none( ); ?></span></span></h2><?php 	
			}
			else
			{
				?>	
				<h2 class="tab_title form_toolbar js-fasten-toolbar"><span class="form_title_go_back"><a href="<?php echo $this->get_url_go_back(); ?>" class="go_back"><span class="dashicons dashicons-arrow-left-alt2"></span></a><span class="form_title"><?php echo $this->get_title_tab( ); ?></span></span>
					<div class="action_buttons"><?php $this->toolbar_buttons();	?></div>
				</h2><?php	
				$this->content_form();
			}
			do_action( 'usam_after_view_form', $this );
			do_action( 'usam_after_form', $this );
			?>			
		</div>
		<?php
	}
	
	protected function content_form() 
	{		
		ob_start();
		$this->display_right();
		$content_right = ob_get_clean();		
		?>
		<div class="content_form_view">				
			<?php
			if ( $content_right || $this->ribbon )
			{
				?>
				<div class="columns-2">
					<div class='page_main_content'>				
						<?php 
						$this->header_view();							
						$this->view_tabs();	
						?>
					</div>	
					<?php 
					if ( $this->ribbon ) 
					{
						$this->display_ribbon_sidebar();						
					}
					else
					{
						?>
						<div class = 'page_sidebar'>	
							<div class = 'menu_fixed_right'>
								<?php echo $content_right; ?>
							</div>	
						</div>	
						<?php
					}
					?>
				</div>
				<?php
			}
			else
			{
				$this->header_view();							
				$this->view_tabs();	
			}
			?>			
		</div>
		<?php
	}
	
	protected function display_right() 
	{
		
	}	
	
	protected function display_ribbon_sidebar() 
	{		
		?>
		<div class = 'ribbon_sidebar'>	
			<div class = 'ribbon_handle'><?php _e( 'Лента', 'usam'); ?></div>
			<?php $this->display_ribbon(); ?>
		</div>	
		<?php 
	}
	
	private function view_tabs() 
	{
		?>
		<div class = 'usam_view_form_tabs'>	 
			<div class="header_tab">
				<a class="tab" v-for='item in <?php echo json_encode( $this->tabs ); ?>' :class="{'active':form_tab==item.slug}" @click="form_tab=item.slug">{{item.title}}<span class="number_events" v-if="'comments'==item.slug && comments > 0">{{comments}}</span></a>
			</div>	
			<div class = "form_view_countent_tabs">		
				<?php
				foreach ( $this->tabs as $menu )
				{						
					?>
					<div class = "tab countent_tab_<?php echo $menu['slug']; ?>"  v-show="form_tab=='<?php echo $menu['slug']; ?>'">
						<?php 										
						$method = 'display_tab_'.$menu['slug'];								
						if ( method_exists($this, $method) )
							$this->$method();							
						elseif ( file_exists(usam_get_filepath_admin("templates/template-parts/form-tabs/".$menu['slug'].".php")) )
							include( usam_get_filepath_admin("templates/template-parts/form-tabs/".$menu['slug'].".php" ) );
						else							
							do_action( 'usam_tab_form_content', $this->form_name, $menu['slug'] );
						?>
					</div>
					<?php
				}	
				?>					
			</div>
		</div>
		<?php 
	}
		
	public function display_tab_report()
	{		
		$file = USAM_FILE_PATH . "/admin/reports-view/{$this->form_name}_reports_view.class.php"; 
		if ( file_exists($file) )
		{ 
			USAM_Admin_Assets::set_graph();
			require_once( $file );
			$class = "USAM_{$this->form_name}_Reports_View";
			$grid = new $class( );
			$grid->display( false );		
		}
	}
	
	public function list_table( $table )
    {					
		$filename = USAM_FILE_PATH .'/admin/list-table/'.$table.'_list_table.php'; 		
		if ( file_exists($filename) ) 
		{
			require_once( $filename );							
			$args = array(
				'singular'  => $this->page,    
				'plural'    => $table,  
				'screen'    => $this->page.'_'.$table,	
				'tab'       => $this->tab,	
				'table'     => $table,	
				'subtab'    => $this->subtab,	
				'form'      => 'view',	
				'id'        => $this->id,
			);							
			$name_class_table = 'USAM_List_Table_'.$table;
			$this->list_table = new $name_class_table( $args );
			$this->list_table->display_table(); 	
		}
	}	
	
	function display_tab_orders()
	{
		$this->list_table( 'orders_form' );			
	}
			
	function display_tab_documents()
	{		
		$customer = isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'contacts'?'contact':'company';
		?>
		<div class ="view_form_tab_buttons table_buttons actions">	
			<?php if ( current_user_can( 'view_suggestions' ) ) {	
				?><a href="#" class="js-action-item button table_buttons__button" data-action="new_<?php echo $customer; ?>" data-group="suggestions" data-id="<?php echo $this->id; ?>"><?php _e('Новое предложение','usam'); ?></a><?php 
			}
			if ( current_user_can( 'view_invoice' ) ) {	
				?><a href="#" class="js-action-item button table_buttons__button" data-action="new_<?php echo $customer; ?>" data-group="invoice" data-id="<?php echo $this->id; ?>"><?php _e('Выставить счет','usam'); ?></a><?php 
			}
			if ( current_user_can( 'view_contracts' ) ) {	
				?><a href="#" class="js-action-item button table_buttons__button" data-action="new_<?php echo $customer; ?>" data-group="contracts" data-id="<?php echo $this->id; ?>"><?php _e('Новый договор','usam'); ?></a><?php
			} ?>
		</div>
		<?php
		include( usam_get_filepath_admin('templates/template-parts/documents/customer-documents-table.php') );
	}
	
	public function display_connect_service( $title, $buttons ) 
	{
		?>
		<div class="blank_state">
			<h2 class="blank_state__message blank_state__icon"><?php echo $title; ?></h2>
			<div class="blank_state__buttons">
				<?php
				foreach ( $buttons as $key => $button )
				{											
					?><a href="<?php echo $button['url']; ?>" class="button"><?php echo $button['title']; ?></a><?php 
				}			
				?>			
			</div>
		</div>
		<?php
	}		

	protected function display_groups( $url = '' ) 
	{
		?>
		<div class ="view_data__row" v-if="crmGroups.length">
			<div class ="view_data__name"><?php _e( 'Группы','usam'); ?>:</div>
			<div class ="view_data__option">
				<div class ="customer_groups">
					<a v-for="group in crmGroups" :href="'<?php echo $url; ?>&groups='+group.id" v-html="group.name"></a>					
				</div>				
			</div>
		</div>
		<?php
	}
	
	function display_map( $properties_type, $title )
	{
		/*$function = "usam_get_{$properties_type}_metadata";
		$latitude = (string)$function( $this->id, 'latitude' );
		$longitude = (string)$function( $this->id, 'longitude' );		
		?>	
		<script src="https://maps.api.2gis.ru/2.0/loader.js?pkg=full"></script>			
		<script src="https://maps.api.2gis.ru/2.0/cluster_realworld.js"></script>
		<link rel="stylesheet" href="https://2gis.github.io/mapsapi/vendors/Leaflet.markerCluster/MarkerCluster.css" />		
		<link rel="stylesheet" href="https://2gis.github.io/mapsapi/vendors/Leaflet.markerCluster/MarkerCluster.Default.css" />			
		<div id="map" style="width: 100%; height: 600px" latitude="<?php echo $latitude; ?>" longitude="<?php echo $longitude; ?>" title="<?php echo $title; ?>"></div>		
		<?php 		*/
	}
	
	function display_form_status( $type )
	{	
		?>	
		<div class ="view_data__row">
			<div class ="view_data__name"><?php _e( 'Статус','usam'); ?>:</div>
			<div class ="view_data__option">
				<?php 
				if ( current_user_can('edit_status_'.$type) ) 
				{ 
					$statuses = usam_get_object_statuses_by_type( $type, $this->data['status'] );
					?>	
					<select v-model='data.status' name = "status" @change="objectStatus">
						<?php
						foreach ( $statuses as $status ) 
						{									
							$style = $status->color != ''?'style="background:'.$status->color.'"':'';												
							?><option value='<?php echo $status->internalname; ?>' <?php echo $style; ?>><?php echo $status->name; ?></option><?php
						}
						?>
					</select>
				<?php 
				} 
				else
					echo usam_display_status( $this->data['status'], $type );
				?>	
			</div>
		</div>
		<?php 
	}
	
	function display_related_documents( $type )
	{
		require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );	
		$parent_documents = usam_get_parent_documents( $this->id, $type );
		$child_documents = usam_get_child_documents( $this->id, $type );		
		$documents_links = [];
		foreach ( $parent_documents as $link )
		{
			$link->status = 'parent';
			$documents_links[] = $link;
		}			
		$x = new stdClass();
		$x->document_type = $type;
		$x->document_id = $this->id;
		$x->status = 'current';
		$documents_links[] = $x;
		foreach ( $child_documents as $link )
		{
			$link->status = 'children';
			$documents_links[] = $link;
		}
		$document_ids = [];
		foreach ( $documents_links as $link )
		{
			if ( !in_array($link->document_type, ['order', 'lead', 'payment', 'shipped']) )
				$document_ids['document'][] = $link->document_id;
			else
				$document_ids[$link->document_type][] = $link->document_id;
		}
		if ( !empty($document_ids['document']) )
		{
			$docs = usam_get_documents(['include' => $document_ids['document']]);
			$documents = [];
			foreach ( $docs as $document ) 
			{
				$documents[$document->type][$document->id] = $document;
			}
		}
		if ( !empty($document_ids['order']) )
		{
			$docs = usam_get_orders(['include' => $document_ids['order']]);			
			foreach ( $docs as $document ) 
			{
				$documents['order'][$document->id] = $document;
			}
		}
		if ( !empty($document_ids['lead']) )
		{
			require_once(USAM_FILE_PATH.'/includes/document/leads_query.class.php');
			$docs = usam_get_leads(['include' => $document_ids['lead']]);
			foreach ( $docs as $document ) 
			{
				$documents['lead'][$document->id] = $document;
			}
		}		
		if ( !empty($document_ids['shipped']) )
		{
			require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
			$docs = usam_get_shipping_documents(['include' => $document_ids['shipped']]);
			foreach ( $docs as $document ) 
			{
				$documents['shipped'][$document->id] = $document;
			}			
		}
		if ( !empty($document_ids['payment']) )
		{
			require_once(USAM_FILE_PATH.'/includes/document/payments_query.class.php');
			$docs = usam_get_payments(['include' => $document_ids['payment']]);
			foreach ( $docs as $document ) 
			{
				$documents['payment'][$document->id] = $document;
			}
		}
		?>
		<div class="related_documents">
			<?php 
			foreach ( $documents_links as $link ) 
			{
				$detail = usam_get_details_document( $link->document_type );				
				$document = $documents[$link->document_type][$link->document_id];	
				if ( $link->status == 'current' )
					$url = '';
				else
					$url = usam_get_document_url( $link->document_id, $link->document_type );
				$sum = isset($document->totalprice) ? $document->totalprice : $document->sum;
				?>
				<div class="related_document related_document-<?php echo $link->status; ?>">
					<div class="related_document__img"></div>
					<div class="related_document__document">
						<div class="related_document-title">
							<a href="<?php echo $url; ?>" class="related_document__document_url" >
								<div class="related_document__document_type_name"><?php printf( __('%s №%s на сумму %s','usam'), $detail['single_name'], $document->number, $this->currency_display($sum) ); ?></div>
								<div class="related_document__document_title"><?php echo isset($document->name)?stripcslashes($document->name):''; ?></div>
							</a>
						</div>
						<div class="related_document__document_date">
							<?php esc_html_e( 'Дата создания', 'usam'); ?>: <span class="related_document__date_insert"><?php echo usam_local_date( $document->date_insert ); ?></span>
						</div>
					</div>
				</div>
			<?php
			}
			?>
		</div>
		<?php
	}	
	
	protected function display_customer( $customer_id, $customer_type )
	{
		$display_text = __('Еще не выбрано','usam');
		if ( $customer_type == 'contact' )
		{
			$contact = usam_get_contact( $customer_id );			
			if ( !empty($contact) )	
			{
				$display_text = "<a href='".usam_get_contact_url( $customer_id )."' target='_blank'>".stripcslashes($contact['appeal'])."</a>";
				if ( !empty($contact['online']) )
				{
					if ( strtotime($contact['online']) >= USAM_CONTACT_ONLINE )
						$display_text .= "<span class='customer_online'></span>";
					else
						$display_text .= "<span class='date_visit'>".sprintf( __('был %s', 'usam'), get_date_from_gmt($contact['online'], 'd.m.Y H:i'))."</span>";
				}
			}
		}
		else
		{								
			$company = usam_get_company( $customer_id );			
			if ( !empty($company) )	
				$display_text = "<a href='".usam_get_company_url( $customer_id )."' target='_blank'>".$company['name']."</a>";
		}
		echo $display_text; 
	}

	protected function display_crm_customer( $customer )
	{	
		if ( empty($customer) )
			return false;
		if ( !empty($customer['thumbnail']) )	
		{
			$image_attributes = wp_get_attachment_image_src( $customer['thumbnail'], array(100, 100) );						
			if ( !empty($image_attributes[0]) )				
				$thumbnail = $image_attributes[0];	
		}
		if ( empty($customer['name']) )
			$customer['name'] = __('Клиент без имени','usam');
		?>				
		<div class ="user_block">	
			<?php
			if ( !empty($thumbnail) )
			{
				?>
				<a href='<?php echo $customer['link']; ?>' target="_blank" class ='image_container usam_foto'><img src="<?php echo esc_url( $thumbnail ); ?>"></a>	
				<?php
			}
			?>
			<div>
				<a href='<?php echo $customer['link']; ?>' target="_blank"><span class="customer_name" ><?php echo $customer['name']; ?></span></a>					
				<div class ="user_capability">					
					<?php 
					if ( !empty($customer['email']) )
					{
						$emails = array();
						foreach ( $customer['email'] as $email ) 
							$emails[] = $email['value'];	
							
						echo '<div class ="communication">'.__('E-mail', 'usam').': '.implode(', ',$emails).'</div>';									
					}
					?>				
					<?php 
					if ( !empty($customer['phone']) )
					{
						$phones = array();
						foreach ( $customer['phone'] as $phone ) 
							$phones[] = $phone['value'];		
							
						echo '<div class ="communication">'.__('т.', 'usam').': '.implode(', ',$phones).'</div>';			
					}
					?>						
				</div>
			</div>
		</div>
		<?php 	
	}	

	public function display_manager_box( $title = '', $select_title = '' )
	{
		$title = $title === '' ? __( 'Менеджер', 'usam') : $title;
		$select_title = $select_title === '' ? __( 'Выбрать менеджера', 'usam') : $select_title;
		?>
		<div class ="view_data__row">
			<div class ="view_data__name"><?php echo $title; ?>:</div>
			<div class ="view_data__option">
				<div class='user_block' v-if="data.manager_id>0" @click="sidebar('managers')">	
					<a class='user_name' v-html="manager.appeal"></a>	
				</div>
				<a v-else @click="sidebar('managers')"><?php echo $select_title; ?></a>
			</div>
		</div>		
		<?php
		add_action('usam_after_view_form',function() {
			require_once( USAM_FILE_PATH.'/admin/templates/template-parts/modal-panel/modal-panel-employees.php' );
		});
		usam_vue_module('list-table');
    }	
}
?>