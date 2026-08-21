<style>
    .controller-row {
        background-color: #f9f9f9;
    }
    .controller-row td {
        vertical-align: middle !important;
    }
    .badge-reader {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: bold;
    }
    .badge-reader-0 {
        background-color: #5bc0de;
        color: #fff;
    }
    .badge-reader-1 {
        background-color: #f0ad4e;
        color: #fff;
    }
    .controller-name {
        font-weight: bold;
        color: #337ab7;
        font-size: 15px;
    }
    .door-name {
        color: #5cb85c;
        font-weight: 500;
    }
    .door-meta {
        font-size: 11px;
        color: #666;
    }
    .label-devtype {
        background-color: #d9edf7;
        color: #31708f;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
    }
    .label-server {
        background-color: #fcf8e3;
        color: #8a6d3b;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
    }
    .ctrl-id-badge {
        font-size: 14px;
        padding: 5px 12px;
    }
    .door-item {
        padding: 4px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .door-item:last-child {
        border-bottom: none;
    }
    .action-icons {
        display: flex;
        gap: 8px;
        justify-content: center;
        align-items: center;
    }
    .action-icons a {
        text-decoration: none;
        font-size: 18px;
        transition: transform 0.2s;
        display: inline-block;
    }
    .action-icons a:hover:not(.disabled-icon) {
        transform: scale(1.2);
    }
    .action-icons .edit-icon {
        color: #337ab7;
    }
    .action-icons .edit-icon:hover:not(.disabled-icon) {
        color: #286090;
    }
    .action-icons .delete-icon {
        color: #d9534f;
    }
    .action-icons .delete-icon:hover:not(.disabled-icon) {
        color: #c9302c;
    }
    .action-icons .disabled-icon {
        opacity: 0.4;
        cursor: not-allowed;
        pointer-events: none;
    }
    .btn-disabled {
        opacity: 0.65;
        cursor: not-allowed;
        pointer-events: none;
    }
    .btn-disabled a {
        pointer-events: none;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработчик для всех ссылок с классом delete-icon
    document.querySelectorAll('.delete-icon').forEach(function(link) {
        link.addEventListener('click', function(e) {
            // Если ссылка disabled - пропускаем
            if (this.classList.contains('disabled-icon')) {
                e.preventDefault();
                return false;
            }
            
            var controllerName = this.getAttribute('data-controller-name') || 'этот контроллер';
            if (!confirm('Удалить контроллер "' + controllerName + '"?')) {
                e.preventDefault();
                return false;
            }
        });
    });
});
</script>

<div class="panel panel-primary">
    <div class="panel-heading">
        <span class="glyphicon glyphicon-list"></span>
        Таблица контроллеров и дверей
        <span class="badge"><?php echo count($controllers); ?> контроллеров</span>
        
        <!-- КНОПКА ДОБАВЛЕНИЯ - всегда показываем, но disabled если нет прав -->
        <a href="<?php echo $is_admin ? URL::site('devices/add') : '#'; ?>" 
           class="btn btn-success btn-xs pull-right <?php echo !$is_admin ? 'disabled' : ''; ?>" 
           style="color: #fff; margin-top: -3px; <?php echo !$is_admin ? 'opacity: 0.65; cursor: not-allowed; pointer-events: none;' : ''; ?>"
           onclick="<?php echo !$is_admin ? 'return false;' : ''; ?>">
            <span class="glyphicon glyphicon-plus"></span> Добавить контроллер
        </a>
    </div>
    <div class="panel-body table-responsive">
        <?php if (empty($controllers)): ?>
            <div class="alert alert-info">Контроллеры не найдены</div>
        <?php else: ?>
            <table class="table table-bordered table-hover">
                <thead>
                    <tr class="active">
                        <th style="width: 50px;">#</th>
                        <th style="width: 200px;">Контроллер</th>
                        <th style="width: 150px;">NetAddr</th>
                        <th style="width: 150px;">Тип</th>
                        <th style="width: 150px;">Сервер</th>
                        <th style="min-width: 250px;">Двери</th>
                        <th style="width: 100px; text-align: center;">Ред.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $index = 1; 
                  
                   foreach ($controllers as $ctrl_id => $data): ?>
                        <?php 
                            $controller = $data['controller'];
                            $doors = $data['doors'];
                            $door_count = count($doors);
                            $rowspan = max(1, $door_count);
                            
                            $ctrl_name = !empty($controller['NAME']) && $controller['NAME'] != 'NULL' 
                                ? $controller['NAME'] : 'Без названия';
                            $ctrl_netaddr = !empty($controller['NETADDR']) && $controller['NETADDR'] != 'NULL' 
                                ? $controller['NETADDR'] : '—';
                            $ctrl_devtype = !empty($controller['devtype_name']) && $controller['devtype_name'] != 'NULL' 
                                ? $controller['devtype_name'] : 'По умолчанию';
                            $ctrl_server = !empty($controller['server_name']) && $controller['server_name'] != 'NULL' 
                                ? $controller['server_name'] : '—';
                            
                            // Классы для иконок действий
                            $edit_class = $is_admin ? 'edit-icon' : 'edit-icon disabled-icon';
                            $delete_class = $is_admin ? 'delete-icon' : 'delete-icon disabled-icon';
                            $edit_href = $is_admin ? URL::site('devices/edit/' . $controller['ID_DEV']) : '#';
                            $delete_href = $is_admin ? URL::site('devices/delete/' . $controller['ID_DEV']) : '#';
                            $edit_onclick = $is_admin ? '' : 'return false;';
                        ?>
                        
                        <?php if ($door_count > 0): ?>
                            <!-- Первая строка -->
                            <tr class="controller-row">
                                <td rowspan="<?php echo $rowspan; ?>" style="vertical-align: middle; text-align: center;">
                                    <?php echo $index++; ?>
                                </td>
                                <td rowspan="<?php echo $rowspan; ?>" style="vertical-align: middle;">
                                    <span class="glyphicon glyphicon-cog text-primary"></span>
                                    <a href="<?php echo $edit_href; ?>" 
                                       class="<?php echo !$is_admin ? 'btn-disabled' : ''; ?>"
                                       style="font-weight: bold; color: #337ab7; text-decoration: none;"
                                       onmouseover="this.style.textDecoration='underline'" 
                                       onmouseout="this.style.textDecoration='none'"
                                       onclick="<?php echo $edit_onclick; ?>"
                                       <?php echo !$is_admin ? 'title="Требуются права администратора"' : ''; ?>>
                                        <?php echo htmlspecialchars($ctrl_name); ?>
                                    </a>
                                    <br>
                                    <span class="text-muted">ID: <?php echo $controller['ID_DEV']; ?></span>
                                    <br>
                                    <span class="text-muted">ID_CTRL: <?php echo $controller['ID_CTRL']; ?></span>
                                </td>
                                <td rowspan="<?php echo $rowspan; ?>" style="vertical-align: middle;">
                                    <code><?php echo htmlspecialchars($ctrl_netaddr); ?></code>
                                </td>
                                <td rowspan="<?php echo $rowspan; ?>" style="vertical-align: middle;">
                                    <span class="label-devtype"><?php echo htmlspecialchars($ctrl_devtype); ?></span>
                                </td>
                                <td rowspan="<?php echo $rowspan; ?>" style="vertical-align: middle;">
                                    <span class="label-server"><?php echo htmlspecialchars($ctrl_server); ?></span>
                                </td>
                                <td>
                                    <?php 
                                        $first_door = $doors[0];
                                        $door_name = !empty($first_door['NAME']) && $first_door['NAME'] != 'NULL' 
                                            ? $first_door['NAME'] : 'Дверь ' . $first_door['ID_DEV'];
                                    ?>
                                    <div class="door-item">
                                        <span class="glyphicon glyphicon-log-in text-success"></span>
                                        <span class="door-name"><?php echo htmlspecialchars($door_name); ?></span>
                                        <span class="badge-reader badge-reader-<?php echo $first_door['ID_READER']; ?>">
                                            Reader <?php echo $first_door['ID_READER']; ?>
                                        </span>
                                        <br>
                                        <span class="door-meta">
                                            ID: <?php echo $first_door['ID_DEV']; ?>
                                            <?php if (!empty($first_door['NETADDR']) && $first_door['NETADDR'] != 'NULL'): ?>
                                                | NetAddr: <?php echo htmlspecialchars($first_door['NETADDR']); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </td>
                                <td rowspan="<?php echo $rowspan; ?>" style="vertical-align: middle; text-align: center;">
                                    <div class="action-icons">
                                        <!-- Иконка редактирования -->
                                        <a href="<?php echo $edit_href; ?>" 
                                           class="<?php echo $edit_class; ?>" 
                                           title="Редактировать"
                                           onclick="<?php echo $edit_onclick; ?>"
                                           <?php echo !$is_admin ? 'style="cursor: not-allowed;"' : ''; ?>>
                                            <span class="glyphicon glyphicon-pencil"></span>
                                        </a>
                                        <!-- Иконка удаления -->
                                        <a href="<?php echo $delete_href; ?>" 
                                           class="<?php echo $delete_class; ?>" 
                                           title="Удалить"
                                           data-controller-name="<?php echo htmlspecialchars($ctrl_name, ENT_QUOTES); ?>"
                                           <?php echo !$is_admin ? 'style="cursor: not-allowed;"' : ''; ?>>
                                            <span class="glyphicon glyphicon-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Остальные двери -->
                            <?php for ($i = 1; $i < $door_count; $i++): ?>
                                <?php 
                                    $door = $doors[$i];
                                    $door_name = !empty($door['NAME']) && $door['NAME'] != 'NULL' 
                                        ? $door['NAME'] : 'Дверь ' . $door['ID_DEV'];
                                ?>
                                <tr class="controller-row">
                                    <td>
                                        <div class="door-item">
                                            <span class="glyphicon glyphicon-log-in text-success"></span>
                                            <span class="door-name"><?php echo htmlspecialchars($door_name); ?></span>
                                            <span class="badge-reader badge-reader-<?php echo $door['ID_READER']; ?>">
                                                Reader <?php echo $door['ID_READER']; ?>
                                            </span>
                                            <br>
                                            <span class="door-meta">
                                                ID: <?php echo $door['ID_DEV']; ?>
                                                <?php if (!empty($door['NETADDR']) && $door['NETADDR'] != 'NULL'): ?>
                                                    | NetAddr: <?php echo htmlspecialchars($door['NETADDR']); ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                            
                        <?php else: ?>
                            <!-- Контроллер без дверей -->
                            <tr class="controller-row">
                                <td><?php echo $index++; ?></td>
                                <td>
                                    <span class="glyphicon glyphicon-cog text-primary"></span>
                                    <a href="<?php echo $edit_href; ?>" 
                                       class="<?php echo !$is_admin ? 'btn-disabled' : ''; ?>"
                                       style="font-weight: bold; color: #337ab7; text-decoration: none;"
                                       onmouseover="this.style.textDecoration='underline'" 
                                       onmouseout="this.style.textDecoration='none'"
                                       onclick="<?php echo $edit_onclick; ?>"
                                       <?php echo !$is_admin ? 'title="Требуются права администратора"' : ''; ?>>
                                        <?php echo htmlspecialchars($ctrl_name); ?>
                                    </a>
                                    <br>
                                    <span class="text-muted">ID: <?php echo $controller['ID_DEV']; ?></span>
                                </td>
                                <td><code><?php echo htmlspecialchars($ctrl_netaddr); ?></code></td>
                                <td><span class="label-devtype"><?php echo htmlspecialchars($ctrl_devtype); ?></span></td>
                                <td><span class="label-server"><?php echo htmlspecialchars($ctrl_server); ?></span></td>
                                <td><span class="text-muted">Нет дверей</span></td>
                                <td style="text-align: center;">
                                    <div class="action-icons">
                                        <!-- Иконка редактирования -->
                                        <a href="<?php echo $edit_href; ?>" 
                                           class="<?php echo $edit_class; ?>" 
                                           title="Редактировать"
                                           onclick="<?php echo $edit_onclick; ?>"
                                           <?php echo !$is_admin ? 'style="cursor: not-allowed;"' : ''; ?>>
                                            <span class="glyphicon glyphicon-pencil"></span>
                                        </a>
                                        <!-- Иконка удаления -->
                                        <a href="<?php echo $delete_href; ?>" 
                                           class="<?php echo $delete_class; ?>" 
                                           title="Удалить"
                                           data-controller-name="<?php echo htmlspecialchars($ctrl_name, ENT_QUOTES); ?>"
                                           <?php echo !$is_admin ? 'style="cursor: not-allowed;"' : ''; ?>>
                                            <span class="glyphicon glyphicon-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="active">
                        <td colspan="7">
                            <span class="glyphicon glyphicon-stats"></span>
                            Всего: <strong><?php echo count($controllers); ?></strong> контроллеров
                            <?php 
                                $total_doors = 0;
                                foreach ($controllers as $data) {
                                    $total_doors += count($data['doors']);
                                }
                            ?>
                            , <strong><?php echo $total_doors; ?></strong> дверей
                        </td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>
</div>