function escaparHtml(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
}

function formatoMiles(valor) {
    const digitos = String(valor ?? '').replace(/\D/g, '');
    return digitos ? digitos.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
}

function formatoPesos(valor) {
    const numero = Math.round(Number(valor) || 0);
    const texto = String(Math.abs(numero)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return numero < 0 ? '-' + texto : texto;
}

function valorNumerico(input) {
    return parseInt(String(input.value).replace(/\./g, ''), 10) || 0;
}

function showAppAlert(message, type = 'danger', autoDismiss = true, timeout = 5000, esHtml = false, titulo = '') {
    const container = document.getElementById('app-alert-container');
    if (!container) return;

    const tonos = {
        success: { icono: 'bi-check-lg', titulo: 'Listo' },
        warning: { icono: 'bi-exclamation-circle-fill', titulo: 'Atención' },
        danger: { icono: 'bi-exclamation-triangle-fill', titulo: 'No se pudo completar' },
        info: { icono: 'bi-info-circle-fill', titulo: 'Información' }
    };
    const tono = tonos[type] || tonos.info;

    const toast = document.createElement('div');
    toast.className = 'app-toast app-toast-' + (tonos[type] ? type : 'info');
    toast.setAttribute('role', 'alert');
    toast.innerHTML =
        '<div class="app-toast-icon"><i class="bi ' + tono.icono + '"></i></div>' +
        '<div class="app-toast-body">' +
        '<div class="app-toast-title"></div>' +
        '<div class="app-toast-message"></div>' +
        '</div>' +
        '<button type="button" class="app-toast-close" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>';

    toast.querySelector('.app-toast-title').textContent = titulo || tono.titulo;

    const mensaje = toast.querySelector('.app-toast-message');
    if (esHtml) {
        mensaje.innerHTML = message;
    } else {
        mensaje.textContent = message;
    }

    function cerrar() {
        if (toast.dataset.cerrando) return;
        toast.dataset.cerrando = '1';
        toast.classList.remove('show');
        setTimeout(function () { toast.remove(); }, 260);
    }

    toast.querySelector('.app-toast-close').addEventListener('click', cerrar);

    if (autoDismiss) {
        const barra = document.createElement('div');
        barra.className = 'app-toast-progress';
        barra.style.animationDuration = timeout + 'ms';
        barra.addEventListener('animationend', cerrar);
        toast.appendChild(barra);
    }

    container.appendChild(toast);
    window.requestAnimationFrame(function () {
        toast.classList.add('show');
    });
}

function showValidationErrors(errors) {
    if (!errors) return;

    if (typeof errors === 'string') {
        showAppAlert(errors, 'danger');
        return;
    }

    const lista = Array.isArray(errors) ? errors : Object.values(errors).flat();

    if (lista.length === 1) {
        showAppAlert(lista[0], 'danger');
        return;
    }

    const html = '<ul class="app-toast-list">' + lista.map(function (e) {
        return '<li>' + escaparHtml(e) + '</li>';
    }).join('') + '</ul>';

    showAppAlert(html, 'danger', true, 10000, true, 'Revisa estos datos');
}

function confirmDialog(message, opciones) {
    const config = Object.assign({ confirmar: 'Confirmar', cancelar: 'Cancelar', peligro: true }, opciones || {});

    let modalEl = document.getElementById('appConfirmModal');

    if (!modalEl) {
        modalEl = document.createElement('div');
        modalEl.className = 'modal fade';
        modalEl.id = 'appConfirmModal';
        modalEl.tabIndex = -1;
        modalEl.setAttribute('aria-hidden', 'true');
        modalEl.innerHTML =
            '<div class="modal-dialog modal-sm modal-dialog-centered">' +
            '<div class="modal-content">' +
            '<div class="modal-body text-center py-4 px-4" style="background:#fff">' +
            '<div class="confirm-icon"><i class="bi bi-exclamation-triangle"></i></div>' +
            '<p id="appConfirmMessage" class="mb-4"></p>' +
            '<div class="d-flex justify-content-center gap-2">' +
            '<button type="button" class="btn btn-outline-secondary" id="appConfirmCancel" data-bs-dismiss="modal"></button>' +
            '<button type="button" class="btn" id="appConfirmOk"></button>' +
            '</div></div></div></div>';
        document.body.appendChild(modalEl);
    }

    document.getElementById('appConfirmMessage').textContent = message;
    document.getElementById('appConfirmCancel').textContent = config.cancelar;

    const okBtn = document.getElementById('appConfirmOk');
    okBtn.textContent = config.confirmar;
    okBtn.className = 'btn ' + (config.peligro ? 'btn-danger' : 'btn-primary');

    return new Promise(function (resolve) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        let resuelto = false;

        function terminar(valor) {
            if (resuelto) return;
            resuelto = true;
            okBtn.removeEventListener('click', alConfirmar);
            modalEl.removeEventListener('hidden.bs.modal', alCerrar);
            resolve(valor);
        }

        function alConfirmar() {
            terminar(true);
            modal.hide();
        }

        function alCerrar() {
            terminar(false);
        }

        okBtn.addEventListener('click', alConfirmar);
        modalEl.addEventListener('hidden.bs.modal', alCerrar);
        modal.show();
    });
}

function normalizarTexto(texto) {
    return String(texto || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, ' ').trim();
}

function iniciarPaginacionTablas() {
    document.querySelectorAll('tbody[data-paginar]').forEach(function (tbody) {
        const porPagina = parseInt(tbody.dataset.paginar, 10) || 10;
        const filas = Array.from(tbody.querySelectorAll('tr')).filter(function (fila) {
            return !fila.querySelector('.empty-state');
        });

        const campoFiltro = tbody.id ? document.querySelector('[data-filtra="' + tbody.id + '"]') : null;

        if (filas.length <= porPagina && !campoFiltro) return;

        const contenedor = tbody.closest('.table-responsive') || tbody.closest('table');
        const barra = document.createElement('div');
        barra.className = 'pager-bar no-print';
        barra.innerHTML =
            '<span class="pager-info"></span>' +
            '<ul class="pagination pagination-sm">' +
            '<li class="page-item pager-prev"><a class="page-link" href="#">&lsaquo; Anterior</a></li>' +
            '<li class="page-item disabled"><span class="page-link pager-num"></span></li>' +
            '<li class="page-item pager-next"><a class="page-link" href="#">Siguiente &rsaquo;</a></li>' +
            '</ul>';
        contenedor.insertAdjacentElement('afterend', barra);

        const columnas = (tbody.closest('table').querySelectorAll('thead th').length) || 1;
        const filaVacia = document.createElement('tr');
        filaVacia.hidden = true;
        filaVacia.innerHTML = '<td colspan="' + columnas + '"><div class="empty-state"><i class="bi bi-search"></i><strong>Sin coincidencias</strong><span>Prueba con otra palabra.</span></div></td>';
        tbody.appendChild(filaVacia);

        const textos = filas.map(function (fila) { return normalizarTexto(fila.textContent); });
        let visibles = filas.slice();
        let pagina = 1;

        function pintar() {
            const total = visibles.length;
            const paginas = Math.max(1, Math.ceil(total / porPagina));
            pagina = Math.min(pagina, paginas);

            const desde = (pagina - 1) * porPagina;
            const hasta = Math.min(pagina * porPagina, total);

            filas.forEach(function (fila) { fila.hidden = true; });
            visibles.slice(desde, hasta).forEach(function (fila) { fila.hidden = false; });
            filaVacia.hidden = total > 0;

            const filtrando = visibles.length !== filas.length;
            barra.querySelector('.pager-info').textContent = total === 0
                ? '0 registros' + (filtrando ? ' de ' + filas.length : '')
                : 'Mostrando ' + (desde + 1) + '–' + hasta + ' de ' + total + ' registros' + (filtrando ? ' (filtrado de ' + filas.length + ')' : '');
            barra.querySelector('.pager-num').textContent = pagina + ' / ' + paginas;
            barra.querySelector('.pager-prev').classList.toggle('disabled', pagina === 1);
            barra.querySelector('.pager-next').classList.toggle('disabled', pagina === paginas);
            barra.querySelector('.pagination').hidden = paginas <= 1;
        }

        barra.querySelector('.pager-prev a').addEventListener('click', function (e) {
            e.preventDefault();
            if (pagina > 1) { pagina--; pintar(); }
        });

        barra.querySelector('.pager-next a').addEventListener('click', function (e) {
            e.preventDefault();
            if (pagina < Math.ceil(visibles.length / porPagina)) { pagina++; pintar(); }
        });

        if (campoFiltro) {
            campoFiltro.addEventListener('input', function () {
                const q = normalizarTexto(campoFiltro.value);
                visibles = q ? filas.filter(function (fila, i) { return textos[i].indexOf(q) !== -1; }) : filas.slice();
                pagina = 1;
                pintar();
            });
        }

        pintar();
    });
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.sidebar .collapse').forEach(function (collapseEl) {
        collapseEl.addEventListener('show.bs.collapse', function () {
            this.closest('.menu-group').classList.add('open');
        });
        collapseEl.addEventListener('hide.bs.collapse', function () {
            this.closest('.menu-group').classList.remove('open');
        });
    });

    const sidebar = document.getElementById('sidebar');
    const fondo = document.getElementById('sidebarBackdrop');
    const boton = document.getElementById('sidebarToggle');

    function alternarMenu(abrir) {
        if (!sidebar) return;
        sidebar.classList.toggle('open', abrir);
        if (fondo) fondo.classList.toggle('show', abrir);
    }

    if (boton) boton.addEventListener('click', function () { alternarMenu(!sidebar.classList.contains('open')); });
    if (fondo) fondo.addEventListener('click', function () { alternarMenu(false); });

    iniciarPaginacionTablas();
});

document.addEventListener('input', function (e) {
    const input = e.target;

    if (!input.matches || !input.matches('.money, .money-input, .dinero')) return;

    const posicion = input.selectionStart;
    const largoAnterior = input.value.length;

    input.value = formatoMiles(input.value);

    const nueva = Math.max(0, posicion + input.value.length - largoAnterior);
    if (document.activeElement === input && input.type === 'text') {
        input.setSelectionRange(nueva, nueva);
    }
});

document.addEventListener('keydown', function (e) {
    const input = e.target;

    if (!input.matches || !input.matches('.money, .money-input, .dinero')) return;
    if (e.ctrlKey || e.metaKey || e.key.length > 1) return;
    if (!/^\d$/.test(e.key)) e.preventDefault();
});

document.addEventListener('submit', function (e) {
    const form = e.target;

    window.setTimeout(function () {
        if (e.defaultPrevented) return;
        if (form.method.toLowerCase() !== 'post' || form.hasAttribute('data-sin-bloqueo')) return;

        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (boton) {
            boton.disabled = true;
        });
    }, 0);
});

window.addEventListener('pageshow', function (e) {
    if (!e.persisted) return;

    document.querySelectorAll('form button[type="submit"]:disabled').forEach(function (boton) {
        boton.disabled = false;
    });
});

function reindexarMaquinas(contenedor) {
    contenedor.querySelectorAll('[data-maquina]').forEach(function (tarjeta, i) {
        const letra = tarjeta.querySelector('.maquina-letra');
        if (letra) letra.textContent = String.fromCharCode(65 + i);

        tarjeta.querySelectorAll('[name]').forEach(function (campo) {
            campo.name = campo.name.replace(/^maquinas\[[^\]]*\]/, 'maquinas[' + i + ']');
        });
    });
}

function agregarMaquina(contenedor) {
    const plantilla = document.getElementById('plantilla-maquina');
    if (!plantilla) return null;

    if (contenedor.querySelectorAll('[data-maquina]').length >= 26) {
        showAppAlert('No se pueden agregar más de 26 máquinas en una misma orden.', 'warning');
        return null;
    }

    contenedor.insertAdjacentHTML('beforeend', plantilla.innerHTML);
    reindexarMaquinas(contenedor);

    const tarjetas = contenedor.querySelectorAll('[data-maquina]');
    const nueva = tarjetas[tarjetas.length - 1];

    nueva.classList.add('maquina-entering');
    window.setTimeout(function () { nueva.classList.remove('maquina-entering'); }, 450);

    return nueva;
}

document.addEventListener('click', function (e) {
    const contenedor = document.getElementById('contenedor-maquinas');
    if (!contenedor) return;

    if (e.target.closest('.btn-agregar-maquina')) {
        const nueva = agregarMaquina(contenedor);
        if (!nueva) return;

        nueva.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        const primerCampo = nueva.querySelector('input[type="text"]');
        if (primerCampo) primerCampo.focus({ preventScroll: true });
        return;
    }

    const botonQuitar = e.target.closest('.btn-eliminar-maquina');
    if (!botonQuitar) return;

    const tarjeta = botonQuitar.closest('[data-maquina]');
    if (!tarjeta || tarjeta.classList.contains('maquina-saliendo')) return;

    if (contenedor.querySelectorAll('[data-maquina]:not(.maquina-saliendo)').length <= 1) {
        showAppAlert('La orden debe tener al menos una máquina.', 'warning');
        return;
    }

    tarjeta.classList.add('maquina-saliendo');
    window.setTimeout(function () {
        tarjeta.remove();
        reindexarMaquinas(contenedor);
    }, 360);
});

const etiquetasEstadoMaquina = {
    pendiente: 'Pendiente',
    en_reparacion: 'En reparación',
    entregada: 'Entregada'
};

function fechaLocalHoy() {
    const hoy = new Date();
    const mes = String(hoy.getMonth() + 1).padStart(2, '0');
    const dia = String(hoy.getDate()).padStart(2, '0');
    return hoy.getFullYear() + '-' + mes + '-' + dia;
}

document.addEventListener('change', function (e) {
    const campo = e.target;
    if (!campo.matches) return;

    if (campo.matches('[data-campo-estado]')) {
        const tarjeta = campo.closest('[data-maquina]');
        if (!tarjeta) return;

        tarjeta.dataset.estado = campo.value;

        const etiqueta = tarjeta.querySelector('.maquina-estado-pill');
        if (etiqueta) etiqueta.textContent = etiquetasEstadoMaquina[campo.value] || '';

        const fecha = tarjeta.querySelector('[data-campo-fecha-salida]');
        if (fecha && campo.value === 'entregada' && !fecha.value) fecha.value = fechaLocalHoy();
        return;
    }

    if (campo.matches('[data-campo-garantia]')) {
        const tarjeta = campo.closest('[data-maquina]');
        const tipo = tarjeta ? tarjeta.querySelector('[data-campo-tipo-garantia]') : null;
        if (!tipo) return;

        tipo.disabled = campo.value !== '1';
        if (tipo.disabled) {
            tipo.value = '';
        } else {
            tipo.focus();
        }
    }
});

function fechaIso(fecha) {
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const dia = String(fecha.getDate()).padStart(2, '0');
    return fecha.getFullYear() + '-' + mes + '-' + dia;
}

function calcularRangoRapido(clave) {
    const hoy = new Date();
    const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);

    if (clave === 'hoy') return [fechaIso(hoy), fechaIso(hoy)];

    if (clave === 'semana') {
        const inicio = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() - 6);
        return [fechaIso(inicio), fechaIso(hoy)];
    }

    if (clave === 'mes') return [fechaIso(inicioMes), fechaIso(hoy)];

    if (clave === 'mes_pasado') {
        const inicio = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1);
        const fin = new Date(hoy.getFullYear(), hoy.getMonth(), 0);
        return [fechaIso(inicio), fechaIso(fin)];
    }

    return ['', ''];
}

document.addEventListener('click', function (e) {
    const chip = e.target.closest ? e.target.closest('[data-rango]') : null;
    if (!chip) return;

    const form = chip.closest('form');
    if (!form) return;

    const desde = form.querySelector('[name="desde"]');
    const hasta = form.querySelector('[name="hasta"]');
    if (!desde || !hasta) return;

    const rango = calcularRangoRapido(chip.dataset.rango);
    desde.value = rango[0];
    hasta.value = rango[1];
    form.submit();
});

document.addEventListener('change', function (e) {
    const campo = e.target;
    if (campo.matches && campo.matches('select[data-auto]') && campo.form) {
        campo.form.submit();
    }
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-rango]').forEach(function (chip) {
        const form = chip.closest('form');
        const desde = form ? form.querySelector('[name="desde"]') : null;
        const hasta = form ? form.querySelector('[name="hasta"]') : null;
        if (!desde || !hasta || !desde.value || !hasta.value) return;

        const rango = calcularRangoRapido(chip.dataset.rango);
        if (desde.value === rango[0] && hasta.value === rango[1]) chip.classList.add('activo');
    });
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input.money, input.money-input, input.dinero').forEach(function (input) {
        if (input.value) input.value = formatoMiles(input.value);
    });
});
