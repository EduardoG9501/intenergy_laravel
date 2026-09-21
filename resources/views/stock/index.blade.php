@extends('layouts.app')

@section('title', 'Consulta de Stock - Intenergy')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Existencias de Productos (Stock)</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Existencias</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-success fw-semibold" id="exportExcel">
            <i class="fa-solid fa-file-excel me-1"></i> Excel
        </button>
        <button type="button" class="btn btn-sm btn-danger fw-semibold" id="exportPDF">
            <i class="fa-solid fa-file-pdf me-1"></i> PDF
        </button>
    </div>
</div>

<!-- Filtros de Búsqueda -->
<div class="card card-custom p-4 mb-4">
    <form action="{{ route('stock.index') }}" method="GET" class="row g-3">
        <div class="col-md-5">
            <label class="form-label fw-semibold">Buscar Producto</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="buscar" class="form-control border-start-0 ps-0" placeholder="Nombre o código de barra..." value="{{ request('buscar') }}">
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Filtrar por Bodega Principal</label>
            <select class="form-select" name="id_bodega" onchange="this.form.submit()">
                <option value="">Todas las Bodegas</option>
                @foreach($bodegas as $bdg)
                    <option value="{{ $bdg->id_bodega }}" @if(request('id_bodega') == $bdg->id_bodega) selected @endif>{{ $bdg->nombreBodega }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-grid align-items-end">
            <button type="submit" class="btn btn-dark py-2">Aplicar Filtros</button>
        </div>
    </form>
</div>

<!-- Listado de Existencias -->
<div class="card card-custom p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tablaStock">
            <thead class="table-dark">
                <tr>
                    <th class="ps-4">Código de Barra</th>
                    <th>Producto</th>
                    <th>Bodega Principal</th>
                    <th>Bodega Destino (Secundaria)</th>
                    <th class="text-end pe-4">Cantidad Disponible (Stock)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stock as $stk)
                    <tr>
                        <td class="ps-4 fw-bold font-monospace">{{ $stk->articulo?->codigobarra ?: 'N/A' }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $stk->articulo?->nombre }}</div>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary">{{ $stk->bodegaPrincipal?->nombreBodega }}</span>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $stk->bodegaSecundaria?->nombreBodega }}</span>
                        </td>
                        <td class="text-end pe-4">
                            @if($stk->cantidad <= 5)
                                <span class="h6 fw-bold text-danger">{{ number_format($stk->cantidad, 2) }}</span>
                            @else
                                <span class="h6 fw-bold text-success">{{ number_format($stk->cantidad, 2) }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-boxes-stacked fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">No se encontraron registros de existencias en el inventario.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($stock->hasPages())
        <div class="card-footer bg-white py-3 border-0 d-flex justify-content-center">
            {{ $stock->links() }}
        </div>
    @endif
</div>
@endsection

@section('scripts')
<!-- ExcelJS y FileSaver -->
<script src="https://cdn.jsdelivr.net/npm/exceljs/dist/exceljs.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>
<!-- jsPDF y AutoTable -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.7.0/jspdf.plugin.autotable.min.js"></script>

<script>
    // Export Excel
    document.getElementById('exportExcel')?.addEventListener('click', function() {
        var table = document.getElementById('tablaStock');
        var headers = Array.from(table.querySelectorAll('thead tr th')).map(cell => cell.innerText);

        var bodyRows = [];
        Array.from(table.querySelectorAll('tbody tr')).forEach(row => {
            var cols = Array.from(row.cells).map(cell => cell.innerText.trim());
            if (cols.length > 1) bodyRows.push(cols);
        });

        if (bodyRows.length === 0) {
            Swal.fire('Atención', 'No hay datos de stock para exportar.', 'warning');
            return;
        }

        var workbook = new ExcelJS.Workbook();
        var worksheet = workbook.addWorksheet('Stock');

        worksheet.columns = [
            { header: 'Código de Barra', key: 'barcode', width: 20 },
            { header: 'Producto', key: 'producto', width: 40 },
            { header: 'Bodega Principal', key: 'bodega', width: 25 },
            { header: 'Bodega Destino', key: 'destino', width: 25 },
            { header: 'Cantidad', key: 'cantidad', width: 15 }
        ];

        let headerRow = worksheet.getRow(1);
        headerRow.eachCell(cell => {
            cell.font = { bold: true, color: { argb: "FFFFFFFF" } };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: "FF0B1A30" } };
            cell.alignment = { horizontal: "center", vertical: "middle" };
        });

        bodyRows.forEach(row => {
            worksheet.addRow({
                barcode: row[0],
                producto: row[1],
                bodega: row[2],
                destino: row[3],
                cantidad: row[4]
            });
        });

        workbook.xlsx.writeBuffer().then(function(buffer) {
            saveAs(new Blob([buffer], { type: "application/octet-stream" }), "Stock_Productos.xlsx");
        });
    });

    // Export PDF
    document.getElementById('exportPDF')?.addEventListener('click', function() {
        var table = document.getElementById('tablaStock');
        var headers = Array.from(table.querySelectorAll('thead tr th')).map(cell => cell.innerText);

        var bodyRows = [];
        Array.from(table.querySelectorAll('tbody tr')).forEach(row => {
            var cols = Array.from(row.cells).map(cell => cell.innerText.trim());
            if (cols.length > 1) bodyRows.push(cols);
        });

        if (bodyRows.length === 0) {
            Swal.fire('Atención', 'No hay datos de stock para exportar.', 'warning');
            return;
        }

        const doc = new window.jspdf.jsPDF('l', 'mm', 'a4');
        doc.setFontSize(16);
        doc.setTextColor(11, 26, 48);
        doc.text("Reporte de Stock - Intenergy", 14, 20);

        doc.setFontSize(10);
        doc.setTextColor(100);
        doc.text("Fecha: {{ date('d/m/Y H:i') }} | Registros: " + bodyRows.length, 14, 30);

        doc.autoTable({
            head: [headers],
            body: bodyRows,
            startY: 36,
            headStyles: { fillColor: [11, 26, 48] },
            alternateRowStyles: { fillColor: [245, 247, 251] },
            styles: { fontSize: 9 }
        });

        doc.save("Stock_Productos.pdf");
    });
</script>
@endsection
