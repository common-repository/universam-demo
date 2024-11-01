<?php
/*
Описание: Шаблон подписки на новости
*/ 
?>
<?php 	
require_once( USAM_FILE_PATH .'/includes/feedback/mailing_lists_query.class.php' );
$lists = usam_get_mailing_lists( );
if ( isset($_GET['stat_id']) || isset($_GET['email']) )
{
	if ( isset($_GET['stat_id']) )
	{
		$stat_id = (int)$_GET['stat_id'];	
		$stat = usam_get_user_stat_mailing( $stat_id );			
		$email = $stat['communication'];
	}
	else
		$email = sanitize_email($_GET['email']);
}
else
{
	$userlists = usam_get_currentuser_list( );							
	$current_user = wp_get_current_user();	
	$email = $current_user->user_email;
}
$subscriber_list = usam_get_subscriber_list( $email );
$user_lists = array();
foreach($subscriber_list as $list)
{
	$user_lists[$list->communication][$list->list] = $list->status;
}
?> 	
<div class = "your_subscribed">	
	<div class = "user_mail"><div class = "mail_div"><?php _e( 'Ваш почтовый ящик', 'usam'); ?>: <span><?php echo $email; ?></span></div></div>
	<div class="edit_form">	
		<?php						
		if ( !empty($lists) )
		{
			foreach($lists as $key => $list)
			{ 						
				if ( !empty($user_lists[$current_user->user_email]) && isset($user_lists[$current_user->user_email][$list->id]) && $user_lists[$current_user->user_email][$list->id] == 1 )
					$checked = 'checked="checked"';
				else
				{
					if ( empty($list->view) )
						continue;	
					
					$checked = '';							
				}			
				?>
				<div class="edit_form__row">
					<div class ="edit_form__item">
						<label><input class="option-input js-user-list" type="checkbox" data-communication="<?php echo $current_user->user_email; ?>" data-list_id="<?php echo $list->id; ?>" <?php echo $checked; ?>><?php echo $list->name; ?></label>
					</div>
				</div>	
				<?php
			}
		}
		else
			_e("Нет доступных подписок","usam");
		?>
	</div>
</div>