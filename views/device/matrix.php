<style>
    .matrix-container {
        overflow-x: auto;
        margin: 20px 0;
    }
    .matrix-table {
        border-collapse: collapse;
        font-size: 13px;
    }
    .matrix-table th, .matrix-table td {
        border: 1px solid #ddd;
        padding: 6px 10px;
        text-align: center;
        white-space: nowrap;
    }
    .matrix-table th {
        background-color: #f5f5f5;
        font-weight: bold;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .matrix-table .door-cell {
        background-color: #f9f9f9;
        font-weight: 500;
        text-align: left;
        min-width: 200px;
        max-width: 300px;
        white-space: normal;
        word-wrap: break-word;
    }
    .matrix-table .controller-header {
        background-color: #d9edf7;
        min-width: 120px;
    }
    .matrix-table .controller-header .ctrl-id {
        font-size: 11px;
        color: #666;
        display: block;
    }
    .matrix-table .checkbox-cell {
        width: 40px;
        background-color: #fff;
    }
    .matrix-table .checkbox-cell.checked {
        background-color: #dff0d8;
    }
    .matrix-table .checkbox-cell input[type="checkbox"] {
        transform: scale(1.2);
        cursor: pointer;
    }
    .matrix-table .door-reader {
        font-size: 10px;
        color: #999;
        display: block;
    }
    .matrix-table .door-name-text {
        font-weight: 500;
        color: #5cb85c;
    }
    .matrix-stats {
        margin: 10px 0;
        padding: 10px;
        background: #f9f9f9;
        border-radius: 4px;
    }
    .matrix-actions {
        margin: 15px 0;
        padding: 15px;
        background: #f5f5f5;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    .matrix-actions .btn-group {
        margin-right: 10px;
    }
    .matrix-actions .btn {
        font-size: 12px;
    }
    .matrix-actions .checkbox-select-all {
        margin-right: 15px;
    }
    .status-badge {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 5px;
    }
    .scrollable-matrix {
        max-height: 600px;
        overflow: auto;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .badge-reader-small {
        display: inline-block;
        padding: 1px 6px;
        border-radius: 8px;
        font-size: 9px;
        font-weight: bold;
        margin-left: 5px;
    }
    .badge-reader-small-0 {
        background-color: #5bc0de;
        color: #fff;
    }
    .badge-reader-small-1 {
        background-color: #f0ad4e;
        color: #fff;
    }
</style>

<div class="panel panel-info">
    <div class="panel-heading">
        <span class="glyphicon glyphicon-th"></span>
        Матрица доступа (Контроллеры × Точки прохода)
        <span class="badge"><?php echo count($all_controllers); ?> контроллеров</span>
        <span class="badge"><?php echo count($all_doors); ?> точек прохода</span>
    </div>
    <div class="panel-body">
        <?php if (empty($all_controllers) || empty($all_doors)): ?>
            <div class="alert alert-info">
                <?php if (empty($all_controllers)): ?>
                    Нет контроллеров
                <?php else: ?>
                    Нет точек прохода (дверей)
                <?php endif; ?>
            </div>
        <?php else: ?>
        
        <!-- Действия -->
        <div class="matrix-actions">
            <div class="checkbox checkbox-select-all">
                <label>
                    <input type="checkbox" id="select-all" onchange="toggleAllCheckboxes(this)">
                    <strong>Выбрать все</strong>
                </label>
            </div>
            <div class="btn-group" role="group">
                <button class="btn btn-success btn-sm" onclick="selectByReader(0)">
                    <span class="glyphicon glyphicon-log-in"></span> Reader 0
                </button>
                <button class="btn btn-warning btn-sm" onclick="selectByReader(1)">
                    <span class="glyphicon glyphicon-log-out"></span> Reader 1
                </button>
            </div>
            <button class="btn btn-danger btn-sm" onclick="clearAll()">
                <span class="glyphicon glyphicon-remove"></span> Снять все
            </button>
            <span class="pull-right text-muted">
                <span class="glyphicon glyphicon-info-sign"></span>
                Кликните по ячейке для переключения
            </span>
        </div>
        
        <!-- Статистика -->
        <div class="matrix-stats">
            <span class="badge badge-info" id="total-controllers"><?php echo count($all_controllers); ?></span>
            Контроллеров
            <span class="badge badge-info" id="total-doors"><?php echo count($all_doors); ?></span>
            Точек прохода
            <span class="badge badge-success" id="checked-count">0</span>
            Выбрано
        </div>
        
        <!-- Матрица -->
        <div class="scrollable-matrix">
            <table class="matrix-table table-bordered">
                <thead>
                    <tr>
                        <th style="min-width: 200px; text-align: left; padding: 8px 12px;">
                            <span class="glyphicon glyphicon-log-in"></span>
                            Точки прохода / Контроллеры
                            <span class="glyphicon glyphicon-arrow-right" style="margin-left: 10px;"></span>
                        </th>
                        <?php foreach ($all_controllers as $ctrl): ?>
                            <th class="controller-header">
                                <div>
                                    <span class="glyphicon glyphicon-cog"></span>
                                    <?php echo htmlspecialchars(substr($ctrl['name'], 0, 15)); ?>
                                    <span class="ctrl-id">ID: <?php echo $ctrl['id']; ?></span>
                                    <?php if (strlen($ctrl['name']) > 15): ?>
                                        <span class="text-muted" title="<?php echo htmlspecialchars($ctrl['name']); ?>">…</span>
                                    <?php endif; ?>
                                </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_doors as $door): ?>
                        <tr>
                            <td class="door-cell">
                                <span class="glyphicon glyphicon-log-in door-icon text-success"></span>
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
                                <span class="door-reader">ID: <?php echo $door['id']; ?></span>
                            </td>
                            <?php foreach ($all_controllers as $ctrl): ?>
                                <?php 
                                    $checked = ($ctrl['id'] == $door['ctrl_id']) ? 'checked' : '';
                                    $checked_class = ($ctrl['id'] == $door['ctrl_id']) ? 'checked' : '';
                                    $checkbox_id = 'cb_' . $door['id'] . '_' . $ctrl['id'];
                                ?>
                                <td class="checkbox-cell <?php echo $checked_class; ?>" 
                                    onclick="toggleCheckbox('<?php echo $checkbox_id; ?>')">
                                    <input type="checkbox" 
                                           id="<?php echo $checkbox_id; ?>" 
                                           <?php echo $checked; ?>
                                           data-door-id="<?php echo $door['id']; ?>"
                                           data-ctrl-id="<?php echo $ctrl['id']; ?>"
                                           data-reader="<?php echo $door['reader']; ?>"
                                           onchange="updateStats()">
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
function toggleCheckbox(id) {
    var checkbox = document.getElementById(id);
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        var td = checkbox.closest('td');
        if (td) {
            td.classList.toggle('checked');
        }
        updateStats();
    }
}

function toggleAllCheckboxes(masterCheckbox) {
    var checkboxes = document.querySelectorAll('.checkbox-cell input[type="checkbox"]');
    checkboxes.forEach(function(cb) {
        cb.checked = masterCheckbox.checked;
        var td = cb.closest('td');
        if (td) {
            td.classList.toggle('checked', masterCheckbox.checked);
        }
    });
    updateStats();
}

function selectByReader(reader) {
    var checkboxes = document.querySelectorAll('.checkbox-cell input[type="checkbox"]');
    checkboxes.forEach(function(cb) {
        var readerVal = parseInt(cb.getAttribute('data-reader'));
        if (readerVal === reader) {
            cb.checked = true;
            var td = cb.closest('td');
            if (td) {
                td.classList.add('checked');
            }
        }
    });
    updateStats();
}

function clearAll() {
    var checkboxes = document.querySelectorAll('.checkbox-cell input[type="checkbox"]');
    checkboxes.forEach(function(cb) {
        cb.checked = false;
        var td = cb.closest('td');
        if (td) {
            td.classList.remove('checked');
        }
    });
    document.getElementById('select-all').checked = false;
    updateStats();
}

function updateStats() {
    var checkboxes = document.querySelectorAll('.checkbox-cell input[type="checkbox"]');
    var checked = 0;
    checkboxes.forEach(function(cb) {
        if (cb.checked) checked++;
    });
    document.getElementById('checked-count').textContent = checked;
}

$(document).ready(function() {
    updateStats();
});
</script>