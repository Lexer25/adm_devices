<?php
// devices/views/device/matrix.php - с полным выводом названий
?>
<style>
    .matrix-container {
        overflow-x: auto;
        margin: 10px 0;
    }
    .matrix-table {
        border-collapse: collapse;
        font-size: 11px;
        table-layout: fixed;
    }
    .matrix-table th, .matrix-table td {
        border: 1px solid #ddd;
        padding: 2px 4px;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .matrix-table th {
        background-color: #f5f5f5;
        font-weight: bold;
        position: sticky;
        top: 0;
        z-index: 10;
        padding: 4px 6px;
        font-size: 10px;
    }
    /* Первая колонка с возможностью ресайза */
    .matrix-table .door-cell {
        background-color: #f9f9f9;
        font-weight: 500;
        text-align: left;
        min-width: 150px;
        width: 200px;
        max-width: 500px;
        padding: 2px 6px;
        font-size: 10px;
        position: relative;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .matrix-table .door-cell-fixed {
        position: sticky;
        left: 0;
        z-index: 5;
        background-color: #f9f9f9;
        border-right: 2px solid #ddd;
    }
    /* Ресайзер */
    .col-resizer {
        position: absolute;
        right: -3px;
        top: 0;
        width: 6px;
        height: 100%;
        cursor: col-resize;
        background: transparent;
        z-index: 10;
        user-select: none;
    }
    .col-resizer:hover {
        background: rgba(51, 122, 183, 0.3);
    }
    .col-resizer.active {
        background: rgba(51, 122, 183, 0.5);
    }
    /* Для остальных колонок контроллеров */
    .matrix-table .controller-header {
        background-color: #d9edf7;
        min-width: 80px;
        width: 100px;
        max-width: 200px;
        font-size: 10px;
        padding: 3px 4px;
        position: relative;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .matrix-table .controller-header .ctrl-id {
        font-size: 9px;
        color: #666;
        display: block;
        font-weight: normal;
    }
    .matrix-table .controller-header .ctrl-name {
        display: block;
        font-weight: bold;
        font-size: 10px;
        line-height: 1.2;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .matrix-table .checkbox-cell {
        width: 28px;
        min-width: 20px;
        background-color: #fff;
        padding: 2px;
    }
    .matrix-table .checkbox-cell.checked {
        background-color: #dff0d8;
    }
    .matrix-table .checkbox-cell input[type="checkbox"] {
        transform: scale(0.9);
        margin: 0;
        pointer-events: none;
        cursor: default;
    }
    .matrix-table .door-reader-badge {
        font-size: 8px;
        color: #999;
        margin-left: 3px;
    }
    .matrix-table .door-name-text {
        font-weight: 500;
        color: #5cb85c;
        font-size: 10px;
    }
    .matrix-stats {
        margin: 5px 0 10px 0;
        padding: 6px 12px;
        background: #f9f9f9;
        border-radius: 3px;
        font-size: 12px;
    }
    .matrix-stats .badge {
        font-size: 11px;
        padding: 3px 8px;
    }
    .scrollable-matrix {
        max-height: 500px;
        overflow: auto;
        border: 1px solid #ddd;
        border-radius: 3px;
        position: relative;
    }
    .badge-reader-small {
        display: inline-block;
        padding: 1px 4px;
        border-radius: 6px;
        font-size: 8px;
        font-weight: bold;
        margin-left: 3px;
        flex-shrink: 0;
    }
    .badge-reader-small-0 {
        background-color: #5bc0de;
        color: #fff;
    }
    .badge-reader-small-1 {
        background-color: #f0ad4e;
        color: #fff;
    }
    .panel-heading .badge {
        font-size: 11px;
        padding: 3px 8px;
    }
    .panel-heading .btn-xs {
        font-size: 11px;
        padding: 2px 10px;
    }
    .panel-heading .glyphicon {
        font-size: 12px;
    }
    .ctrl-header-compact {
        padding: 2px 3px !important;
        font-size: 9px !important;
        line-height: 1.1;
    }
    .ctrl-header-compact .ctrl-name {
        font-size: 9px;
    }
    .ctrl-header-compact .ctrl-id {
        font-size: 8px;
    }
    .table-bordered.matrix-table {
        margin-bottom: 0;
    }
    .table-bordered.matrix-table > thead > tr > th,
    .table-bordered.matrix-table > tbody > tr > td {
        border: 1px solid #ddd;
    }
    /* Сброс стилей для ресайзера в заголовке */
    .matrix-table thead th .col-resizer {
        top: 0;
        height: 100%;
    }
    /* Стили для ресайзера в заголовке */
    .matrix-table th .col-resizer {
        position: absolute;
        right: -3px;
        top: 0;
        width: 6px;
        height: 100%;
        cursor: col-resize;
        background: transparent;
        z-index: 20;
        user-select: none;
    }
    .matrix-table th .col-resizer:hover {
        background: rgba(51, 122, 183, 0.3);
    }
    .matrix-table th .col-resizer.active {
        background: rgba(51, 122, 183, 0.5);
    }
    /* Для отображения полного текста при наведении */
    .matrix-table .door-cell:hover,
    .matrix-table .controller-header:hover {
        overflow: visible;
        white-space: normal;
        word-wrap: break-word;
        z-index: 30;
        position: relative;
        background-color: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        padding: 4px 8px;
    }
    .matrix-table .door-cell:hover .door-name-text,
    .matrix-table .controller-header:hover .ctrl-name {
        white-space: normal;
        word-wrap: break-word;
    }
    .matrix-table .door-cell-fixed:hover {
        z-index: 35;
    }
</style>

<div class="panel panel-info" style="margin-bottom: 10px;">
    <div class="panel-heading" style="padding: 8px 15px;">
        <span class="glyphicon glyphicon-th" style="font-size: 13px;"></span>
        <span style="font-size: 13px; font-weight: bold;">Матрица доступа</span>
       

    </div>
    <div class="panel-body" style="padding: 8px 10px;">
        <?php if (empty($all_controllers) || empty($all_doors)): ?>
            <div class="alert alert-info" style="margin: 0; padding: 8px 12px; font-size: 12px;">
                <?php if (empty($all_controllers)): ?>
                    Нет контроллеров
                <?php else: ?>
                    Нет точек прохода (дверей)
                <?php endif; ?>
            </div>
        <?php else: ?>
        
        <!-- Статистика -->
        <div class="matrix-stats">
            <span class="badge badge-info"><?php echo count($all_controllers); ?></span> контр.
            <span class="badge badge-info"><?php echo count($all_doors); ?></span> дверей
            <?php 
                $total_checked = 0;
                foreach ($all_doors as $door) {
                    foreach ($all_controllers as $ctrl) {
                        if ($ctrl['id'] == $door['ctrl_id']) {
                            $total_checked++;
                        }
                    }
                }
            ?>
            <span style="color: #999; font-size: 11px; margin-left: 10px;">
                <span class="glyphicon glyphicon-ok-sign" style="color: #5cb85c;"></span> 
                зелёный = принадлежит
                <span style="margin-left: 15px;">
                    <span class="glyphicon glyphicon-resize-h" style="color: #337ab7;"></span> 
                    тяните за границу колонки для изменения ширины
                </span>
                <span style="margin-left: 15px;">
                    <span class="glyphicon glyphicon-info-sign" style="color: #337ab7;"></span> 
                    наведите на название для полного просмотра
                </span>
            </span>
        </div>
        
        <!-- Матрица -->
        <div class="scrollable-matrix">
            <table class="matrix-table table-bordered" id="matrixTable">
                <thead>
                    <tr>
                        <th id="colDoor" style="min-width: 150px; width: 200px; max-width: 500px; text-align: left; padding: 4px 6px; font-size: 10px; position: sticky; left: 0; z-index: 15; background-color: #f5f5f5; border-right: 2px solid #ddd;">
                            <span class="glyphicon glyphicon-log-in" style="font-size: 10px;"></span>
                            Точка прохода
                            <div class="col-resizer" data-target="colDoor"></div>
                        </th>
                        <?php foreach ($all_controllers as $index => $ctrl): ?>
                            <th class="controller-header ctrl-header-compact" id="colCtrl<?php echo $index; ?>" style="min-width: 80px; width: 100px; max-width: 200px; position: relative;">
                                <span class="ctrl-name" title="<?php echo __('Транспортный сервер \':ts\'', array(':ts'=>htmlspecialchars($ctrl['server_name']))); ?>">
                                    <?php echo htmlspecialchars($ctrl['name']); ?>
                                </span>
                                <span class="ctrl-id">ID:<?php echo $ctrl['id']; ?></span>
                                <div class="col-resizer" data-target="colCtrl<?php echo $index; ?>"></div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_doors as $door): ?>
                        <tr>
                            <td class="door-cell door-cell-fixed" style="font-size: 10px; padding: 2px 6px; position: relative;" title="<?php echo htmlspecialchars($door['name']); ?>">
                                <span class="door-name-text">
                                    <?php 
                                        $door_name = !empty($door['name']) && $door['name'] != 'NULL' 
                                            ? $door['name'] 
                                            : 'Дверь ' . $door['id'];
                                        echo htmlspecialchars($door_name);
                                    ?>
                                </span>
                                <span class="badge-reader-small badge-reader-small-<?php echo $door['reader']; ?>">
                                    R<?php echo $door['reader']; ?>
                                </span>
                                <span class="door-reader-badge">ID:<?php echo $door['id']; ?></span>
                            </td>
                            <?php foreach ($all_controllers as $ctrl): ?>
                                <?php 
								
                                    $checked = ($ctrl['id'] == $door['ctrl_id']) ? 'checked' : '';
                                    $checked_class = ($ctrl['id'] == $door['ctrl_id']) ? 'checked' : '';
                                ?>
                                <td class="checkbox-cell <?php echo $checked_class; ?>" style="padding: 1px 2px;">
                                    <input type="checkbox" 
                                           <?php echo $checked; ?>
                                           disabled
                                           style="transform: scale(0.8); margin: 0;">
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    var isResizing = false;
    var currentTarget = null;
    var startX = 0;
    var startWidth = 0;
    var targetCol = null;
    
    // Функция для получения элемента по ID
    function getElement(id) {
        return document.getElementById(id);
    }
    
    // Начало ресайза
    document.addEventListener('mousedown', function(e) {
        var resizer = e.target.closest('.col-resizer');
        if (!resizer) return;
        
        var targetId = resizer.getAttribute('data-target');
        if (!targetId) return;
        
        targetCol = getElement(targetId);
        if (!targetCol) return;
        
        isResizing = true;
        currentTarget = resizer;
        startX = e.pageX;
        startWidth = targetCol.offsetWidth;
        
        resizer.classList.add('active');
        document.body.style.cursor = 'col-resize';
        document.body.style.userSelect = 'none';
        
        e.preventDefault();
    });
    
    // Процесс ресайза
    document.addEventListener('mousemove', function(e) {
        if (!isResizing || !targetCol) return;
        
        var diff = e.pageX - startX;
        var newWidth = startWidth + diff;
        
        // Ограничения
        var minWidth = parseInt(targetCol.style.minWidth) || 80;
        var maxWidth = parseInt(targetCol.style.maxWidth) || 500;
        if (newWidth < minWidth) newWidth = minWidth;
        if (newWidth > maxWidth) newWidth = maxWidth;
        
        targetCol.style.width = newWidth + 'px';
        targetCol.style.minWidth = newWidth + 'px';
        
        // Обновляем ширину ячеек в колонке
        var colIndex = targetCol.cellIndex;
        var table = targetCol.closest('table');
        if (table) {
            var rows = table.querySelectorAll('tbody tr');
            rows.forEach(function(row) {
                var cell = row.cells[colIndex];
                if (cell) {
                    cell.style.width = newWidth + 'px';
                    cell.style.minWidth = newWidth + 'px';
                }
            });
        }
    });
    
    // Завершение ресайза
    document.addEventListener('mouseup', function(e) {
        if (isResizing) {
            isResizing = false;
            if (currentTarget) {
                currentTarget.classList.remove('active');
                currentTarget = null;
            }
            targetCol = null;
            document.body.style.cursor = '';
            document.body.style.userSelect = '';
        }
    });
    
    // Предотвращаем выделение текста при ресайзе
    document.addEventListener('selectstart', function(e) {
        if (isResizing) {
            e.preventDefault();
        }
    });
})();
</script>