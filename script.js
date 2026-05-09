// Mobile Menu Toggle
const burger = document.querySelector('.burger');
const navLinks = document.querySelector('.nav-links');
const navItems = document.querySelectorAll('.nav-links li');
const hero = document.querySelector('.hero');
const logo = document.querySelector('.logo');

burger.addEventListener('click', () => {
    navLinks.classList.toggle('active');
    burger.classList.toggle('active');
    
    
    if (navLinks.classList.contains('active')) {
        hero.style.opacity = '0';
        hero.style.visibility = 'hidden';
        logo.style.opacity = '0';
        logo.style.visibility = 'hidden';
    } else {
        hero.style.opacity = '1';
        hero.style.visibility = 'visible';
        logo.style.opacity = '1';
        logo.style.visibility = 'visible';
    }
});

// Mobile Dropdown Toggle
navItems.forEach(item => {
    if (item.querySelector('.dropdown')) {
        item.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                item.classList.toggle('active');
            }
        });
    }
});

// Color Picker - обновленный код для Mercedes
const colorOptions = document.querySelectorAll('.color-option');

// Маппинг цветов к названиям файлов изображений для Mercedes
const colorImageMap = {
    '1': { // Mercedes A-Class
        'Черный': 'black-a-class.jfif',
        'Серебристый': 'grey-a-class.jfif',
        'Синий': 'blue-a-class.jfif',
        'Белый': 'white-a-class.jfif'
    },
    '2': { // Mercedes C-Class
        'Черный': 'black-c-class.jfif',
        'Серебристый': 'grey-c-class.jfif',
        'Синий': 'blue-c-class.jfif',
        'Белый': 'white-c-class.jfif'
    },
    '3': { // Mercedes E-Class
        'Черный': 'black-e-class.jfif',
        'Серебристый': 'grey-e-class.jfif',
        'Синий': 'blue-e-class.jfif',
        'Белый': 'white-e-class.jfif'
    }
};

colorOptions.forEach(option => {
    option.addEventListener('click', function() {
        const modelId = this.getAttribute('data-model');
        const colorName = this.getAttribute('data-color-name');
        
        // Remove active class from all options in this model group
        const modelOptions = document.querySelectorAll(`.color-option[data-model="${modelId}"]`);
        modelOptions.forEach(opt => {
            opt.classList.remove('active');
        });
        
        // Add active class to clicked option
        this.classList.add('active');
        
        // Change car image based on color
        const carImage = document.getElementById(`model-${modelId}-img`);
        if (carImage && colorImageMap[modelId] && colorImageMap[modelId][colorName]) {
            // Add loading state
            carImage.style.opacity = '0.7';
            
            setTimeout(() => {
                carImage.src = colorImageMap[modelId][colorName];
                carImage.alt = `Mercedes ${modelId === '1' ? 'A-Class' : modelId === '2' ? 'C-Class' : 'E-Class'} ${colorName}`;
                
                // Remove loading state
                setTimeout(() => {
                    carImage.style.opacity = '1';
                }, 300);
            }, 300);
        }
    });
});

// Gallery functionality
const gallerySlider = document.querySelector('.gallery-slider');
const gallerySlides = document.querySelectorAll('.gallery-slide');
const prevBtn = document.querySelector('.prev-btn');
const nextBtn = document.querySelector('.next-btn');
const galleryDots = document.querySelectorAll('.gallery-dot');

let currentSlide = 0;
const totalSlides = gallerySlides.length;

function goToSlide(n) {
    if (n < 0) {
        currentSlide = totalSlides - 1;
    } else if (n >= totalSlides) {
        currentSlide = 0;
    } else {
        currentSlide = n;
    }
    
    gallerySlider.style.transform = `translateX(-${currentSlide * 100}%)`;
    
    // Update dots
    galleryDots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentSlide);
    });
}

function nextSlide() {
    goToSlide(currentSlide + 1);
}

function prevSlide() {
    goToSlide(currentSlide - 1);
}

// Auto slide every 5 seconds
let slideInterval = setInterval(nextSlide, 5000);

// Pause auto slide on hover
gallerySlider.addEventListener('mouseenter', () => {
    clearInterval(slideInterval);
});

gallerySlider.addEventListener('mouseleave', () => {
    slideInterval = setInterval(nextSlide, 5000);
});

// Event listeners for gallery controls
if (nextBtn) nextBtn.addEventListener('click', () => {
    nextSlide();
    // Reset auto slide timer
    clearInterval(slideInterval);
    slideInterval = setInterval(nextSlide, 5000);
});

if (prevBtn) prevBtn.addEventListener('click', () => {
    prevSlide();
    // Reset auto slide timer
    clearInterval(slideInterval);
    slideInterval = setInterval(nextSlide, 5000);
});

// Event listeners for dots
galleryDots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
        goToSlide(index);
        // Reset auto slide timer
        clearInterval(slideInterval);
        slideInterval = setInterval(nextSlide, 5000);
    });
});

// Price Calculator
const calculatorForm = document.getElementById('price-calculator');
const totalPriceElement = document.getElementById('total-price');

if (calculatorForm && totalPriceElement) {
    function calculateTotalPrice() {
        let totalPrice = 0;
        
        // Base model price
        const modelSelect = document.getElementById('model');
        totalPrice += parseInt(modelSelect.value);
        
        // Color price
        const colorSelect = document.getElementById('color');
        totalPrice += parseInt(colorSelect.value);
        
        // Interior price
        const interiorSelect = document.getElementById('interior');
        totalPrice += parseInt(interiorSelect.value);
        
        // Wheels price
        const wheelsSelect = document.getElementById('wheels');
        totalPrice += parseInt(wheelsSelect.value);
        
        // Additional options
        const panorama = document.getElementById('panorama');
        if (panorama && panorama.checked) totalPrice += parseInt(panorama.value);
        
        const premiumSound = document.getElementById('premium-sound');
        if (premiumSound && premiumSound.checked) totalPrice += parseInt(premiumSound.value);
        
        const assistPackage = document.getElementById('assist-package');
        if (assistPackage && assistPackage.checked) totalPrice += parseInt(assistPackage.value);
        
        const amgPackage = document.getElementById('amg-package');
        if (amgPackage && amgPackage.checked) totalPrice += parseInt(amgPackage.value);
        
        // Format and display total price
        totalPriceElement.textContent = totalPrice.toLocaleString('ru-RU') + ' ₽';
        
        // Add animation effect
        totalPriceElement.style.transform = 'scale(1.05)';
        setTimeout(() => {
            totalPriceElement.style.transform = 'scale(1)';
        }, 300);
    }

    // Add event listeners to all form elements
    const formElements = calculatorForm.querySelectorAll('select, input');
    formElements.forEach(element => {
        element.addEventListener('change', calculateTotalPrice);
        element.addEventListener('input', calculateTotalPrice);
    });

    // Initialize calculator
    calculateTotalPrice();
}



// Modal elements
const modal = document.getElementById('contact-modal');
const contactBtn = document.getElementById('contact-btn');
const closeModal = document.querySelector('.close-modal');

// Form elements
const modalContactForm = document.getElementById('modal-contact-form');
const contactForm = document.getElementById('contact-form');


const FORMCARRY_URL = 'https://formcarry.com/s/Eifz-QH6quc';


function openModal() {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModalFunc() {
    modal.classList.add('closing');
    setTimeout(() => {
        modal.classList.remove('active', 'closing');
        document.body.style.overflow = 'auto';
    }, 400);
}


if (contactBtn && modal) {
    contactBtn.addEventListener('click', (e) => {
        e.preventDefault();
        openModal();
    });
}

if (closeModal && modal) {
    closeModal.addEventListener('click', closeModalFunc);
}

if (modal) {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeModalFunc();
        }
    });
}




function validateName(name) {
    return name && name.trim().length >= 2;
}


function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}


function isValidPhone(phone) {
    
    const cleaned = phone.replace(/[^\d+]/g, '');
    
    
    
    if (cleaned.startsWith('+7') && cleaned.length === 12) {
        return true;
    }
    
    if (cleaned.startsWith('8') && cleaned.length === 11) {
        return true;
    }
    
    return false;
}

function validateMessage(message) {
    return message && message.trim().length >= 10;
}


function showError(input, message) {
    const existingError = input.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    input.classList.remove('error');
    
    if (message) {
        input.classList.add('error');
        const errorElement = document.createElement('div');
        errorElement.className = 'error-message';
        errorElement.style.color = '#e91e63';
        errorElement.style.fontSize = '0.8rem';
        errorElement.style.marginTop = '0.3rem';
        errorElement.textContent = message;
        input.parentNode.appendChild(errorElement);
    }
}

// Форма модального окна 
async function handleModalFormSubmit(form, e) {
    e.preventDefault();
    
    const nameInput = form.querySelector('input[name="name"]');
    const emailInput = form.querySelector('input[type="email"]');
    const messageInput = form.querySelector('textarea[name="message"]');
    
    let isValid = true;
    
    // Validation
    if (!validateName(nameInput.value)) {
        showError(nameInput, 'Имя должно содержать минимум 2 символа');
        isValid = false;
    } else {
        showError(nameInput, null);
    }
    
    if (!isValidEmail(emailInput.value)) {
        showError(emailInput, 'Введите корректный email адрес');
        isValid = false;
    } else {
        showError(emailInput, null);
    }
    
    if (!validateMessage(messageInput.value)) {
        showError(messageInput, 'Сообщение должно содержать минимум 10 символов');
        isValid = false;
    } else {
        showError(messageInput, null);
    }
    
    if (!isValid) {
        const formMessage = document.getElementById('modal-form-message');
        if (formMessage) {
            formMessage.textContent = 'Пожалуйста, исправьте ошибки в форме';
            formMessage.className = 'form-message error';
            formMessage.style.display = 'block';
        }
        return;
    }
    
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Отправка...';
    submitBtn.disabled = true;
    
    
    const formData = new FormData(form);
    
    
    const data = Object.fromEntries(formData);
    
    try {
        const response = await fetch(FORMCARRY_URL, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            
            const formMessage = document.getElementById('modal-form-message');
            if (formMessage) {
                formMessage.textContent = 'Спасибо! Ваша заявка отправлена. Мы свяжемся с вами в ближайшее время.';
                formMessage.className = 'form-message success';
                formMessage.style.display = 'block';
            }
            
            
            form.reset();
            
            
            setTimeout(() => {
                closeModalFunc();
            }, 2000);
        } else {
            throw new Error(result.message || 'Ошибка при отправке');
        }
    } catch (error) {
        const formMessage = document.getElementById('modal-form-message');
        if (formMessage) {
            formMessage.textContent = 'Ошибка при отправке. Пожалуйста, попробуйте еще раз.';
            formMessage.className = 'form-message error';
            formMessage.style.display = 'block';
        }
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}


async function handleContactFormSubmit(form, e) {
    e.preventDefault();
    
    const nameInput = form.querySelector('input[name="name"]');
    const emailInput = form.querySelector('input[type="email"]');
    const phoneInput = form.querySelector('input[type="tel"]');
    const messageInput = form.querySelector('textarea[name="message"]');
    
    let isValid = true;
    
    // Validation
    if (!validateName(nameInput.value)) {
        showError(nameInput, 'Имя должно содержать минимум 2 символа');
        isValid = false;
    } else {
        showError(nameInput, null);
    }
    
    if (!isValidEmail(emailInput.value)) {
        showError(emailInput, 'Введите корректный email адрес');
        isValid = false;
    } else {
        showError(emailInput, null);
    }
    
    if (!isValidPhone(phoneInput.value)) {
        showError(phoneInput, 'Введите номер в формате +71234567890');
        isValid = false;
    } else {
        showError(phoneInput, null);
    }
    
    if (!validateMessage(messageInput.value)) {
        showError(messageInput, 'Сообщение должно содержать минимум 10 символов');
        isValid = false;
    } else {
        showError(messageInput, null);
    }
    
    if (!isValid) {
        const formMessage = document.getElementById('form-message');
        if (formMessage) {
            formMessage.textContent = 'Пожалуйста, исправьте ошибки в форме';
            formMessage.className = 'form-message error';
            formMessage.style.display = 'block';
            
            
            formMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return;
    }
    
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Отправка...';
    submitBtn.disabled = true;
    
    
    const formData = new FormData(form);
    
    
    const data = Object.fromEntries(formData);
    
    try {
        const response = await fetch(FORMCARRY_URL, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            
            const formMessage = document.getElementById('form-message');
            if (formMessage) {
                formMessage.textContent = 'Спасибо! Ваша заявка отправлена. Мы свяжемся с вами в ближайшее время.';
                formMessage.className = 'form-message success';
                formMessage.style.display = 'block';
                
                setTimeout(() => {
                    formMessage.style.display = 'none';
                }, 5000);
            }
            
            
            form.reset();
        } else {
            throw new Error(result.message || 'Ошибка при отправке');
        }
    } catch (error) {
        const formMessage = document.getElementById('form-message');
        if (formMessage) {
            formMessage.textContent = 'Ошибка при отправке. Пожалуйста, попробуйте еще раз.';
            formMessage.className = 'form-message error';
            formMessage.style.display = 'block';
        }
    } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}


if (contactForm) {
    contactForm.addEventListener('submit', (e) => handleContactFormSubmit(contactForm, e));
}

if (modalContactForm) {
    modalContactForm.addEventListener('submit', (e) => handleModalFormSubmit(modalContactForm, e));
}

// Упрощенная валидация с реальной обратной связью
document.addEventListener('DOMContentLoaded', function() {
    
    const nameInputs = document.querySelectorAll('input[type="text"][name="name"]');
    
    nameInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (!validateName(this.value)) {
                showError(this, 'Имя должно содержать минимум 2 символа');
            } else {
                showError(this, null);
            }
        });
        
        input.addEventListener('focus', function() {
            showError(this, null);
        });
    });
    
    
    const emailInputs = document.querySelectorAll('input[type="email"]');
    
    emailInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (!isValidEmail(this.value)) {
                showError(this, 'Введите корректный email адрес');
            } else {
                showError(this, null);
            }
        });
        
        input.addEventListener('focus', function() {
            showError(this, null);
        });
    });
    
    
    const phoneInputs = document.querySelectorAll('#contact-form input[type="tel"]');
    
    phoneInputs.forEach(input => {
        input.placeholder = '+71234567890';
        
        
        
        input.addEventListener('blur', function() {
            if (!isValidPhone(this.value)) {
                showError(this, 'Введите номер в формате +71234567890');
            } else {
                showError(this, null);
            }
        });
        
        input.addEventListener('focus', function() {
            showError(this, null);
        });
    });
    
    
    const messageInputs = document.querySelectorAll('textarea[name="message"]');
    
    messageInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (!validateMessage(this.value)) {
                showError(this, 'Сообщение должно содержать минимум 10 символов');
            } else {
                showError(this, null);
            }
        });
        
        input.addEventListener('focus', function() {
            showError(this, null);
        });
    });
});


document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('active')) {
        closeModalFunc();
    }
});


document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth'
            });
            
            
            if (navLinks.classList.contains('active')) {
                navLinks.classList.remove('active');
                burger.classList.remove('active');
                hero.style.opacity = '1';
                hero.style.visibility = 'visible';
                logo.style.opacity = '1';
                logo.style.visibility = 'visible';
            }
        }
    });
});


document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
            navLinks.classList.remove('active');
            burger.classList.remove('active');
            hero.style.opacity = '1';
            hero.style.visibility = 'visible';
            logo.style.opacity = '1';
            logo.style.visibility = 'visible';
        }
    });
});


window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        navLinks.classList.remove('active');
        burger.classList.remove('active');
        hero.style.opacity = '1';
        hero.style.visibility = 'visible';
        logo.style.opacity = '1';
        logo.style.visibility = 'visible';
    }
});


const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);


document.addEventListener('DOMContentLoaded', () => {
    const animateElements = document.querySelectorAll('.model-card, .tuning-card, .calculator, .contact-form');
    
    animateElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
});


if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });

    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}