@extends('layouts.app')

@section('title', 'Nuevo producto')
@section('page-icon', 'plus-circle')
@section('page-title', 'Nuevo producto')
@section('page-subtitle', 'Registra un producto en el inventario.')

@section('page-actions')
    <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Volver al inventario
    </a>
@endsection

@section('content')

    <form method="POST" action="{{ route('inventario.productos.store') }}" class="form-page" autocomplete="off">
        @csrf

        <div class="form-block">
            <div class="form-block-head"><span><i class="bi bi-tag"></i> Identificación</span></div>
            <div class="form-block-body">
                <div class="fields-grid">
                    <div>
                        <label class="form-label" for="codigo">Código</label>
                        <input type="text" id="codigo" name="codigo" class="form-control" placeholder="Ej: EL-1001"
                            value="{{ old('codigo') }}" required autofocus>
                    </div>

                    <div>
                        <label class="form-label" for="modelo">Modelo</label>
                        <input type="text" id="modelo" name="modelo" class="form-control" placeholder="Ej: VF-3100"
                            value="{{ old('modelo') }}">
                    </div>

                    <div class="span-all">
                        <label class="form-label" for="nombre">Nombre del producto</label>
                        <input type="text" id="nombre" name="nombre" class="form-control"
                            placeholder="Ej: Electroválvula 24V" value="{{ old('nombre') }}" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-block">
            <div class="form-block-head"><span><i class="bi bi-cash-coin"></i> Precios y existencias</span></div>
            <div class="form-block-body">
                <div class="fields-grid">
                    <div>
                        <label class="form-label" for="precio_compra">Precio de compra</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="text" id="precio_compra" name="precio_compra" class="form-control money-input"
                                placeholder="0" value="{{ old('precio_compra') }}" inputmode="numeric" required>
                        </div>
                    </div>

                    <div>
                        <label class="form-label" for="precio_venta">Precio de venta</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="text" id="precio_venta" name="precio_venta" class="form-control money-input"
                                placeholder="0" value="{{ old('precio_venta') }}" inputmode="numeric" required>
                        </div>
                    </div>

                    <div>
                        <label class="form-label" for="stock">Stock inicial</label>
                        <input type="number" id="stock" name="stock" class="form-control" min="0" placeholder="0"
                            value="{{ old('stock') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="form-block">
            <div class="form-block-head"><span><i class="bi bi-boxes"></i> Organización <small class="text-muted fw-normal">(opcional)</small></span></div>
            <div class="form-block-body">
                <div class="fields-grid">
                    <div>
                        <label class="form-label" for="categoria">Categoría</label>
                        <input type="text" id="categoria" name="categoria" class="form-control" list="listaCategorias"
                            maxlength="80" placeholder="Ej: Válvulas" value="{{ old('categoria') }}">
                    </div>

                    <div>
                        <label class="form-label" for="ubicacion">Ubicación</label>
                        <input type="text" id="ubicacion" name="ubicacion" class="form-control" maxlength="80"
                            placeholder="Ej: Estante A-2" value="{{ old('ubicacion') }}">
                    </div>

                    <div>
                        <label class="form-label" for="proveedor">Proveedor</label>
                        <input type="text" id="proveedor" name="proveedor" class="form-control" list="listaProveedores"
                            maxlength="120" placeholder="Ej: Distribuidora Andina" value="{{ old('proveedor') }}">
                    </div>

                    <div>
                        <label class="form-label" for="stock_minimo">Stock mínimo</label>
                        <input type="number" id="stock_minimo" name="stock_minimo" class="form-control" min="0"
                            max="100000" placeholder="2" value="{{ old('stock_minimo') }}">
                        <div class="form-text">Avisa como «Stock bajo» cuando las unidades lleguen a este número.</div>
                    </div>
                </div>

                <datalist id="listaCategorias">
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria }}"></option>
                    @endforeach
                </datalist>
                <datalist id="listaProveedores">
                    @foreach ($proveedores as $proveedor)
                        <option value="{{ $proveedor }}"></option>
                    @endforeach
                </datalist>
            </div>
        </div>

        <div class="form-card-actions border-0 pt-0">
            <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2-circle"></i> Guardar producto
            </button>
        </div>

    </form>

@endsection
