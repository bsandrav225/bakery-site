// Товары из базы
const products = Array.isArray(window.productsData) ? window.productsData : [];
const hasServerProducts = products.length > 0;

const categoryMap = {};
products.forEach((p) => {
    if (p.category_slug) categoryMap[p.category_slug] = p.category_name || '';
});

// Состояние
let cart = [];
let currentCategory = 'all';

// Элементы страницы
const menuGrid = document.getElementById('menuGrid');
const cartSidebar = document.getElementById('cartSidebar');
const cartOverlay = document.getElementById('cartOverlay');
const cartBody = document.getElementById('cartBody');
const cartTotalPrice = document.getElementById('cartTotalPrice');
const cartBadge = document.getElementById('cartBadge');
const toastContainer = document.getElementById('toastContainer');
const orderForm = document.getElementById('orderForm');
const preloader = document.getElementById('preloader');

// Корзина
function loadCart() {
    try {
        const saved = JSON.parse(localStorage.getItem('cart') || '[]');
        cart = Array.isArray(saved) ? saved : [];
    } catch (e) {
        cart = [];
    }
}

function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

// Прелоадер
window.addEventListener('load', () => {
    if (!preloader) return;
    setTimeout(() => preloader.classList.add('hidden'), 400);
});

// Фон
function createParticles() {
    const container = document.getElementById('particles');
    if (!container) return;
    for (let i = 0; i < 25; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.width = (Math.random() * 4 + 2) + 'px';
        particle.style.height = particle.style.width;
        particle.style.animationDuration = (Math.random() * 20 + 15) + 's';
        particle.style.animationDelay = (Math.random() * 20) + 's';
        container.appendChild(particle);
    }
}
createParticles();

// Подсветка указателя
const cursorGlow = document.getElementById('cursorGlow');
if (cursorGlow) {
    document.addEventListener('mousemove', (e) => {
        cursorGlow.style.left = e.clientX + 'px';
        cursorGlow.style.top = e.clientY + 'px';
    });
}

// Меню
const nav = document.getElementById('nav');
const menuBtn = document.getElementById('menuBtn');
const navLinks = document.getElementById('navLinks');

window.addEventListener('scroll', () => {
    if (nav) nav.classList.toggle('scrolled', window.scrollY > 50);
});

if (menuBtn && navLinks) {
    menuBtn.addEventListener('click', () => {
        menuBtn.classList.toggle('active');
        navLinks.classList.toggle('open');
    });
}

// Закрыть мобильное меню по клику на ссылку
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        if (menuBtn) menuBtn.classList.remove('active');
        if (navLinks) navLinks.classList.remove('open');
    });
});

// Вспомогательные функции
function escapeHtml(str) {
    return String(str == null ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatPrice(value) {
    return new Intl.NumberFormat('ru-RU').format(Math.round(Number(value) || 0));
}

// Каталог
function renderProducts(category = 'all') {
    if (!menuGrid || !hasServerProducts) return;

    const filtered = category === 'all'
        ? products
        : products.filter(p => p.category_slug === category);

    if (filtered.length === 0) {
        menuGrid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:var(--text-light);padding:40px 0;">В этой категории пока нет товаров</p>';
        return;
    }

    menuGrid.innerHTML = filtered.map(product => `
        <div class="menu-item" data-category="${escapeHtml(product.category_slug)}" data-id="${product.id}">
            ${product.image ? `<img src="${escapeHtml(product.image)}" alt="${escapeHtml(product.name)}" class="menu-item-image" loading="lazy">` : ''}
            <div class="menu-item-content">
                <span class="menu-item-tag">${escapeHtml(product.category_name || 'Выпечка')}</span>
                <h3 class="menu-item-name">${escapeHtml(product.name)}</h3>
                <div class="menu-item-price">${formatPrice(product.price)} ₽</div>
                <button class="menu-item-btn" data-id="${product.id}">
                    <i class="fas fa-plus"></i>
                    <span>В корзину</span>
                </button>
            </div>
        </div>
    `).join('');
}

// Делегирование: работает и для товаров, отрендеренных PHP, и для перерисованных JS
if (menuGrid) {
    menuGrid.addEventListener('click', (e) => {
        const btn = e.target.closest('.menu-item-btn');
        if (!btn) return;
        addToCart(parseInt(btn.dataset.id, 10));
    });
}

// Корзина: действия
function addToCart(productId) {
    const product = products.find(p => Number(p.id) === Number(productId));
    if (!product) return;

    const existing = cart.find(item => Number(item.id) === Number(productId));
    if (existing) {
        existing.quantity += 1;
    } else {
        cart.push({
            id: Number(product.id),
            name: product.name,
            price: Number(product.price) || 0,
            image: product.image,
            quantity: 1
        });
    }

    updateCart();
    showToast(product.name, 'добавлен в корзину');

    const btn = document.querySelector(`.menu-item-btn[data-id="${productId}"]`);
    if (btn) {
        btn.classList.add('added');
        btn.innerHTML = '<i class="fas fa-check"></i><span>В корзине</span>';
        setTimeout(() => {
            btn.classList.remove('added');
            btn.innerHTML = '<i class="fas fa-plus"></i><span>В корзину</span>';
        }, 1200);
    }
}

function removeFromCart(productId) {
    cart = cart.filter(item => Number(item.id) !== Number(productId));
    updateCart();
}

function updateQuantity(productId, change) {
    const item = cart.find(i => Number(i.id) === Number(productId));
    if (!item) return;

    item.quantity += change;
    if (item.quantity <= 0) {
        removeFromCart(productId);
    } else {
        updateCart();
    }
}

function updateCart() {
    saveCart();

    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const totalPrice = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);

    if (cartBadge) {
        cartBadge.textContent = totalItems;
        cartBadge.style.transform = totalItems > 0 ? 'scale(1)' : 'scale(0)';
    }
    if (cartTotalPrice) cartTotalPrice.textContent = `${formatPrice(totalPrice)} ₽`;

    const cartField = document.getElementById('cartItemsField');
    if (cartField) cartField.value = JSON.stringify(cart);

    if (!cartBody) return;

    if (cart.length === 0) {
        cartBody.innerHTML = `
            <div class="cart-empty">
                <div class="cart-empty-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h4>Корзина пуста</h4>
                <p>Добавьте товары из меню</p>
            </div>
        `;
        return;
    }

    cartBody.innerHTML = cart.map(item => `
        <div class="cart-item">
            <img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" class="cart-item-image">
            <div class="cart-item-info">
                <div class="cart-item-name">${escapeHtml(item.name)}</div>
                <div class="cart-item-price">${formatPrice(item.price)} ₽</div>
                <div class="cart-item-actions">
                    <button class="cart-item-qty-btn" onclick="updateQuantity(${item.id}, -1)">−</button>
                    <span class="cart-item-qty">${item.quantity}</span>
                    <button class="cart-item-qty-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
                    <button class="cart-item-remove" onclick="removeFromCart(${item.id})">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// Открытие корзины
function toggleCart() {
    if (!cartSidebar || !cartOverlay) return;
    cartSidebar.classList.toggle('open');
    cartOverlay.classList.toggle('active');
    document.body.style.overflow = cartSidebar.classList.contains('open') ? 'hidden' : '';
}

document.getElementById('cartToggle')?.addEventListener('click', toggleCart);
document.getElementById('cartClose')?.addEventListener('click', toggleCart);
cartOverlay?.addEventListener('click', toggleCart);

// Оформление
document.getElementById('checkoutBtn')?.addEventListener('click', () => {
    if (cart.length === 0) {
        showToast('Корзина пуста', 'Добавьте товары');
        return;
    }
    toggleCart();
    const orderSection = document.getElementById('order');
    if (orderSection) {
        orderSection.scrollIntoView({ behavior: 'smooth' });
    } else {
        window.location.href = 'index.php#order';
    }
});

// Уведомления
function showToast(title, message) {
    if (!toastContainer) return;
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="toast-content">
            <h4>${escapeHtml(title)}</h4>
            <p>${escapeHtml(message)}</p>
        </div>
    `;
    toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('removing');
        setTimeout(() => toast.remove(), 400);
    }, 2800);
}

// Форма заказа
function setFieldError(fieldId, hasError) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    const group = input.closest('.form-group');
    const errorEl = group ? group.querySelector('.form-error') : null;
    input.classList.toggle('error', hasError);
    if (errorEl) errorEl.classList.toggle('show', hasError);
}

if (orderForm) {
    orderForm.addEventListener('submit', function (e) {
        const name = (document.getElementById('customerName')?.value || '').trim();
        const phone = (document.getElementById('customerPhone')?.value || '').trim();
        const address = (document.getElementById('customerAddress')?.value || '').trim();

        let isValid = true;

        if (name.length < 2) { setFieldError('customerName', true); isValid = false; }
        else setFieldError('customerName', false);

        const digits = phone.replace(/\D/g, '');
        if (digits.length < 10) { setFieldError('customerPhone', true); isValid = false; }
        else setFieldError('customerPhone', false);

        if (address.length < 5) { setFieldError('customerAddress', true); isValid = false; }
        else setFieldError('customerAddress', false);

        if (!isValid) {
            e.preventDefault();
            return;
        }

        // Кладём состав корзины в скрытое поле — заявка уходит в БД вместе с товарами
        const cartField = document.getElementById('cartItemsField');
        if (cartField) cartField.value = JSON.stringify(cart);

        // Корзина оформлена — очищаем локальное хранилище
        localStorage.removeItem('cart');
        // форма отправляется обычным POST -> index.php сохраняет заявку в БД
    });
}

// Сброс ошибок при вводе
document.querySelectorAll('.form-input-wrap input').forEach(input => {
    input.addEventListener('input', function () {
        this.classList.remove('error');
        const group = this.closest('.form-group');
        const errorEl = group ? group.querySelector('.form-error') : null;
        if (errorEl) errorEl.classList.remove('show');
    });
});

// Фильтры меню
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        currentCategory = this.dataset.category || 'all';

        if (hasServerProducts) {
            renderProducts(currentCategory);
        } else {
            // Фолбэк: фильтруем уже отрендеренную разметку по data-category
            document.querySelectorAll('#menuGrid .menu-item').forEach(item => {
                const match = currentCategory === 'all' || item.dataset.category === currentCategory;
                item.style.display = match ? '' : 'none';
            });
        }
    });
});

// Прокрутка к секциям
document.querySelector('.hero-actions .btn-primary')?.addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('menu')?.scrollIntoView({ behavior: 'smooth' });
});

document.querySelector('.hero-actions .btn-ghost')?.addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('order')?.scrollIntoView({ behavior: 'smooth' });
});

// Клавиатура
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && cartSidebar?.classList.contains('open')) {
        toggleCart();
    }
});

// Старт
loadCart();
if (hasServerProducts) renderProducts('all');
updateCart();

// АНИМАЦИИ: пар, крошки, появление секций
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// --- Прогресс чтения страницы ---
(function initScrollProgress() {
    const bar = document.getElementById("scrollProgress");
    if (!bar) return;

    let ticking = false;
    const update = () => {
        const max = document.documentElement.scrollHeight - window.innerHeight;
        const ratio = max > 0 ? window.scrollY / max : 0;
        bar.style.width = Math.min(100, ratio * 100) + "%";
        ticking = false;
    };

    window.addEventListener("scroll", () => {
        if (!ticking) {
            ticking = true;
            requestAnimationFrame(update);
        }
    }, { passive: true });
    update();
})();

// --- Появление блоков при прокрутке ---
(function initReveal() {
    const items = document.querySelectorAll("[data-reveal]");
    if (!items.length) return;

    if (prefersReducedMotion || !("IntersectionObserver" in window)) {
        items.forEach(el => el.classList.add("revealed"));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add("revealed");
            const title = entry.target.querySelector(".section-title");
            if (title) title.classList.add("proofing");
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.15, rootMargin: "0px 0px -60px 0px" });

    items.forEach((el, i) => {
        const siblings = el.parentElement ? Array.from(el.parentElement.children).indexOf(el) : i;
        el.style.setProperty("--reveal-i", Math.max(0, siblings % 6));
        observer.observe(el);
    });
})();

// --- Пар над свежей выпечкой (включается, когда блок в зоне видимости) ---
(function initSteam() {
    const steams = document.querySelectorAll(".steam");
    if (!steams.length || prefersReducedMotion) return;

    if (!("IntersectionObserver" in window)) {
        steams.forEach(s => s.classList.add("is-active"));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => entry.target.classList.toggle("is-active", entry.isIntersecting));
    }, { threshold: 0.2 });

    steams.forEach(s => observer.observe(s));
})();

// --- Пар, поднимающийся при пролистывании страницы ---
(function initScrollSteam() {
    if (prefersReducedMotion) return;

    const layer = document.getElementById("scrollSteamLayer");
    if (!layer || typeof Element.prototype.animate !== "function") return;

    let lastY = window.scrollY;
    let accumulated = 0;
    let active = 0;
    let ticking = false;

    function puff() {
        if (active > 14) return;
        active++;

        const wisp = document.createElement("div");
        wisp.className = "scroll-steam";
        const size = 34 + Math.random() * 46;
        wisp.style.width = size + "px";
        wisp.style.height = size + "px";
        wisp.style.left = (Math.random() * 92) + "vw";
        wisp.style.top = "100vh";
        layer.appendChild(wisp);

        const drift = (Math.random() - 0.5) * 160;
        const rise = window.innerHeight * (0.55 + Math.random() * 0.4);
        const animation = wisp.animate([
            { transform: "translate3d(0, 0, 0) scale(0.55)", opacity: 0 },
            { opacity: 0.5, offset: 0.25 },
            { transform: `translate3d(${drift}px, -${rise}px, 0) scale(2.2)`, opacity: 0 }
        ], {
            duration: 3200 + Math.random() * 2200,
            easing: "cubic-bezier(0.25, 0.6, 0.3, 1)"
        });

        animation.onfinish = () => {
            wisp.remove();
            active--;
        };
    }

    window.addEventListener("scroll", () => {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(() => {
            const delta = Math.abs(window.scrollY - lastY);
            lastY = window.scrollY;
            accumulated += delta;
            if (accumulated > 260) {
                accumulated = 0;
                puff();
            }
            ticking = false;
        });
    }, { passive: true });
})();

// --- Крошки: разлетаются при добавлении товара и при «укусе» карточки ---
const crumbLayer = document.getElementById("crumbLayer");

function scatterCrumbs(x, y, count = 14) {
    if (prefersReducedMotion || !crumbLayer || typeof Element.prototype.animate !== "function") return;

    const tones = ["", "dark", "light"];
    for (let i = 0; i < count; i++) {
        const crumb = document.createElement("div");
        crumb.className = "crumb " + tones[Math.floor(Math.random() * tones.length)];
        const scale = 0.6 + Math.random() * 1.1;
        crumb.style.left = x + "px";
        crumb.style.top = y + "px";
        crumbLayer.appendChild(crumb);

        const dx = (Math.random() - 0.5) * 220;
        const dy = 60 + Math.random() * 180;
        const rot = (Math.random() - 0.5) * 720;

        const animation = crumb.animate([
            { transform: `translate3d(0, 0, 0) rotate(0deg) scale(${scale})`, opacity: 1 },
            { transform: `translate3d(${dx * 0.5}px, ${-40 - Math.random() * 50}px, 0) rotate(${rot * 0.4}deg) scale(${scale})`, opacity: 1, offset: 0.35 },
            { transform: `translate3d(${dx}px, ${dy}px, 0) rotate(${rot}deg) scale(${scale * 0.8})`, opacity: 0 }
        ], {
            duration: 900 + Math.random() * 700,
            easing: "cubic-bezier(0.3, 0.8, 0.4, 1)"
        });

        animation.onfinish = () => crumb.remove();
    }
}

document.addEventListener("click", (e) => {
    const btn = e.target.closest(".menu-item-btn");
    if (!btn) return;
    const rect = btn.getBoundingClientRect();
    scatterCrumbs(rect.left + rect.width / 2, rect.top + rect.height / 2, 16);
});

// Лёгкая осыпь крошек, когда карточку «надкусывают» наведением
document.addEventListener("mouseover", (e) => {
    const card = e.target.closest(".menu-item");
    if (!card || card.dataset.bitten === "1") return;
    card.dataset.bitten = "1";
    const rect = card.getBoundingClientRect();
    scatterCrumbs(rect.right - 40, rect.top + 30, 7);
    setTimeout(() => { card.dataset.bitten = "0"; }, 1400);
});

// --- Выпадающее меню «Ещё» в шапке (клик для тач-устройств) ---
document.querySelectorAll(".nav-dropdown").forEach((dropdown) => {
    const btn = dropdown.querySelector(".nav-dropdown-btn");
    if (!btn) return;

    btn.addEventListener("click", (e) => {
        e.stopPropagation();
        const isOpen = dropdown.classList.toggle("open");
        btn.setAttribute("aria-expanded", String(isOpen));
    });

    document.addEventListener("click", (e) => {
        if (!dropdown.contains(e.target)) {
            dropdown.classList.remove("open");
            btn.setAttribute("aria-expanded", "false");
        }
    });
});
