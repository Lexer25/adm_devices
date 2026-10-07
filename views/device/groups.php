<?php
/**
 * device/groups.php — управление группами точек прохода.
 *
 * Команда (открыть 1 раз, закрыть навсегда, открыть навсегда, разблокировать)
 * выполняется для набора точек прохода, который задаётся:
 *   - перечнем точек прохода (флажки, ID_DEV);
 *   - группами устройств (таблица DEVGROUP, включая вложенные группы);
 *   - строкой с набором ID_DEV (например, «12, 15, 17»).
 *
 * Данные приходят из Controller_Devices::action_groups().
 */

if ( ! isset($errors))           $errors = array();
if ( ! isset($groups))           $groups = array();
if ( ! isset($access_points))    $access_points = array();
if ( ! isset($commands))         $commands = array();
if ( ! isset($selected_doors))   $selected_doors = array();
if ( ! isset($selected_groups))  $selected_groups = array();
if ( ! isset($selected_command)) $selected_command = 'opendoor';
if ( ! isset($id_dev_text))      $id_dev_text = '';
if ( ! isset($result))           $result = NULL;
if ( ! isset($csrf_token))       $csrf_token = Security::token();

$isAdmin = ! empty($is_admin);

// Список отмеченных точек прохода приводим к виду id_dev => id_dev.
$selected_doors = is_array($selected_doors) ? $selected_doors : array();
$checked_doors = array();
foreach ($selected_doors as $key => $value) {
    $id = (int) (is_array($value) ? $key : $value);
    if ($id > 0) $checked_doors[$id] = $id;
}

$selected_groups = is_array($selected_groups) ? $selected_groups : array();
$checked_groups = array();
foreach ($selected_groups as $key => $value) {
    $id = (int) (is_array($value) ? $key : $value);
    if ($id > 0) $checked_groups[$id] = $id;
}

// Если пришла неизвестная команда (например, после ошибки), показываем первую.
if ( ! isset($commands[$selected_command])) {
    reset($commands);
    $selected_command = key($commands);
}

/**
 * Название точки прохода: в БД название может быть пустым или 'NULL'.
 */
if ( ! function_exists('devices_point_name')) {
    function devices_point_name($row)
    {
        $name = isset($row['NAME']) ? $row['NAME'] : '';

        if ($name === NULL OR $name === '' OR $name === 'NULL') {
            $name = 'Точка прохода '.$row['ID_DEV'];
        }

        return $name;
    }
}

/**
 * Название контроллера.
 */
if ( ! function_exists('devices_controller_name')) {
    function devices_controller_name($row)
    {
        $name = isset($row['CONTROLLER_NAME']) ? $row['CONTROLLER_NAME'] : '';

        if ($name === NULL OR $name === '' OR $name === 'NULL') {
            $name = 'Контроллер '.$row['CONTROLLER_ID'];
        }

        return $name;
    }
}

// Перечень точек прохода, сгруппированный по контроллерам.
$points_by_controller = array();

foreach ($access_points as $point) {
    $controller_id = isset($point['CONTROLLER_ID']) ? $point['CONTROLLER_ID'] : 0;

    if ( ! isset($points_by_controller[$controller_id])) {
        $points_by_controller[$controller_id] = array(
            'controller_id'   => $controller_id,
            'controller_name' => devices_controller_name($point),
            'server_name'     => isset($point['SERVER_NAME']) ? $point['SERVER_NAME'] : '',
            'points'          => array(),
        );
    }

    $points_by_controller[$controller_id]['points'][] = $point;
}
?>

<style>
    .ap-command-list .ap-command {
        display: block;
        padding: 8px 12px;
        margin-bottom: 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
        cursor: pointer;
        font-weight: normal;
    }
    .ap-command-list .ap-command:hover { background-color: #f5f5f5; }
    .ap-command-list .ap-command input { margin-right: 8px; }
    .ap-command-list .ap-command.ap-command-danger { border-color: #f0ad4e; }
    .ap-group-table td, .ap-group-table th { vertical-align: middle !important; }
    .ap-group-table .ap-group-name { font-weight: 500; }
    .ap-group-table tr.ap-group-row:hover { background-color: #f9f9f9; }
    .ap-group-table tr.ap-group-empty .ap-group-name { color: #999; font-weight: normal; }
    .ap-points-block { max-height: 420px; overflow-y: auto; border: 1px solid #eee; border-radius: 4px; padding: 8px; }
    .ap-points-block .ap-controller { margin-bottom: 10px; }
    .ap-points-block .ap-controller-title { font-weight: bold; color: #337ab7; margin: 8px 0 4px 0; }
    .ap-points-block .ap-point { display: block; font-weight: normal; margin-bottom: 2px; }
    .ap-points-block .ap-point .ap-point-id { color: #999; font-size: 11px; }
    .ap-result-log { background: #f9f9f9; border: 1px solid #eee; border-radius: 4px; padding: 10px; font-size: 12px; }
</style>

<div class="panel panel-primary">
    <div class="panel-heading">
        <span class="glyphicon glyphicon-cog"></span>
        <?php echo __('access_points_control_title'); ?>
    </div>
    <div class="panel-body">

        <?php if ( ! $isAdmin): ?>
            <div class="alert alert-danger">
                <span class="glyphicon glyphicon-ban-circle"></span>
                <?php echo __('Управление точками прохода доступно только администраторам'); ?>
            </div>
        <?php endif; ?>

        <?php if ( ! empty($errors)): ?>
            <div class="alert alert-danger">
                <span class="glyphicon glyphicon-exclamation-sign"></span>
                <strong><?php echo __('Ошибка'); ?>:</strong>
                <ul style="margin: 5px 0 0 0;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ( ! empty($result)): ?>
            <?php
            $executed = isset($result['doors']) ? $result['doors'] : array();
            $log = isset($result['log']) ? (string) $result['log'] : '';
            ?>
            <div class="panel panel-success">
                <div class="panel-heading">
                    <span class="glyphicon glyphicon-ok-sign"></span>
                    <?php echo __('Команда выполнена'); ?>:
                    <b><?php echo htmlspecialchars($result['command_label'], ENT_QUOTES, 'UTF-8'); ?></b>
                    <?php if ( ! empty($result['time'])): ?>
                        <span class="text-muted">(<?php echo htmlspecialchars($result['time'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                    <?php endif; ?>
                    <span class="badge"><?php echo count($executed); ?></span>
                </div>
                <div class="panel-body">
                    <?php if ( ! empty($result['groups'])): ?>
                        <p>
                            <span class="glyphicon glyphicon-folder-open"></span>
                            <?php echo __('Группы'); ?>:
                            <?php
                            $group_labels = array();
                            foreach ($result['groups'] as $group_label) {
                                $group_labels[] = htmlspecialchars($group_label, ENT_QUOTES, 'UTF-8');
                            }
                            echo implode(', ', $group_labels);
                            ?>
                        </p>
                    <?php endif; ?>

                    <?php if ( ! empty($executed)): ?>
                        <table class="table table-condensed table-bordered" style="background: #fff;">
                            <thead>
                                <tr>
                                    <th><?php echo __('ID_DEV'); ?></th>
                                    <th><?php echo __('Точка прохода'); ?></th>
                                    <th><?php echo __('Считыватель'); ?></th>
                                    <th><?php echo __('Контроллер'); ?></th>
                                    <th><?php echo __('Транспортный сервер'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($executed as $point): ?>
                                    <tr>
                                        <td><?php echo (int) $point['ID_DEV']; ?></td>
                                        <td><?php echo htmlspecialchars(devices_point_name($point), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo (int) $point['ID_READER']; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars(devices_controller_name($point), ENT_QUOTES, 'UTF-8'); ?>
                                            <span class="text-muted">(ID: <?php echo (int) $point['CONTROLLER_ID']; ?>)</span>
                                        </td>
                                        <td><?php echo htmlspecialchars((string) $point['SERVER_NAME'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                    <?php if ($log !== ''): ?>
                        <a role="button" data-toggle="collapse" href="#apResultLog" aria-expanded="false" aria-controls="apResultLog">
                            <span class="glyphicon glyphicon-list-alt"></span>
                            <?php echo __('Подробный журнал выполнения команды'); ?>
                        </a>
                        <div class="collapse" id="apResultLog">
                            <div class="ap-result-log" style="margin-top: 8px;">
                                <?php
                                // В журнале допустимы только переводы строк и выделение
                                // из языковых шаблонов — остальные теги вырезаем.
                                echo strip_tags($log, '<br><b><strong>');
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo URL::site('devices/groups'); ?>" id="doorGroupForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="row">
                <div class="col-md-4">
                    <h4><span class="glyphicon glyphicon-flash"></span> 1. <?php echo __('Команда'); ?></h4>
                    <div class="ap-command-list">
                        <?php foreach ($commands as $value => $label): ?>
                            <?php
                            $is_danger = in_array($value, array('lockdoor', 'opendooralways'), TRUE);
                            ?>
                            <label class="ap-command <?php echo $is_danger ? 'ap-command-danger' : ''; ?>">
                                <input type="radio" name="command" value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo ($selected_command === $value) ? 'checked' : ''; ?>
                                    <?php echo $isAdmin ? '' : 'disabled'; ?>>
                                <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                <?php if ($is_danger): ?>
                                    <span class="label label-warning pull-right"><?php echo __('режим'); ?></span>
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="help-block">
                        <?php echo __('Команда применяется к точке прохода сразу после отправки формы.'); ?>
                    </p>
                </div>

                <div class="col-md-4">
                    <h4>
                        <span class="glyphicon glyphicon-folder-open"></span> 2. <?php echo __('Группы точек прохода'); ?>
                        <?php if ( ! empty($groups)): ?>
                            <span class="badge" title="<?php echo __('Всего групп устройств'); ?>"><?php echo count($groups); ?></span>
                        <?php endif; ?>
                    </h4>

                    <?php if (empty($groups)): ?>
                        <div class="alert alert-info" style="margin: 0;">
                            <?php echo __('Группы устройств не найдены. Создайте их в разделе «Группы устройств».'); ?>
                        </div>
                    <?php else: ?>
                        <?php
                        // Группы без точек прохода показываем (как в разделе «Группы
                        // устройств»), но выбрать их нельзя — команда им не адресована.
                        $selectable_groups = 0;
                        foreach ($groups as $group) {
                            if ((int) $group['total'] > 0) $selectable_groups++;
                        }
                        ?>
                        <div style="max-height: 420px; overflow-y: auto; border: 1px solid #eee; border-radius: 4px;">
                            <table class="table table-condensed ap-group-table" style="margin-bottom: 0;">
                                <thead>
                                    <tr>
                                        <th style="width: 30px;">
                                            <input type="checkbox" id="check_all_groups"
                                                   title="<?php echo __('Отметить все группы с точками прохода'); ?>"
                                                   <?php echo $isAdmin ? '' : 'disabled'; ?>>
                                        </th>
                                        <th><?php echo __('Группа'); ?></th>
                                        <th style="width: 120px;" class="text-right"><?php echo __('Точек прохода'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($groups as $group): ?>
                                        <?php
                                        $group_total = (int) $group['total'];
                                        $group_direct = (int) $group['direct'];
                                        $group_enabled = ($group_total > 0) AND $isAdmin;
                                        ?>
                                        <tr class="ap-group-row <?php echo ($group_total > 0) ? '' : 'ap-group-empty'; ?>"
                                            data-level="<?php echo (int) $group['level']; ?>">
                                            <td>
                                                <input type="checkbox" class="js-group-checkbox"
                                                       name="group_ids[]"
                                                       value="<?php echo (int) $group['id']; ?>"
                                                       data-level="<?php echo (int) $group['level']; ?>"
                                                    <?php echo isset($checked_groups[$group['id']]) ? 'checked' : ''; ?>
                                                    <?php echo $group_enabled ? '' : 'disabled'; ?>
                                                    <?php echo ($group_total > 0) ? '' : ' title="'.htmlspecialchars(__('В группе нет точек прохода'), ENT_QUOTES, 'UTF-8').'"'; ?>>
                                            </td>
                                            <td>
                                                <span style="display: inline-block; width: <?php echo ((int) $group['level'] * 18); ?>px;"></span>
                                                <?php if ($group['has_children']): ?>
                                                    <span class="glyphicon glyphicon-folder-open text-success"></span>
                                                <?php else: ?>
                                                    <span class="glyphicon glyphicon-folder-close text-muted"></span>
                                                <?php endif; ?>
                                                <span class="ap-group-name" title="<?php echo htmlspecialchars($group['path'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php echo htmlspecialchars($group['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                                <span class="text-muted" style="font-size: 11px;">(ID: <?php echo (int) $group['id']; ?>)</span>
                                            </td>
                                            <td class="text-right">
                                                <?php if ($group_total > 0): ?>
                                                    <span class="badge" title="<?php echo __('Всего точек прохода в группе с учётом вложенных групп'); ?>">
                                                        <?php echo $group_total; ?>
                                                    </span>
                                                    <?php if ($group_direct !== $group_total): ?>
                                                        <span class="text-muted" style="font-size: 11px;">
                                                            (<?php echo $group_direct; ?> <?php echo __('в самой группе'); ?>)
                                                        </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size: 11px;">
                                                        <?php echo __('нет точек прохода'); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="help-block">
                            <?php echo __('Флажок группы отмечает и все вложенные группы.'); ?>
                            <?php if ($selectable_groups < count($groups)): ?>
                                <br>
                                <?php echo __('Группы без точек прохода показаны, но выбрать их нельзя.'); ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <h4><span class="glyphicon glyphicon-list"></span> 3. <?php echo __('Набор точек прохода (ID_DEV)'); ?></h4>

                    <div class="form-group">
                        <input type="text" class="form-control" name="id_dev_text" id="id_dev_text"
                               value="<?php echo htmlspecialchars($id_dev_text, ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder="<?php echo __('Например: 12, 15, 17'); ?>"
                               <?php echo $isAdmin ? '' : 'disabled'; ?>>
                        <p class="help-block">
                            <?php echo __('Список ID_DEV через запятую, точку с запятой или пробел. Дополняет отмеченные ниже точки прохода.'); ?>
                        </p>
                    </div>

                    <label>
                        <input type="checkbox" id="check_all_points" <?php echo $isAdmin ? '' : 'disabled'; ?>>
                        <b><?php echo __('Отметить все точки прохода'); ?></b>
                    </label>

                    <div class="ap-points-block">
                        <?php if (empty($points_by_controller)): ?>
                            <div class="alert alert-info" style="margin: 0;">
                                <?php echo __('Точки прохода не найдены.'); ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($points_by_controller as $controller): ?>
                                <div class="ap-controller">
                                    <div class="ap-controller-title">
                                        <span class="glyphicon glyphicon-cog"></span>
                                        <?php echo htmlspecialchars($controller['controller_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        <span class="text-muted" style="font-weight: normal; font-size: 11px;">
                                            (ID: <?php echo (int) $controller['controller_id']; ?>
                                            <?php if ( ! empty($controller['server_name'])): ?>
                                                , <?php echo htmlspecialchars($controller['server_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            <?php endif; ?>)
                                        </span>
                                        <label style="font-weight: normal; font-size: 11px; margin-left: 5px;">
                                            <input type="checkbox" class="js-controller-all" <?php echo $isAdmin ? '' : 'disabled'; ?>>
                                            <?php echo __('все'); ?>
                                        </label>
                                    </div>
                                    <?php foreach ($controller['points'] as $point): ?>
                                        <label class="ap-point">
                                            <input type="checkbox" class="js-point-checkbox"
                                                   name="id_dev[]"
                                                   value="<?php echo (int) $point['ID_DEV']; ?>"
                                                <?php echo isset($checked_doors[$point['ID_DEV']]) ? 'checked' : ''; ?>
                                                <?php echo $isAdmin ? '' : 'disabled'; ?>>
                                            <?php echo htmlspecialchars(devices_point_name($point), ENT_QUOTES, 'UTF-8'); ?>
                                            <span class="ap-point-id">
                                                (ID_DEV: <?php echo (int) $point['ID_DEV']; ?>,
                                                <?php echo __('считыватель'); ?> <?php echo (int) $point['ID_READER']; ?>)
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <p class="help-block" style="margin-top: 10px;">
                        <?php echo __('Отмечено точек прохода'); ?>:
                        <span class="badge" id="ap_checked_count"><?php echo count($checked_doors); ?></span>
                    </p>
                </div>
            </div>

            <hr>

            <div class="text-center">
                <button type="submit" class="btn btn-warning btn-lg" <?php echo $isAdmin ? '' : 'disabled'; ?>>
                    <span class="glyphicon glyphicon-send"></span>
                    <?php echo __('Выполнить команду'); ?>
                </button>
                <div class="text-muted" style="margin-top: 8px;">
                    <?php echo __('Перед выполнением команды будет запрошено подтверждение.'); ?>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var commandLabels = <?php echo json_encode($commands); ?>;

    function updateCheckedCount() {
        $('#ap_checked_count').text($('.js-point-checkbox:checked').length);
    }

    // Флажок группы отмечает все вложенные группы
    // (список групп «плоский», вложенные строки идут ниже и имеют больший уровень).
    // Группы без точек прохода отключены — их не трогаем.
    $(document).on('change', '.js-group-checkbox', function () {
        var level = parseInt($(this).attr('data-level'), 10);
        var checked = $(this).prop('checked');
        var $row = $(this).closest('tr');

        $row.nextAll().each(function () {
            var nextLevel = parseInt($(this).attr('data-level'), 10);

            if (isNaN(nextLevel) || nextLevel <= level) {
                return false;
            }

            $(this).find('.js-group-checkbox:enabled').prop('checked', checked);
        });
    });

    $(document).on('change', '#check_all_groups', function () {
        $('.js-group-checkbox:enabled').prop('checked', $(this).prop('checked'));
    });

    $(document).on('change', '.js-controller-all', function () {
        $(this).closest('.ap-controller')
            .find('.js-point-checkbox').prop('checked', $(this).prop('checked'));
        updateCheckedCount();
    });

    $(document).on('change', '.js-point-checkbox', function () {
        updateCheckedCount();
    });

    $(document).on('change', '#check_all_points', function () {
        $('.js-point-checkbox').prop('checked', $(this).prop('checked'));
        updateCheckedCount();
    });

    $('#doorGroupForm').on('submit', function () {
        var command = $('input[name="command"]:checked').val();
        var label = commandLabels[command] || command;
        var points = $('.js-point-checkbox:checked').length;
        var groups = $('.js-group-checkbox:checked').length;
        var ids = $.trim($('#id_dev_text').val());

        var message = '<?php echo __('Выполнить команду'); ?> «' + label + '»?';

        if (points) {
            message += '\n<?php echo __('Отмечено точек прохода'); ?>: ' + points + '.';
        }
        if (groups) {
            message += '\n<?php echo __('Отмечено групп'); ?>: ' + groups + '.';
        }
        if (ids) {
            message += '\n<?php echo __('Набор ID_DEV'); ?>: ' + ids + '.';
        }
        if (command === 'lockdoor' || command === 'opendooralways') {
            message += '\n\n<?php echo __('ВНИМАНИЕ: команда изменяет режим точки прохода до следующей команды!'); ?>';
        }

        return confirm(message);
    });

    updateCheckedCount();
})();
</script>
