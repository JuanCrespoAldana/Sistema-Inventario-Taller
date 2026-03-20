@extends('layouts.app')

@section('title', 'Movimientos de Inventario')

@section('page-title')
    <i class="bi bi-arrow-left-right me-2"></i>
    Movimientos de Inventario
@endsection

@section('content')

    <div class="card shadow-sm border-0">

        <div class="card-body">

            {{-- ================= FILTROS ================= --}}
            <form method="GET" class="mb-4">
                <div class="row g-3 align-items-end justify-content-start">

                    <div class="col-12 col-md-5">
                        <label class="form-label fw-semibold text-muted small mb-1">
                            <i class="bi bi-search me-1"></i> Buscar Producto
                        </label>
                        <input type="text" name="buscar" value="{{ request('buscar') }}" class="form-control rounded-3"
                            placeholder="Código o nombre de la máquina...">
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold text-muted small mb-1">
                            <i class="bi bi-filter-circle me-1"></i> Tipo de movimiento
                        </label>
                        <select name="tipo" class="form-select rounded-3">
                            <option value="">Todos</option>
                            <option value="entrada" {{ request('tipo') == 'entrada' ? 'selected' : '' }}>Entrada</option>
                            <option value="salida" {{ request('tipo') == 'salida' ? 'selected' : '' }}>Salida</option>
                            <option value="ajuste" {{ request('tipo') == 'ajuste' ? 'selected' : '' }}>Ajuste</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4 d-flex gap-2 align-items-end">
                        <button type="submit" class="btn btn-primary rounded-3 px-4">
                            <i class="bi bi-search me-1"></i> Filtrar
                        </button>
                        <a href="{{ route('inventario.movimientos.index') }}" class="btn btn-outline-secondary rounded-3 px-3">
                            <i class="bi bi-x-circle me-1"></i> Limpiar
                        </a>
                    </div>

                </div>
            </form>

            {{-- ================= TABLA ================= --}}
            <div class="table-responsive">

                <table class="table table-hover align-middle movimientos-table">

                    <colgroup>
                        <col style="width:30px">
                        <col style="width:80px">
                        <col style="width:320px">
                        <col style="width:100px">
                        <col style="width:90px">
                        <col style="width:170px">
                        <col style="width:150px">
                    </colgroup>

                    <thead class="table-light">
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">Código</th>
                            <th>Producto</th>
                            <th class="text-center">Tipo</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-center">Referencia</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($movimientos as $mov)

                            <tr>
                                {{-- ID --}}
                                <td class="text-center">
                                    <div>{{ $mov->id }}</div>
                                </td>

                                {{-- Código --}}
                                <td class="text-center">
                                    <div>
                                        <span class="badge bg-secondary">
                                            {{ $mov->producto->codigo ?? '-' }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Producto --}}
                                <td>
                                    <div title="{{ $mov->producto->nombre ?? '-' }}">
                                        {{ $mov->producto->nombre ?? '-' }}
                                    </div>
                                </td>

                                {{-- Tipo --}}
                                <td class="text-center">
                                    <div>
                                        @if($mov->tipo == 'entrada')
                                            <span class="badge bg-success">
                                                <i class="bi bi-arrow-down-circle me-1"></i>
                                                Entrada
                                            </span>
                                        @elseif($mov->tipo == 'salida')
                                            <span class="badge bg-danger">
                                                <i class="bi bi-arrow-up-circle me-1"></i>
                                                Salida
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-pencil-square me-1"></i>
                                                Ajuste
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Cantidad --}}
                                <td class="fw-bold text-center">
                                    <div>{{ $mov->cantidad }}</div>
                                </td>

                                {{-- Referencia --}}
                                <td class="text-center">
                                    <div title="{{ $mov->referencia ?? '-' }}">
                                        {{ $mov->referencia ?? '-' }}
                                    </div>
                                </td>

                                {{-- Fecha --}}
                                <td>
                                    <div>
                                        {{ $mov->created_at ? $mov->created_at->format('d/m/Y H:i') : '-' }}
                                    </div>
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                    No hay movimientos registrados
                                </td>
                            </tr>

                        @endforelse
                    </tbody>

                </table>

            </div>

            {{-- ================= PAGINACIÓN ================= --}}
            <div class="mt-3">
                {{ $movimientos->links() }}
            </div>

        </div>
    </div>

@endsection