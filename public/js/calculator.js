// Независимый калькулятор для старой секции (демо-версия)
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('old-price-calculator');
    if (!form) return;

    const modelSelect = document.getElementById('old-model');
    const colorSelect = document.getElementById('old-color');
    const interiorSelect = document.getElementById('old-interior');
    const wheelsSelect = document.getElementById('old-wheels');
    const panoramaCheckbox = document.getElementById('old-panorama');
    const premiumSoundCheckbox = document.getElementById('old-premium-sound');
    const assistPackageCheckbox = document.getElementById('old-assist-package');
    const amgPackageCheckbox = document.getElementById('old-amg-package');
    const totalPriceSpan = document.getElementById('old-total-price');

    function updateOldTotalPrice() {
        let total = 0;
        total += parseInt(modelSelect.value);
        total += parseInt(colorSelect.value);
        total += parseInt(interiorSelect.value);
        total += parseInt(wheelsSelect.value);
        if (panoramaCheckbox.checked) total += parseInt(panoramaCheckbox.value);
        if (premiumSoundCheckbox.checked) total += parseInt(premiumSoundCheckbox.value);
        if (assistPackageCheckbox.checked) total += parseInt(assistPackageCheckbox.value);
        if (amgPackageCheckbox.checked) total += parseInt(amgPackageCheckbox.value);
        totalPriceSpan.innerText = total.toLocaleString('ru-RU') + ' ₽';
    }

    modelSelect.addEventListener('change', updateOldTotalPrice);
    colorSelect.addEventListener('change', updateOldTotalPrice);
    interiorSelect.addEventListener('change', updateOldTotalPrice);
    wheelsSelect.addEventListener('change', updateOldTotalPrice);
    panoramaCheckbox.addEventListener('change', updateOldTotalPrice);
    premiumSoundCheckbox.addEventListener('change', updateOldTotalPrice);
    assistPackageCheckbox.addEventListener('change', updateOldTotalPrice);
    amgPackageCheckbox.addEventListener('change', updateOldTotalPrice);

    updateOldTotalPrice();
});