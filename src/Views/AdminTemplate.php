<?php
namespace App\Views;

require_once __DIR__ . '/BaseTemplate.php';

class AdminTemplate extends BaseTemplate
{
    /**
     * Рендер главной страницы админки (дашборд)
     */
    public static function renderDashboard(array $stats): string
    {
        $revenueFormatted = number_format($stats['total_revenue'], 0, '.', ' ');
        
        $content = '
        <div class="container py-4">
            <h1 class="mb-4">Панель администратора</h1>
            
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Пользователи</h6>
                                    <h2 class="mb-0">' . $stats['total_users'] . '</h2>
                                </div>
                                <i class="bi bi-people fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Товары</h6>
                                    <h2 class="mb-0">' . $stats['total_products'] . '</h2>
                                </div>
                                <i class="bi bi-box fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Заказы</h6>
                                    <h2 class="mb-0">' . $stats['total_orders'] . '</h2>
                                </div>
                                <i class="bi bi-cart fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Выручка</h6>
                                    <h2 class="mb-0">' . $revenueFormatted . ' ₽</h2>
                                </div>
                                <i class="bi bi-currency-dollar fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-cart me-2"></i>Управление заказами</h5>
                        </div>
                        <div class="card-body">
                            <p>Просмотр и управление заказами клиентов</p>
                            <a href="/admin/orders" class="btn btn-primary">Перейти к заказам</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-people me-2"></i>Управление пользователями</h5>
                        </div>
                        <div class="card-body">
                            <p>Просмотр списка зарегистрированных пользователей</p>
                            <a href="/admin/users" class="btn btn-primary">Перейти к пользователям</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        
        return parent::getTemplate($content);
    }
    
    /**
     * Рендер страницы заказов с вкладками
     */
    public static function renderOrders(array $orders): string
    {
        // Разделяем заказы
        $activeOrders = array_filter($orders, fn($o) => in_array($o['status'] ?? 'new', ['new', 'processing']));
        $completedOrders = array_filter($orders, fn($o) => in_array($o['status'] ?? '', ['completed', 'cancelled']));
        
        $activeCount = count($activeOrders);
        $completedCount = count($completedOrders);
        
        $content = '
        <div class="container py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0">Заказы</h1>
                <a href="/admin" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Назад
                </a>
            </div>
            
            <!-- Вкладки -->
            <ul class="nav nav-tabs mb-4" id="ordersTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-orders" type="button" role="tab">
                        <i class="bi bi-clock-history me-1"></i>Активные
                        <span class="badge bg-danger ms-2">' . $activeCount . '</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed-orders" type="button" role="tab">
                        <i class="bi bi-check2-circle me-1"></i>Завершённые
                        <span class="badge bg-secondary ms-2">' . $completedCount . '</span>
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="ordersTabsContent">
                <!-- Активные заказы -->
                <div class="tab-pane fade show active" id="active-orders" role="tabpanel">
                    ' . self::renderOrdersList($activeOrders, true) . '
                </div>
                
                <!-- Завершённые заказы -->
                <div class="tab-pane fade" id="completed-orders" role="tabpanel">
                    ' . self::renderOrdersList($completedOrders, false) . '
                </div>
            </div>
        </div>
        
        <!-- Модальное окно просмотра заказа -->
        <div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-receipt me-2"></i>Заказ #<span id="orderModalId"></span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card h-100 border-0 bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-3">
                                            <i class="bi bi-person me-1"></i>Клиент
                                        </h6>
                                        <p class="mb-2"><strong>ФИО:</strong> <span id="orderModalFio"></span></p>
                                        <p class="mb-2"><strong>Email:</strong> <span id="orderModalEmail"></span></p>
                                        <p class="mb-2"><strong>Телефон:</strong> <span id="orderModalPhone"></span></p>
                                        <p class="mb-0"><strong>Адрес:</strong> <span id="orderModalAddress"></span></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100 border-0 bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-3">
                                            <i class="bi bi-info-circle me-1"></i>Детали
                                        </h6>
                                        <p class="mb-2"><strong>Дата:</strong> <span id="orderModalDate"></span></p>
                                        <p class="mb-2"><strong>Статус:</strong> <span id="orderModalStatus"></span></p>
                                        <p class="mb-0"><strong>Оплата:</strong> <span id="orderModalPayment"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <h6 class="text-muted mb-3">
                            <i class="bi bi-box-seam me-1"></i>Товары
                        </h6>
                        <div class="card border-0 shadow-sm">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50%">Товар</th>
                                            <th>Цена</th>
                                            <th>Кол-во</th>
                                            <th class="text-end">Сумма</th>
                                        </tr>
                                    </thead>
                                    <tbody id="orderModalItems">
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="3" class="text-end">Итого:</th>
                                            <th class="text-end text-success" id="orderModalTotal"></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i>Закрыть
                        </button>
                        <button type="button" class="btn btn-success" id="btnCompleteOrder">
                            <i class="bi bi-check-lg me-1"></i>Завершить
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="btnCancelOrder">
                            <i class="bi bi-x-lg me-1"></i>Отменить
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            let currentOrderId = null;
            let currentOrderStatus = null;
            const orders = ' . json_encode($orders, JSON_UNESCAPED_UNICODE) . ';
            
            // Функция для получения даты
            function getOrderDate(order) {
                return order.created_at || order.date || null;
            }
            
            // Функция форматирования даты
            function formatDate(dateStr) {
                if (!dateStr) return "—";
                const date = new Date(dateStr);
                if (isNaN(date.getTime())) return "—";
                return date.toLocaleString("ru-RU", {
                    day: "2-digit",
                    month: "2-digit",
                    year: "numeric",
                    hour: "2-digit",
                    minute: "2-digit"
                });
            }
            
            // Открытие модального окна
            document.querySelectorAll(".btn-view-order").forEach(btn => {
                btn.addEventListener("click", function() {
                    const orderId = this.dataset.id;
                    const order = orders.find(o => o.id === orderId);
                    if (!order) return;
                    
                    currentOrderId = orderId;
                    currentOrderStatus = order.status;
                    
                    document.getElementById("orderModalId").textContent = orderId;
                    document.getElementById("orderModalFio").textContent = order.fio || "—";
                    document.getElementById("orderModalEmail").textContent = order.email || "—";
                    document.getElementById("orderModalPhone").textContent = order.phone || "—";
                    document.getElementById("orderModalAddress").textContent = order.address || "—";
                    document.getElementById("orderModalDate").textContent = formatDate(getOrderDate(order));
                    document.getElementById("orderModalPayment").textContent = order.payment === "cash" ? "Наличными" : "Картой";
                    
                    const statusMap = {
                        "new": {text: "Новый", class: "bg-primary"},
                        "processing": {text: "В обработке", class: "bg-warning text-dark"},
                        "completed": {text: "Выполнен", class: "bg-success"},
                        "cancelled": {text: "Отменён", class: "bg-danger"}
                    };
                    const status = statusMap[order.status] || {text: order.status, class: "bg-secondary"};
                    document.getElementById("orderModalStatus").innerHTML = `<span class="badge ${status.class}">${status.text}</span>`;
                    
                    // Товары
                    const itemsHtml = order.items.map(item => `
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="${item.image || "/assets/img/no-image.jpg"}" alt="" width="40" height="40" class="rounded me-2" style="object-fit: cover;">
                                    <span>${item.name}</span>
                                </div>
                            </td>
                            <td>${new Intl.NumberFormat("ru-RU").format(item.price)} ₽</td>
                            <td>× ${item.quantity}</td>
                            <td class="text-end">${new Intl.NumberFormat("ru-RU").format(item.price * item.quantity)} ₽</td>
                        </tr>
                    `).join("");
                    document.getElementById("orderModalItems").innerHTML = itemsHtml;
                    document.getElementById("orderModalTotal").textContent = new Intl.NumberFormat("ru-RU").format(order.total) + " ₽";
                    
                    // Показать/скрыть кнопки действий
                    const btnComplete = document.getElementById("btnCompleteOrder");
                    const btnCancel = document.getElementById("btnCancelOrder");
                    
                    if (order.status === "completed" || order.status === "cancelled") {
                        btnComplete.style.display = "none";
                        btnCancel.style.display = "none";
                    } else {
                        btnComplete.style.display = "";
                        btnCancel.style.display = "";
                    }
                    
                    const modal = new bootstrap.Modal(document.getElementById("orderModal"));
                    modal.show();
                });
            });
            
            // Завершение заказа
            document.getElementById("btnCompleteOrder").addEventListener("click", function() {
                if (!currentOrderId) return;
                updateOrderStatus(currentOrderId, "completed");
            });
            
            // Отмена заказа
            document.getElementById("btnCancelOrder").addEventListener("click", function() {
                if (!currentOrderId) return;
                if (confirm("Вы уверены, что хотите отменить этот заказ?")) {
                    updateOrderStatus(currentOrderId, "cancelled");
                }
            });
            
            // Кнопки в таблице
            document.querySelectorAll(".btn-complete-order").forEach(btn => {
                btn.addEventListener("click", function(e) {
                    e.stopPropagation();
                    updateOrderStatus(this.dataset.id, "completed");
                });
            });
            
            document.querySelectorAll(".btn-cancel-order").forEach(btn => {
                btn.addEventListener("click", function(e) {
                    e.stopPropagation();
                    if (confirm("Вы уверены, что хотите отменить этот заказ?")) {
                        updateOrderStatus(this.dataset.id, "cancelled");
                    }
                });
            });
            
            async function updateOrderStatus(orderId, status) {
                try {
                    const response = await fetch("/api/cart/update-status", {
                        method: "POST",
                        headers: {"Content-Type": "application/json"},
                        body: JSON.stringify({id: orderId, status: status})
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        location.reload();
                    } else {
                        alert(result.error || "Ошибка");
                    }
                } catch (e) {
                    alert("Ошибка соединения");
                }
            }
        });
        </script>';
        
        return parent::getTemplate($content);
    }
    
    /**
     * Рендер списка заказов
     */
    private static function renderOrdersList(array $orders, bool $showActions): string
    {
        if (empty($orders)) {
            return '
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>
                <h5 class="text-muted">Заказов нет</h5>
            </div>';
        }
        
        $html = '';
        
        foreach ($orders as $order) {
            $id = $order['id'] ?? '—';
            $fio = htmlspecialchars($order['fio'] ?? '—');
            $phone = htmlspecialchars($order['phone'] ?? '—');
            $total = number_format($order['total'] ?? 0, 0, '.', ' ');
            $status = $order['status'] ?? 'new';
            
            // Пробуем обе даты
            $dateStr = $order['created_at'] ?? $order['date'] ?? '';
            $createdAt = !empty($dateStr) ? date('d.m.Y H:i', strtotime($dateStr)) : '—';
            
            $statusBadge = match($status) {
                'new' => '<span class="badge bg-primary">Новый</span>',
                'processing' => '<span class="badge bg-warning text-dark">В обработке</span>',
                'completed' => '<span class="badge bg-success">Выполнен</span>',
                'cancelled' => '<span class="badge bg-danger">Отменён</span>',
                default => '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>'
            };
            
            $itemsCount = count($order['items'] ?? []);
            
            // Кнопки действий
            $actions = '';
            if ($showActions && ($status === 'new' || $status === 'processing')) {
                $actions = '
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-success btn-complete-order" data-id="' . $id . '" title="Завершить">
                        <i class="bi bi-check-lg"></i>
                    </button>
                    <button class="btn btn-danger btn-cancel-order" data-id="' . $id . '" title="Отменить">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>';
            }
            
            $html .= '
            <div class="card mb-3 shadow-sm order-card" style="cursor: pointer;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-1">
                            <button class="btn btn-link text-decoration-none fw-bold btn-view-order" data-id="' . $id . '">
                                #' . substr($id, -8) . '
                            </button>
                        </div>
                        <div class="col-md-3">
                            <div class="fw-medium">' . $fio . '</div>
                            <small class="text-muted">' . $phone . '</small>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted d-block">Товаров</small>
                            <strong>' . $itemsCount . '</strong>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted d-block">Сумма</small>
                            <strong class="text-success">' . $total . ' ₽</strong>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted d-block">Статус</small>
                            <div>' . $statusBadge . '</div>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted d-block">Дата</small>
                            <small>' . $createdAt . '</small>
                        </div>
                    </div>
                </div>
            </div>';
        }
        
        return $html;
    }
    
    /**
     * Рендер страницы пользователей
     */
    public static function renderUsers(array $users): string
    {
        $usersHtml = '';
        
        if (empty($users)) {
            $usersHtml = '
            <tr>
                <td colspan="4" class="text-center py-4 text-muted">
                    <i class="bi bi-people fs-1 d-block mb-2"></i>
                    Пользователей пока нет
                </td>
            </tr>';
        } else {
            foreach ($users as $user) {
                $id = $user['id'] ?? '—';
                $name = htmlspecialchars($user['name'] ?? '—');
                $email = htmlspecialchars($user['email'] ?? '—');
                $createdAt = date('d.m.Y H:i', strtotime($user['created_at'] ?? time()));
                
                $usersHtml .= '
                <tr>
                    <td>' . $id . '</td>
                    <td>' . $name . '</td>
                    <td>' . $email . '</td>
                    <td>' . $createdAt . '</td>
                </tr>';
            }
        }
        
        $content = '
        <div class="container py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0">Пользователи</h1>
                <a href="/admin" class="btn btn-outline-secondary">Назад в панель</a>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Имя</th>
                                    <th>Email</th>
                                    <th>Дата регистрации</th>
                                </tr>
                            </thead>
                            <tbody>
                                ' . $usersHtml . '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>';
        
        return parent::getTemplate($content);
    }
}
