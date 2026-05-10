document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('order-form');
    if (!form) return;

    function updateTotalPrice() {
        let total = 0;
        const modelSelect = document.getElementById('model_id');
        total += parseFloat(modelSelect.options[modelSelect.selectedIndex].getAttribute('data-price') || 0);
        const packageSelect = document.getElementById('package_id');
        total += parseFloat(packageSelect.options[packageSelect.selectedIndex].getAttribute('data-price') || 0);
        document.querySelectorAll('input[name="services[]"]:checked').forEach(cb => {
            total += parseFloat(cb.getAttribute('data-price') || 0);
        });
        document.getElementById('total-price').innerText = total.toLocaleString('ru-RU') + ' ₽';
    }

    document.getElementById('model_id').addEventListener('change', updateTotalPrice);
    document.getElementById('package_id').addEventListener('change', updateTotalPrice);
    document.querySelectorAll('input[name="services[]"]').forEach(cb => cb.addEventListener('change', updateTotalPrice));
    updateTotalPrice();

    form.addEventListener('submit', async function(e) {
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
        if (!data.full_name || !data.email || !data.phone || !data.consent) {
            document.getElementById('form-message').innerHTML = '<div class="error-message">Заполните все поля</div>';
            document.getElementById('form-message').style.display = 'block';
            return;
        }
        const isEdit = document.querySelector('#user-logged-indicator') !== null;
        if (isEdit) data._method = 'PUT';
        try {
            const response = await fetch('/project/index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (response.ok) {
                if (result.login && result.password) {
                    document.getElementById('form-message').innerHTML = `<div class="success-message">Заказ создан!<br>Логин: ${result.login}<br>Пароль: ${result.password}</div>`;
                    form.reset();
                    updateTotalPrice();
                } else {
                    document.getElementById('form-message').innerHTML = '<div class="success-message">Заказ обновлён!</div>';
                }
            } else {
                document.getElementById('form-message').innerHTML = `<div class="error-message">${result.error || 'Ошибка'}</div>`;
            }
            document.getElementById('form-message').style.display = 'block';
        } catch (err) {
            document.getElementById('form-message').innerHTML = '<div class="error-message">Ошибка сети</div>';
            document.getElementById('form-message').style.display = 'block';
        }
    });
});