<?php
require_once(USAM_FILE_PATH . "/admin/map-view/contacts_map_view.class.php");
class USAM_monitor_Map_View extends USAM_Contacts_Map_View
{		
	public function prepare_items( ) 
	{		
		$this->get_contacts_query_vars(['online' => true]);
		$contacts = usam_get_contacts( $this->query_vars );
		$points = array();
		foreach ( $contacts as $contact ) 
		{
			$latitude = (string)usam_get_contact_metadata( $contact->id, 'latitude' );
			$longitude = (string)usam_get_contact_metadata( $contact->id, 'longitude' );
			$address = (string)usam_get_contact_metadata( $contact->id, 'address' ); 
			$sum = $contact->sum ? "<div class='map__pointer_sum'>".sprintf(__("Всего куплено %s", "usam"),"<span>$contact->sum</span>")."</div>" : "";
			$points[] = array( 'id' => $contact->id, 'title' => $contact->appeal, 'description' => "<div class='map__pointer'><div class='map__pointer_foto'><img src='".usam_get_contact_foto( $contact->id )."'></div><div class='map__pointer_text'><div class='map__pointer_name'><a href='".usam_get_contact_url( $contact->id )."'>".$contact->appeal."</a></div><div class='map__pointer_address'>$address</div>$sum</div></div>", 'latitude' => $latitude, 'longitude' => $longitude );	
		}
		return $points;
	}
}
?>