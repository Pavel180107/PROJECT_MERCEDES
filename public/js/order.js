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
        if (window.fetch) e.preventDefault();

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
                if (result.first_time) {
                    // Показываем модальное окно с логином/паролем
                    showCredentialsPopup(result.login, result.password);
                    location.reload(); // перезагружаем страницу, чтобы форма заменилась на авторизованную секцию
                } else if (result.message === 'Order updated') {
                    document.getElementById('form-message').innerHTML = '<div class="success-message">Заказ обновлён!</div>';
                    document.getElementById('form-message').style.display = 'block';
                } else {
                    document.getElementById('form-message').innerHTML = '<div class="success-message">Новый заказ создан!</div>';
                    document.getElementById('form-message').style.display = 'block';
                    form.reset();
                    updateTotalPrice();
                }
            } else {
                if (result.existing_user) {
                    // Пользователь уже существует – показываем предложение авторизоваться
                    if (confirm(result.message + '\nПерейти к авторизации?')) {
                        window.location.href = 'login.php?redirect=profile';
                    }
                } else if (result.errors) {
                    for (const [field, msg] of Object.entries(result.errors)) {
                        const errSpan = document.getElementById(`error-${field}`);
                        if (errSpan) errSpan.textContent = msg;
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

    function showCredentialsPopup(login, password) {
        // Создаём модальное окно
        const overlay = document.createElement('div');
        overlay.className = 'popup-overlay';
        overlay.innerHTML = `
            <div class="popup-content">
                <h3>🎉 Регистрация успешна!</h3>
                <p>Ваши данные для входа (сохраните их!):</p>
                <div class="credentials">
                    <strong>Логин:</strong> ${login}<br>
                    <strong>Пароль:</strong> ${password}
                </div>
                <p>Вы будете автоматически авторизованы после закрытия окна.</p>
                <button id="closeCredPopup">Я сохранил(а) логин и пароль</button>
            </div>
        `;
        document.body.appendChild(overlay);
        const closeBtn = overlay.querySelector('#closeCredPopup');
        closeBtn.onclick = () => {
            if (confirm('Вы точно сохранили логин и пароль? Закрыть окно?')) {
                overlay.remove();
            }
        };
    }
});