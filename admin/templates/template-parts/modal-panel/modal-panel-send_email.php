<modal-panel ref="modalsendemail" :size="'85%'" :backdrop="true">
	<template v-slot:title><?php _e('Написать письмо', 'usam'); ?></template>
	<template v-slot:body="modalProps">		
		<send-email :emails="contact.emails" :object_id="object_id" :object_type="object_type" @add="addEmail" inline-template>
			<?php include( usam_get_filepath_admin('templates/template-parts/table_send_email.php') ); ?>
		</send-email>
	</template>
</modal-panel>