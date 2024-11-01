<?php
$templates['invoice'] = array( 'title' => __("Счет на оплату","usam")  );

$templates_items['invoice'][1] = array( 'title' => __("Добавить счет на оплату","usam"), 'action' => 'add_document',  'document_type' => 'invoice' ); 
$templates_items['invoice'][2] = array( 'title' => __("Согласовать с руководителем отдела","usam"), 'action' => 'matching',  'whom' => 'department_head' ); 
$templates_items['invoice'][3] = array( 'title' => __("Добавить в документ согласовавшего","usam"), 'action' => 'edit_document',  'document_type' => 'document' ); 
$templates_items['invoice'][4] = array( 'title' => __("Добавить в документ не согласовавшего","usam"), 'action' => 'edit_document',  'document_type' => 'document' ); 
$templates_items['invoice'][5] = array( 'title' => __("Согласовать с руководителем","usam"), 'action' => 'matching',  'whom' => 'supervisor' ); 
$templates_items['invoice'][6] = array( 'title' => __("Добавить в документ согласовавшего","usam"), 'action' => 'edit_document',  'document_type' => 'document' ); 
$templates_items['invoice'][7] = array( 'title' => __("Добавить в документ не согласовавшего","usam"), 'action' => 'edit_document',  'document_type' => 'document' ); 
$templates_items['invoice'][8] = array( 'title' => __("Оплата бухгалтером","usam"), 'action' => 'employee',  'whom' => 'document' ); 
$templates_items['invoice'][9] = array( 'title' => __("Оповещение добавившему счет","usam"), 'action' => 'notification',  'whom' => 'author' ); 
$templates_items['invoice'][10] = array( 'title' => __("Задание на предоставление актов или накладных","usam"), 'action' => 'task',  'whom' => 'author' ); 
$templates_items['invoice'][11] = array( 'title' => __("Оплата отклонена","usam"), 'action' => 'notification',  'whom' => 'author' ); 

$templates_way['invoice'][] = array( 'item' => 1, 'choice' => 2 );
$templates_way['invoice'][] = array( 'item' => 2, 'choice' => 3 );
$templates_way['invoice'][] = array( 'item' => 2, 'choice' => 4 );
$templates_way['invoice'][] = array( 'item' => 3, 'choice' => 5 );
$templates_way['invoice'][] = array( 'item' => 5, 'choice' => 6 );
$templates_way['invoice'][] = array( 'item' => 5, 'choice' => 7 );
$templates_way['invoice'][] = array( 'item' => 6, 'choice' => 8 );
$templates_way['invoice'][] = array( 'item' => 8, 'choice' => 9 );
$templates_way['invoice'][] = array( 'item' => 8, 'choice' => 10 );
$templates_way['invoice'][] = array( 'item' => 8, 'choice' => 11 );
?>