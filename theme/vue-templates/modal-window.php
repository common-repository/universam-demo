<script type="x-template" id="modal-window">
	<div v-show="show" class="modal fade" @click.self="closeModal" :class="{'in':animation}">		
		<slot name="header">
			<div class="modal-header">
				<div class="header-title"><slot name="title"></slot></div>
				<span class="close" @click="closeModal">×</span>
			</div>
		</slot>
		<div class="modal-body">
			<slot name="body"></slot>
		</div>
		<slot name="footer">
			<div class="modal__buttons">
				<slot name="buttons"></slot>
			</div>		
		</slot>
	</div>
</script>