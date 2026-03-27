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
     * Рендер страницы заказов
     */
    public static function renderOrders(array $orders): string
    {
        $ordersHtml = '';
        
        if (empty($orders)) {
            $ordersHtml = '
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    Заказов пока нет
                </td>
            </tr>';
        } else {
            foreach ($orders as $order) {
                $id = $order['id'] ?? '—';
                $fio = htmlspecialchars($order['fio'] ?? '—');
                $phone = htmlspecialchars($order['phone'] ?? '—');
                $total = number_format($order['total'] ?? 0, 0, '.', ' ');
                $status = $order['status'] ?? 'new';
                $createdAt = !empty($order['created_at']) ? date('d.m.Y H:i', strtotime($order['created_at'])) : '—';
                
                $statusBadge = match($status) {
                    'new' => '<span class="badge bg-primary">Новый</span>',
                    'processing' => '<span class="badge bg-warning">В обработке</span>',
                    'completed' => '<span class="badge bg-success">Выполнен</span>',
                    'cancelled' => '<span class="badge bg-danger">Отменён</span>',
                    default => '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>'
                };
                
                $itemsCount = count($order['items'] ?? []);
                
                // Кнопки действий
                $actions = '';
                if ($status === 'new' || $status === 'processing') {
                    $actions .= '<button class="btn btn-sm btn-success me-1 btn-complete-order" data-id="' . $id . '" title="Завершить"><i class="bi bi-check-lg"></i></button>';
                }
                if ($status !== 'completed' && $status !== 'cancelled') {
                    $actions .= '<button class="btn btn-sm btn-danger btn-cancel-order" data-id="' . $id . '" title="Отменить"><i class="bi bi-x-lg"></i></button>';
                }
                
                $ordersHtml .= '
                <tr>
                    <td>
                        <button class="btn btn-link text-decoration-none btn-view-order" data-id="' . $id . '">
                            #' . $id . '
                        </button>
                    </td>
                    <td>' . $fio . '<br><small class="text-muted">' . $phone . '</small></td>
                    <td>' . $itemsCount . ' товар(ов)</td>
                    <td class="fw-bold">' . $total . ' ₽</td>
                    <td>' . $statusBadge . '</td>
                    <td>' . $createdAt . '</td>
                    <td>' . $actions . '</td>
                </tr>';
            }
        }
        
        $content = '
        <div class="container py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0">Заказы</h1>
                <a href="/admin" class="btn btn-outline-secondary">Назад в панель</a>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Клиент</th>
                                    <th>Товары</th>
                                    <th>Сумма</th>
                                    <th>Статус</th>
                                    <th>Дата</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                ' . $ordersHtml . '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Модальное окно просмотра заказа -->
        <div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Заказ #<span id="orderModalId"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Информация о клиенте</h6>
                                <p class="mb-1"><strong>ФИО:</strong> <span id="orderModalFio"></span></p>
                                <p class="mb-1"><strong>Email:</strong> <span id="orderModalEmail"></span></p>
                                <p class="mb-1"><strong>Телефон:</strong> <span id="orderModalPhone"></span></p>
                                <p class="mb-0"><strong>Адрес:</strong> <span id="orderModalAddress"></span></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Информация о заказе</h6>
                                <p class="mb-1"><strong>Дата:</strong> <span id="orderModalDate"></span></p>
                                <p class="mb-1"><strong>Статус:</strong> <span id="orderModalStatus"></span></p>
                                <p class="mb-0"><strong>Оплата:</strong> <span id="orderModalPayment"></span></p>
                            </div>
                        </div>
                        <h6 class="text-muted mb-2">Товары</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Товар</th>
                                        <th>Цена</th>
                                        <th>Кол-во</th>
                                        <th>Сумма</th>
                                    </tr>
                                </thead>
                                <tbody id="orderModalItems">
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">Итого:</th>
                                        <th class="fw-bold" id="orderModalTotal"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                        <button type="button" class="btn btn-success" id="btnCompleteOrder">Завершить заказ</button>
                        <button type="button" class="btn btn-danger" id="btnCancelOrder">Отменить заказ</button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            let currentOrderId = null;
            let currentOrderStatus = null;
            const orders = ' . json_encode($orders, JSON_UNESCAPED_UNICODE) . ';
            
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
                    document.getElementById("orderModalDate").textContent = order.created_at ? new Date(order.created_at).toLocaleString("ru-RU") : "—";
                    document.getElementById("orderModalPayment").textContent = order.payment === "cash" ? "Наличными" : "Картой";
                    
                    const statusText = {
                        "new": "<span class=\\"badge bg-primary\\">Новый</span>",
                        "processing": "<span class=\\"badge bg-warning\\">В обработке</span>",
                        "completed": "<span class=\\"badge bg-success\\">Выполнен</span>",
                        "cancelled": "<span class=\\"badge bg-danger\\">Отменён</span>"
                    };
                    document.getElementById("orderModalStatus").innerHTML = statusText[order.status] || order.status;
                    
                    // Товары
                    const itemsHtml = order.items.map(item => `
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="${item.image || "/assets/img/no-image.jpg"}" alt="" width="40" height="40" class="me-2" style="object-fit: cover;">
                                    ${item.name}
                                </div>
                            </td>
                            <td>${new Intl.NumberFormat("ru-RU").format(item.price)} ₽</td>
                            <td>${item.quantity}</td>
                            <td>${new Intl.NumberFormat("ru-RU").format(item.price * item.quantity)} ₽</td>
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
                btn.addEventListener("click", function() {
                    updateOrderStatus(this.dataset.id, "completed");
                });
            });
            
            document.querySelectorAll(".btn-cancel-order").forEach(btn => {
                btn.addEventListener("click", function() {
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
