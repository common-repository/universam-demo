document.addEventListener("DOMContentLoaded", () => {	
	//document.querySelector('#parent option:contains("   ")').remove();
	document.querySelector('#bulk-action-selector-top').addEventListener("change", function(e) {		
		if ( e.target.value == 'variant_management' )
		{
			jQuery.usam_get_modal( e.target.value );
		}		
	});
	jQuery('body').on('append_modal', '#variant_management', () => {
		document.querySelector('#variant_management #modal_action').addEventListener("click", function(e)
		{
			var variation1 = document.querySelector('#variation1').value;
			var variation2 = document.querySelector('#variation2').value;
			usam_send({action: 'variant_management', 'variation1': variation1, 'variation2': variation2, nonce: USAM_Variation.variant_management_nonce});
			document.querySelector('#variation1').value = '';
		});
	});		
})
