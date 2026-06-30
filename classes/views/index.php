<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style>
        .controller-row {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .door-row {
            background-color: #ffffff;
        }
        .door-row td:first-child {
            padding-left: 30px;
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
        .table-device {
            margin-top: 20px;
        }
        .table-device .glyphicon {
            margin-right: 5px;
        }
        .panel-heading .badge {
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>
                <span class="glyphicon glyphicon-tasks"></span>
                <?php echo $title; ?>
                <small>Kohana 3.3</small>
            </h1>
        </div>
        
        <div class="panel panel-primary">
            <div class="panel-heading">
                <span class="glyphicon glyphicon-list"></span>
                Контроллеры и их двери
                <span class="badge"><?php echo count($controllers); ?> контроллеров</span>
            </div>
            <div class="panel-body">
                <?php if (empty($controllers)): ?>
                    <div class="alert alert-info">
                        <span class="glyphicon glyphicon-info-sign"></span>
                        Контроллеры не найдены
                    </div>
                <?php else: ?>
                    <table class="table table-bordered table-hover table-device">
                        <thead>
                            <tr class="active">
                                <th style="width: 50px;">#</th>
                                <th style="width: 200px;">Контроллер</th>
                                <th style="width: 150px;">NetAddr</th>
                                <th style="width: 150px;">Тип</th>
                                <th style="width: 150px;">Сервер</th>
                                <th style="width: 250px;">Двери</th>
                                <th style="width: 120px;">ID_CTRL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $index = 1; ?>
                            <?php foreach ($controllers as $ctrl_id => $data): ?>
                                <?php 
                                    $controller = $data['controller'];
                                    $doors = $data['doors'];
                                    $rowspan = max(1, count($doors));
                                ?>
                                <tr class="controller-row info">
                                    <td rowspan="<?php echo $rowspan; ?>">
                                        <?php echo $index++; ?>
                                    </td>
                                    <td rowspan="<?php echo $rowspan; ?>">
                                        <span class="glyphicon glyphicon-cog text-primary"></span>
                                        <strong><?php echo htmlspecialchars($controller['NAME']); ?></strong>
                                        <br>
                                        <small class="text-muted">ID: <?php echo $controller['ID_DEV']; ?></small>
                                    </td>
                                    <td rowspan="<?php echo $rowspan; ?>">
                                        <?php echo htmlspecialchars($controller['NETADDR'] ?: '—'); ?>
                                    </td>
                                    <td rowspan="<?php echo $rowspan; ?>">
                                        <?php echo htmlspecialchars($controller['devtype_name'] ?: 'По умолчанию'); ?>
                                    </td>
                                    <td rowspan="<?php echo $rowspan; ?>">
                                        <?php echo htmlspecialchars($controller['server_name'] ?: '—'); ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($doors)): ?>
                                            <table class="table table-condensed table-striped" style="margin-bottom: 0;">
                                                <?php foreach ($doors as $door_index => $door): ?>
                                                    <?php if ($door_index > 0): ?>
                                                        <tr>
                                                    <?php endif; ?>
                                                    <td>
                                                        <span class="glyphicon glyphicon-log-in text-success"></span>
                                                        <?php echo htmlspecialchars($door['NAME']); ?>
                                                        <span class="badge-reader badge-reader-<?php echo $door['ID_READER']; ?>">
                                                            Reader <?php echo $door['ID_READER']; ?>
                                                        </span>
                                                        <small class="text-muted">
                                                            (ID: <?php echo $door['ID_DEV']; ?>)
                                                        </small>
                                                        <br>
                                                        <small class="text-muted">
                                                            NetAddr: <?php echo htmlspecialchars($door['NETADDR'] ?: '—'); ?>
                                                        </small>
                                                    </td>
                                                    <?php if ($door_index > 0): ?>
                                                        </tr>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </table>
                                        <?php else: ?>
                                            <span class="text-muted">Нет дверей</span>
                                        <?php endif; ?>
                                    </td>
                                    <td rowspan="<?php echo $rowspan; ?>">
                                        <span class="label label-primary"><?php echo $ctrl_id; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="active">
                                <td colspan="7">
                                    <span class="glyphicon glyphicon-stats"></span>
                                    Всего: <strong><?php echo count($controllers); ?></strong> контроллеров
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="panel panel-default">
            <div class="panel-heading">
                <span class="glyphicon glyphicon-info-sign"></span>
                Легенда
            </div>
            <div class="panel-body">
                <ul class="list-inline">
                    <li>
                        <span class="badge-reader badge-reader-0">Reader 0</span>
                        <span class="text-muted">— Первая дверь</span>
                    </li>
                    <li>
                        <span class="badge-reader badge-reader-1">Reader 1</span>
                        <span class="text-muted">— Вторая дверь</span>
                    </li>
                    <li>
                        <span class="glyphicon glyphicon-cog text-primary"></span>
                        <span class="text-muted">— Контроллер</span>
                    </li>
                    <li>
                        <span class="glyphicon glyphicon-log-in text-success"></span>
                        <span class="text-muted">— Дверь</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="panel panel-info">
            <div class="panel-heading">
                <span class="glyphicon glyphicon-question-sign"></span>
                SQL Запрос
            </div>
            <div class="panel-body">
                <pre style="background: #f7f7f7; border: none;">
SELECT 
    d1.ID_DEV as controller_id,
    d1.NAME as controller_name,
    d1.NETADDR as controller_netaddr,
    d1.ID_CTRL,
    d2.ID_DEV as door_id,
    d2.NAME as door_name,
    d2.NETADDR as door_netaddr,
    d2.ID_READER,
    s.NAME as server_name,
    dt.NAME as devtype_name
FROM DEVICE d1
LEFT JOIN DEVICE d2 ON d1.ID_CTRL = d2.ID_CTRL
LEFT JOIN SERVER s ON d1.ID_SERVER = s.ID_SERVER
LEFT JOIN DEVTYPE dt ON d1.ID_DEVTYPE = dt.ID_DEVTYPE
WHERE d1.ID_READER IS NULL 
  AND d2.ID_READER IN (0, 1)
ORDER BY d1.ID_CTRL ASC, d2.ID_READER ASC
                </pre>
            </div>
        </div>
    </div>
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>