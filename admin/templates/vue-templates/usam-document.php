<script type="x-template" id="usam-document">
	<div class="usam_document">
		<div class="usam_document-title-container">
			<div class="usam_document-title"><slot name="title"></slot></div>
			<div class="usam_document-action-block">					
				<slot name="action"></slot>	
			</div>
		</div>
		<div class="usam_document_content" v-show="!toggle">
			<div class="usam_document_header">				
				<div class="usam_document__sidebar"><slot name="sidebar"></slot>	</div>
			</div>		
			<div class="usam_document__right">				
				<slot name="data"></slot>				
			</div>				
		</div>	
	</div>
</script>