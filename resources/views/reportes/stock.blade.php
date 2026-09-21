@extends('layouts.app')

@section('title', 'Reporte de Stock de Artículos - Intenergy')

@section('styles')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css"/>
<style>
    .select2-container .select2-selection--single {
        height: 38px !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 6px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-0">Reporte de Stock Consolidado</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Stock</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Panel de Filtros -->
<div class="card card-custom p-4 mb-4">
    <form id="formStockFiltros" autocomplete="off" class="row g-3">
        @csrf
        <div class="col-md-3">
            <label class="form-label fw-semibold">Fecha Inicio:</label>
            <input type="date" class="form-control" name="finicio" id="finicio" value="{{ date('Y-m-d') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Fecha Fin:</label>
            <input type="date" class="form-control" name="ffin" id="ffin" value="{{ date('Y-m-d') }}">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Artículo / Material:</label>
            <select name="articuloSelect" id="articuloSelect" class="form-select select2">
                <option value="0">TODOS</option>
                @foreach($articulos as $art)
                    <option value="{{ $art->id_producto }}">{{ $art->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Bodega Principal (Lugar):</label>
            <select name="almacenSelect" id="almacenSelect" class="form-select select2">
                <option value="0">TODOS</option>
                @foreach($bodegas as $b)
                    <option value="{{ $b->id_bodega }}" @if($b->id_bodega == session('bodega_seleccionada')) selected @endif>{{ $b->nombreBodega }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Bodega Secundaria (Ubicación):</label>
            <select name="almacenSelectSecundaria" id="almacenSelectSecundaria" class="form-select select2">
                <option value="0">TODOS</option>
                @foreach($bodegas as $b)
                    <option value="{{ $b->id_bodega }}">{{ $b->nombreBodega }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-12 d-flex justify-content-center gap-3 mt-4">
            <button type="submit" class="btn btn-dark px-4 py-2">
                <i class="fa-solid fa-magnifying-glass me-2"></i> Generar Reporte
            </button>
            <button type="button" class="btn btn-success px-4 py-2" id="exportExcel">
                <i class="fa-solid fa-file-excel me-2"></i> Excel
            </button>
            <button type="button" class="btn btn-danger px-4 py-2" id="exportPDF">
                <i class="fa-solid fa-file-pdf me-2"></i> PDF
            </button>
        </div>
    </form>
</div>

<!-- Tabla de Resultados -->
<div class="card card-custom p-4 overflow-hidden">
    <div id="tablaStockLoadDetalle" class="table-responsive">
        <div class="text-center py-5 text-muted">
            <i class="fa-solid fa-layer-group fa-2x mb-3 text-warning animate-bounce"></i>
            <p class="mb-0">Completa los filtros y pulsa Generar Reporte para mostrar los resultados.</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<!-- ExcelJS y FileSaver -->
<script src="https://cdn.jsdelivr.net/npm/exceljs/dist/exceljs.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver@2.0.5/dist/FileSaver.min.js"></script>
<!-- jsPDF y AutoTable -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.7.0/jspdf.plugin.autotable.min.js"></script>

<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%', placeholder: 'Seleccione...' });

        $('#formStockFiltros').submit(function(e) {
            e.preventDefault();
            generarReporte();
        });

        $('#exportExcel').click(function() {
            if ($('#tbDatosStock').length === 0) {
                Swal.fire('Atención', 'No hay datos cargados para exportar.', 'warning');
                return;
            }
            exportarExcelStock();
        });

        $('#exportPDF').click(function() {
            if ($('#tbDatosStock').length === 0) {
                Swal.fire('Atención', 'No hay datos cargados para exportar.', 'warning');
                return;
            }
            var filtrosAplicados = {
                "Artículo": $('#articuloSelect option:selected').text() || 'Todos',
                "Bodega Principal": $('#almacenSelect option:selected').text() || 'Todas',
                "Bodega Secundaria": $('#almacenSelectSecundaria option:selected').text() || 'Todas',
                "Rango": $('#finicio').val() + ' al ' + $('#ffin').val()
            };
            const resultados = obtenerDatosDeTablaStock('tbDatosStock');
            exportarTablaStockAPDF(resultados, filtrosAplicados);
        });
    });

    function generarReporte() {
        var filtros = $('#formStockFiltros').serialize();
        $('#tablaStockLoadDetalle').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Generando reporte de stock...</p></div>');

        $.get('{{ route("reportes.stock") }}?' + filtros, function(html) {
            $('#tablaStockLoadDetalle').html(html);
            var $tabla = $('#tbDatosStock');
            if ($tabla.length) {
                $tabla.DataTable({
                    responsive: true,
                    destroy: true,
                    ordering: false, // Mantener agrupamiento e historico
                    pageLength: 25
                });
            }
        });
    }

    function obtenerDatosDeTablaStock(tablaId) {
        const resultados = [];
        const table = $('#' + tablaId).DataTable();
        const allData = table.rows().data().toArray();
        allData.forEach(row => {
            resultados.push({
                campo1: row[0].replace(/<[^>]*>/g, ""), // Tipo Mov
                campo2: row[1].replace(/<[^>]*>/g, ""), // Sub Tipo
                campo3: row[2].replace(/<[^>]*>/g, ""), // Lugar
                campo4: row[3].replace(/<[^>]*>/g, ""), // Ubicacion
                campo5: row[4].replace(/<[^>]*>/g, ""), // No.Doc
                campo6: row[5].replace(/<[^>]*>/g, ""), // Producto
                campo7: row[6].replace(/<[^>]*>/g, ""), // Precio
                campo8: row[7].replace(/<[^>]*>/g, ""), // Cantidad
                campo9: row[8].replace(/<[^>]*>/g, ""), // Subtotal
                campo10: row[9].replace(/<[^>]*>/g, ""), // Descuento
                campo11: row[10].replace(/<[^>]*>/g, ""), // Iva
                campo12: row[11].replace(/<[^>]*>/g, ""), // Total
                campo13: row[12].replace(/<[^>]*>/g, "") // Fecha
            });
        });
        return resultados;
    }

    function exportarExcelStock() {
        var table = document.getElementById('tbDatosStock');
        var headers = Array.from(table.querySelectorAll('thead tr th')).map(cell => cell.innerText);

        var bodyRows = [];
        Array.from(table.querySelectorAll('tbody tr')).forEach(row => {
            var cols = Array.from(row.cells).map(cell => cell.innerText);
            bodyRows.push(cols);
        });

        var workbook = new ExcelJS.Workbook();
        var worksheet = workbook.addWorksheet('Stock');

        let headerRow = worksheet.addRow(headers);
        headerRow.eachCell(cell => {
            cell.font = { bold: true, color: { argb: "FFFFFFFF" } };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: "FF0B1A30" } };
            cell.alignment = { horizontal: "center", vertical: "middle" };
            cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
        });

        bodyRows.forEach(row => {
            let addedRow = worksheet.addRow(row);
            if (row[0] === 'TOTAL') {
                addedRow.eachCell(cell => {
                    cell.font = { bold: true, color: { argb: "FF0B1A30" } };
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: "FFCDE6FE" } };
                    cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
                });
            }
        });

        worksheet.columns.forEach(function(column, i) {
            let maxLength = 10;
            column.eachCell({ includeEmpty: true }, function(cell) {
                let cellValue = cell.value ? cell.value.toString() : "";
                maxLength = Math.max(maxLength, cellValue.length + 2);
            });
            column.width = maxLength;
        });

        workbook.xlsx.writeBuffer().then(function(buffer) {
            saveAs(new Blob([buffer], { type: "application/octet-stream" }), "ReporteStockConsolidado.xlsx");
        });
    }

    function exportarTablaStockAPDF(resultados, filtrosAplicados) {
        const doc = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'pt', format: 'A4' });

        doc.setFontSize(22);
        doc.setTextColor(11, 26, 48);
        doc.text("REPORTE DE STOCK DE ARTICULOS", doc.internal.pageSize.getWidth() / 2, 40, { align: "center" });

        doc.setFontSize(10);
        doc.setTextColor(44, 62, 80);
        let filtroY = 60;
        doc.text('Filtros aplicados:', 40, filtroY);
        doc.line(40, filtroY + 2, 120, filtroY + 2);

        filtroY += 15;
        let lineStr = "";
        Object.entries(filtrosAplicados).forEach(([key, value]) => {
            lineStr += `${key}: ${value}  |  `;
        });
        doc.text(lineStr, 40, filtroY);
        filtroY += 20;

        const columns = [
            { header: 'Tipo', dataKey: 'campo1' },
            { header: 'Subtipo', dataKey: 'campo2' },
            { header: 'Lugar', dataKey: 'campo3' },
            { header: 'Ubicacion', dataKey: 'campo4' },
            { header: 'No.Doc', dataKey: 'campo5' },
            { header: 'Producto', dataKey: 'campo6' },
            { header: 'Precio', dataKey: 'campo7' },
            { header: 'Cantidad', dataKey: 'campo8' },
            { header: 'Subtotal', dataKey: 'campo9' },
            { header: 'Descuento', dataKey: 'campo10' },
            { header: 'Iva', dataKey: 'campo11' },
            { header: 'Total', dataKey: 'campo12' },
            { header: 'Fecha', dataKey: 'campo13' }
        ];

        // Mapeo simple de datos
        const dataBody = resultados.map(r => ({
            campo1: r.campo1, campo2: r.campo2, campo3: r.campo3, campo4: r.campo4,
            campo5: r.campo5, campo6: r.campo6, campo7: r.campo7, campo8: r.campo8,
            campo9: r.campo9, campo10: r.campo10, campo11: r.campo11, campo12: r.campo12,
            campo13: r.campo13
        }));

        doc.autoTable({
            columns: columns,
            body: dataBody,
            startY: filtroY,
            styles: {
                fontSize: 8,
                cellPadding: 3,
            },
            headStyles: {
                fillColor: [11, 26, 48],
                textColor: [255, 255, 255],
                fontStyle: 'bold'
            },
            alternateRowStyles: {
                fillColor: [245, 247, 251]
            },
            didParseCell: function(data) {
                if (data.row.raw.campo1 === 'TOTAL') {
                    data.cell.styles.fillColor = [205, 230, 254]; // celeste
                    data.cell.styles.fontStyle = 'bold';
                    data.cell.styles.textColor = [11, 26, 48];
                }
            },
            margin: { left: 40, right: 40 }
        });

        const fecha = new Date().toLocaleString();
        doc.setFontSize(8);
        doc.setTextColor(127, 140, 141);
        doc.text(`Generado el: ${fecha} | Usuario: {{ Auth::user()->nombre }}`, 40, doc.internal.pageSize.getHeight() - 20);
        doc.save('Reporte_Stock.pdf');
    }
</script>
@endsection
