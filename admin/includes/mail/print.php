<?php
/*
Printing Forms: Печать письма
*/
?>
<?php 
if ( !isset($_GET['id']) )
	return false;

$id = (int)$_GET['id'];
$email = usam_get_email( $id ); 
?> 
<!DOCTYPE HTML PUBLIC '-//W3C//DTD XHTML 1.0 Transitional //EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
<style>
  .printWindow {
    overflow:auto;
    margin:2em 5% 10%;
    padding:0;
    background:#fff;
    color:#000;
    word-wrap:break-word;
    font-size:9pt;
    font-family:'Lucida Grande CY','Arial','Liberation Sans','DejaVu Sans',sans-serif;
    line-height:12pt;
  }
  .printWindow .messageHeader,
  .printWindow .messageSubject,
  .printWindow .messageBody {
    position:static;
    padding:1em 0;
  }
  .printWindow .isInnerPartHeader {
    border-top:1px solid #676767;
  }
  .printWindow .messageBodyContainer {
    margin:1em 0 0 0;
  }
  .printWindow .messageHeaderDate {
    padding-bottom:1.5em;
    color:#676767;
    font-size:8pt;
  }
  .printWindow .messageHeaderContacts {
    padding-bottom:.85em;
  }
  .printWindow .messageHeaderLabel,
  .printWindow .messageContactItem,
  .printWindow .messageContactPrint {
    display:inline;
  }
  .printWindow .messageHeaderLabel {
    padding-right:.75em;
  }
  .printWindow .messageContactItem {
    padding-right:.25em;
    white-space:nowrap;
  }
  .printWindow .messageHeaderNavigation,
  .printWindow .uiButton,
  .printWindow .uiPopup,
  .printWindow .messageContactDisplay,
  .printWindow .moreHeadersLink,
  .messageAttachesCounter,
  .attachDownload {
    display:none;
  }
  .printWindow .messageLabelFrom,
  .printWindow .messageContactFrom {
    font-weight:bold;
  }
  .printWindow .messageLabelTo,
  .printWindow .messageContactTo {
    color:#676767;
  }
  .printWindow .messageSubject {
    padding:1em 0 1.5em;
    font-weight:bold;
    font-size:21px;
  }
  .printWindow .attachesList {
    overflow:hidden;
    padding:2em 0;
    page-break-after:always;
  }
  .printWindow .attachCell {
    float:left;
    width:10em;
  }
  .printWindow .attachItem {
    color:#676767;
    text-align:center;
    font-size:8pt;
  }
</style>
</head>
<body onload="window.print()">
<div class="printWindow">
<?php
if ( $email['type'] == 'inbox_letter' )	
{
	$title =  __('Получено', 'usam');
	$date =  $email['date_insert'];
} 
else 
{ 	
	if ( !empty($email['sent_at']) ) 
	{ 
		$title =  __('Отправлено', 'usam');
		$date =  $email['sent_at'];
	}
	else
	{
		$title =  __('Создано', 'usam');
		$date =  $email['date_insert'];
	}
}
?>
<title><?php echo $email['title']; ?></title>
<div class="printPreview">
	<div>
		<?php
		if( current_user_can('view_communication_data') )
		{
			?>
			<div class="messageHeader isTopPartHeader">
				<div class="messageHeaderLabel messageLabelFrom"><?php _e('От', 'usam'); ?></div>
				<div class="messageContactItem messageContactFrom">
					<span class="messageContactPrint"><?php echo $email['from_name']; ?> <span class="messageContactPart">&lt;<?php echo htmlspecialchars( $email['from_email'] ); ?>&gt;</span></span>
					<span class="messageContactDisplay"><?php echo htmlspecialchars($email['from_email']); ?></span>
				</div>
			</div>
			<div class="messageHeaderContacts contactsRowTo">
				<div class="messageHeaderLabel messageLabelTo"><?php _e('Кому', 'usam'); ?>:</div>
				<div class="messageContactItem messageContactTo">
					<span class="messageContactPrint"><?php echo $email['to_name']; ?> <span class="messageContactPart">&lt;<?php echo htmlspecialchars( $email['to_email'] ); ?>&gt;</span></span>
					<span class="messageContactDisplay"><?php echo htmlspecialchars( $email['to_email'] ); ?></span>
				</div>
			</div>
			<?php
		}
		?>
		<div class="messageHeaderContacts contactsRowFrom">
			<span class="messageHeaderDate"><?php echo $title; ?>: </span><span class="messageHeaderDate"><?php echo usam_local_date( $date, get_option( 'date_format', 'd.m.Y' )." ".__('в', 'usam')." H:i" ); ?></span>
		</div>
	</div>
	<div>
		<div class="messageBodyContainer" id="part0">
			<div class="messageBodyContainer isInnerPart" id="part2">
				<div class="messageBody isFormattedText">
					<?php echo $email['body']; ?>
				</div>
			</div>
		</div>
	</div>
</div>
</div>
</body>
</html>
<?php exit;	?>	