window.BMC = (function(window, document, $) {
    var app = {};

    app.cache = function() {
        app.$els = {
            $document: $(document),
            $BMC: $('#bmc')
        };
        app.$els.$sidebar = app.$els.$BMC.find('.c-sidebar');
        app.$els.$hamburger = app.$els.$BMC.find('.c-hamburger');
        app.$els.$dropdownLinks = app.$els.$sidebar.find('.c-dropdown a');
        app.$els.$listaActividades = app.$els.$BMC.find('.c-buscador__contenido');
        app.$els.$actividades = app.$els.$listaActividades.find('.o-actividad');
        app.$els.$particular = app.$els.$BMC.find('.c-actividades--particular');
        app.$els.$loader = app.$els.$BMC.find('.c-loading');
        app.$els.$mensaje = app.$els.$BMC.find('.c-mensaje');
    		app.$els.$verTodo = app.$els.$BMC.find('#volver-ver-todo');
        app.$els.$twitter = app.$els.$particular.find('.c-social__boton--twitter');
        app.$els.$facebook = app.$els.$particular.find('.c-social__boton--facebook');
        app.$els.$link = app.$els.$particular.find('.c-social__boton--link');
        app.$els.$inputLink = app.$els.$particular.find('.c-social__link');

        app.url = window.location.protocol + "//" + window.location.hostname + window.location.pathname;
        app.idAnterior = 1;
        app.sinResultados = false;

        app.eventos = {
            toggleSidebar: function() {
                app.$els.$sidebar.toggleClass('c-sidebar--abierto');
            },

            toggleDropdown: function(e) {
                e.preventDefault();
                var padre = $(this).parent();

                if (padre.hasClass('c-sidebar__nav--abierto')) {
                    $(this).siblings('.c-dropdown__menu').first().stop(true, true).slideUp();
                } else {
                    $(this).siblings('.c-dropdown__menu').first().stop(true, true).slideDown();
                }
                padre.toggleClass('c-sidebar__nav--abierto');

            },

            cerrarSidebarEsc: function(e) {
                if (e.keyCode == 27) {
                    app.$els.$sidebar.removeClass('c-sidebar__nav--abierto');
                }
            },

            toggleLoader: function(callback) {
                var $loader = app.$els.$loader;
                if (callback) {
                    $loader.is(':visible') ? $loader.hide("normal", callback) : $loader.fadeIn(app.eventos.ocultarMensaje);
                } else {
                    if ($loader.is(':visible')) {
                        $loader.hide()
                    } else {
                        app.eventos.ocultarMensaje();
                        $loader.fadeIn("normal", callback);
                    }
                }
            },

            ocultarMensaje: function() {
                app.$els.$mensaje.hide();
            },

            volver: function(e) {
                e.preventDefault();
                app.$els.$mensaje.hide();
                app.$els.$particular.hide();
                app.$els.$listaActividades.slideDown();
                app.sinResultados && app.$els.$actividades.fadeIn();
            },

            mostrarMensaje: function(texto) {
                app.eventos.toggleLoader(function() {
                    app.$els.$mensaje.find('p').html('<p>' + texto + '</p>').parent().fadeIn();
                });
            },

            mostrarTodasActividades: function(e) {
                e.preventDefault();
                app.$els.$particular.hide();
                app.$els.$listaActividades.show();
                app.$els.$mensaje.hide();
                app.$els.$loader.hide();
                app.$els.$actividades.fadeIn();
				app.$els.$verTodo.hide();
            }
        };

        app.eventos.cambiarDatosEventoParticular = function(datos) {
            var $particular = app.$els.$particular;

            if (datos.detail === 'No encontrado.') {
                app.eventos.mostrarMensaje(('Disculpe, no se encontró el evento. Puede que ya haya terminado.'));
            } else if (datos.id === undefined || datos.success === false || datos.id < 1) {
                app.eventos.mostrarMensaje('Ocurrió un error al cargar el evento, podés <a href="#" id="reintentar">intentar de nuevo</a> o buscar otra actividad.');
            } else {
                $particular.find('.o-actividad--particular').attr('data-id', datos.id);
                $particular.find('.o-actividad__titulo').html(datos.titulo);
                datos.descripcion ? $particular.find('.o-actividad__descripcion').html(datos.descripcion) : $particular.find('.o-actividad__descripcion').html('');
                datos.imagen != '' ? $particular.find('img').attr('src', datos.imagen.thumbnail) : $particular.find('img').attr('src', buscarActividad.imagen);

                datos.agrupador ? $particular.find('.o-actividad__evento').html('<b>' + datos.agrupador.nombre + '<b>').show() : $particular.find('.o-actividad__evento').html('').hide();

                datos.fecha_actividad ? $particular.find('.o-actividad__fecha-actividad').html(datos.fecha_actividad) : $particular.find('.o-actividad__fecha-actividad').html('');
                datos.fecha_inicia ? $particular.find('.o-actividad__fecha-inicia').html("<b>Inicia:</b> " + datos.fecha_inicia).show() : $particular.find('.o-actividad__fecha-inicia').html('').hide();
                datos.fecha_termina ? $particular.find('.o-actividad__fecha-termina').html("<b>Termina:</b> " + datos.fecha_termina).show() : $particular.find('.o-actividad__fecha-termina').html('').hide();

                $particular.find('input').val('').hide();

                if (datos.tipos) {
                    var tipos = datos.tipos;
                    var textoTipo = "";
                    for (var t in tipos) {
                        textoTipo += '<span class="c-tipos__tipo">' + tipos[t].nombre + '</span>';
                    }
                    $particular.find('.c-tipos').html(textoTipo).show();
                } else {
                    $particular.find('.c-tipos').html('').hide();
                }
                datos.lugar ? $particular.find('.o-actividad__lugar').html('<b>Lugar:</b> ' + datos.lugar.nombre + (datos.lugar.descripcion && " (" + datos.lugar.descripcion + ")")).show() : $particular.find('.o-actividad__lugar').html('').hide();

                app.idAnterior = datos.id;

                app.eventos.toggleLoader(function() {
                    $particular.fadeIn();
                });
            }
        };

        app.eventos.buscarResultadoAjax = function(data) {
            app.eventos.toggleLoader();
            $.ajax({
                type: "POST",
                dataType: "JSON",
                url: buscarActividad.url,
                data: data,
                success: function(response) {
                    if (response.data) {
                        app.eventos.cambiarDatosEventoParticular(response.data);
                    } else {
                        alert('Sin respuesta');
                        $('.c-atras').click();
                        app.eventos.toggleLoader();
                    }
                },
                error: function(error) {
                    $('.c-atras').click();
                    app.eventos.toggleLoader();
                }
            });
        };

        app.eventos.buscarResultado = function($actividad) {
            var id = $actividad.attr('data-id');
            var data = {
                action: 'buscar_actividad',
                nonce: buscarActividad.nonce,
                id: id
            };
            app.eventos.buscarResultadoAjax(data);
        };

        app.eventos.clickEnActividad = function(e) {
            app.$els.$listaActividades.hide();

            if ($(e.target).parents('li').attr('data-id') != app.idAnterior) {
                app.eventos.buscarResultado($(e.target).parents('li'));
            } else {
                app.$els.$particular.fadeIn();
            }
        }

        app.eventos.compartirFacebook = function(e) {
            e.preventDefault();
            var id = $(e.target).parents('.o-actividad').attr('data-id');
            window.open("https://www.facebook.com/sharer/sharer.php?u=" + app.url + "?ac=" + id, "pop", "width=600, height=400, 'menubar=0,location=0,toolbar=0,status=0,scrollbars=1'");
            return false;
        }

        app.eventos.compartirTwitter = function(e) {
            e.preventDefault();
            var $actividad = $(e.target).parents('.o-actividad');
            window.open("http://twitter.com/home?status=" + $actividad.find('.o-actividad__titulo').text() + " " + app.url + "?ac=" + $actividad.attr('data-id'), "pop", "width=600, height=300, 'menubar=0,location=0,toolbar=0,status=0,scrollbars=1'");
            return false;
        }

        app.eventos.compartirLink = function(e) {
            e.preventDefault();
            var $actividad = $(e.target).parents('.o-actividad');
            app.$els.$inputLink.val(app.url + "?ac=" + $actividad.attr('data-id')).slideDown();
            return false;
        }

        app.eventos.filtrar = function(e) {
            app.eventos.volver(e);
            app.$els.$loader.hide();
            app.$els.$loader.fadeIn();

            var $this = $(this);
            var actividades = app.$els.$actividades;
            var cantidadActividades = actividades.length;
            var resultados = [];

            filtro = $this.attr('data-filtro');
            id = $this.attr('data-id');
            
            actividades.hide();
            if (filtro == 'tipo' || filtro == 'disciplina' || filtro == 'audiencia' || filtro == 'fecha') {
                for (var i = 0; i < cantidadActividades; i++) {
                    var ids = actividades.eq(i).attr('data-' + filtro).split('|');
                    ids.pop();

                    if ($.inArray(id, ids) >= 0) {
                        resultados.push(actividades.eq(i));
                    }
                }
                $.each(resultados, function(index, res) {
                    $(res).fadeIn();
                });
            } else {
                resultados = app.$els.$listaActividades.find('li[data-' + filtro + '="' + id + '"]').fadeIn();
            }

            if (resultados.length < 1) {
                app.sinResultados = true;
                app.$els.$listaActividades.hide();
                app.eventos.mostrarMensaje('No se encontraron resultados. <a id="ver-todo" href="#">Ver todas las actividades</a>');
            } else {
                app.sinResultados = false;
                app.eventos.toggleLoader()
				app.$els.$verTodo.show();
            }

            app.eventos.toggleSidebar();
        }
    };

    app.init = function() {
        // Cachea las referencias a los elementos que se van a usar
        app.cache();

        // Se asocian eventos a elementos
        $('.c-atras').click(app.eventos.volver);
        $('.c-sidebar__toggle').on('click', app.eventos.toggleSidebar);
        app.$els.$hamburger.on('click', app.eventos.toggleSidebar);
        app.$els.$dropdownLinks.on('click', app.eventos.toggleDropdown);
        app.$els.$document.keyup(app.eventos.cerrarSidebarEsc);
        app.$els.$document.on('click', '.c-buscador__contenido .o-actividad', app.eventos.clickEnActividad);
        app.$els.$document.on('click', '#reintentar', function(e) {
            e.preventDefault();
            app.eventos.buscarResultadoAjax({
                action: 'buscar_actividad',
                nonce: buscarActividad.nonce,
                id: app.idAnterior
            });
        });

        // Botones de redes sociales
        app.$els.$facebook.click(app.eventos.compartirFacebook);
        app.$els.$twitter.click(app.eventos.compartirTwitter);
        app.$els.$link.click(app.eventos.compartirLink);

        // Eventos de filtrado
        app.$els.$document.on('click', '.c-dropdown__item', app.eventos.filtrar);
        app.$els.$document.on('click', '.c-boton_fecha--semana', app.eventos.filtrar);
        app.$els.$document.on('click', '.c-boton_fecha--mes', app.eventos.filtrar);
        app.$els.$document.on('click', '.c-boton_fecha--ninguno', function (e) {
            app.eventos.mostrarTodasActividades(e);
            app.eventos.toggleSidebar(e);
        });
        app.$els.$document.on('click', '#ver-todo', app.eventos.mostrarTodasActividades);
		app.$els.$document.on('click', '#volver-ver-todo', app.eventos.mostrarTodasActividades);

        // Si viene un id por GET, busca esa actividad
        if (buscarActividad.actividad !== undefined) {
            app.$els.$listaActividades.hide();
            app.eventos.buscarResultadoAjax({
                action: 'buscar_actividad',
                nonce: buscarActividad.nonce,
                id: buscarActividad.actividad
            });
        }
    };

    $(document).ready(app.init);

    return app;

})(window, document, jQuery);