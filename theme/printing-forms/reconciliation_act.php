<?php
/*
Printing Forms: Акт сверки
type:crm
object_type:document
object_name:reconciliation_act
Description: Используется в документах CRM
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php	
if ( $this->edit )
{
	$print = '';
}
else
	$print = 'onload="window.print()"';

$start_date = usam_get_document_metadata($this->id, 'start_date'); 	
$start_date = $start_date?usam_local_date( $start_date, get_option( 'date_format', 'd.m.Y' ) ):'';
$end_date = usam_get_document_metadata($this->id, 'end_date' ); 
$end_date = $end_date?usam_local_date( $end_date, get_option( 'date_format', 'd.m.Y' ) ):'';
$start_balance = usam_get_document_metadata($this->id, 'start_balance' );
$end_balance = (float)usam_get_document_metadata($this->id, 'end_balance' );		
$contract_id = usam_get_document_metadata( $this->id, 'contract' ); 
$format_price = ['currency_symbol' => false, 'decimal_point' => false, 'currency_code' => false, 'decimal_point' => true];
?>	
<head>		
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo esc_html__('Счет', 'usam'); ?>: %id% <?php esc_html_e( 'от', 'usam'); ?> %date%</title>
	<style type="text/css">				
		table { border-collapse: collapse; }
		table td { border: 1pt solid #000000; }	
		table.sign td { font-weight: bold; vertical-align: top; }
	</style>
	<?php $this->style(); ?>	
</head>
<body <?php echo $print; ?> style="background: #ffffff">
	<div style="margin: 0pt; padding:15pt; width:565pt; background: #ffffff">		
		<h1 style="text-align:center; white-space:normal;"><?php _e( 'Акт сверки', 'usam') ?></h1>	
		<p style="text-align:center; white-space:normal;"><?php printf( __( 'взаимных расчётов по состоянию на %s г.', 'usam'), $end_date) ?></p>	
		<p style="text-align:center; white-space:normal;"><?php _e( 'между %recipient_company_name% и %customer_name%', 'usam') ?></p>	
		<?php		
		if ( $contract_id )
		{
			$contract_document = usam_get_document( $contract_id );
			if ( $contract_document ) 
			{
				?><p style="text-align:center; white-space:normal;"><?php _e( 'по договору №', 'usam').' '.$contract_document['number']." ".__("от","usam")." ".usam_local_date($contract_document['date_insert'], 'd.m.Y'); ?></p><?php
			}
		}		
		?>	
		<br>
		<table>
			<tr>
				<td colspan = '4'><?php _e( 'По данным %recipient_company_name%', 'usam') ?></td>
				<td colspan = '4'><?php _e( 'По данным %customer_name%', 'usam') ?></td>
			</tr>
			<tr>
				<td>№</td>
				<td><?php _e( 'Наименование операции, документы', 'usam') ?></td>
				<td><?php _e( 'Дебет', 'usam') ?></td>
				<td><?php _e( 'Кредит', 'usam') ?></td>
				<td>№</td>
				<td><?php _e( 'Наименование операции, документы', 'usam') ?></td>
				<td><?php _e( 'Дебет', 'usam') ?></td>
				<td><?php _e( 'Кредит', 'usam') ?></td>
			</tr>
			<tr>
				<td></td>
				<td><?php _e( 'Сальдо', 'usam') ?></td>
				<td><?php echo usam_currency_display( $start_balance, $format_price ); ?></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>
			<?php 
			require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );				
			$v = apply_filters('usam_reconciliation_documents', 'act' );
			$documents = usam_get_documents(['child_document' => ['id' => $this->id, 'type' => $this->data['type'], 'link_type' => 'subordinate'], 'orderby' => 'date_insert']);
			$i = 0;
			$sum1 = 0;
			$sum2 = 0;
			foreach( $documents as $document ) 
			{ 
				$detail = usam_get_details_document( $document->type );
				$i++;					
				?>
				<tr>
					<td><?php echo $i; ?></td>
					<td>
						<p style="margin:0 0 2px 0"><?php echo $detail['single_name']; ?></p>
						<p style="margin:0"><?php echo '№'.$document->number.' '.usam_local_date($document->date_insert, "d.m.Y"); ?></p>
					</td>
					<?php if ( $v == 'payment_received' ) { ?>	
						<?php if ( $document->type != 'payment_received' ) { ?>						
							<?php $sum1 += $document->totalprice; ?>
							<td><?php echo usam_currency_display( $document->totalprice, $format_price ); ?></td>
							<td></td>
						<?php } else { ?>
							<?php $sum2 += $document->totalprice; ?>
							<td></td>
							<td><?php echo usam_currency_display( $document->totalprice, $format_price ); ?></td>
						<?php } ?>
					<?php } else { ?>
						<?php if ( $document->type == 'act' ) { ?>						
							<?php $sum1 += $document->totalprice; ?>
							<td><?php echo usam_currency_display( $document->totalprice, $format_price ); ?></td>
							<td></td>
						<?php } else { ?>
							<?php $sum2 += $document->totalprice; ?>
							<td></td>
							<td><?php echo usam_currency_display( $document->totalprice, $format_price ); ?></td>
						<?php } ?>
					<?php } ?>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
				</tr>
			<?php } ?>
			<tr>
				<td></td>
				<td>
					<p style="margin:0 0 2px 0"><?php _e( 'Обороты за период с', 'usam') ?></p>
					<p style="margin:0"><?php printf( __( '%s по %s', 'usam'), $start_date, $end_date) ?></p>
				</td>
				<td><?php echo usam_currency_display( $sum1, $format_price ); ?></td>
				<td><?php echo usam_currency_display( $sum2, $format_price ); ?></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>
			<tr>
				<td></td>
				<td><?php _e( 'Сальдо', 'usam') ?></td>
				<?php if ( $end_balance < 0 ) { ?>						
					<td><?php echo usam_currency_display( abs($end_balance), $format_price ); ?></td>
					<td></td>
				<?php } else { ?>					
					<td></td>
					<td><?php echo usam_currency_display( abs($end_balance), $format_price ); ?></td>
				<?php } ?>	
				<td></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>
		</table>
		<br>
		<?php _e( 'По данным %recipient_company_name%', 'usam'); ?><br>
		<?php 
		if ( $end_balance > 0 )
			printf( __('На %s задолженность в пользу %s: %s', 'usam'), $end_date, '%customer_name%', usam_currency_display( $end_balance, $format_price )); 
		elseif ( $end_balance == 0 )
			printf( __('На %s задолженность отсутствует', 'usam'), $end_date); 
		else
		{
			printf( __('На %s задолженность в пользу %s: %s', 'usam'), $end_date, '%recipient_company_name%', usam_currency_display( abs($end_balance), $format_price )); 
		}
		?><br>
		<p style="border-top: 2pt solid #000000;"></p>		
		<br>
		<br>		
		<div class ="invoice-footer" style="margin-top:10px">%%sign textarea "<table class='sign' width='100%'><tbody><tr><td colspan = '2'>От %recipient_company_name%</td><td colspan = '2'>От %customer_name%</td></tr><tr><td colspan = '2'><?php _e( 'Руководитель', 'usam'); ?></td><td colspan = '2'><?php _e( 'Руководитель', 'usam'); ?></td></tr><tr><td>_________________</td><td>%recipient_gm%</td><td>____________________</td><td>___________________</td></tr></tbody></table>" "Описание"%%</div>
		<div class ="invoice-footer" style="margin-top:10px">%%description textarea "" "Описание"%%</div>
	</div>	
</body>
</html>		