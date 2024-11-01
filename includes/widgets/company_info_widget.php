<?php
/**
 * Виджет выводит контакты компании
 */
class USAM_Widget_Company_Info extends WP_Widget
{
	function __construct() 
	{
		$widget = array(
			'classname'   => 'widget_company_info',
			'description' => __('Универсам: Контакты компании', 'usam')
		);
		parent::__construct( 'usam_company_info', __('Контакты компании', 'usam'), $widget );
	}
	
	function widget( $args, $instance )
	{
		$instance = wp_parse_args( (array)$instance, ['title' => '', 'logo' => true, 'phone' => true, 'email' => true, 'address' => true, 'map_button' => true, 'schedule' => '']);
		extract( $args );
		
		echo $before_widget;
		$info = usam_shop_requisites_shortcode();
		?>
		<div itemscope itemtype="http://schema.org/Organization">					
			<?php			
			$logo = get_custom_logo();
			if ( !empty($instance['logo']) && $logo ) 
			{ 
				?>
				<div class="company_info company_info__logo">
					<?php echo $logo; 
					if ( $instance['title'] )
					{
						?><div class="company_info__title"><?php echo $instance['title']; ?></div><?php
					}
					?>
				</div><?php
			}		
			$phones = usam_get_phones();			
			?>
			<?php if( !empty($instance['phone']) && !empty($phones) ) { ?>
				<div class="company_info company_info__telephones">
					<?php usam_svg_icon("phone", "phone_svg_icon"); ?>
					<div class="company_info__telephones_group">
					<?php foreach ($phones as $key => $phone) { ?>
						<div class="company_info__telephone">
							<a href="tel:<?php echo usam_phone_format($phone['phone'], $phone['format']); ?>"><span><?php echo usam_phone_format($phone['phone'], $phone['format']); ?></a>
						</div>
					<?php } ?>	
					</div>						
				</div>				
			<?php } ?>
			<?php if ( !empty($instance['email']) ) { ?>
			<div class="company_info company_info__email" itemprop="email">
				<a href="mailto:<?php echo usam_get_shop_mail(); ?>"><?php usam_svg_icon("email", "mail_svg_icon"); ?><?php echo usam_get_shop_mail(); ?></a>
			</div>
			<?php } ?>
			<?php if ( !empty($instance['schedule']) ) { ?>
			<div class="company_info company_info__schedule" itemscope itemtype="http://schema.org/OpeningHoursSpecification">
				<?php usam_svg_icon("schedule", "schedule_svg_icon"); ?><div><?php echo $instance['schedule']; ?></div>
			</div>
			<?php } ?>
			<?php 
			if ( $instance['address'] || $instance['map_button'] ) 
			{ 				
				if ( !empty($info['full_legaladdress']) ) 
				{
					if ( $instance['address'] )
					{
						?><div class="company_info company_info__address" itemprop="address" itemscope itemtype="http://schema.org/PostalAddress">
							<?php usam_svg_icon("marker", "schedule_svg_icon"); ?>
							<meta itemprop='streetAddress' content='<?php echo $info['contactaddress']; ?>'/>	
							<meta itemprop='postalCode' content='<?php echo $info['contactpostcode']; ?>'/>	
							<meta itemprop='addressLocality' content='<?php echo $info['contactcity']; ?>'/>	
							<?php if ( isset($info['full_legaladdress']) ){ ?>							
								<div class="company_info__address_text"><?php echo $info['full_legaladdress']; ?></div>
							<?php } ?>
						</div><?php 
					}
					if ( $instance['map_button'] ) 
					{ 
						?><p class="company_info company_info__map_button"><a href="https://www.google.ru/maps/search/<?php echo urlencode($info['full_legaladdress']); ?>" class="button button-v1" target="_blank"><?php usam_svg_icon("marker"); _e("На карте","usam") ?></a></p><?php 
					} 
				}			
			} 	
			?>
			<meta itemprop='name' content='<?php echo $info['full_company_name']; ?>'>
			<meta itemprop='telephone' content='<?php echo usam_get_shop_phone(); ?>'>	
		</div>
		<?php 
		echo $after_widget;
	}
	
	function update( $new_instance, $old_instance ) 
	{
		$instance = $old_instance;
		$instance['title']  = strip_tags( $new_instance['title'] );
		$instance['logo'] = (int)$new_instance['logo'];
		$instance['phone'] = (int)$new_instance['phone'];
		$instance['email'] = (int)$new_instance['email'];
		$instance['address'] = (int)$new_instance['address'];
		$instance['map_button'] = (int)$new_instance['map_button'];
		$instance['schedule'] = $new_instance['schedule'];
		return $instance;
	}
	
	function form( $instance )
	{	
		$instance = wp_parse_args( (array)$instance, ['title' => '', 'logo' => true, 'phone' => true, 'email' => true, 'address' => true, 'map_button' => true, 'schedule' => '']);				
		?>			
		<p>		
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('logo'); ?>" name="<?php echo $this->get_field_name('logo'); ?>"<?php checked( $instance['logo'] ); ?> value='1'/>
			<label for="<?php echo $this->get_field_id('logo'); ?>"><?php _e('Показать лого', 'usam'); ?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Название компании:', 'usam'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo htmlspecialchars(esc_attr( $instance['title'] )); ?>" />
		</p>		
		<p>		
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('phone'); ?>" name="<?php echo $this->get_field_name('phone'); ?>"<?php checked( $instance['phone'] ); ?> value='1'/>
			<label for="<?php echo $this->get_field_id('phone'); ?>"><?php _e('Показать телефоны', 'usam'); ?></label>
		</p>	
		<p>		
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('email'); ?>" name="<?php echo $this->get_field_name('email'); ?>"<?php checked( $instance['email'] ); ?> value='1'/>
			<label for="<?php echo $this->get_field_id('email'); ?>"><?php _e('Показать почту', 'usam'); ?></label>
		</p>	
		<p>		
			<label for="<?php echo $this->get_field_id('schedule'); ?>"><?php _e('График работы', 'usam'); ?></label>
			<textarea name="<?php echo $this->get_field_name('schedule'); ?>" rows="3" id ="<?php echo $this->get_field_id('schedule'); ?>" style="width:100%;"><?php echo htmlspecialchars($instance['schedule']); ?></textarea>
		</p>	
		<p>		
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('address'); ?>" name="<?php echo $this->get_field_name('address'); ?>"<?php checked( $instance['address'] ); ?> value='1'/>
			<label for="<?php echo $this->get_field_id('address'); ?>"><?php _e('Показать адрес', 'usam'); ?></label>
		</p>		
		<p>		
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('map_button'); ?>" name="<?php echo $this->get_field_name('map_button'); ?>"<?php checked( $instance['map_button'] ); ?> value='1'/>
			<label for="<?php echo $this->get_field_id('map_button'); ?>"><?php _e('Показать кнопку карты', 'usam'); ?></label>
		</p>			
		<?php
	}
}
?>