<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_company_duplicate extends USAM_List_Table
{	
	private $results = array();
	function __construct( $args = array() )
	{	
		parent::__construct( $args );
    }

	public function extra_tablenav( $which ) 
	{ 
		if ( 'top' == $which && $this->filter_box ) 
		{					
			?>			
			<div class="alignleft actions bulkactions">
				<div class = "usam_manage usam_manage-contacts_duplicate">							
					<input type='checkbox' id ="find_duplicate_phone"/>
					<label for="phone"><?php esc_html_e( 'Телефон', 'usam'); ?></label>
					<input type='checkbox' id ="find_duplicate_email"/>
					<label for="email"><?php esc_html_e( 'E-mail', 'usam'); ?></label>
					<?php submit_button( __('Найти дубликаты', 'usam'), 'button-secondary', 'find-duplicates', false ); ?>	
					<?php submit_button( __('Объединить', 'usam'), 'button-secondary', 'button-combine', false ); ?>					
				</div>				
			</div>
			<?php			
		}			
	}		
		
	function column_ID( $item ) 
    {			
		$out = "<a href='".usam_get_company_url( $item['id'] )."'>".$item['id']."</a>";	
		foreach( $item['items'] as $id )
		{				
			$out .= "<hr size='1' width='100%'><a href='".usam_get_company_url( $id )."'>$id</a>";	
			$out .= "<input type='hidden' name='duplicate_".$item['id']."' value='$id' />";
		}	
		echo $out;
	}	
	
	function column_company( $item ) 
    {		
		$count = 0;	
		$out = '';
		foreach( $item['items'] as $id )
		{			
			if ( $this->results[$id]->name != '' )
				$count++;
			$out .= '<br><hr size="1" width="100%">&nbsp;&nbsp;&nbsp;&nbsp;<i><a href="'.usam_get_company_url( $this->results[$id]->id ).'" target="_blank">'.$this->results[$id]->name.'</a></i>';			
		}		
		echo '<i><a href="'.usam_get_company_url( $item['id'] ).'" target="_blank">'.$this->results[$item['id']]->name.'</a></i>'.$this->get_message( $count ).$out;
	}	

	function column_email( $item ) 
    {		
		$count = 0;	
		$out = '';		
		foreach( $item['items'] as $id )
		{
			if ( !empty($this->results[$id]->email) )
			{
				$count++;
				$out .= '<br><hr size="1" width="100%">&nbsp;&nbsp;&nbsp;&nbsp;'.$this->results[$id]->email;				
			}
		}		
		echo $this->results[$item['id']]->email.$this->get_message( $count ).$out;	
	}	
	
	function column_phone( $item ) 
    {		
		$count = 0;	
		$out = '';
		foreach( $item['items'] as $id )
		{
			if ( !empty($this->results[$id]->phone) )
			{
				$count++;
				$out .= '<br><hr size="1" width="100%">&nbsp;&nbsp;&nbsp;&nbsp;'.$this->results[$id]->phone;			
			}
		}	
		echo $this->results[$item['id']]->phone.$this->get_message( $count ).$out;	
	}
	
	private function get_message( $count ) 
    {
		return $count>0?' '.'<b>'.sprintf( _n('Найдено %s совпадений','Найдено %s совпадений', $count, 'usam'), $count ).'</b>':'';	
	}
	 	
	function get_columns()
	{		
        $columns = array(           
			'cb'             => '<input type="checkbox" />',			
			'id'             => __('ID', 'usam'),		
			'company'        => __('Компания', 'usam'),
        );	
		if ( isset($_REQUEST['email']) )
			$columns['email'] = __('Почта', 'usam');
		if ( isset($_REQUEST['phone']) )
			$columns['phone'] = __('Телефон', 'usam');		
        return $columns;
    }	
	
	function prepare_items() 
	{		
		global $wpdb;					
	
		if ( !isset($_REQUEST['phone']) && !isset($_REQUEST['email'])	)			
			return;
					
		$this->where = array( "1=1" );
		$select = ['company.name', 'company.id', 'company.date_insert'];			
		$phone_properties = usam_get_properties( array( 'type' => 'company', 'active' => 1, 'field_type' => array('mobile_phone', 'phone'), 'fields' => 'code' ) );
		$email_properties = usam_get_properties( array( 'type' => 'company', 'active' => 1, 'field_type' => 'email', 'fields' => 'code' ) );
		$phone_join = USAM_TABLE_COMPANY_META." AS com_phone ON (com_phone.company_id=company.id AND com_phone.meta_key IN ('".implode("','",$phone_properties)."'))";
		if ( isset($_REQUEST['phone']) )	
		{							
			$this->joins[] = "INNER JOIN ".$phone_join;			
			$this->joins[] = "INNER JOIN (SELECT meta_value FROM `".USAM_TABLE_COMPANY_META."` WHERE meta_key IN ('".implode("','",$phone_properties)."') AND meta_value != '' GROUP BY meta_value HAVING COUNT(meta_value) >1) AS phone_dupes ON (com_phone.meta_value=phone_dupes.meta_value)";		
			$select[] = "IFNULL( com_phone.meta_value,'') AS phone";
		}				
		$email_join = USAM_TABLE_COMPANY_META." AS com_email ON (com_email.company_id=company.id AND com_email.meta_key IN ('".implode("','",$email_properties)."'))";
		if ( isset($_REQUEST['email']) )	
		{
			$this->joins[] = "INNER JOIN ".$email_join;		
			$this->joins[] = "INNER JOIN (SELECT meta_value FROM `".USAM_TABLE_COMPANY_META."` WHERE meta_key IN ('".implode("','",$email_properties)."') AND meta_value != '' GROUP BY meta_value HAVING COUNT( meta_value ) >1) AS email_dupes ON (com_email.meta_value=email_dupes.meta_value)";				
			$select[] = "IFNULL( com_email.meta_value,'') AS email";				
		}	
		$where = implode( ' OR ', $this->where );	
		$joins = implode( ' ', $this->joins );			
		$sql = "SELECT ".implode( ', ', $select )." FROM ".USAM_TABLE_COMPANY." AS company $joins WHERE $where ORDER BY `company`.`name` LIMIT 5000";
	//	$sql = "SELECT * FROM ".USAM_TABLE_COMPANY_META." WHERE	meta_value IN ( SELECT meta_value FROM `".USAM_TABLE_COMPANY_META."` GROUP BY meta_value HAVING COUNT( meta_value ) >1 ) LIMIT 0 , 30 ";
		
		$results = $wpdb->get_results( $sql );	
		foreach(  $results as $data )
		{
			$this->results[$data->id] = $data;
		}		
		$results = $this->results;
		foreach( $this->results as $key1 => $data1 )
		{
			$items = array();
			unset($results[$key1]);
			foreach( $results as $key2 => $data2 )
			{
				if ( isset($_REQUEST['phone']) )	
				{
					if ( $data1->phone == $data2->phone )
					{
						$items[] = $data2->id;
						unset($results[$key2]);
						continue;
					}				
				}
				if ( isset($_REQUEST['email']) )	
				{
					if ( $data1->email == $data2->email )
					{
						$items[] = $data2->id;
						unset($results[$key2]);
						continue;
					}				
				}
			}
			if ( !empty($items) )
			{					
				$this->items[] = array( 'id' => $data1->id, 'items' => $items);
			}
		} 
	}
}
?>