@extends('layouts.app')

@section('title', 'Reporte de Disponibilidad de Artículos por Bodega - Intenergy')

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
        <h2 class="fw-bold mb-0">Disponibilidad de Artículos por Bodega</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('inicio') }}">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Disponibilidad</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Panel de Filtros -->
<div class="card card-custom p-4 mb-4">
    <form id="formDisponibilidadFiltros" autocomplete="off" class="row g-3">
        @csrf
        <div class="col-md-5">
            <label class="form-label fw-semibold">Bodega / Almacén:</label>
            <select name="id_bodega" id="id_bodega" class="form-select select2">
                <option value="">Todas</option>
                @foreach($bodegas as $b)
                    <option value="{{ $b->id_bodega }}" @if($b->id_bodega == session('bodega_seleccionada')) selected @endif>{{ $b->nombreBodega }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label fw-semibold">Artículo / Insumo:</label>
            <select name="id_producto" id="id_producto" class="form-select select2">
                <option value="">Todos</option>
                @foreach($articulos as $art)
                    <option value="{{ $art->id_producto }}">{{ $art->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-grid align-self-end">
            <button type="submit" class="btn btn-dark py-2">
                <i class="fa-solid fa-magnifying-glass me-2"></i> Buscar
            </button>
        </div>

        <div class="col-md-12 d-flex justify-content-center gap-3 mt-4 border-top pt-3">
            <button type="button" class="btn btn-success px-4 py-2" id="exportExcel">
                <i class="fa-solid fa-file-excel me-2"></i> Exportar a Excel
            </button>
            <button type="button" class="btn btn-danger px-4 py-2" id="exportPDF">
                <i class="fa-solid fa-file-pdf me-2"></i> Imprimir PDF
            </button>
        </div>
    </form>
</div>

<!-- Tabla de Resultados -->
<div class="card card-custom p-4 overflow-hidden">
    <table id="tablaDisponibilidad" class="table table-bordered table-striped align-middle mb-0" style="width:100%">
        <thead class="table-dark">
            <tr>
                <th style="width: 15%;">ID Artículo</th>
                <th style="width: 15%;">ID Bodega</th>
                <th style="width: 40%;">Nombre Artículo</th> 
                <th style="width: 20%;">Nombre Bodega</th>
                <th style="width: 10%;">Cantidad Disponible</th>
            </tr>
        </thead>
        <tbody>
            <!-- Se llena dinámicamente vía AJAX -->
        </tbody>
    </table>
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

        $('#formDisponibilidadFiltros').submit(function(e) {
            e.preventDefault();
            buscarDisponibilidad();
        });

        $('#exportExcel').click(function() {
            var dataCount = $('#tablaDisponibilidad').DataTable().rows().count();
            if (dataCount === 0) {
                Swal.fire('Atención', 'No hay datos cargados para exportar.', 'warning');
                return;
            }
            exportarExcelDisponibilidad();
        });

        $('#exportPDF').click(function() {
            var dataCount = $('#tablaDisponibilidad').DataTable().rows().count();
            if (dataCount === 0) {
                Swal.fire('Atención', 'No hay datos cargados para exportar.', 'warning');
                return;
            }
            exportarTablaDisponibilidadAPDF();
        });

        // Ejecutar carga inicial
        buscarDisponibilidad();
    });

    function buscarDisponibilidad() {
        var filtros = $('#formDisponibilidadFiltros').serialize();

        $.get('{{ route("reportes.disponibilidad") }}?' + filtros, function(r) {
            var dt = $('#tablaDisponibilidad').DataTable({
                responsive: true,
                destroy: true,
                data: r,
                columns: [
                    { data: 'id_producto', className: 'font-monospace fw-bold' },
                    { data: 'id_bodega', className: 'font-monospace' },
                    { data: 'nombre_articulo', className: 'fw-semibold text-dark text-start ps-3' },
                    { data: 'nombreBodega', className: 'text-start ps-3' },
                    { data: 'cantidad', className: 'fw-bold text-primary font-monospace', render: function(d) {
                        return parseFloat(d).toFixed(2);
                    }}
                ],
                order: [[3, 'asc'], [2, 'asc']]
            });
        });
    }

    function exportarExcelDisponibilidad() {
        var table = document.getElementById('tablaDisponibilidad');
        var headers = Array.from(table.querySelectorAll('thead tr th')).map(cell => cell.innerText);

        var bodyRows = [];
        var tableDT = $('#tablaDisponibilidad').DataTable();
        var allData = tableDT.rows().data().toArray();
        allData.forEach(row => {
            bodyRows.push([
                row.id_producto,
                row.id_bodega,
                row.nombre_articulo,
                row.nombreBodega,
                parseFloat(row.cantidad).toFixed(2)
            ]);
        });

        var workbook = new ExcelJS.Workbook();
        var worksheet = workbook.addWorksheet('Disponibilidad');

        let headerRow = worksheet.addRow(headers);
        headerRow.eachCell(cell => {
            cell.font = { bold: true, color: { argb: "FFFFFFFF" } };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: "FF0B1A30" } };
            cell.alignment = { horizontal: "center", vertical: "middle" };
            cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
        });

        bodyRows.forEach(row => worksheet.addRow(row));

        worksheet.columns.forEach(function(column, i) {
            let maxLength = 10;
            column.eachCell({ includeEmpty: true }, function(cell) {
                let cellValue = cell.value ? cell.value.toString() : "";
                maxLength = Math.max(maxLength, cellValue.length + 2);
            });
            column.width = maxLength;
        });

        workbook.xlsx.writeBuffer().then(function(buffer) {
            saveAs(new Blob([buffer], { type: "application/octet-stream" }), "ReporteDisponibilidadBodegas.xlsx");
        });
    }

    function exportarTablaDisponibilidadAPDF() {
        const doc = new window.jspdf.jsPDF({ orientation: 'portrait', unit: 'pt', format: 'A4' });

        doc.setFontSize(20);
        doc.setTextColor(11, 26, 48);
        doc.text("REPORTE DE DISPONIBILIDAD DE ARTÍCULOS POR BODEGA", doc.internal.pageSize.getWidth() / 2, 40, { align: "center" });

        doc.setFontSize(10);
        doc.setTextColor(127, 140, 141);
        doc.text("Filtro: " + ($('#id_bodega option:selected').val() ? $('#id_bodega option:selected').text() : 'Todas las Bodegas'), doc.internal.pageSize.getWidth() / 2, 55, { align: "center" });

        var tableDT = $('#tablaDisponibilidad').DataTable();
        var allData = tableDT.rows().data().toArray();
        const resultadosMapped = allData.map(row => ({
            campo1: row.id_producto,
            campo2: row.id_bodega,
            campo3: row.nombre_articulo,
            campo4: row.nombreBodega,
            campo5: parseFloat(row.cantidad).toFixed(2)
        }));

        const columns = [
            { header: 'ID Artículo', dataKey: 'campo1' },
            { header: 'ID Bodega', dataKey: 'campo2' },
            { header: 'Nombre Artículo', dataKey: 'campo3' },
            { header: 'Nombre Bodega', dataKey: 'campo4' },
            { header: 'Disponible', dataKey: 'campo5' }
        ];

        doc.autoTable({
            columns: columns,
            body: resultadosMapped,
            startY: 70,
            styles: {
                fontSize: 9,
                cellPadding: 4,
            },
            headStyles: {
                fillColor: [11, 26, 48],
                textColor: [255, 255, 255],
                fontStyle: 'bold'
            },
            alternateRowStyles: {
                fillColor: [245, 247, 251]
            },
            margin: { left: 40, right: 40 }
        });

        const fecha = new Date().toLocaleString();
        doc.setFontSize(8);
        doc.setTextColor(127, 140, 141);
        doc.text(`Generado el: ${fecha} | Usuario: {{ Auth::user()->nombre }}`, 40, doc.internal.pageSize.getHeight() - 20);
        doc.save('Reporte_Disponibilidad.pdf');
    }
</script>
@endsection
