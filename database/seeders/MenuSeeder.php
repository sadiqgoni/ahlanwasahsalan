<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- Staff (they sign in with the username) ----------
        User::updateOrCreate(
            ['username' => 'owner'],
            [
                'name' => 'Hajiya',
                'password' => Hash::make('12345678'),
                'role' => 'owner',
                'pin' => Hash::make('1234'),
                'is_active' => true,
            ],
        );

        User::updateOrCreate(
            ['username' => 'cashier'],
            [
                'name' => 'Cashier 1',
                'password' => Hash::make('12345678'),
                'role' => 'cashier',
                'is_active' => true,
            ],
        );

        User::updateOrCreate(
            ['username' => 'accountant'],
            [
                'name' => 'Accountant',
                'password' => Hash::make('12345678'),
                'role' => 'accountant',
                'is_active' => true,
            ],
        );

        // ---------- Menu: sections → items → add-ons ----------
        $menu = [
            'Rice' => ['color' => '#f59e0b', 'items' => [
                ['name' => 'Rice & Beans with Oil & Pepper', 'price' => 1000, 'options' => [
                    ['group' => 'Extras', 'name' => 'Salad', 'price' => 200],
                    ['group' => 'Extras', 'name' => 'Fried Meat', 'price' => 300],
                ]],
                ['name' => 'Jollof Rice', 'price' => 1200, 'options' => [
                    ['group' => 'Extras', 'name' => 'Salad', 'price' => 200],
                    ['group' => 'Protein', 'name' => 'Chicken', 'price' => 700],
                    ['group' => 'Protein', 'name' => 'Beef', 'price' => 300],
                ]],
                ['name' => 'Fried Rice', 'price' => 1200, 'options' => [
                    ['group' => 'Extras', 'name' => 'Salad', 'price' => 200],
                    ['group' => 'Protein', 'name' => 'Chicken', 'price' => 700],
                ]],
                ['name' => 'White Rice & Stew', 'price' => 900, 'options' => [
                    ['group' => 'Protein', 'name' => 'Beef (1pc)', 'price' => 300],
                    ['group' => 'Protein', 'name' => 'Beef (2pc)', 'price' => 600],
                ]],
            ]],
            'Tuwo' => ['color' => '#dc2626', 'items' => [
                ['name' => 'Tuwo Semovita + Miyan Taushe', 'price' => 1000, 'options' => [
                    ['group' => 'Meat', 'name' => 'Extra Meat (1pc)', 'price' => 200],
                    ['group' => 'Meat', 'name' => 'Extra Meat (2pc)', 'price' => 400],
                ]],
                ['name' => 'Tuwo Shinkafa + Miyan Kuka', 'price' => 1000, 'options' => [
                    ['group' => 'Meat', 'name' => 'Extra Meat (1pc)', 'price' => 200],
                    ['group' => 'Meat', 'name' => 'Extra Meat (2pc)', 'price' => 400],
                ]],
                ['name' => 'Dan Wake', 'price' => 500, 'options' => [
                    ['group' => 'Extras', 'name' => 'Egg', 'price' => 200],
                    ['group' => 'Extras', 'name' => 'Yaji (Suya Spice)', 'price' => 50],
                ]],
            ]],
            'Tea & Drinks' => ['color' => '#2563eb', 'items' => [
                ['name' => 'Tea', 'price' => 500, 'options' => [
                    ['group' => 'Extras', 'name' => 'Lemon', 'price' => 100],
                    ['group' => 'Extras', 'name' => 'Milk', 'price' => 150],
                ]],
                ['name' => 'Fura da Nono', 'price' => 600, 'options' => []],
                ['name' => 'Zobo', 'price' => 300, 'options' => []],
                ['name' => 'Bottled Water', 'price' => 200, 'options' => []],
                ['name' => 'Soft Drink', 'price' => 400, 'options' => []],
            ]],
            'Masa & Chips' => ['color' => '#7c3aed', 'items' => [
                ['name' => 'Masa (5pcs)', 'price' => 500, 'options' => [
                    ['group' => 'Extras', 'name' => 'Suya', 'price' => 500],
                    ['group' => 'Extras', 'name' => 'Yaji (Suya Spice)', 'price' => 50],
                    ['group' => 'Extras', 'name' => 'Honey', 'price' => 200],
                ]],
                ['name' => 'Chips (Fries)', 'price' => 800, 'options' => [
                    ['group' => 'Extras', 'name' => 'Egg', 'price' => 200],
                    ['group' => 'Extras', 'name' => 'Chicken', 'price' => 700],
                ]],
                ['name' => 'Suya (Beef)', 'price' => 1000, 'options' => [
                    ['group' => 'Extras', 'name' => 'Extra Yaji', 'price' => 0],
                    ['group' => 'Extras', 'name' => 'Onions & Cabbage', 'price' => 100],
                ]],
            ]],
        ];

        $sort = 0;

        foreach ($menu as $sectionName => $data) {
            $category = Category::updateOrCreate(
                ['name' => $sectionName],
                ['color' => $data['color'], 'sort' => $sort++, 'is_active' => true],
            );

            foreach ($data['items'] as $j => $item) {
                $product = $category->products()->updateOrCreate(
                    ['name' => $item['name']],
                    ['price' => $item['price'], 'sort' => $j, 'is_active' => true],
                );

                foreach ($item['options'] as $k => $option) {
                    $product->options()->updateOrCreate(
                        ['name' => $option['name'], 'group' => $option['group']],
                        ['price' => $option['price'], 'sort' => $k, 'is_active' => true],
                    );
                }
            }
        }
    }
}
