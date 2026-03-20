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
                        <small class="text-muted">Valor Potencial Venta</small>
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

            {{-- HEADER --}}
            <div class="d-flex justify-content-between align-items-center mb-4">

                <h5 class="mb-0">
                </h5>

                {{-- BUSCADOR --}}
                <form method="GET" class="d-flex align-items-center gap-2">

                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control border-start-0"
                            placeholder="Código o nombre">
                    </div>

                    <button class="btn btn-primary rounded-3">
                        Buscar
                    </button>

                    @if(request('buscar'))
                        <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary rounded-3">
                            Limpiar
                        </a>
                    @endif

                </form>

            </div>

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

                                <td>
                                    ${{ number_format($producto->precio_compra, 0, ',', '.') }}
                                </td>

                                <td>
                                    ${{ number_format($producto->precio_venta, 0, ',', '.') }}
                                </td>

                                <td class="text-center">
                                    @if($producto->stock == 0)
                                        <span class="badge bg-danger px-3 py-2">0</span>
                                    @elseif($producto->stock <= 2)
                                        <span class="badge bg-warning text-dark px-3 py-2">
                                            {{ $producto->stock }}
                                        </span>
                                    @else
                                        <span class="badge bg-success px-3 py-2">
                                            {{ $producto->stock }}
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">

                                    {{-- EDITAR --}}
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#editModal{{ $producto->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    {{-- ELIMINAR --}}
                                    <form action="{{ route('inventario.productos.destroy', $producto) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('DELETE')

                                        <button class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('¿Eliminar este producto?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>

                                </td>

                            </tr>

                            {{-- MODAL EDITAR --}}
                            <div class="modal fade" id="editModal{{ $producto->id }}" tabindex="-1">

                                <div class="modal-dialog">
                                    <div class="modal-content">

                                        <form method="POST" action="{{ route('inventario.productos.update', $producto) }}">
                                            @csrf
                                            @method('PUT')

                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    Editar Producto
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>

                                            <div class="modal-body">

                                                <div class="mb-3">
                                                    <label class="form-label">Código</label>
                                                    <input type="text" name="codigo" class="form-control"
                                                        value="{{ $producto->codigo }}" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Nombre</label>
                                                    <input type="text" name="nombre" class="form-control"
                                                        value="{{ $producto->nombre }}" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Modelo</label>
                                                    <input type="text" name="modelo" class="form-control"
                                                        value="{{ $producto->modelo }}">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Precio Compra</label>
                                                    <input type="text" name="precio_compra" class="form-control money-input"
                                                        value="{{ number_format($producto->precio_compra, 0, ',', '.') }}"
                                                        required>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Precio Venta</label>
                                                    <input type="text" name="precio_venta" class="form-control money-input"
                                                        value="{{ number_format($producto->precio_venta, 0, ',', '.') }}"
                                                        required>
                                                </div>

                                            </div>

                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    Cancelar
                                                </button>

                                                <button type="submit" class="btn btn-primary">
                                                    Guardar Cambios
                                                </button>
                                            </div>

                                        </form>

                                    </div>
                                </div>
                            </div>

                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No se encontraron productos
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

            {{-- PAGINACIÓN --}}
            <div class="mt-3">
                {{ $productos->links() }}
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {

                function formatMoney(value) {
                    value = value.replace(/\D/g, '');
                    return value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                }

                function cleanMoney(value) {
                    return value.replace(/\./g, '');
                }

                // Formatear mientras escribe
                document.addEventListener('input', function (e) {
                    if (e.target.classList.contains('money-input')) {
                        e.target.value = formatMoney(e.target.value);
                    }
                });

                // Limpiar formato antes de enviar
                document.addEventListener('submit', function (e) {
                    const inputs = e.target.querySelectorAll('.money-input');
                    inputs.forEach(input => {
                        input.value = cleanMoney(input.value);
                    });
                });

            });
        </script>
    @endpush

@endsection