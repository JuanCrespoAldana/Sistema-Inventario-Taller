@extends('layouts.app')

@section('page-title')
    <i class="bi bi-box-seam me-2"></i>
    Inventario
@endsection

@section('content')

    {{-- ================= MINI DASHBOARD ================= --}}
    <div class="row mb-4 g-3">

        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3 text-primary fs-2">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div>
                        <small class="text-muted">Valor Inventario</small>
                        <h5 class="fw-bold mb-0">
                            ${{ number_format($valorCompra, 0, ',', '.') }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3 text-success fs-2">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div>
                        <small class="text-muted">Valor Inventario</small>
                        <h5 class="fw-bold mb-0">
                            ${{ number_format($valorVenta, 0, ',', '.') }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 bg-danger text-white">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3 fs-2">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <small>Productos Críticos</small>
                        <h5 class="fw-bold mb-0">{{ $productosCriticos }}</h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-0 rounded-4 bg-warning">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3 fs-2">
                        <i class="bi bi-exclamation-circle-fill"></i>
                    </div>
                    <div>
                        <small>Stock Bajo</small>
                        <h5 class="fw-bold mb-0">{{ $productosBajos }}</h5>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ================= TABLA ================= --}}
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">

            <form method="GET" action="{{ route('inventario.index') }}" id="formBusqueda" autocomplete="off">
                <div class="row align-items-end gx-3 gy-2 mb-4">

                    <div class="col-12 col-md-4 col-lg-3">
                        <label for="filtro_stock" class="form-label text-muted">Filtrar stock</label>
                        <select name="filtro_stock" id="filtro_stock" class="form-select shadow-sm">
                            <option value="">Todos</option>
                            <option value="bajo" {{ request('filtro_stock') == 'bajo' ? 'selected' : '' }}>Stock Bajo</option>
                            <option value="critico" {{ request('filtro_stock') == 'critico' ? 'selected' : '' }}>Productos Críticos</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-5">
                        <label for="buscar" class="form-label text-muted">Buscar</label>
                        {{-- Contenedor relativo para que el dropdown quede posicionado correctamente --}}
                        <div class="position-relative">
                            <div class="input-group shadow-sm">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-search" id="iconoBuscar"></i>
                                </span>
                                <input type="text" id="buscar" name="buscar"
                                    value="{{ request('buscar') }}"
                                    class="form-control border-start-0 rounded-end"
                                    placeholder="Código o nombre del producto"
                                    role="combobox"
                                    aria-expanded="false"
                                    aria-autocomplete="list"
                                    aria-controls="autocomplete-lista">
                            </div>
                            {{-- Dropdown autocomplete --}}
                            <ul id="autocomplete-lista"
                                role="listbox"
                                class="list-unstyled mb-0 position-absolute w-100 bg-white border rounded-3 shadow-lg"
                                style="top: calc(100% + 4px); z-index: 1055; display: none; max-height: 260px; overflow-y: auto;">
                            </ul>
                        </div>
                    </div>

                    <div class="col-12 col-md-auto d-flex align-items-center gap-2">
                        <button type="submit" class="btn btn-primary rounded-3 px-4">
                            <i class="bi bi-search me-1"></i>Buscar
                        </button>
                        @if(request('buscar') || request('filtro_stock'))
                            <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary rounded-3 px-4">
                                <i class="bi bi-x-lg me-1"></i>Limpiar
                            </a>
                        @endif
                    </div>

                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Modelo</th>
                            <th>Costo</th>
                            <th>Venta</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productos as $producto)
                            <tr>
                                <td class="fw-semibold">{{ $producto->codigo }}</td>
                                <td>{{ $producto->nombre }}</td>
                                <td class="text-muted">{{ $producto->modelo }}</td>
                                <td>${{ number_format($producto->precio_compra, 0, ',', '.') }}</td>
                                <td>${{ number_format($producto->precio_venta, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    @if($producto->stock == 0)
                                        <span class="badge bg-danger px-3 py-2">0</span>
                                    @elseif($producto->stock <= 2)
                                        <span class="badge bg-warning text-dark px-3 py-2">{{ $producto->stock }}</span>
                                    @else
                                        <span class="badge bg-success px-3 py-2">{{ $producto->stock }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button class="btn btn-sm btn-outline-primary btn-editar-producto"
                                            title="Editar producto"
                                            data-id="{{ $producto->id }}"
                                            data-codigo="{{ $producto->codigo }}"
                                            data-nombre="{{ $producto->nombre }}"
                                            data-modelo="{{ $producto->modelo ?? '' }}"
                                            data-precio-compra="{{ $producto->precio_compra }}"
                                            data-precio-venta="{{ $producto->precio_venta }}"
                                            data-url="{{ route('inventario.productos.update', $producto) }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <form action="{{ route('inventario.productos.destroy', $producto) }}"
                                            method="POST" class="d-inline m-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger btn-confirm-delete"
                                                title="Eliminar producto"
                                                data-message="¿Eliminar el producto {{ $producto->nombre }}? Esta acción no se puede deshacer.">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No se encontraron productos</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $productos->links() }}
            </div>

        </div>
    </div>

@endsection

{{-- ===== MODAL editar producto ===== --}}
@push('modals')
<div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formEditarProducto">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square"></i> Editar Producto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Código</label>
                        <input type="text" name="codigo" id="edit_codigo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Modelo</label>
                        <input type="text" name="modelo" id="edit_modelo" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Precio Compra</label>
                        <div class="input-group">

                            <input type="text" name="precio_compra" id="edit_precio_compra"
                                class="form-control money-input" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Precio Venta</label>
                        <div class="input-group">

                            <input type="text" name="precio_venta" id="edit_precio_venta"
                                class="form-control money-input" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<style>
    /* ── Autocomplete ── */
    #autocomplete-lista li {
        padding: 0.55rem 0.9rem;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.12s;
        line-height: 1.35;
    }
    #autocomplete-lista li:last-child  { border-bottom: none; }
    #autocomplete-lista li:hover,
    #autocomplete-lista li.ac-active  { background: #f0f4ff; }
    #autocomplete-lista .ac-codigo    { font-size: .72rem; font-weight: 600; color: #6c757d; letter-spacing: .04em; }
    #autocomplete-lista .ac-nombre    { font-size: .9rem;  font-weight: 500; color: #212529; }
    #autocomplete-lista .ac-modelo    { font-size: .75rem; color: #adb5bd; }
    #autocomplete-lista .ac-stock     { font-size: .72rem; font-weight: 600; }
    #autocomplete-lista mark          { background: #fff3cd; padding: 0 2px; border-radius: 2px; }
    #autocomplete-lista .ac-empty     { padding: .8rem 1rem; color: #adb5bd; font-size: .88rem; text-align: center; cursor: default; }
    #autocomplete-lista .ac-loading   { padding: .8rem 1rem; color: #adb5bd; font-size: .88rem; text-align: center; cursor: default; }
</style>
<script>
(function () {
    /* ─────────────────────────────────────────────
       UTILIDADES
    ───────────────────────────────────────────── */
    function formatearMoneda(valor) {
        valor = Math.round(parseFloat(valor) || 0).toString();
        return valor.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function escaparHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, c =>
            ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' })[c]
        );
    }

    function resaltar(texto, query) {
        if (!query) return escaparHtml(texto);
        const re = new RegExp('(' + query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return escaparHtml(texto).replace(re, '<mark>$1</mark>');
    }

    /* ─────────────────────────────────────────────
       AUTOCOMPLETE
    ───────────────────────────────────────────── */
    const inputBuscar  = document.getElementById('buscar');
    const lista        = document.getElementById('autocomplete-lista');
    const formBusqueda = document.getElementById('formBusqueda');
    const urlBuscar    = "{{ route('inventario.buscar.producto') }}";

    let timer       = null;
    let activeIndex = -1;
    let ultimaQuery = '';

    function mostrarLista(html) {
        lista.innerHTML = html;
        lista.style.display = 'block';
        inputBuscar.setAttribute('aria-expanded', 'true');
        activeIndex = -1;
    }

    function ocultarLista() {
        lista.style.display = 'none';
        lista.innerHTML = '';
        inputBuscar.setAttribute('aria-expanded', 'false');
        activeIndex = -1;
    }

    function stockBadge(stock) {
        if (stock === 0)    return '<span class="ac-stock text-danger">● Sin stock</span>';
        if (stock <= 2)     return `<span class="ac-stock text-warning">● Stock: ${stock}</span>`;
        return `<span class="ac-stock text-success">● Stock: ${stock}</span>`;
    }

    function renderSugerencias(productos, query) {
        if (!productos.length) {
            mostrarLista('<li class="ac-empty"><i class="bi bi-search me-1"></i>Sin resultados para "<em>' + escaparHtml(query) + '</em>"</li>');
            return;
        }
        const items = productos.map((p, i) => `
            <li role="option" data-value="${escaparHtml(p.nombre)}" data-index="${i}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="ac-codigo">${resaltar(p.codigo, query)}</div>
                        <div class="ac-nombre">${resaltar(p.nombre, query)}</div>
                        ${p.modelo ? `<div class="ac-modelo">${escaparHtml(p.modelo)}</div>` : ''}
                    </div>
                    <div class="text-end ms-2 flex-shrink-0">
                        ${stockBadge(p.stock)}
                    </div>
                </div>
            </li>
        `).join('');
        mostrarLista(items);
    }

    async function buscar(query) {
        if (query.length < 2) { ocultarLista(); return; }
        if (query === ultimaQuery) return;
        ultimaQuery = query;

        mostrarLista('<li class="ac-loading"><span class="spinner-border spinner-border-sm me-2"></span>Buscando…</li>');

        try {
            const res = await fetch(`${urlBuscar}?q=${encodeURIComponent(query)}`);
            if (!res.ok) throw new Error();
            const data = await res.json();
            renderSugerencias(data, query);
        } catch {
            ocultarLista();
        }
    }

    // Evento: tecleo en el input
    inputBuscar.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { ocultarLista(); ultimaQuery = ''; return; }
        timer = setTimeout(() => buscar(q), 220);
    });

    // Evento: navegación con teclado
    inputBuscar.addEventListener('keydown', function (e) {
        const items = lista.querySelectorAll('li[data-value]');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
        } else if (e.key === 'Escape') {
            ocultarLista();
            return;
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            items[activeIndex].click();
            return;
        } else {
            return;
        }

        items.forEach((li, i) => li.classList.toggle('ac-active', i === activeIndex));
        if (items[activeIndex]) items[activeIndex].scrollIntoView({ block: 'nearest' });
    });

    // Evento: clic en sugerencia
    lista.addEventListener('click', function (e) {
        const li = e.target.closest('li[data-value]');
        if (!li) return;
        inputBuscar.value = li.dataset.value;
        ocultarLista();
        formBusqueda.submit();
    });

    // Cerrar al hacer clic fuera
    document.addEventListener('click', function (e) {
        if (!inputBuscar.contains(e.target) && !lista.contains(e.target)) {
            ocultarLista();
        }
    });

    /* ─────────────────────────────────────────────
       AUTO-FORMATO EN CAMPOS DE PRECIO (modal editar)
       Inserta puntos de miles mientras el usuario escribe:
       460000 → 460.000  /  1200000 → 1.200.000
    ───────────────────────────────────────────── */
    document.querySelectorAll('.money-input').forEach(function (input) {

        input.addEventListener('input', function () {
            // Conservar posición del cursor para no saltar al final
            const selStart = this.selectionStart;
            const prevLen  = this.value.length;

            // Quitar todo lo que no sea dígito
            const solo = this.value.replace(/\D/g, '');

            // Formatear con puntos de miles
            const formateado = solo.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            this.value = formateado;

            // Ajustar cursor: compensar los puntos que se añadieron/quitaron
            const diff = formateado.length - prevLen;
            const pos  = Math.max(0, selStart + diff);
            this.setSelectionRange(pos, pos);
        });

        // Solo permitir teclas numéricas y de control (borrar, flechas, etc.)
        input.addEventListener('keydown', function (e) {
            const permitidas = [
                'Backspace','Delete','ArrowLeft','ArrowRight',
                'ArrowUp','ArrowDown','Tab','Home','End'
            ];
            if (permitidas.includes(e.key)) return;
            if (e.ctrlKey || e.metaKey) return; // Ctrl+C, Ctrl+V, etc.
            if (!/^\d$/.test(e.key)) e.preventDefault();
        });
    });

    /* ─────────────────────────────────────────────
       BOTÓN EDITAR — abre el modal con los datos
    ───────────────────────────────────────────── */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-editar-producto');
        if (!btn) return;

        document.getElementById('edit_codigo').value        = btn.dataset.codigo;
        document.getElementById('edit_nombre').value        = btn.dataset.nombre;
        document.getElementById('edit_modelo').value        = btn.dataset.modelo;
        document.getElementById('edit_precio_compra').value = formatearMoneda(btn.dataset.precioCompra);
        document.getElementById('edit_precio_venta').value  = formatearMoneda(btn.dataset.precioVenta);
        document.getElementById('formEditarProducto').action = btn.dataset.url;

        // ✅ Abrir el modal de Bootstrap
        const modalEl = document.getElementById('modalEditarProducto');
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });

    /* ─────────────────────────────────────────────
       BOTÓN ELIMINAR — confirmación antes de borrar
    ───────────────────────────────────────────── */
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.btn-confirm-delete');
        if (!btn) return;

        const form    = btn.closest('form');
        const message = btn.dataset.message || '¿Confirmar eliminación?';
        const ok      = await confirmDialog(message);
        if (ok && form) form.submit();
    });

})();
</script>
@endpush
