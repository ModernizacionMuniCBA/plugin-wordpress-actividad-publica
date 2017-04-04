var disciplinas = [];

(function($) {
	if (disciplinas.length == 0) {
		$.get('https://gobiernoabierto.cordoba.gob.ar/api/disciplina-actividad/?audiencia_id=4')
			.then(response => {
				disciplinas = response.results.map(disc => {return { text: disc.nombre, value: disc.id}} );
			});
	}

	tinymce.create('tinymce.plugins.lactivcba_plugin', {
        init: function(ed, url) {
            ed.addCommand('lactivcba_insertar_shortcode', function() {
                selected = tinyMCE.activeEditor.selection.getContent();
                var content = '';

                ed.windowManager.open({
					title: 'Listado de actividades',
					body: [{
						type: 'textbox',
						name: 'titulo',
						label: 'Título'
					},{
						type: 'listbox',
						name: 'disciplina',
						label: 'Disciplina',
						values: disciplinas
					},{
						type: 'textbox',
						name: 'cant',
						label: 'Cantidad de Resultados'
					}],
					onsubmit: function(e) {
						ed.insertContent( '[lista_actividades_cba titulo="' + e.data.titulo + '" cant="' + e.data.cant + '" disciplina="' + e.data.disciplina + '"]' );
					}
				});
                tinymce.execCommand('mceInsertContent', false, content);
            });
            ed.addButton('lactivcba_button', {title : 'Insertar lista de actividades', cmd : 'lactivcba_insertar_shortcode', image: url.replace('/js', '') + '/images/logo-shortcode.png' });
        },   
    });
    tinymce.PluginManager.add('lactivcba_button', tinymce.plugins.lactivcba_plugin);
})(jQuery);