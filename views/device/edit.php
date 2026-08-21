<?php 
// $is_admin - доступна глобально через View::bind_global()
$form_disabled = !$is_admin;
?>
<style>
    .form-container {
        max-width: 700px;
        margin: 20px auto;
        padding: 20px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .form-container .form-group {
        margin-bottom: 20px;
    }
    .form-container label {
        font-weight: 600;
        display: block;
        margin-bottom: 5px;
    }
    .form-container .required:after {
        content: " *";
        color: red;
    }
    .form-container .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }
    .form-container .form-control:focus {
        border-color: #337ab7;
        outline: none;
        box-shadow: 0 0 5px rgba(51,122,183,0.3);
    }
    .form-container .form-control[disabled] {
        background-color: #f5f5f5;
        cursor: not-allowed;
        opacity: 0.7;
    }
    .form-container .help-block {
        font-size: 12px;
        color: #999;
        margin-top: 3px;
    }
    .form-container .has-error .form-control {
        border-color: #a94442;
    }
    .form-container .has-error .help-block {
        color: #a94442;
    }
    .form-container .alert-danger {
        background: #f2dede;
        color: #a94442;
        padding: 10px 15px;
        border-radius: 4px;
        margin-bottom: 15px;
        border: 1px solid #ebccd1;
    }
    .form-container .alert-success {
        background: #dff0d8;
        color: #3c763d;
        padding: 10px 15px;
        border-radius: 4px;
        margin-bottom: 15px;
        border: 1px solid #d6e9c6;
    }
    .form-actions {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #eee;
        display: flex;
        gap: 10px;
    }
    .btn {
        padding: 8px 25px;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    .btn-primary {
        background: #337ab7;
        color: #fff;
    }
    .btn-primary:hover:not([disabled]) {
        background: #286090;
    }
    .btn-primary[disabled] {
        opacity: 0.65;
        cursor: not-allowed;
    }
    .btn-default {
        background: #f5f5f5;
        color: #333;
        border: 1px solid #ddd;
    }
    .btn-default:hover:not([disabled]) {
        background: #e8e8e8;
    }
    .btn-default[disabled] {
        opacity: 0.65;
        cursor: not-allowed;
    }
    .btn-success {
        background: #5cb85c;
        color: #fff;
    }
    .page-header {
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #337ab7;
    }
    .page-header h1 {
        margin: 0;
        font-size: 24px;
        color: #337ab7;
    }
    .page-header small {
        font-size: 14px;
        color: #999;
        margin-left: 10px;
    }
    .info-note {
        background: #f9f9f9;
        padding: 12px 15px;
        border-radius: 4px;
        border-left: 4px solid #337ab7;
        margin-bottom: 20px;
        font-size: 13px;
        color: #555;
    }
    .info-note code {
        background: #eee;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 12px;
    }
    .doors-section {
        background: #f9f9f9;
        padding: 15px 20px;
        border-radius: 4px;
        border: 1px solid #e0e0e0;
        margin-bottom: 20px;
    }
    .doors-section .section-title {
        font-weight: 600;
        margin-bottom: 15px;
        color: #337ab7;
        font-size: 16px;
    }
    .door-group {
        background: #fff;
        padding: 15px;
        border-radius: 4px;
        border: 1px solid #e0e0e0;
        margin-bottom: 10px;
    }
    .door-group .door-title {
        font-weight: 600;
        color: #5cb85c;
        margin-bottom: 10px;
    }
    .door-group .badge-reader {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: bold;
        margin-left: 5px;
    }
    .door-group .badge-reader-0 {
        background-color: #5bc0de;
        color: #fff;
    }
    .door-group .badge-reader-1 {
        background-color: #f0ad4e;
        color: #fff;
    }
    .door-id-display {
        font-size: 12px;
        color: #999;
        margin-left: 10px;
    }
    .auth-warning {
        background: #fcf8e3;
        border: 1px solid #faebcc;
        color: #8a6d3b;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
</style>

<div class="form-container">
    <div class="page-header">
        <h1>
            <span class="glyphicon glyphicon-pencil"></span>
            Редактирование контроллера
            <small>ID_DEV: <?php echo $controller['ID_DEV']; ?></small>
        </h1>
    </div>
    
    <?php if (!$is_admin): ?>
        <div class="auth-warning">
            <span class="glyphicon glyphicon-lock"></span>
            <strong>Требуются права администратора</strong>
            <p style="margin: 5px 0 0 0;">Для редактирования контроллера необходимы права администратора.</p>
        </div>
    <?php endif; ?>
 
    <?php if (!empty($errors['general'])): ?>
        <div class="alert-danger"><?php echo htmlspecialchars($errors['general']); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert-success">Контроллер успешно обновлен!</div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <!-- Название контроллера -->
        <div class="form-group <?php echo isset($errors['name']) ? 'has-error' : ''; ?>">
            <label for="name" class="required">Название контроллера</label>
            <input type="text" 
                   id="name" 
                   name="name" 
                   class="form-control" 
                   placeholder="Введите название контроллера"
                   value="<?php echo isset($post_data['name']) ? htmlspecialchars($post_data['name']) : (isset($controller['NAME']) ? htmlspecialchars($controller['NAME']) : ''); ?>"
                   <?php echo $form_disabled ? 'disabled' : ''; ?>
                   required>
            <?php if (isset($errors['name'])): ?>
                <div class="help-block"><?php echo htmlspecialchars($errors['name']); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- NetAddr -->
        <div class="form-group">
            <label for="netaddr">NetAddr (IP адрес)</label>
            <input type="text" 
                   id="netaddr" 
                   name="netaddr" 
                   class="form-control" 
                   placeholder="Например: 192.168.1.100"
                   value="<?php echo isset($post_data['netaddr']) ? htmlspecialchars($post_data['netaddr']) : (isset($controller['NETADDR']) ? htmlspecialchars($controller['NETADDR']) : ''); ?>"
                   <?php echo $form_disabled ? 'disabled' : ''; ?>>
            <div class="help-block">IP-адрес контроллера (необязательно)</div>
        </div>
        
        <!-- Сервер -->
        <div class="form-group">
            <label for="id_server">Сервер</label>
            <select id="id_server" name="id_server" class="form-control" <?php echo $form_disabled ? 'disabled' : ''; ?>>
                <option value="">-- Выберите сервер --</option>
                <?php foreach ($servers as $server): ?>
                    <option value="<?php echo $server['ID_SERVER']; ?>"
                        <?php 
                            $selected_server = isset($post_data['id_server']) ? $post_data['id_server'] : (isset($controller['ID_SERVER']) ? $controller['ID_SERVER'] : '');
                            echo ($selected_server == $server['ID_SERVER']) ? 'selected' : '';
                        ?>>
                        <?php echo htmlspecialchars($server['NAME']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="help-block">Сервер, к которому подключен контроллер (необязательно)</div>
        </div>
        
        <!-- Тип устройства -->
        <div class="form-group">
            <label for="id_devtype">Тип устройства</label>
            <select id="id_devtype" name="id_devtype" class="form-control" <?php echo $form_disabled ? 'disabled' : ''; ?>>
                <option value="">-- Выберите тип --</option>
                <?php foreach ($devtypes as $devtype): ?>
                    <option value="<?php echo $devtype['ID_DEVTYPE']; ?>"
                        <?php 
                            $selected_devtype = isset($post_data['id_devtype']) ? $post_data['id_devtype'] : (isset($controller['ID_DEVTYPE']) ? $controller['ID_DEVTYPE'] : '');
                            echo ($selected_devtype == $devtype['ID_DEVTYPE']) ? 'selected' : '';
                        ?>>
                        <?php echo htmlspecialchars($devtype['NAME']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="help-block">Тип устройства (необязательно)</div>
        </div>
        
        <!-- Точки прохода (двери) -->
        <div class="doors-section">
            <div class="section-title">
                <span class="glyphicon glyphicon-log-in"></span>
                Точки прохода (двери)
                <span class="text-muted" style="font-weight: normal; font-size: 13px;">— ID_READER = 0 и 1</span>
            </div>
            
            <?php 
                // Получаем двери из массива
                $door0 = null;
                $door1 = null;
                foreach ($doors as $door) {
                    if ($door['ID_READER'] == 0) {
                        $door0 = $door;
                    } elseif ($door['ID_READER'] == 1) {
                        $door1 = $door;
                    }
                }
            ?>
            
            <!-- Door 1 (Reader 0) -->
            <div class="door-group">
                <div class="door-title">
                    <span class="glyphicon glyphicon-log-in text-success"></span>
                    Точка прохода 1
                    <span class="badge-reader badge-reader-0">Reader 0</span>
                    <span class="door-id-display">ID_DEV: <?php echo $door0 ? $door0['ID_DEV'] : '—'; ?></span>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="door0_name">Название точки прохода 1</label>
                    <input type="text" 
                           id="door0_name" 
                           name="door0_name" 
                           class="form-control" 
                           placeholder="Например: Входная дверь"
                           value="<?php 
                               if (isset($post_data['door0_name'])) {
                                   echo htmlspecialchars($post_data['door0_name']);
                               } elseif ($door0 && isset($door0['NAME'])) {
                                   echo htmlspecialchars($door0['NAME']);
                               }
                           ?>"
                           <?php echo $form_disabled ? 'disabled' : ''; ?>>
                    <input type="hidden" name="door0_id" value="<?php echo $door0 ? $door0['ID_DEV'] : ''; ?>">
                </div>
            </div>
            
            <!-- Door 2 (Reader 1) -->
            <div class="door-group">
                <div class="door-title">
                    <span class="glyphicon glyphicon-log-out text-warning"></span>
                    Точка прохода 2
                    <span class="badge-reader badge-reader-1">Reader 1</span>
                    <span class="door-id-display">ID_DEV: <?php echo $door1 ? $door1['ID_DEV'] : '—'; ?></span>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="door1_name">Название точки прохода 2</label>
                    <input type="text" 
                           id="door1_name" 
                           name="door1_name" 
                           class="form-control" 
                           placeholder="Например: Выходная дверь"
                           value="<?php 
                               if (isset($post_data['door1_name'])) {
                                   echo htmlspecialchars($post_data['door1_name']);
                               } elseif ($door1 && isset($door1['NAME'])) {
                                   echo htmlspecialchars($door1['NAME']);
                               }
                           ?>"
                           <?php echo $form_disabled ? 'disabled' : ''; ?>>
                    <input type="hidden" name="door1_id" value="<?php echo $door1 ? $door1['ID_DEV'] : ''; ?>">
                </div>
            </div>
            
            <div class="help-block" style="margin-top: 10px;">
                <span class="glyphicon glyphicon-info-sign"></span>
                Изменение названий дверей обновит соответствующие записи в таблице DEVICE.
            </div>
        </div>
        
        <div class="info-note">
            <span class="glyphicon glyphicon-info-sign"></span>
            <strong>Информация:</strong>
            <ul style="margin: 5px 0 0 20px; padding: 0;">
                <li>ID_CTRL: <code><?php echo $controller['ID_CTRL']; ?></code></li>
                <li>ID_DEV: <code><?php echo $controller['ID_DEV']; ?></code></li>
                <li>ID_READER контроллера = <code>NULL</code></li>
                <li>Точки прохода имеют ID_READER = <code>0</code> и <code>1</code></li>
            </ul>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary" <?php echo $form_disabled ? 'disabled' : ''; ?>>
                <span class="glyphicon glyphicon-save"></span> Сохранить
            </button>
            <a href="<?php echo URL::site('devices'); ?>" class="btn btn-default">
                <span class="glyphicon glyphicon-arrow-left"></span> Отмена
            </a>
        </div>
    </form>
</div>