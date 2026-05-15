document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('order-form');
    if (!form) return;

    // Функция пересчёта итоговой стоимости
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

    // Инициализация кнопок для дополнительных услуг
    function initServiceButtons() {
        const serviceBtns = document.querySelectorAll('.service-btn');
        serviceBtns.forEach(btn => {
            const checkbox = btn.querySelector('input[type="checkbox"]');
            if (!checkbox) return;

            // Устанавливаем начальное состояние класса active в соответствии с чекбоксом
            if (checkbox.checked) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }

            // Удаляем старый обработчик, чтобы не навешивать несколько раз
            btn.removeEventListener('click', btn._clickHandler);
            const clickHandler = (e) => {
                e.preventDefault();
                // Переключаем состояние чекбокса
                checkbox.checked = !checkbox.checked;
                // Меняем класс active у кнопки
                if (checkbox.checked) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
                // Искусственно вызываем событие change у чекбокса, чтобы сработал общий обработчик
                const changeEvent = new Event('change', { bubbles: true });
                checkbox.dispatchEvent(changeEvent);
                updateTotalPrice();
            };
            btn._clickHandler = clickHandler;
            btn.addEventListener('click', clickHandler);
        });
    }

    // Обработчики для базовых элементов формы
    document.getElementById('model_id').addEventListener('change', updateTotalPrice);
    document.getElementById('package_id').addEventListener('change', updateTotalPrice);
    // Обработчик для всех чекбоксов услуг (включая те, что внутри кнопок)
    document.querySelectorAll('input[name="services[]"]').forEach(cb => {
        cb.addEventListener('change', updateTotalPrice);
    });

    // Инициализируем кнопки
    initServiceButtons();

    // Первоначальный расчёт стоимости
    updateTotalPrice();

    // Обработка отправки формы (AJAX)
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

        // Очищаем предыдущие сообщения об ошибках
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
                    showCredentialsPopup(result.login, result.password);
                } else if (result.message === 'Order updated') {
                    document.getElementById('form-message').innerHTML = '<div class="success-message">Заказ обновлён!</div>';
                    document.getElementById('form-message').style.display = 'block';
                } else {
                    document.getElementById('form-message').innerHTML = '<div class="success-message">Новый заказ создан!</div>';
                    document.getElementById('form-message').style.display = 'block';
                    form.reset();
                    // Сбросить активные классы кнопок
                    document.querySelectorAll('.service-btn').forEach(btn => btn.classList.remove('active'));
                    updateTotalPrice();
                }
            } else {
                if (result.existing_user) {
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

    // Функция показа модального окна с логином и паролем
    function showCredentialsPopup(login, password) {
        const overlay = document.createElement('div');
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.backgroundColor = 'rgba(0,0,0,0.9)';
        overlay.style.backdropFilter = 'blur(5px)';
        overlay.style.zIndex = '10000';
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        
        overlay.innerHTML = `
            <div style="background: #1a1a1a; border-radius: 20px; padding: 2rem; max-width: 500px; text-align: center; border: 2px solid #00A0E3; box-shadow: 0 0 30px rgba(0,160,227,0.5);">
                <h3 style="color: #00A0E3; margin-bottom: 1rem;">🎉 Регистрация успешна!</h3>
                <p>Ваши данные для входа (сохраните их!):</p>
                <div style="background: #0d0d0d; padding: 1rem; border-radius: 12px; font-family: monospace; margin: 1rem 0;">
                    <strong>Логин:</strong> ${escapeHtml(login)}<br>
                    <strong>Пароль:</strong> ${escapeHtml(password)}
                </div>
                <p>Вы будете автоматически авторизованы после закрытия окна.</p>
                <button id="closeCredPopup" style="background: #00A0E3; border: none; padding: 0.8rem 2rem; border-radius: 30px; color: white; cursor: pointer; margin-top: 1rem;">Я сохранил(а) логин и пароль</button>
            </div>
        `;
        
        document.body.appendChild(overlay);
        
        const closeBtn = overlay.querySelector('#closeCredPopup');
        closeBtn.onclick = () => {
            if (confirm('Вы точно сохранили логин и пароль? Закрыть окно?')) {
                overlay.remove();
                location.reload();
            }
        };
    }

    function escapeHtml(str) {
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
});