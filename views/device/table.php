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
</style>

<div class="panel panel-primary">
    <div class="panel-heading">
	<div class="panel-heading">
    <span class="glyphicon glyphicon-list"></span>
    Таблица контроллеров и дверей
    <span class="badge"><?php echo count($controllers); ?> контроллеров</span>
    
    <!-- 🔥 КНОПКА ДОБАВЛЕНИЯ -->
    <a href="<?php echo URL::site('devices/add'); ?>" class="btn btn-success btn-xs pull-right" style="color: #fff; margin-top: -3px;">
        <span class="glyphicon glyphicon-plus"></span> Добавить контроллер
    </a>
</div>
        <span class="glyphicon glyphicon-list"></span>
        Таблица контроллеров и дверей
        <span class="badge"><?php echo count($controllers); ?> контроллеров</span>
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
                        <th style="width: 100px;">ID_CTRL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $index = 1; ?>
                    <?php foreach ($controllers as $ctrl_id => $data): ?>
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
                        ?>
                        
                        <?php if ($door_count > 0): ?>
                            <!-- Первая строка -->
                            <tr class="controller-row">
                                <td rowspan="<?php echo $rowspan; ?>" style="vertical-align: middle; text-align: center;">
                                    <?php echo $index++; ?>
                                </td>
                                <td rowspan="<?php echo $rowspan; ?>" style="vertical-align: middle;">
                                    <span class="glyphicon glyphicon-cog text-primary"></span>
                                    <span class="controller-name"><?php echo htmlspecialchars($ctrl_name); ?></span>
                                    <br>
                                    <span class="text-muted">ID: <?php echo $controller['ID_DEV']; ?></span>
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
                                    <span class="label label-primary ctrl-id-badge"><?php echo $ctrl_id; ?></span>
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
                                    <span class="controller-name"><?php echo htmlspecialchars($ctrl_name); ?></span>
                                    <br>
                                    <span class="text-muted">ID: <?php echo $controller['ID_DEV']; ?></span>
                                </td>
                                <td><code><?php echo htmlspecialchars($ctrl_netaddr); ?></code></td>
                                <td><span class="label-devtype"><?php echo htmlspecialchars($ctrl_devtype); ?></span></td>
                                <td><span class="label-server"><?php echo htmlspecialchars($ctrl_server); ?></span></td>
                                <td><span class="text-muted">Нет дверей</span></td>
                                <td style="text-align: center;">
                                    <span class="label label-primary ctrl-id-badge"><?php echo $ctrl_id; ?></span>
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