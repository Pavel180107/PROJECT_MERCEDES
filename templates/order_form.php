<form id="order-form" action="order_submit.php" method="post">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    
    <div class="form-group">
        <label>ФИО</label>
        <input type="text" name="full_name" id="full_name" value="<?= htmlspecialchars($formData['full_name']) ?>" required>
        <div class="field-error" id="error-full_name"></div>
    </div>
    <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($formData['email']) ?>" required>
        <div class="field-error" id="error-email"></div>
    </div>
    <div class="form-group">
        <label>Телефон</label>
        <input type="tel" name="phone" id="phone" value="<?= htmlspecialchars($formData['phone']) ?>" placeholder="+7XXXXXXXXXX" required>
        <div class="field-error" id="error-phone"></div>
    </div>
    <div class="form-group checkbox">
        <label>
            <input type="checkbox" name="consent" id="consent" value="1" <?= $formData['consent'] ? 'checked' : '' ?> required>
            Я даю согласие на обработку персональных данных
        </label>
        <div class="field-error" id="error-consent"></div>
    </div>

    <div class="form-group">
        <label>Модель</label>
        <select name="model_id" id="model_id">
            <?php foreach ($models as $m): ?>
                <option value="<?= $m['id'] ?>" data-price="<?= $m['base_price'] ?>" <?= ($formData['model_id'] == $m['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['name']) ?> – <?= number_format($m['base_price'], 0, '', ' ') ?> ₽
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Пакет опций</label>
        <select name="package_id" id="package_id">
            <option value="">Без пакета</option>
            <?php foreach ($packages as $p): ?>
                <option value="<?= $p['id'] ?>" data-price="<?= $p['price_add'] ?>" <?= ($formData['package_id'] == $p['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['name']) ?> (+<?= number_format($p['price_add'], 0, '', ' ') ?> ₽)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Дополнительные услуги</label>
        <div class="services-group">
            <?php foreach ($services as $s): ?>
                <label class="service-check">
                    <input type="checkbox" name="services[]" value="<?= $s['id'] ?>" data-price="<?= $s['price_add'] ?>" <?= in_array($s['id'], $formData['services']) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($s['name']) ?> (+<?= number_format($s['price_add'], 0, '', ' ') ?> ₽)
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="calculator-result">
        <h3>Итоговая стоимость</h3>
        <div class="total-price" id="total-price">0 ₽</div>
    </div>

    <button type="submit" class="btn"><?= $isLoggedIn ? 'Обновить заказ' : 'Оформить заказ' ?></button>
    <div id="form-message" class="form-message" style="display:none;"></div>
</form>

<?php if ($isLoggedIn): ?>
    <div class="logged-in-info" style="text-align:center; margin-top:1rem;">
        Вы авторизованы как <?= htmlspecialchars($_SESSION['auto_user_login']) ?>
        <a href="logout.php">Выйти</a>
    </div>
<?php endif; ?>