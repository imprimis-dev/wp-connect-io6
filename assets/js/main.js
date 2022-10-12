if (typeof jQuery !== 'undefined') {
  jQuery(document).ready(function () {
    $cancel = false;
    		
    jQuery(document).on('click',"#io6-exec-cancel-sync", function() {
			$cancel = true;
			jQuery('#io6-exec-sync-info').html("Annullamento in corso...");
		});
				
    jQuery(document).on('click', "#io6-exec-sync", async function() {
			
      jQuery(this).prop('disabled', true);
			jQuery("#io6-exec-cancel-sync").removeClass("d-none");
      
			var currentPage = 1;
			var totalPages = 1;
      var fastSync = jQuery('#io6-fast-sync').prop("checked") ? 1 : 0;

			jQuery('#io6-exec-sync-info').html('Inizio sincronizzazione...');			
			jQuery('#io6-exec-sync-info').show();

			jQuery('#io6-exec-sync-status').hide();
			jQuery('#io6-exec-sync-status').html('');
      
			
			while (currentPage <= totalPages && !$cancel) {
        await jQuery.ajax({
          method: "get",
          async: true,
					dataType: 'json',
          url: io6_ajax_url + '?action=io6-sync&page=' + currentPage + "&fastsync=" + fastSync,
          
          success: function (data) {
            
						totalPages = data.pages;
						
            jQuery('#io6-exec-sync-info').html('Totale prodotti: ' + data.elementsFounds + ". Pagine: " + currentPage + " di " + data.pages);
						jQuery('#io6-exec-sync-status').show();
						data.products.forEach(element => {
							jQuery('#io6-exec-sync-status').append("Prodotto: " + element.io6_id + " - EAN: " + element.ean + " - SKU: " + element.partnumber + " - Status: " + element.status_message +  "<br/>");
						});
						
            

          },
          error: function (error) {
            jQuery('#io6-exec-sync-info').html(error.statusText + "<br/>" + error.responseText);
						
          },
          complete: function() {
          }
        });
				currentPage++;
      }
			
			jQuery(this).prop('disabled', false);
			jQuery("#io6-exec-cancel-sync").addClass("d-none");
			      
    });    
  });
}
else
  document.getElementById('io6-exec-sync').style.display ='none';