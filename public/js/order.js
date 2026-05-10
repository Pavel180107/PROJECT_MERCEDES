document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('order-form');
    if (!form) return;

    function updateTotalPrice() {
        let total = 0;
        const modelSelect = document.getElementById('model_id');
        const modelOption = modelSelect.options[modelSelect.selectedIndex];
        total += parseFloat(modelOption.getAttribute('data-price') || 0);

        const packageSelect = document.getElementById('package_id');
        const packageOption = packageSelect.options[packageSelect.selectedIndex];
        total += parseFloat(packageOption.getAttribute('data-price') || 0);

        const checkboxes = document.querySelectorAll('input[name="services[]"]:checked');
        checkboxes.forEach(cb => {
            total += parseFloat(cb.getAttribute('data-price') || 0);
        });
        document.getElementById('total-price').innerText = total.toLocaleString('ru-RU') + ' ₽';
    }

    document.getElementById('model_id').addEventListener('change', updateTotalPrice);
    document.getElementById('package_id').addEventListener('change', updateTotalPrice);
    document.querySelectorAll('input[name="services[]"]').forEach(cb => cb.addEventListener('change', updateTotalPrice));
    updateTotalPrice();

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const data = {
            full_name: document.getElementById('full_name').value.trim(),
            email: document.getElementById('email').value.trim(),
            phone: document.getElementById('phone').value.trim(),
            consent: document.getElementById('consent').checked,
            model_id: parseInt(document.getElementById('model_id').value),
            package_id: document.getElementById('package_id').value ? parseInt(document.getElementById('package_id').value) : null,
            services: Array.from(document.querySelectorAll('input[name="services[]"]:checked')).map(cb => parseInt(cb.value)),
            total_price: parseFloat(document.getElementById('total-price').innerText.replace(/[^0-9.-]+/g, ''))
        };

        // Определяем, редактирование или создание (если пользователь авторизован)
        const isEdit = document.querySelector('.logged-in-info') !== null;

        try {
            const response = await fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (response.ok) {
                if (result.login && result.password) {
                    document.getElementById('form-message').innerHTML = `<div class="success-message">
                        Заказ создан!<br>Логин: ${result.login}<br>Пароль: ${result.password}
                    </div>`;
                } else {
                    document.getElementById('form-message').innerHTML = `<div class="success-message">${result.message}</div>`;
                }
                document.getElementById('form-message').style.display = 'block';
                // если нужно очистить форму для нового заказа (опционально)
                if (!isEdit) form.reset();
            } else {
                document.getElementById('form-message').innerHTML = `<div class="error-message">${result.error || 'Ошибка'}</div>`;
                document.getElementById('form-message').style.display = 'block';
            }
        } catch (err) {
            document.getElementById('form-message').innerHTML = '<div class="error-message">Ошибка сети</div>';
            document.getElementById('form-message').style.display = 'block';
        }
    });
});