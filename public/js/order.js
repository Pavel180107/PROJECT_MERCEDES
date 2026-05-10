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

    // Функция клиентской валидации
    function validateFields(full_name, email, phone, consent) {
        const errors = {};
        const nameRegex = /^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u;
        if (!full_name.trim()) {
            errors.full_name = 'ФИО обязательно для заполнения.';
        } else if (!nameRegex.test(full_name)) {
            errors.full_name = 'ФИО должно содержать только буквы (русские или латинские), пробелы и дефис. Пример: Иванов Иван Иванович.';
        } else if (full_name.length > 150) {
            errors.full_name = 'ФИО не должно превышать 150 символов.';
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email.trim()) {
            errors.email = 'Email обязателен для заполнения.';
        } else if (!emailRegex.test(email)) {
            errors.email = 'Введите корректный email, например: name@example.com.';
        }

        const phoneRegex = /^[\d\s\-\+\(\)]{6,12}$/;
        if (!phone.trim()) {
            errors.phone = 'Телефон обязателен для заполнения.';
        } else if (!phoneRegex.test(phone)) {
            errors.phone = 'Телефон должен содержать от 6 до 12 символов, разрешены цифры, +, -, (, ), пробел. Пример: +7 (912) 345-67-89.';
        }

        if (!consent) {
            errors.consent = 'Необходимо подтвердить согласие на обработку персональных данных.';
        }

        return errors;
    }

    function clearErrors() {
        document.querySelectorAll('.field-error').forEach(el => el.textContent = '');
        document.querySelectorAll('.error-border').forEach(el => el.classList.remove('error-border'));
    }

    function displayErrors(errors) {
        for (const [field, message] of Object.entries(errors)) {
            const errorDiv = document.getElementById(`error-${field}`);
            if (errorDiv) errorDiv.textContent = message;
            const input = document.getElementById(field);
            if (input) input.classList.add('error-border');
        }
    }

    form.addEventListener('submit', async function(e) {
        const full_name = document.getElementById('full_name').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const consent = document.getElementById('consent').checked;

        const validationErrors = validateFields(full_name, email, phone, consent);
        clearErrors();

        if (Object.keys(validationErrors).length > 0) {
            e.preventDefault();
            displayErrors(validationErrors);
            return;
        }

        // Если fetch поддерживается – используем AJAX
        if (window.fetch) {
            e.preventDefault();

            const data = {
                full_name: full_name,
                email: email,
                phone: phone,
                consent: consent,
                model_id: parseInt(document.getElementById('model_id').value),
                package_id: document.getElementById('package_id').value ? parseInt(document.getElementById('package_id').value) : null,
                services: Array.from(document.querySelectorAll('input[name="services[]"]:checked')).map(cb => parseInt(cb.value)),
                total_price: parseFloat(document.getElementById('total-price').innerText.replace(/[^0-9.-]+/g, ''))
            };

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
                        document.getElementById('form-message').innerHTML = '<div class="success-message">' + (result.message || 'Заказ обновлён') + '</div>';
                    }
                    clearErrors();
                } else {
                    if (result.errors) {
                        displayErrors(result.errors);
                        document.getElementById('form-message').innerHTML = '<div class="error-message">Пожалуйста, исправьте ошибки в форме.</div>';
                    } else {
                        document.getElementById('form-message').innerHTML = `<div class="error-message">${result.error || 'Ошибка сервера'}</div>`;
                    }
                }
                document.getElementById('form-message').style.display = 'block';
            } catch (err) {
                document.getElementById('form-message').innerHTML = '<div class="error-message">Ошибка сети. Попробуйте позже.</div>';
                document.getElementById('form-message').style.display = 'block';
            }
        } else {
            // Если fetch не поддерживается – форма отправится обычным POST
            // валидация уже прошла, submit продолжится
        }
    });
});