/**
 * Глобальные функции корзины
 */
const CartManager = {
    STORAGE_KEY: 'cart',
    
    // Получить корзину
    get() {
        return JSON.parse(localStorage.getItem(this.STORAGE_KEY) || '[]');
    },
    
    // Сохранить корзину
    save(cart) {
        localStorage.setItem(this.STORAGE_KEY, JSON.stringify(cart));
        this.updateCounter();
    },
    
    // Добавить товар
    add(product, quantity = 1) {
        let cart = this.get();
        const existing = cart.find(item => item.id === product.id);
        
        if (existing) {
            existing.quantity += quantity;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                price: product.price,
                image: product.image,
                quantity: quantity
            });
        }
        
        this.save(cart);
        this.syncToBackend(product.id, product.name, product.price, product.image, quantity);
        return true;
    },
    
    // Обновить количество
    updateQuantity(id, quantity) {
        let cart = this.get();
        const item = cart.find(i => i.id === id);
        
        if (item) {
            if (quantity <= 0) {
                return this.remove(id);
            }
            item.quantity = quantity;
            this.save(cart);
            this.syncUpdateBackend(id, quantity);
            return true;
        }
        return false;
    },
    
    // Удалить товар
    remove(id) {
        let cart = this.get().filter(item => item.id !== id);
        this.save(cart);
        this.syncRemoveBackend(id);
        return true;
    },
    
    // Очистить корзину
    clear() {
        localStorage.removeItem(this.STORAGE_KEY);
        this.updateCounter();
        this.syncClearBackend();
    },
    
    // Обновить счётчик в навбаре
    updateCounter() {
        const cart = this.get();
        const total = cart.reduce((sum, item) => sum + item.quantity, 0);
        const badge = document.querySelector('.cart-counter');
        
        if (badge) {
            badge.textContent = total;
            badge.style.display = total > 0 ? 'inline-block' : 'none';
        }
        
        // Обновляем на странице корзины
        this.updateCartPage();
    },
    
    // Показать уведомление
    showToast(message, type = 'success') {
        const toastEl = document.getElementById('cartToast');
        if (!toastEl) return;
        
        const toastBody = toastEl.querySelector('.toast-body');
        toastEl.className = `toast align-items-center text-bg-${type} border-0`;
        document.getElementById('toastMessage').textContent = message;
        
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
    },
    
    // Синхронизация с бэкендом (опционально)
    syncToBackend(id, name, price, image, quantity) {
        fetch('/api/cart/add', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id, name, price, image, quantity})
        }).catch(() => {}); // Игнорируем ошибки, если бэкенд не настроен
    },
    
    syncUpdateBackend(id, quantity) {
        fetch('/api/cart/update', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id, quantity})
        }).catch(() => {});
    },
    
    syncRemoveBackend(id) {
        fetch('/api/cart/remove', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id})
        }).catch(() => {});
    },
    
    syncClearBackend() {
        fetch('/api/cart/clear', {method: 'POST'}).catch(() => {});
    },
    
    // Обновление на странице корзины
    updateCartPage() {
        if (!document.getElementById('cart-items')) return;
        
        const cart = this.get();
        const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
        const count = cart.reduce((sum, item) => sum + item.quantity, 0);
        
        // Обновляем сумму и количество
        const totalEl = document.querySelector('.h3.fw-bold.text-warning');
        const countEl = document.querySelector('.text-white-50 + .text-white.fw-bold');
        
        if (totalEl) totalEl.textContent = new Intl.NumberFormat('ru-RU').format(total) + ' ₽';
        if (countEl) countEl.textContent = count;
        
        // Если корзина пуста - перезагружаем
        if (cart.length === 0 && window.location.pathname === '/cart') {
            window.location.reload();
        }
    }
};

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    CartManager.updateCounter();
    
    // Обработчики для страницы корзины
    if (document.getElementById('cart-items')) {
        initCartPage();
    }
    
    // Обработчики для кнопок "В корзину"
    document.querySelectorAll('.btn-add-to-cart').forEach(btn => {
        btn.addEventListener('click', function() {
            const product = JSON.parse(this.dataset.product);
            CartManager.add(product);
            CartManager.showToast('"' + product.name + '" добавлен в корзину!');
            animateButton(this);
        });
    });
});

// Инициализация страницы корзины
function initCartPage() {
    // Изменение количества
    document.querySelectorAll('.btn-quantity').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = parseInt(this.dataset.id);
            const action = this.dataset.action;
            const input = document.querySelector(`.quantity-input[data-id="${id}"]`);
            let value = parseInt(input.value) || 1;
            
            value = action === 'increase' ? value + 1 : Math.max(1, value - 1);
            input.value = value;
            
            CartManager.updateQuantity(id, value);
            CartManager.showToast('Количество обновлено');
        });
    });
    
    // Прямой ввод количества
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function() {
            const id = parseInt(this.dataset.id);
            const value = parseInt(this.value) || 1;
            CartManager.updateQuantity(id, value);
        });
    });
    
    // Удаление товара
    document.querySelectorAll('.btn-remove').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = parseInt(this.dataset.id);
            if (confirm('Удалить этот товар из корзины?')) {
                CartManager.remove(id);
                CartManager.showToast('Товар удалён');
            }
        });
    });
    
    // Очистка корзины
    document.getElementById('clear-cart')?.addEventListener('click', function() {
        if (confirm('Очистить всю корзину?')) {
            CartManager.clear();
            CartManager.showToast('Корзина очищена');
            setTimeout(() => window.location.reload(), 500);
        }
    });
    
    // Оформление заказа
    document.getElementById('checkout-btn')?.addEventListener('click', function() {
        CartManager.showToast('🚧 Функция оформления в разработке', 'info');
        // Здесь можно добавить редирект на форму заказа
        // window.location.href = '/checkout';
    });
}

// Анимация кнопки при добавлении
function animateButton(button) {
    const original = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="bi bi-check-lg me-2"></i>Добавлено!';
    button.classList.replace('btn-light', 'btn-success');
    
    setTimeout(() => {
        button.innerHTML = original;
        button.disabled = false;
        button.classList.replace('btn-success', 'btn-light');
    }, 2000);
}