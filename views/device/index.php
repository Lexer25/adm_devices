<?php
// Проверяем и устанавливаем переменные по умолчанию
if (!isset($view_type)) $view_type = 'table';
if (!isset($controllers)) $controllers = array();
if (!isset($all_doors)) $all_doors = array();
if (!isset($all_controllers)) $all_controllers = array();
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
        
        <span style="margin-left: auto; color: #999; font-size: 14px;">
            <span class="glyphicon glyphicon-info-sign"></span>
            Всего: <?php echo count($controllers); ?> контроллеров
        </span>
    </div>
</div>

<!-- ОТЛАДКА: показываем какое представление загружается -->
<div class="alert alert-info" style="margin: 10px 0;">
    <strong>Загружено представление:</strong> <?php echo $view_type; ?>
    <br>
    <strong>Файл:</strong> device/<?php echo $view_type; ?>.php
    <br>
    <strong>Найден:</strong> <?php echo (Kohana::find_file('views', 'device/' . $view_type) !== FALSE) ? 'ДА' : 'НЕТ'; ?>
</div>

<?php 
// Загружаем соответствующее представление в зависимости от view_type
$view_file = 'device/' . $view_type;

// Проверяем существование файла
if (Kohana::find_file('views', $view_file) !== FALSE) {
    // ✅ Правильный способ - используем View::factory для подгрузки
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