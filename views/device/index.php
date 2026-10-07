<?php if (!empty($success_message)): ?>
    <div class="alert alert-success" style="margin: 10px 0; padding: 15px; border-radius: 4px;">
        <span class="glyphicon glyphicon-ok"></span>
        <strong>Успех!</strong> <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="close" onclick="this.parentElement.style.display='none'" style="float: right; background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
    </div>
<?php endif; ?>

<?php
// Проверяем и устанавливаем переменные по умолчанию
if (!isset($view_type)) $view_type = 'table';
if (!isset($controllers)) $controllers = array();
if (!isset($all_doors)) $all_doors = array();
if (!isset($all_controllers)) $all_controllers = array();
// $is_admin - доступна глобально через View::bind_global()
?>

<!-- Переключатель представлений -->
<div style="margin: 20px 0; padding: 15px; background: #f5f5f5; border: 2px solid #337ab7; border-radius: 4px; clear: both;">
    <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 10px;">
        <strong style="font-size: 16px; margin-right: 10px;">
            <span class="glyphicon glyphicon-eye-open"></span> Представление:
        </strong>
        
        <a href="?view=table" class="btn btn-<?php echo ($view_type == 'table') ? 'primary' : 'default'; ?>" style="font-size: 14px; padding: 8px 20px; text-decoration: none; display: inline-block;">
            <span class="glyphicon glyphicon-list"></span> Таблица
        </a>
        
        <a href="?view=tree" class="btn btn-<?php echo ($view_type == 'tree') ? 'primary' : 'default'; ?>" style="font-size: 14px; padding: 8px 20px; text-decoration: none; display: inline-block;">
            <span class="glyphicon glyphicon-tree-deciduous"></span> Дерево
        </a>
        
        <a href="?view=matrix" class="btn btn-<?php echo ($view_type == 'matrix') ? 'primary' : 'default'; ?>" style="font-size: 14px; padding: 8px 20px; text-decoration: none; display: inline-block;">
            <span class="glyphicon glyphicon-th"></span> Матрица
        </a>
        
        <span style="margin-left: auto; display: flex; gap: 10px; align-items: center;">
            <!-- УПРАВЛЕНИЕ ГРУППАМИ ТОЧЕК ПРОХОДА - команды для набора точек прохода -->
            <a href="<?php echo $is_admin ? URL::site('devices/groups') : '#'; ?>"
               class="btn btn-warning <?php echo !$is_admin ? 'disabled' : ''; ?>"
               style="font-size: 14px; padding: 8px 20px; text-decoration: none; display: inline-block; <?php echo !$is_admin ? 'opacity: 0.65; cursor: not-allowed; pointer-events: none;' : ''; ?>"
               onclick="<?php echo !$is_admin ? 'return false;' : ''; ?>">
                <span class="glyphicon glyphicon-cog"></span> Группы точек прохода
            </a>

            <!-- КНОПКА ДОБАВЛЕНИЯ - всегда показываем, но disabled если нет прав -->
            <a href="<?php echo $is_admin ? URL::site('devices/add') : '#'; ?>" 
               class="btn btn-success <?php echo !$is_admin ? 'disabled' : ''; ?>" 
               style="font-size: 14px; padding: 8px 20px; text-decoration: none; display: inline-block; <?php echo !$is_admin ? 'opacity: 0.65; cursor: not-allowed; pointer-events: none;' : ''; ?>"
               onclick="<?php echo !$is_admin ? 'return false;' : ''; ?>">
                <span class="glyphicon glyphicon-plus"></span> Добавить контроллер
            </a>
            
            <span style="color: #999; font-size: 14px;">
                <span class="glyphicon glyphicon-info-sign"></span>
                Всего: <?php echo count($controllers); ?> контроллеров
            </span>
        </span>
    </div>
</div>

<?php 
// Загружаем соответствующее представление
$view_file = 'device/' . $view_type;

if (Kohana::find_file('views', $view_file) !== FALSE) {
    echo View::factory($view_file, array(
        'controllers' => $controllers,
        'all_doors' => $all_doors,
        'all_controllers' => $all_controllers,
        'view_type' => $view_type,
        'title' => isset($title) ? $title : 'Устройства'
    ));
} else {
    echo '<div class="alert alert-danger">';
    echo 'Представление "' . htmlspecialchars($view_file) . '" не найдено!<br>';
    echo 'Проверьте файл: ' . APPPATH . 'views/' . $view_file . '.php';
    echo '</div>';
}
?>