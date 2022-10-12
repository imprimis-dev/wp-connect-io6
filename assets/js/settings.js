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
					if(data.response.catalogs.passed && data.response.products.passed) {
						jQuery('div.test-api').append('<div class="response updated" style="margin-top:15px"><h4>Connessione ImporterONE avvenuta correttamente.<h4></div>');
					}
					else {
						jQuery('div.test-api').append('<div class="response error" style="margin-top:15px"><h4>C\'è stato un problema di connessione con ImporterONE.<br/>Controllare i parametri immessi o contattare il supporto tecnico.</h4></div>');
					}
					
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