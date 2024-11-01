<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_ya extends USAM_Edit_Form
{
	private $statistic;
	
	protected function get_title_tab()
	{ 	
		return sprintf( __('Статистика рассылки &laquo;%s&raquo; в Яндексе','usam'), $this->data['subject'] );
	}
	
	protected function get_data_tab(  )
	{		
		if ( $this->id != null )							
		{
			$this->data = usam_get_newsletter( $this->id );				
		}
		else
			$this->data = array( 'type' => 'mail', 'class' => 'simple', 'subject' => '' );					
	}
	
	protected function toolbar_buttons( ) 
	{
		
	}
	
	public function display_form()
	{		
		require_once( USAM_FILE_PATH . '/includes/seo/yandex/postoffice.class.php' );		
		
		$mailbox = usam_get_mailbox( $this->id );
		$this->postoffice = new USAM_Yandex_Postoffice( $mailbox['email'] );
		if ( !$this->postoffice->check_token() )
		{
			echo "<p>".__('К сожалению Вы еще не настроили сервисы Яндекса.','usam')."</p>";			
			return;
		}
		usam_add_box( 'usam_sending', __('Основная информация','usam'), array( $this, 'sending_meta_box' ) );		
		usam_add_box( 'usam_statistics_group', __('Статистика','usam'), array( $this, 'statistics_group_meta_box' ) );			
		usam_add_box( 'usam_list_spam', __('Список жалоб на спам','usam'), array( $this, 'list_spam_meta_box' ) );	
    }		
	
	public function sending_meta_box()
	{		
		$statistic = $this->postoffice->get_statistics( 'listid', $this->id );		
		if ( empty($statistic) )
		{
			return $this->postoffice_get_error();	
		}	
		?>			
		<div class ='usam_rectangular_framing'>
			<div class ='usam_framing'>
				<div class ='usam_frame'><?php _e('Отправлено', 'usam'); ?>:<span class="number"><?php echo $statistic['messages']; ?></span></div>
				<div class ='usam_frame'><?php _e('Прочитанно', 'usam'); ?>:<span class="number"><?php echo $statistic['read']; ?></span></div>
				<div class ='usam_frame'><?php _e('Не прочитанно', 'usam'); ?>:<span class="number"><?php echo $statistic['not_read']; ?></span></div>
				<div class ='usam_frame'><?php _e('В спам', 'usam'); ?>:<span class="number"><?php echo $statistic['spam']; ?></span></div>
				<div class ='usam_frame'><?php _e('Удалено', 'usam'); ?>:<span class="number"><?php echo $statistic['deleted']; ?></span></div>
			</div>
		</div>		
		<?php	
    }
	
	public function statistics_group_meta_box()
	{		
		$statistic = $this->postoffice->get_statistics_group( $this->data['id'], 'listid' );		
		if ( empty($statistic) )
			return $this->postoffice_get_error();	
		
		if ( !isset($statistic['spam']) )
			return '';
		
		$pozent_spam = round($statistic['spam']['total']/$statistic['total']*100, 1);
		$pozent_spam_instant = round($statistic['spam']['instant']/$statistic['spam']['total']*100, 1);
		$pozent_spam_read = round($statistic['spam']['read']/$statistic['spam']['total']*100, 1);
		$pozent_spam_not_read = round($statistic['spam']['not_read']/$statistic['spam']['total']*100, 1);
		$pozent_spam_personal = round($statistic['spam']['personal']/$statistic['spam']['total']*100, 1);
		
		$pozent_deleted = round($statistic['deleted']['total']/$statistic['total']*100, 1);		
		$pozent_deleted_read = round($statistic['deleted']['read']/$statistic['deleted']['total']*100, 1);
		$pozent_deleted_not_read = round($statistic['deleted']['not_read']/$statistic['deleted']['total']*100, 1);
		
		$pozent_read = round($statistic['read']['total']/$statistic['total']*100, 1);		
		$pozent_read_inbox = round($statistic['read']['inbox']/$statistic['read']['total']*100, 1);
		$pozent_read_spam = round($statistic['read']['spam']/$statistic['read']['total']*100, 1);
		$pozent_read_deleted = round($statistic['read']['deleted']/$statistic['read']['total']*100, 1);
		
		$pozent_not_read = round($statistic['not_read']['total']/$statistic['total']*100, 1);		
		$pozent_not_read_inbox = round($statistic['not_read']['inbox']/$statistic['not_read']['total']*100, 1);
		$pozent_not_read_spam = round($statistic['not_read']['spam']/$statistic['not_read']['total']*100, 1);
		$pozent_not_read_deleted = round($statistic['not_read']['deleted']/$statistic['not_read']['total']*100, 1);
		?>			
		<table class="stat__detail-table">
			<tbody>
				<tr>
					<th rowspan="12"><?php _e('Получено', 'usam'); ?><div class="stat__detail-table__total"><?php echo $statistic['total']; ?></div></th>
					<td rowspan="4" class="stat__detail-table__td_no-h-b stat__detail-table__td_bold"><?php _e('Спам', 'usam'); ?></td>
					<td rowspan="4" class="stat__detail-table__td_no-h-b"><?php echo $statistic['spam']['total']; ?></td>
					<td rowspan="4" class="stat__detail-table__td_no-h-b">(<?php echo $pozent_spam; ?>%)</td>
					<td class="stat__detail-table__td_no-r-b stat__detail-table__td_alt"><?php _e('Автоматически', 'usam'); ?></td>
					<td class="stat__detail-table__td_no-h-b stat__detail-table__td_alt"><?php echo $statistic['spam']['instant']; ?></td>
					<td class="stat__detail-table__td_no-h-b stat__detail-table__td_alt">(<?php echo $pozent_spam_instant; ?>%)</td>
				</tr>
				<tr>
					<td class="stat__detail-table__td_no-r-b"><?php _e('После прочтения', 'usam'); ?></td>
					<td class="stat__detail-table__td_no-h-b"><?php echo $statistic['spam']['read']; ?></td>
					<td class="stat__detail-table__td_no-h-b">(<?php echo $pozent_spam_read; ?>%)</td>
				</tr>
				<tr>
					<td class="stat__detail-table__td_no-r-b stat__detail-table__td_alt"><?php _e('До прочтения', 'usam'); ?></td>
					<td class="stat__detail-table__td_no-h-b stat__detail-table__td_alt"><?php echo $statistic['spam']['not_read']; ?></td>
					<td class="stat__detail-table__td_no-h-b stat__detail-table__td_alt">(<?php echo $pozent_spam_not_read; ?>%)</td>
				</tr>
				<tr>
					<td class="stat__detail-table__td_no-r-b"><?php _e('Персональный фильтр', 'usam'); ?></td>
					<td class="stat__detail-table__td_no-h-b"><?php echo $statistic['spam']['personal']; ?></td>
					<td class="stat__detail-table__td_no-h-b">(<?php echo $pozent_spam_personal; ?>%)</td>
				</tr>
				<tr>
					<td rowspan="2" class="stat__detail-table__td_no-h-b stat__detail-table__td_bold"><?php _e('Удалено', 'usam'); ?></td>
					<td rowspan="2" class="stat__detail-table__td_no-h-b"><?php echo $statistic['deleted']['total']; ?></td>
					<td rowspan="2" class="stat__detail-table__td_no-h-b">(<?php echo $pozent_deleted; ?>%)</td>
					<td class="stat__detail-table__td_no-r-b"><?php _e('После прочтения', 'usam'); ?></td>
					<td class="stat__detail-table__td_no-h-b"><?php echo $statistic['deleted']['read']; ?></td>
					<td class="stat__detail-table__td_no-h-b">(<?php echo $pozent_deleted_read; ?>%)</td>
				</tr>
				<tr>
					<td class="stat__detail-table__td_no-r-b stat__detail-table__td_alt"><?php _e('До прочтения', 'usam'); ?></td>
					<td class="stat__detail-table__td_no-h-b stat__detail-table__td_alt"><?php echo $statistic['deleted']['not_read']; ?></td>
					<td class="stat__detail-table__td_no-h-b stat__detail-table__td_alt">(<?php echo $pozent_deleted_not_read; ?>%)</td>
				</tr>
				<tr>
					<td rowspan="3" class="stat__detail-table__td_no-h-b stat__detail-table__td_bold"><?php _e('После прочтения', 'usam'); ?></td>
					<td rowspan="3" class="stat__detail-table__td_no-h-b"><?php echo $statistic['read']['total']; ?></td>
					<td rowspan="3" class="stat__detail-table__td_no-h-b">(<?php echo $pozent_read; ?>%)</td>
					<td class="stat__detail-table__td_no-r-b"><?php _e('Во входящих', 'usam'); ?></td>
					<td class="stat__detail-table__td_no-h-b"><?php echo $statistic['read']['inbox']; ?></td>
					<td class="stat__detail-table__td_no-h-b">(<?php echo $pozent_read_inbox; ?>%)</td>
				</tr>
				<tr>
					<td class="stat__detail-table__td_no-r-b stat__detail-table__td_alt"><?php _e('В спаме', 'usam'); ?></td>
					<td class="stat__detail-table__td_no-h-b stat__detail-table__td_alt"><?php echo $statistic['read']['spam']; ?></td>
					<td class="stat__detail-table__td_no-h-b stat__detail-table__td_alt">(<?php echo $pozent_read_spam; ?>%)</td>
				</tr>
				<tr>
					<td class="stat__detail-table__td_no-r-b"><?php _e('Удалено', 'usam'); ?></td>
					<td class="stat__detail-table__td_no-h-b"><?php echo $statistic['read']['deleted']; ?></td>
					<td class="stat__detail-table__td_no-h-b">(<?php echo $pozent_read_deleted; ?>%)</td>
				</tr>
				<tr>
					<td rowspan="3" class="stat__detail-table__td_no-h-b stat__detail-table__td_bold"><?php _e('До прочтения', 'usam'); ?></td>
					<td rowspan="3" class="stat__detail-table__td_no-h-b"><?php echo $statistic['not_read']['total']; ?></td>
					<td rowspan="3" class="stat__detail-table__td_no-h-b">(<?php echo $pozent_not_read; ?>%)</td>
					<td class="stat__detail-table__td_no-r-b stat__detail-table__td_alt"><?php _e('Во входящих', 'usam'); ?></td>
					<td class="stat__detail-table__td_no-h-b stat__detail-table__td_alt"><?php echo $statistic['not_read']['inbox']; ?></td>
					<td class="stat__detail-table__td_no-h-b stat__detail-table__td_alt">(<?php echo $pozent_not_read_inbox; ?>%)</td>
				</tr>
				<tr>
					<td class="stat__detail-table__td_no-r-b"><?php _e('В спаме', 'usam'); ?></td>
					<td class="stat__detail-table__td_no-h-b"><?php echo $statistic['not_read']['spam']; ?></td>
					<td class="stat__detail-table__td_no-h-b">(<?php echo $pozent_not_read_spam; ?>%)</td>
				</tr>
				<tr>
					<td class="stat__detail-table__td_no-r-b stat__detail-table__td_alt"><?php _e('Удалено', 'usam'); ?></td>
					<td class="stat__detail-table__td_no-h-b stat__detail-table__td_alt"><?php echo $statistic['not_read']['deleted']; ?></td>
					<td class="stat__detail-table__td_no-h-b stat__detail-table__td_alt">(<?php echo $pozent_not_read_deleted; ?>%)</td>
				</tr>
				<tr class="stat__detail-table__tr_warn js-spam-table-row hidden">
					<th><?php _e('Предупреждения', 'usam'); ?></th>
					<td class="stat__detail-table__td_no-h-b stat__detail-table__td_bold js-spam-table-cell"></td>
					<td colspan="5" class="stat__detail-table__td_no-h-b"><?php _e('Удалите из рассылки адреса пользователей, которые считают вашу рассылку спамом.', 'usam'); ?></td>
				</tr>
			</tbody>
		</table>	
		<?php return '';?>
		<table class="stat__detail-table stat__detail-table_no-v-b">
			<tbody>
				<tr class="stat__detail-table__tr_warn">
					<th colspan="2">Прокрутка</th>
					<th colspan="3">Время чтения, мин.</th>
					<th colspan="3">Время до прочтения, ч.</th>
					<th colspan="2">Время суток</th>
				</tr>
				<tr>
					<td>10%</td>
					<td>5</td>
					<td>00:10</td>
					<td>7</td>
					<td>(17.9%)</td>
					<td>01:00</td>
					<td>24</td>
					<td>(61.5%)</td>
					<td rowspan="2" class="stat__detail-table__td_no-r-b">утро<br>6—12 ч</td>
					<td rowspan="2" class="stat__detail-table__td_no-h-b">25<br>(64.1%)</td>
				</tr>
				<tr class="stat__detail-table__tr_alt">
					<td>20%</td><td>4</td><td>00:20</td><td>3</td><td>(7.7%)</td><td>02:00</td><td>2</td><td>(5.1%)</td></tr><tr><td>30%</td><td>2</td><td>00:30</td><td>1</td><td>(2.6%)</td><td>04:00</td><td>2</td><td>(5.1%)</td><td rowspan="2" class="stat__detail-table__td_no-r-b stat__detail-table__td_alt">день<br>12—18 ч</td><td rowspan="2" class="stat__detail-table__td_no-h-b stat__detail-table__td_alt">9<br>(23.1%)</td>
				</tr>
				<tr class="stat__detail-table__tr_alt"><td>40%</td><td>2</td><td>00:40</td><td>2</td><td>(5.1%)</td><td>06:00</td><td>2</td><td>(5.1%)</td></tr><tr><td>50%</td><td>1</td><td>00:50</td><td>1</td><td>(2.6%)</td><td>07:00</td><td>2</td><td>(5.1%)</td><td rowspan="2" class="stat__detail-table__td_no-r-b">вечер<br>18—24 ч</td><td rowspan="2" class="stat__detail-table__td_no-h-b"><br>(0%)</td></tr><tr class="stat__detail-table__tr_alt"><td>60%</td><td>2</td><td>01:00</td><td>1</td><td>(2.6%)</td><td>08:00</td><td>2</td><td>(5.1%)</td></tr><tr><td>70%</td><td>0</td><td>02:00</td><td>1</td><td>(2.6%)</td><td>09:00</td><td>1</td><td>(2.6%)</td><td rowspan="2" class="stat__detail-table__td_no-r-b stat__detail-table__td_alt">ночь<br>0—6 ч</td><td rowspan="2" class="stat__detail-table__td_no-h-b stat__detail-table__td_alt">9<br>(23.1%)</td></tr>
				<tr class="stat__detail-table__tr_alt"><td>80%</td><td>0</td><td>03:00</td><td>1</td><td>(2.6%)</td><td>16:00</td><td>1</td><td>(2.6%)</td></tr><tr><td>90%</td><td>1</td><td>04:00</td><td>1</td>
				<td>(2.6%)</td><td>19:00</td><td>1</td><td>(2.6%)</td><td></td><td></td></tr><tr class="stat__detail-table__tr_alt"><td>100%</td><td>8</td><td>05:00</td><td>1</td><td>(2.6%)</td><td>20:00</td><td>2</td><td>(5.1%)</td><td></td><td></td></tr></tbody></table>
		<?php	
    }
	
	public function list_spam_meta_box() 
	{				
		$count = 20;
		$emails = $this->postoffice->get_mailing_as_spam( array( 'listid' => $this->data['id']) );		
		if ( empty($emails) )
		{
			echo "<p>".__('Никто не добавил Вашу рассылку в спам. Похоже клиентам нравится Ваша рассылка.','usam')."</p>";
			return $this->postoffice_get_error();
		}
		
		$out = "<table class ='usam_list_table'>";
		$out .= "<tbody>";			
			$i = 0;
			foreach ( $emails as $email )
			{							
				$out .= "<tr><td>".$email."</td></tr>";
				$i++;
			//	if ( $i >= $count )
			//		break;
			}	 				
		$out .= "</tbody></table>";
		echo $out;		
	}
			
	public function postoffice_get_error()
	{       
		$errors = $this->postoffice->get_errors();	
		foreach ( $errors as $error )
		{ 
			echo "<div class=\"error\"><p>{$error}</p></div>";									
		}
	}	
}
?>