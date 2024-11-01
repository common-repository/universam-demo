<?php
/**
 * Класса виджет кнопки регистрации входа
 */
class USAM_Widget_Personal_Account extends WP_Widget
{
	function __construct() 
	{
		$widget = array(
			'classname'   => 'widget_personal_account',
			'description' => __('Универсам: Виджет личного кабинета', 'usam')
		);
		parent::__construct( 'usam_personal_account', __('Личный кабинет', 'usam'), $widget );
	}
	
	function widget( $args, $instance )
	{
		global $cache_enabled;
		extract( $args );		
				
		echo $before_widget;	
		
		if ( is_user_logged_in() ) 
		{ 
			if ( $instance['personal_account_icon'] )
				usam_svg_icon( $instance['personal_account_icon'], 'widget_personal_account__icon js-toggle-menu' );
			if ( $instance['personal_account_title'] )
			{
				?><span class="personal_account__title js-toggle-menu"><?php echo $instance['personal_account_title'] ?></span><?php 
			}			
			?>
			<div class="personal_account__menu js-menu">
				<a href="<?php echo usam_get_url_system_page('your-account'); ?>/"><?php _e( 'Мой профиль', 'usam'); ?></a>
				<a href="<?php echo usam_get_url_system_page('your-account'); ?>/my-orders/"><?php _e( 'Мои заказы', 'usam'); ?></a>
				<a href="<?php echo usam_get_url_system_page('your-account'); ?>/my-bonus"><?php _e( 'Мои бонусы', 'usam'); ?></a>
				<a href="<?php echo usam_get_url_system_page('your-account'); ?>/my-comments/"><?php _e( 'Мои отзывы', 'usam'); ?></a>
				<a href="<?php echo wp_logout_url(); ?>"><?php _e( 'Выйти', 'usam'); ?></a>
			</div>
			<?php 
		} else { ?>						
			<a href="<?php echo usam_get_url_system_page('login') ?>" class="widget_personal_account__login" title="<?php _e( 'Вход или регистрация', 'usam'); ?>">
				<?php 
				if ( $instance['icon'] )
					usam_svg_icon( $instance['icon'], 'widget_personal_account__icon' );
				if ( $instance['title'] )
				{
					?><span class="personal_account__title"><?php echo $instance['title'] ?></span><?php 
				}
				?>
			</a>			
			<?php 
		}			
		echo $after_widget;
	}
	
	function update( $new_instance, $old_instance ) 
	{
		$instance = $old_instance;
		$instance['icon'] = sanitize_title($new_instance['icon']);
		$instance['title'] = sanitize_text_field(stripslashes($new_instance['title']));
		$instance['personal_account_icon'] = sanitize_title($new_instance['personal_account_icon']);
		$instance['personal_account_title'] = sanitize_text_field(stripslashes($new_instance['personal_account_title']));
		return $instance;
	}
	
	function form( $instance )
	{	
		$instance = wp_parse_args( (array)$instance, [
			'title' => __('Вход / Регистрация', 'usam'),	
			'icon' => 'marker',		
			'personal_account_icon' => 'marker',	
			'personal_account_title' => __('Личный кабинет', 'usam'),				
		]);				
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Название кнопки:', 'usam'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo htmlspecialchars($instance['title']); ?>" />
		</p>		
		<p>
			<label for="<?php echo $this->get_field_id( 'icon' ); ?>"><?php _e( 'Иконка:', 'usam'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'icon' ); ?>" name="<?php echo $this->get_field_name( 'icon' ); ?>" type="text" value="<?php echo htmlspecialchars($instance['icon']); ?>" />	
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('personal_account_title'); ?>"><?php _e( 'Название кнопки кабинета:', 'usam'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'personal_account_title' ); ?>" name="<?php echo $this->get_field_name( 'personal_account_title' ); ?>" type="text" value="<?php echo htmlspecialchars($instance['personal_account_title']); ?>" />
		</p>		
		<p>
			<label for="<?php echo $this->get_field_id( 'personal_account_icon' ); ?>"><?php _e( 'Иконка кабинета:', 'usam'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'personal_account_icon' ); ?>" name="<?php echo $this->get_field_name( 'personal_account_icon' ); ?>" type="text" value="<?php echo htmlspecialchars($instance['personal_account_icon']); ?>" />
		</p>	
		<?php
	}
}
?>