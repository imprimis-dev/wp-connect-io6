if (typeof jQuery !== 'undefined') {
  jQuery(document).ready(function () {
        
		jQuery(document).on('click', '#test-api', function(event) {
			var endPoint = jQuery('#io6_apiendpoint').val();
			var token = jQuery('#io6_apitoken').val();
			
			if(endPoint == "" || token == "") {
				alert("Parametri Connessione ImporterONE non impostati.");
				return;
			}

			jQuery('div.test-api > div.response').remove();

			jQuery.ajax({
				method: "get",
				async: true,
				dataType: 'json',				
				url: io6_ajax_url + '?action=io6-test-api&ep=' + endPoint + '&t=' + token,			
				success: function (data) {
					jQuery('div.test-api > div.notice').remove();
					jQuery('div.test-api').append('<div class="notice notice-' + (data.response.passed ? data.response.iswarning ? 'warning' : 'success' : 'error') + '" style="margin-top:15px"><h4>' + data.response.message + '<h4></div>');
					
				},
				error: function (error) {
					console.log("ERROR " + error.toString());					
				},
				complete: function() {
					
				}
			});

		});
	});
}