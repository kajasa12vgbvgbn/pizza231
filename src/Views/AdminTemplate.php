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
                <td colspan="6" class="text-center py-4 text-muted">
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
                $createdAt = date('d.m.Y H:i', strtotime($order['created_at'] ?? time()));
                
                $statusBadge = match($status) {
                    'new' => '<span class="badge bg-primary">Новый</span>',
                    'processing' => '<span class="badge bg-warning">В обработке</span>',
                    'completed' => '<span class="badge bg-success">Выполнен</span>',
                    'cancelled' => '<span class="badge bg-danger">Отменён</span>',
                    default => '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>'
                };
                
                $itemsCount = count($order['items'] ?? []);
                
                $ordersHtml .= '
                <tr>
                    <td>#' . $id . '</td>
                    <td>' . $fio . '<br><small class="text-muted">' . $phone . '</small></td>
                    <td>' . $itemsCount . ' товар(ов)</td>
                    <td class="fw-bold">' . $total . ' ₽</td>
                    <td>' . $statusBadge . '</td>
                    <td>' . $createdAt . '</td>
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
                                </tr>
                            </thead>
                            <tbody>
                                ' . $ordersHtml . '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>';
        
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
