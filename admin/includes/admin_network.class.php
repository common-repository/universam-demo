<?php
new USAM_Admin_Network();	
class USAM_Admin_Network
{
	function __construct( ) 
	{							
		add_action( 'network_site_info_form', array($this, 'network_site_info_form') );	
		add_action( 'signup_blogform', array($this, 'signup_blogform') );
		add_action( 'wp_update_site', array($this, 'update_site'), 10, 2 );		
	}
	
	function update_site( $new_site, $old_site ) 
	{
		if ( isset($_POST['language']) )
		{			
			$language = sanitize_title($_POST['language']);				
			update_blog_option( $new_site->id, 'usam_language', $language ); 
		}
	}
		
	function network_site_info_form( $id ) 
	{
		$language = get_blog_option( $id, 'usam_language' );
		$this->network_settings(['language' => $language]);
	}
	
	function signup_blogform( $errors ) 
	{
		$this->network_settings(['language' => '']);
	}
	
	function network_settings( $args ) 
	{
		$languages = include USAM_FILE_PATH . '/admin/db/db-install/languages.php';
		?>		
		<div class ="edit_form">
			<div class ="edit_form__item">
				<div class="edit_form__item_name"><?php esc_html_e( 'Язык сайта для клиентов', 'usam');  ?>:</div>
				<div class="edit_form__item_option">
					<select name = "language">
						<?php 
						foreach ( $languages as $language )
						{	
							?><option value='<?php echo $language['code']; ?>' <?php selected($language['code'], $args['language']) ?>><?php echo $language['name']; ?></option><?php 
						}
						?>	
					</select>
				</div>
			</div>	
		</div>
		<?php
	}
}
?>