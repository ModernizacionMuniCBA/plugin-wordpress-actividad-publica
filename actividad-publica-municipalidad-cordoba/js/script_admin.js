jQuery(document).ready(function( $ ) {
	var formfield;
	var $preview = $('#vista_previa');
	var $btnReiniciar = $('#reiniciar-buscador a');
	var idPagina = $btnReiniciar.attr('data-id');
	
	$('#upload_image_button').click(function() {
		$('html').addClass('Image');
		formfield = $('#upload_logo_buscador').attr('name');
		tb_show('', 'media-upload.php?type=image&TB_iframe=true');
		return false;
	});

	window.original_send_to_editor = window.send_to_editor;
	window.send_to_editor = function(html){
		$preview.html('');
		if (formfield) {
			var fileurl = $(html).attr('src');
			$('#vista_previa').append(html);
			$('#upload_logo_buscador').val(fileurl);
			tb_remove();
			$('html').removeClass('Image');
		} else {
			window.original_send_to_editor(html);
		}
	};
	
	$btnReiniciar.click(function (e) {
		e.preventDefault();
		
		if (confirm('¿Está seguro de que quiere reiniciar los estilos del buscador de actividades? La página completa se volverá a cargar.')) {
			$.ajax({
				url: reiniciarOpciones.url,
				data: {
					nonce: reiniciarOpciones.nonce,
					action: 'reiniciar_opciones',
					id: idPagina
				},
				success: function(respuesta) {
					window.location.replace(respuesta);
				}
			})
		}
	});
});