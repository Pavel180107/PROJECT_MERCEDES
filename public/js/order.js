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
        document.getElementById('total_price_hidden').value = total;
    }

    document.getElementById('model_id').addEventListener('change', updateTotalPrice);
    document.getElementById('package_id').addEventListener('change', updateTotalPrice);
    document.querySelectorAll('input[name="services[]"]').forEach(cb => cb.addEventListener('change', updateTotalPrice));
    updateTotalPrice();

    form.addEventListener('submit', async function(e) {
        if (window.fetch) {
            e.preventDefault();
        }

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

        // Очищаем предыдущие ошибки
        document.querySelectorAll('.field-error').forEach(el => el.textContent = '');
        document.getElementById('form-message').style.display = 'none';

        const isLoggedIn = document.getElementById('user-logged-indicator') !== null;
        if (isLoggedIn) data._method = 'PUT';

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
                        Заказ создан!<br>
                        Логин: <strong>${result.login}</strong><br>
                        Пароль: <strong>${result.password}</strong><br>
                        <small>Сохраните эти данные для входа.</small>
                    </div>`;
                    form.reset();
                    updateTotalPrice();
                } else {
                    document.getElementById('form-message').innerHTML = '<div class="success-message">' + (result.message || 'Заказ обновлён!') + '</div>';
                }
                document.getElementById('form-message').style.display = 'block';
            } else {
                if (result.errors) {
                    // Показываем ошибки под каждым полем
                    for (const [field, message] of Object.entries(result.errors)) {
                        const errorSpan = document.getElementById(`error-${field}`);
                        if (errorSpan) errorSpan.textContent = message;
                    }
                    document.getElementById('form-message').innerHTML = '<div class="error-message">Пожалуйста, исправьте ошибки в форме.</div>';
                } else {
                    document.getElementById('form-message').innerHTML = `<div class="error-message">${result.error || 'Ошибка сервера'}</div>`;
                }
                document.getElementById('form-message').style.display = 'block';
            }
        } catch (err) {
            document.getElementById('form-message').innerHTML = '<div class="error-message">Ошибка сети. Попробуйте позже.</div>';
            document.getElementById('form-message').style.display = 'block';
            console.error(err);
        }
    });
});