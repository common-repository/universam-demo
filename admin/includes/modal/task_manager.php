<div id="task_manager" class="modal fade modal-large">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Диспетчер задач', 'usam'); ?></div>
	</div>
	<div class="usam_table_container modal-scroll">
		<table class="task_manager_table usam_list_table" cellspacing="0">
			<thead>
				<tr>
					<td class="column-status"></td>
					<td class="column-name"><?php esc_html_e( 'Задача', 'usam'); ?></td>
					<td class="column-status"><?php esc_html_e( 'Состояние', 'usam'); ?></td>
					<td class="column-percent"><?php esc_html_e( 'Процент', 'usam'); ?></td>
					<td class="column-date"><?php esc_html_e( 'Запущен', 'usam'); ?></td>
					<td class="column-action"></td>
				</tr>
			</thead>
			<tbody>				
				<tr :class="[process.status?'usam_performed':'usam_not_performed']" v-for="(process, k) in processes">					
					<td	class="column-status">
						<span class="dashicons dashicons-controls-pause" v-if="process.status!='pause'" title="<?php _e( 'Приостановить', 'usam'); ?>" @click="pause(k)"></span>
						<span class="dashicons dashicons-controls-play" v-if="process.status=='pause'" title="<?php _e( 'Запустить', 'usam'); ?>" @click="start(k)"></span>
					</td>					
					<td	class="column-name" v-html="process.title"></td>
					<td class="column-status">
						<span class='item_status item_status_valid' v-if="process.status == 'start'"><?php _e('выполняется','usam') ?></span>
						<span class='item_status status_white' v-else-if="process.status == 'pause'"><?php _e('в паузе','usam') ?></span>
						<span class='item_status item_status_notcomplete' v-else><?php _e('в очереди','usam') ?></span>
					</td>
					<td class="column-percent"><div class='progress'><div class='progress_text'>{{process.percent}}%</div><div class='progress_bar' :style="'width:'+process.percent+'%'"></div></div></td>
					<td class="column-date" v-html="process.date"></td>
					<td class="column-action"><span @click="del(k)" class='task_manager__delete'>х</span></td>
				</tr>
				<tr><td class="no_items" v-if="!processes.length" colspan='5'><?php esc_html_e( 'Сейчас нет задач', 'usam'); ?></td></tr>
			</tbody>		
		</table>
	</div>	
</div>
<style>
	table.task_manager_table{border-spacing: 0; width: 100%; clear: both; margin: 0; border:none}
	table.task_manager_table tr{display:table-row;}
	table.task_manager_table th,
	table.task_manager_table td{padding: 8px 10px; text-align:left; font-size:13px; color: #000;}
	table.task_manager_table td a{text-decoration:none}
	table.task_manager_table .no-items td{text-align:left;}
	table.task_manager_table thead{background-color:#e0e0e0; color:#555;}
	table.task_manager_table thead td{vertical-align:inherit !important; font-size:14px; color:#3b4649; font-weight:600;}
	table.task_manager_table tbody tr:nth-child(odd){background-color:#f7f7f7;}
	table.task_manager_table td, 
	table.task_manager_table th{border:none}
	table.task_manager_table .dashicons{cursor:pointer}
	table.task_manager_table .column-status{width:20px; white-space: nowrap;}
	table.task_manager_table .column-action{padding:0; width: 5px;}
	table.task_manager_table .task_manager__delete{padding:8px 10px; cursor:pointer}	
	table.task_manager_table .progress{width:100%; margin:5px 0; background-color:#ffffff;}
	table.task_manager_table .progress_bar{height: 20px; background-color:#2271b1;}
</style>
