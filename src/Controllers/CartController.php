<?php
namespace App\Controllers;

use App\Models\Cart;
use App\Views\CartTemplate;

class CartController
{
    /**
     * Отображение страницы корзины
     */
    public function get(): string
    {
        return CartTemplate::render();
    }

    /**
     * API: Добавить товар в корзину
     * @param array $data ['id', 'name', 'price', 'image', 'quantity']
     */
    public function add(?array $data): string
    {
        if (!$data || !isset($data['id'], $data['name'], $data['price'])) {
            http_response_code(400);
            return json_encode(['success' => false, 'error' => 'Некорректные данные']);
        }

        $result = Cart::add(
            (int)$data['id'],
            (string)$data['name'],
            (float)$data['price'],
            $data['image'] ?? '',
            (int)($data['quantity'] ?? 1)
        );

        return json_encode([
            'success' => $result,
            'count' => Cart::getCount(),
            'total' => Cart::getTotal()
        ]);
    }

    /**
     * API: Обновить количество товара
     * @param array $data ['id', 'quantity']
     */
    public function update(?array $data): string
    {
        if (!$data || !isset($data['id'], $data['quantity'])) {
            http_response_code(400);
            return json_encode(['success' => false, 'error' => 'Некорректные данные']);
        }

        $result = Cart::updateQuantity(
            (int)$data['id'],
            (int)$data['quantity']
        );

        return json_encode([
            'success' => $result,
            'count' => Cart::getCount(),
            'total' => Cart::getTotal()
        ]);
    }

    /**
     * API: Удалить товар из корзины
     * @param array $data ['id']
     */
    public function remove(?array $data): string
    {
        if (!$data || !isset($data['id'])) {
            http_response_code(400);
            return json_encode(['success' => false, 'error' => 'Некорректные данные']);
        }

        $result = Cart::remove((int)$data['id']);

        return json_encode([
            'success' => $result,
            'count' => Cart::getCount(),
            'total' => Cart::getTotal()
        ]);
    }

    /**
     * API: Очистить корзину
     */
    public function clear(): string
    {
        $result = Cart::clear();

        return json_encode([
            'success' => $result,
            'count' => 0,
            'total' => 0
        ]);
    }
}