<style>
    .tree {
        margin: 20px 0;
    }
    .tree ul {
        padding-left: 30px;
        list-style: none;
    }
    .tree li {
        position: relative;
        padding: 5px 0;
        border-left: 2px solid #ddd;
    }
    .tree li:before {
        content: "";
        position: absolute;
        top: 15px;
        left: -2px;
        width: 20px;
        height: 0;
        border-top: 2px solid #ddd;
    }
    .tree li:last-child {
        border-left: 2px solid transparent;
    }
    .tree li:last-child:before {
        border-left: 2px solid #ddd;
    }
    .tree .server-node {
        background: #f5f5f5;
        padding: 10px 15px;
        border-radius: 4px;
        margin: 5px 0;
        font-weight: bold;
        color: #337ab7;
        border-left: 4px solid #337ab7;
    }
    .tree .controller-node {
        background: #f9f9f9;
        padding: 8px 12px;
        border-radius: 4px;
        margin: 3px 0;
        border-left: 4px solid #5bc0de;
        cursor: pointer;
        transition: all 0.3s;
    }
    .tree .controller-node:hover {
        background: #e8f4f8;
    }
    .tree .door-node {
        padding: 5px 12px;
        margin: 2px 0;
        border-left: 4px solid #5cb85c;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .tree .door-node .badge-reader {
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: bold;
    }
    .tree .door-node .badge-reader-0 {
        background-color: #5bc0de;
        color: #fff;
    }
    .tree .door-node .badge-reader-1 {
        background-color: #f0ad4e;
        color: #fff;
    }
    .tree .door-node .door-meta {
        font-size: 11px;
        color: #999;
    }
    .tree .controller-count {
        font-size: 12px;
        color: #999;
        margin-left: 10px;
    }
    .tree-toggle {
        cursor: pointer;
        user-select: none;
    }
    .tree-toggle .glyphicon {
        transition: transform 0.3s;
    }
    .tree-toggle.collapsed .glyphicon {
        transform: rotate(-90deg);
    }
    .tree-children {
        overflow: hidden;
        transition: max-height 0.3s ease;
    }
    .tree-children.hidden {
        max-height: 0 !important;
    }
    .badge-door-count {
        background-color: #5cb85c;
        color: #fff;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
        margin-left: 8px;
    }
</style>

<div class="panel panel-success">
    <div class="panel-heading">
        <span class="glyphicon glyphicon-tree-deciduous"></span>
        Дерево устройств
        <span class="badge"><?php echo count($controllers); ?> контроллеров</span>
    </div>
    <div class="panel-body">
        <?php if (empty($controllers)): ?>
            <div class="alert alert-info">Контроллеры не найдены</div>
        <?php else: ?>
            <div class="tree">
                <?php 
                    // Группируем контроллеры по серверам
                    $servers = array();
                    foreach ($controllers as $ctrl_id => $data) {
                        $server_name = !empty($data['controller']['server_name']) 
                            ? $data['controller']['server_name'] 
                            : 'Без сервера';
                        if (!isset($servers[$server_name])) {
                            $servers[$server_name] = array();
                        }
                        $servers[$server_name][$ctrl_id] = $data;
                    }
                    
                    ksort($servers);
                ?>
                
                <?php foreach ($servers as $server_name => $server_controllers): ?>
                    <div class="server-node tree-toggle" onclick="toggleTree(this)">
                        <span class="glyphicon glyphicon-server"></span>
                        <span class="glyphicon glyphicon-chevron-down"></span>
                        <?php echo htmlspecialchars($server_name); ?>
                        <span class="controller-count">(<?php echo count($server_controllers); ?> контроллеров)</span>
                    </div>
                    <ul class="tree-children">
                        <?php foreach ($server_controllers as $ctrl_id => $data): ?>
                            <?php 
                                $controller = $data['controller'];
                                $doors = $data['doors'];
                                $ctrl_name = !empty($controller['NAME']) && $controller['NAME'] != 'NULL' 
                                    ? $controller['NAME'] : 'Без названия';
                            ?>
                            <li>
                                <div class="controller-node tree-toggle" onclick="toggleTree(this)">
                                    <span class="glyphicon glyphicon-cog text-primary"></span>
                                    <span class="glyphicon glyphicon-chevron-down"></span>
                                    <strong><?php echo htmlspecialchars($ctrl_name); ?></strong>
                                    <span class="text-muted">(ID: <?php echo $controller['ID_DEV']; ?>)</span>
                                    <?php if (!empty($controller['NETADDR']) && $controller['NETADDR'] != 'NULL'): ?>
                                        <span class="label label-info">NetAddr: <?php echo htmlspecialchars($controller['NETADDR']); ?></span>
                                    <?php endif; ?>
                                    <span class="badge-door-count"><?php echo count($doors); ?> дверей</span>
                                </div>
                                <ul class="tree-children">
                                    <?php if (!empty($doors)): ?>
                                        <?php foreach ($doors as $door): ?>
                                            <?php 
                                                $door_name = !empty($door['NAME']) && $door['NAME'] != 'NULL' 
                                                    ? $door['NAME'] : 'Дверь ' . $door['ID_DEV'];
                                            ?>
                                            <li>
                                                <div class="door-node">
                                                    <span class="glyphicon glyphicon-log-in text-success"></span>
                                                    <span><?php echo htmlspecialchars($door_name); ?></span>
                                                    <span class="badge-reader badge-reader-<?php echo $door['ID_READER']; ?>">
                                                        Reader <?php echo $door['ID_READER']; ?>
                                                    </span>
                                                    <span class="door-meta">
                                                        ID: <?php echo $door['ID_DEV']; ?>
                                                        <?php if (!empty($door['NETADDR']) && $door['NETADDR'] != 'NULL'): ?>
                                                            | NetAddr: <?php echo htmlspecialchars($door['NETADDR']); ?>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li>
                                            <div class="door-node text-muted">
                                                <span class="glyphicon glyphicon-minus"></span>
                                                Нет дверей
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleTree(element) {
    var children = element.nextElementSibling;
    while (children && children.tagName != 'UL') {
        children = children.nextElementSibling;
    }
    
    if (children) {
        children.classList.toggle('hidden');
        var chevron = element.querySelector('.glyphicon-chevron-down, .glyphicon-chevron-right');
        if (chevron) {
            chevron.className = children.classList.contains('hidden') 
                ? 'glyphicon glyphicon-chevron-right' 
                : 'glyphicon glyphicon-chevron-down';
        }
    }
}

$(document).ready(function() {
    $('.tree .tree-children').addClass('hidden');
    $('.server-node .tree-children').removeClass('hidden');
    $('.server-node .glyphicon-chevron-right').removeClass('glyphicon-chevron-right').addClass('glyphicon-chevron-down');
});
</script>