<?php
/*
Printing Forms: Приказ
type:crm
object_type:document
object_name:decree
Description: Печатная форма документа приказ
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>	
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?php printf( esc_html__('Приказ №%s', 'usam'), $this->id ); ?></title>
	<style type="text/css">		
		@page {	margin: 0; }		
		@media print {
			.more{page-break-after: always;} 
		} 
		included unicode fonts:*  serif: 'dejavu serif'*  sans: 'devavu sans'
		body {margin: 30px;font-family:"dejavu serif", Helvetica, Arial, Verdana, sans-serif;}
		h1, h2, p, td, th, *{margin:2px; font-family: 'dejavu serif'; font-size: 14px;}		
		h1{font-size:22px; font-weight:bold; text-align: center;}
		p {margin-bottom:10px; white-space: normal; text-align: left;}	
		div {margin:2px;}	
		table { border-collapse: collapse; width:100%;}		
	</style>	
</head>
<?php
if ( $this->edit )
{
	$print = '';
}
else
	$print = 'onload="window.print()"';
?>	
<body <?php echo $print; ?>>
	<div style="margin: 0pt; padding: 10pt 10pt 10pt 10pt; width: 571pt; background: #ffffff">
		
		<p style="text-align:center; margin-bottom:30px; font-size:18px; font-weight:600;">%%document_header input "%recipient_full_company_name%" "Шапка документа"%%</p>
		
		<table>
			<tr>
				<td><p style="text-align:left;">%date%</p></td>
				<td><p style="text-align:right;"><?php _e( 'г.', 'usam') ?> %recipient_legalcity%</p></td>
			</tr>
		</table>
		
		<h1 style="margin:30px 0;">%%name input "<?php echo __('Приказ №', 'usam'); ?> %document_number%"%%</h1>	
		
		<div style="text-align:justify; margin:10px; 0"> %document_content%</div>
		
		<p style="margin:30px 0;">%%director_signature input "<?php _e( 'Директор', 'usam') ?> %recipient_company_name% ____________________________________ %recipient_gm%" "Подпись директора"%%</p>
		 
		<p>%%author_signature input "<?php _e( 'Подготовил', 'usam') ?>: %document_author_post% ____________________________________ %document_author%" "Подпись автора"%%</p>		
		
		<div style="text-align:justify; margin-top:30px;">
			<p><?php _e( 'Согласовано:', 'usam'); ?></p>
			<?php
			$contacts = usam_get_contacts(['document_ids' => $this->id, 'source' => 'employee']);
			foreach ( $contacts as $contact ) 		
			{
				?><p style="margin-top:30px;"><?php echo "$contact->post ____________________________________ $contact->appeal"; ?></p><?php
			}	
			?>
		</div>
	</div>
</body>
</html>