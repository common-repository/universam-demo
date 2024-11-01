<div class="webform">
	<div class ="webform_steps" v-if="main_groups.length!=1">
		<div class ="webform_step" v-for="(group, g) in main_groups" v-html="group.name" :class="{'active':tab==g}" @click="tab=g"></div>
	</div>
	<div class ="edit_form active" v-for="(group, g) in main_groups" v-if="tab==g" :class="{'webform_content':main_groups.length!=1}">
		<div class ="edit_form__item" v-for="(property, k) in properties" v-if="property.group==group.code && property.field_type!='personal_data'">
			<div class ="edit_form__item_name"><span v-html="property.name"></span><span v-if="property.mandatory">*</span>:</div>
			<div class ="edit_form__item_option" v-if="form_type=='view'">
				<?php include( usam_get_filepath_admin('templates/template-parts/view-property.php') ); ?>
			</div>
			<div class ="edit_form__item_option" v-else>
				<?php include( usam_get_filepath_admin('templates/template-parts/property.php') ); ?>
			</div>
		</div>
		<div v-for="group2 in propertyGroups" v-if="group2.parent_id==group.id">
			<div class ="edit_form__title" v-html="group2.name"></div>
			<div class ="edit_form__item" v-for="(property, k) in properties" v-if="property.group==group2.code && property.field_type!='personal_data'">
				<div class ="edit_form__item_name"><span v-html="property.name"></span><span v-if="property.mandatory">*</span>:</div>
				<div class ="edit_form__item_option" v-if="form_type=='view'">
					<?php include( usam_get_filepath_admin('templates/template-parts/view-property.php') ); ?>
				</div>
				<div class ="edit_form__item_option" v-else>
					<?php include( usam_get_filepath_admin('templates/template-parts/property.php') ); ?>
				</div>
			</div>
		</div>
	</div>	
</div>	