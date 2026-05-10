document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('order-form');
    if (!form) return;

    // Функция обновления цены
    function updateTotalPrice() {
        let total = 0;
        const modelSelect = document.getElementById('model_id');
        const selectedModel = modelSelect.options[modelSelect.selectedIndex];
        const modelPrice = parseFloat(selectedModel.getAttribute('data-price') || 0);
        total += modelPrice;

        const packageSelect = document.getElementById('package_id');
        const packageOption = packageSelect.options[packageSelect.selectedIndex];
        const packagePrice = parseFloat(packageOption.getAttribute('data-price') || 0);
        total += packagePrice;

        const checkboxes = document.querySelectorAll('input[name="services[]"]:checked');
        checkboxes.forEach(cb => {
            total += parseFloat(cb.getAttribute('data-price') || 0);
        });

        document.getElementById('total-price').innerText = total.toLocaleString('ru-RU') + ' ₽';
    }

    // Слушатели изменений
    document.getElementById('model_id').addEventListener('change', updateTotalPrice);
    document.getElementById('package_id').addEventListener('change', updateTotalPrice);
    document.querySelectorAll('input[name="services[]"]').forEach(cb => {
        cb.addEventListener('change', updateTotalPrice);
    });
    updateTotalPrice();

    // Функция отправки через fetch
    async function submitOrder(formDataObj, isEdit = false) {
        const method = isEdit ? 'PUT' : 'POST';
        const response = await fetch('/order_api.php', {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formDataObj)
        });
        const result = await response.json();
        if (response.ok) {
            if (result.login && result.password) {
                // Показываем логин/пароль
                const msgDiv = document.getElementById('form-message');
                msgDiv.innerHTML = `<div class="success-message">
                    Заказ успешно создан!<br>
                    Ваш логин: <strong>${result.login}</strong><br>
                    Пароль: <strong>${result.password}</strong><br>
                    <small>Сохраните эти данные для входа.</small>
                </div>`;
                msgDiv.style.display = 'block';
                // Очищаем форму?
                form.reset();
                updateTotalPrice();
            } else {
                document.getElementById('form-message').innerHTML = '<div class="success-message">Заказ обновлён!</div>';
                document.getElementById('form-message').style.display = 'block';
            }
        } else {
            let errMsg = result.error || 'Ошибка сервера';
            if (result.errors) {
                errMsg = Object.values(result.errors).join('; ');
            }
            document.getElementById('form-message').innerHTML = `<div class="error-message">${errMsg}</div>`;
            document.getElementById('form-message').style.display = 'block';
        }
    }

    // Обработка отправки формы
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Собираем данные
        const full_name = document.getElementById('full_name').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const consent = document.getElementById('consent').checked;
        const model_id = parseInt(document.getElementById('model_id').value);
        const package_id = document.getElementById('package_id').value ? parseInt(document.getElementById('package_id').value) : null;
        const services = Array.from(document.querySelectorAll('input[name="services[]"]:checked')).map(cb => parseInt(cb.value));
        const total_price = parseFloat(document.getElementById('total-price').innerText.replace(/[^0-9.-]+/g, ''));

        if (!full_name || !email || !phone || !consent) {
            alert('Заполните все обязательные поля');
            return;
        }

        const data = {
            full_name, email, phone, consent,
            model_id, package_id, services, total_price
        };

        // Определяем, редактируем ли мы (есть ли авторизация)
        const isLoggedIn = document.querySelector('.logged-in-info') !== null;
        await submitOrder(data, isLoggedIn);
    });
});