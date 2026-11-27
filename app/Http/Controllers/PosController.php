<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\InventoryWarehouse;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PosShift;
use App\Models\Product;
use App\Models\ProductModifier;
use App\Models\ProductRecipe;
use App\Models\RecipeDeduction;
use App\Models\RedeemCode;
use App\Models\StockCard;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WarehouseStock;
use App\Services\MidtransQrisService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PosController extends Controller
{
    /**
     * Display the Barista Touchscreen Web POS Interface.
     */
    public function index(Request $request)
    {
        $categories = [
            [
                'id' => 'espresso',
                'name' => 'Espresso Based',
                'icon' => 'zap',
                'badge' => '8 Items',
            ],
            [
                'id' => 'manual_brew',
                'name' => 'Manual Brew',
                'icon' => 'droplets',
                'badge' => '5 Single Origin',
            ],
            [
                'id' => 'tea_bev',
                'name' => 'Tea & Beverages',
                'icon' => 'sparkles',
                'badge' => '5 Items',
            ],
            [
                'id' => 'food',
                'name' => 'Artisan Pastry',
                'icon' => 'croissant',
                'badge' => '5 Items',
            ],
            [
                'id' => 'retail',
                'name' => 'Roasted Beans',
                'icon' => 'package',
                'badge' => '5 Lots',
            ],
            [
                'id' => 'merch',
                'name' => 'Merchandise',
                'icon' => 'shopping-bag',
                'badge' => '4 Gear',
            ],
        ];

        $products = [
            // Espresso Based
            [
                'id' => 'PRD-ESP-01',
                'image' => '/images/pos/prd-esp-01-espresso.png',
                'name' => 'Espresso Single Origin',
                'category' => 'espresso',
                'price' => 28000,
                'recipe_yield' => '18g Gayo Honey -> 36g Liquid Double Shot',
                'stock' => 140,
                'stock_status' => 'in_stock',
                'image_tag' => 'Single Origin Clean Crema',
                'temperature' => ['Hot'],
                'can_custom_shot' => true,
                'base_shots' => 2,
                'extras' => [
                    ['id' => 'ice_cream', 'name' => 'Vanilla Ice Cream (Affogato)', 'price' => 8000, 'category' => 'Topping'],
                    ['id' => 'vanilla_syrup', 'name' => 'Artisan Vanilla Syrup', 'price' => 5000, 'category' => 'Sirup & Flavour'],
                    ['id' => 'caramel_sauce', 'name' => 'Salted Caramel Sauce', 'price' => 5000, 'category' => 'Sirup & Flavour'],
                    ['id' => 'honey', 'name' => 'Organic Blossom Honey', 'price' => 4000, 'category' => 'Pemanis Alami'],
                ],
                'modifiers' => ['Double Shot', 'Single Origin Gayo', 'Single Origin Ijen'],
                'badge_color' => 'amber',
            ],
            [
                'id' => 'PRD-ESP-02',
                'image' => '/images/pos/prd-esp-02-latte.png',
                'name' => 'Cafe Latte Velvet',
                'category' => 'espresso',
                'price' => 38000,
                'recipe_yield' => '18g Espresso + 180ml Steamed Fresh Milk',
                'stock' => 110,
                'stock_status' => 'in_stock',
                'image_tag' => 'Microfoam Latte Art 65°C',
                'temperature' => ['Hot', 'Iced'],
                'can_custom_shot' => true,
                'base_shots' => 1,
                'extras' => [
                    ['id' => 'oat_milk', 'name' => 'Oat Milk (Oatly Barista)', 'price' => 8000, 'category' => 'Susu Alternatif'],
                    ['id' => 'almond_milk', 'name' => 'Almond Milk (Unsweetened)', 'price' => 10000, 'category' => 'Susu Alternatif'],
                    ['id' => 'soy_milk', 'name' => 'Soy Milk Organik', 'price' => 6000, 'category' => 'Susu Alternatif'],
                    ['id' => 'vanilla_syrup', 'name' => 'Artisan Vanilla Syrup', 'price' => 5000, 'category' => 'Sirup & Flavour'],
                    ['id' => 'caramel_sauce', 'name' => 'Salted Caramel Sauce', 'price' => 5000, 'category' => 'Sirup & Flavour'],
                    ['id' => 'hazelnut_syrup', 'name' => 'Roasted Hazelnut Syrup', 'price' => 5000, 'category' => 'Sirup & Flavour'],
                    ['id' => 'ice_cream', 'name' => 'Vanilla Ice Cream Scoop', 'price' => 8000, 'category' => 'Topping'],
                    ['id' => 'whipped_cream', 'name' => 'Whipped Cream', 'price' => 5000, 'category' => 'Topping'],
                ],
                'modifiers' => ['Oat Milk (+8k)', 'Almond Milk (+10k)', 'Extra Shot (+6k)', 'Vanilla Artisan (+5k)'],
                'badge_color' => 'amber',
            ],
            [
                'id' => 'PRD-ESP-03',
                'image' => '/images/pos/prd-esp-03-magic.png',
                'name' => 'Magic (Melbourne Double Ristretto)',
                'category' => 'espresso',
                'price' => 40000,
                'recipe_yield' => '20g Ristretto (1:1.5) + 130ml Milk in 160ml cup',
                'stock' => 85,
                'stock_status' => 'in_stock',
                'image_tag' => 'Bold Sweet Intensely Balanced',
                'temperature' => ['Hot'],
                'can_custom_shot' => true,
                'base_shots' => 2,
                'extras' => [
                    ['id' => 'oat_milk', 'name' => 'Oat Milk (Oatly Barista)', 'price' => 8000, 'category' => 'Susu Alternatif'],
                    ['id' => 'almond_milk', 'name' => 'Almond Milk (Unsweetened)', 'price' => 10000, 'category' => 'Susu Alternatif'],
                    ['id' => 'vanilla_syrup', 'name' => 'Artisan Vanilla Syrup', 'price' => 5000, 'category' => 'Sirup & Flavour'],
                ],
                'modifiers' => ['Standard 160ml Glass', 'Extra Shot (+6k)'],
                'badge_color' => 'amber',
            ],
            [
                'id' => 'PRD-ESP-04',
                'image' => '/images/pos/prd-esp-04-flatwhite.png',
                'name' => 'Flat White Reserve',
                'category' => 'espresso',
                'price' => 38000,
                'recipe_yield' => 'Double Ristretto + Thin Microfoam',
                'stock' => 95,
                'stock_status' => 'in_stock',
                'image_tag' => 'Silky Glossy Surface',
                'temperature' => ['Hot', 'Iced'],
                'can_custom_shot' => true,
                'base_shots' => 2,
                'extras' => [
                    ['id' => 'oat_milk', 'name' => 'Oat Milk (Oatly Barista)', 'price' => 8000, 'category' => 'Susu Alternatif'],
                    ['id' => 'almond_milk', 'name' => 'Almond Milk (Unsweetened)', 'price' => 10000, 'category' => 'Susu Alternatif'],
                    ['id' => 'caramel_sauce', 'name' => 'Salted Caramel Sauce', 'price' => 5000, 'category' => 'Sirup & Flavour'],
                    ['id' => 'hazelnut_syrup', 'name' => 'Roasted Hazelnut Syrup', 'price' => 5000, 'category' => 'Sirup & Flavour'],
                ],
                'modifiers' => ['Oat Milk (+8k)', 'Extra Shot (+6k)'],
                'badge_color' => 'amber',
            ],
            [
                'id' => 'PRD-ESP-05',
                'image' => '/images/pos/prd-esp-05-americano.png',
                'name' => 'Americano / Long Black',
                'category' => 'espresso',
                'price' => 32000,
                'recipe_yield' => 'Double Espresso + 150ml Hot/Cold Water (TDS 45ppm)',
                'stock' => 200,
                'stock_status' => 'in_stock',
                'image_tag' => 'Bright Acidity & Clean Aftertaste',
                'temperature' => ['Hot', 'Iced'],
                'can_custom_shot' => true,
                'base_shots' => 2,
                'extras' => [
                    ['id' => 'vanilla_syrup', 'name' => 'Artisan Vanilla Syrup', 'price' => 5000, 'category' => 'Sirup & Flavour'],
                    ['id' => 'honey', 'name' => 'Organic Blossom Honey', 'price' => 4000, 'category' => 'Pemanis Alami'],
                    ['id' => 'ice_cream', 'name' => 'Vanilla Ice Cream Float', 'price' => 8000, 'category' => 'Topping'],
                ],
                'modifiers' => ['Extra Shot (+6k)', 'Less Water', 'Light Sweet (+3k)'],
                'badge_color' => 'amber',
            ],
            [
                'id' => 'PRD-ESP-06',
                'image' => '/images/pos/prd-esp-06-dirty.png',
                'name' => 'Dirty Coffee Cold Foam',
                'category' => 'espresso',
                'price' => 42000,
                'recipe_yield' => 'Hot Double Ristretto layered over Iced Heavy Sweet Cream',
                'stock' => 45,
                'stock_status' => 'low_stock',
                'image_tag' => 'Stunning Temperature Contrast Layer',
                'temperature' => ['Iced Only'],
                'can_custom_shot' => true,
                'base_shots' => 2,
                'extras' => [
                    ['id' => 'oat_cream', 'name' => 'Oat Cream Heavy Base', 'price' => 10000, 'category' => 'Susu Alternatif'],
                    ['id' => 'vanilla_syrup', 'name' => 'Artisan Vanilla Syrup', 'price' => 5000, 'category' => 'Sirup & Flavour'],
                    ['id' => 'ice_cream', 'name' => 'Vanilla Ice Cream Scoop', 'price' => 8000, 'category' => 'Topping'],
                ],
                'modifiers' => ['Oat Cream (+10k)', 'Extra Ristretto (+6k)'],
                'badge_color' => 'orange',
            ],
            [
                'id' => 'PRD-ESP-07',
                'image' => '/images/pos/prd-esp-07-piccolo.png',
                'name' => 'Piccolo Latte 100ml',
                'category' => 'espresso',
                'price' => 35000,
                'recipe_yield' => 'Single Ristretto + 70ml Steamed Milk',
                'stock' => 70,
                'stock_status' => 'in_stock',
                'image_tag' => 'Punchy Coffee with Sweet Finish',
                'temperature' => ['Hot'],
                'can_custom_shot' => true,
                'base_shots' => 1,
                'extras' => [
                    ['id' => 'oat_milk', 'name' => 'Oat Milk (Oatly Barista)', 'price' => 8000, 'category' => 'Susu Alternatif'],
                    ['id' => 'vanilla_syrup', 'name' => 'Artisan Vanilla Syrup', 'price' => 5000, 'category' => 'Sirup & Flavour'],
                ],
                'modifiers' => ['Oat Milk (+8k)'],
                'badge_color' => 'amber',
            ],
            [
                'id' => 'PRD-ESP-08',
                'image' => '/images/pos/prd-esp-08-spanishlatte.png',
                'name' => 'Spanish Latte (Leche Condensada)',
                'category' => 'espresso',
                'price' => 42000,
                'recipe_yield' => 'Double Espresso + Artisan Sweet Milk Base',
                'stock' => 60,
                'stock_status' => 'in_stock',
                'image_tag' => 'Creamy Sweet Comfort Coffee',
                'temperature' => ['Hot', 'Iced'],
                'can_custom_shot' => true,
                'base_shots' => 2,
                'extras' => [
                    ['id' => 'oat_milk', 'name' => 'Oat Milk (Oatly Barista)', 'price' => 8000, 'category' => 'Susu Alternatif'],
                    ['id' => 'almond_milk', 'name' => 'Almond Milk (Unsweetened)', 'price' => 10000, 'category' => 'Susu Alternatif'],
                    ['id' => 'caramel_sauce', 'name' => 'Salted Caramel Drizzle', 'price' => 5000, 'category' => 'Sirup & Flavour'],
                    ['id' => 'whipped_cream', 'name' => 'Whipped Cream', 'price' => 5000, 'category' => 'Topping'],
                ],
                'modifiers' => ['Less Sweet', 'Oat Milk (+8k)', 'Extra Shot (+6k)'],
                'badge_color' => 'amber',
            ],

            // Manual Brew
            [
                'id' => 'PRD-MB-01',
                'image' => '/images/pos/prd-mb-01-v60gayo.png',
                'name' => 'V60 - Aceh Gayo Honey Anaerobic',
                'category' => 'manual_brew',
                'price' => 45000,
                'recipe_yield' => '15g Dose -> 225ml Brew (1:15), 92°C, 2m 45s',
                'stock' => 35,
                'stock_status' => 'in_stock',
                'image_tag' => 'Notes: Peach, Bergamot, Wildflower Honey',
                'temperature' => ['Hot', 'Iced'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'ice_rock', 'name' => 'Hand Carved Ice Rock (Es Kristal)', 'price' => 3000, 'category' => 'Penyajian'],
                    ['id' => 'ice_drip', 'name' => 'Nitrogen Chilled Finish', 'price' => 5000, 'category' => 'Penyajian'],
                ],
                'modifiers' => ['Standard Ratio 1:15', 'Concentrated 1:13', 'Ice Drip Finish (+5k)'],
                'badge_color' => 'emerald',
            ],
            [
                'id' => 'PRD-MB-02',
                'image' => '/images/pos/prd-mb-02-aeropress.png',
                'name' => 'Aeropress - Ijen Gesha Carbonic',
                'category' => 'manual_brew',
                'price' => 58000,
                'recipe_yield' => '18g Inverted -> 200ml, 88°C, Bypass 30ml',
                'stock' => 18,
                'stock_status' => 'low_stock',
                'image_tag' => 'Notes: Jasmine, Papaya, Sparkling Acidity',
                'temperature' => ['Hot', 'Iced'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'ice_rock', 'name' => 'Hand Carved Ice Rock (Es Kristal)', 'price' => 3000, 'category' => 'Penyajian'],
                ],
                'modifiers' => ['Inverted Method', 'Paper Filter Dual'],
                'badge_color' => 'emerald',
            ],
            [
                'id' => 'PRD-MB-03',
                'image' => '/images/pos/prd-mb-03-kalita.png',
                'name' => 'Kalita Wave - Toraja Sapan Washed',
                'category' => 'manual_brew',
                'price' => 42000,
                'recipe_yield' => '15g Flat Bed -> 240ml (1:16), 93°C, 3m 10s',
                'stock' => 40,
                'stock_status' => 'in_stock',
                'image_tag' => 'Notes: Brown Sugar, Ripe Apple, Clean Cedar',
                'temperature' => ['Hot', 'Iced'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'ice_rock', 'name' => 'Hand Carved Ice Rock (Es Kristal)', 'price' => 3000, 'category' => 'Penyajian'],
                ],
                'modifiers' => ['Standard Flat Bed'],
                'badge_color' => 'emerald',
            ],
            [
                'id' => 'PRD-MB-04',
                'image' => '/images/pos/prd-mb-04-colddrip.png',
                'name' => 'Cold Drip Reserve Ethiopia 18H',
                'category' => 'manual_brew',
                'price' => 48000,
                'recipe_yield' => 'Slow Drip 1 drop/sec for 18 Hours, Nitrogen Chilled',
                'stock' => 12,
                'stock_status' => 'low_stock',
                'image_tag' => 'Notes: Blueberry, Dark Chocolate, Winey Body',
                'temperature' => ['Iced Only'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'extra_ice_rock', 'name' => 'Double Hand Carved Ice Rock', 'price' => 3000, 'category' => 'Penyajian'],
                ],
                'modifiers' => ['Served with Hand Carved Ice Rock'],
                'badge_color' => 'emerald',
            ],
            [
                'id' => 'PRD-MB-05',
                'image' => '/images/pos/prd-mb-05-syphon.png',
                'name' => 'Syphon Brew - Panama Boquete',
                'category' => 'manual_brew',
                'price' => 65000,
                'recipe_yield' => 'Halogen Beam Syphon Vacuum Extraction 240ml',
                'stock' => 8,
                'stock_status' => 'low_stock',
                'image_tag' => 'Tea-like Clarity & Explosive Aroma',
                'temperature' => ['Hot Only'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'extra_tasting_glass', 'name' => 'Artisan Sensory Tasting Glass Duo', 'price' => 5000, 'category' => 'Penyajian'],
                ],
                'modifiers' => ['Table-side Visual Extraction'],
                'badge_color' => 'emerald',
            ],

            // Tea & Beverages
            [
                'id' => 'PRD-TEA-01',
                'image' => '/images/pos/prd-tea-01-matcha.png',
                'name' => 'Kyoto Uji Ceremonial Matcha Latte',
                'category' => 'tea_bev',
                'price' => 44000,
                'recipe_yield' => '4g Ceremonial Grade Uji Matcha + 180ml Fresh Milk',
                'stock' => 50,
                'stock_status' => 'in_stock',
                'image_tag' => 'Vibrant Emerald Green, Umami & Sweet',
                'temperature' => ['Hot', 'Iced'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'oat_milk', 'name' => 'Oat Milk (Oatly Barista)', 'price' => 8000, 'category' => 'Susu Alternatif'],
                    ['id' => 'almond_milk', 'name' => 'Almond Milk (Unsweetened)', 'price' => 10000, 'category' => 'Susu Alternatif'],
                    ['id' => 'vanilla_syrup', 'name' => 'Artisan Vanilla Syrup', 'price' => 5000, 'category' => 'Sirup & Flavour'],
                    ['id' => 'whipped_cream', 'name' => 'Whipped Cream', 'price' => 5000, 'category' => 'Topping'],
                    ['id' => 'ice_cream', 'name' => 'Matcha Ice Cream Scoop', 'price' => 8000, 'category' => 'Topping'],
                ],
                'modifiers' => ['Oat Milk (+8k)', 'Less Sweet', 'No Sugar'],
                'badge_color' => 'teal',
            ],
            [
                'id' => 'PRD-TEA-02',
                'image' => '/images/pos/prd-tea-02-cascara.png',
                'name' => 'Cascara Sparkling Citrus Tonic',
                'category' => 'tea_bev',
                'price' => 38000,
                'recipe_yield' => 'Dried Coffee Cherry Brew + Fever Tree Tonic + Dehydrated Orange',
                'stock' => 60,
                'stock_status' => 'in_stock',
                'image_tag' => 'Refreshing Fizzy Antioxidant Elixir',
                'temperature' => ['Iced Only'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'citrus_slice', 'name' => 'Extra Dehydrated Orange Slice', 'price' => 3000, 'category' => 'Garnish'],
                    ['id' => 'honey', 'name' => 'Organic Blossom Honey', 'price' => 4000, 'category' => 'Pemanis Alami'],
                ],
                'modifiers' => ['Extra Orange Slice'],
                'badge_color' => 'teal',
            ],
            [
                'id' => 'PRD-TEA-03',
                'image' => '/images/pos/prd-tea-03-chocolate.png',
                'name' => 'Valrhona Noir 70% Dark Chocolate',
                'category' => 'tea_bev',
                'price' => 42000,
                'recipe_yield' => 'French Valrhona Guanaja Ganache + Steamed Whole Milk',
                'stock' => 45,
                'stock_status' => 'in_stock',
                'image_tag' => 'Decadent Bittersweet Velvety Finish',
                'temperature' => ['Hot', 'Iced'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'marshmallow', 'name' => 'Marshmallow Torch', 'price' => 5000, 'category' => 'Topping'],
                    ['id' => 'whipped_cream', 'name' => 'Whipped Cream', 'price' => 5000, 'category' => 'Topping'],
                    ['id' => 'oat_milk', 'name' => 'Oat Milk (Oatly Barista)', 'price' => 8000, 'category' => 'Susu Alternatif'],
                    ['id' => 'caramel_sauce', 'name' => 'Salted Caramel Sauce', 'price' => 5000, 'category' => 'Sirup & Flavour'],
                ],
                'modifiers' => ['Marshmallow Torch (+5k)', 'Oat Milk (+8k)'],
                'badge_color' => 'teal',
            ],
            [
                'id' => 'PRD-TEA-04',
                'image' => '/images/pos/prd-tea-04-earlgrey.png',
                'name' => 'Artisan Earl Grey Blue Lavender',
                'category' => 'tea_bev',
                'price' => 34000,
                'recipe_yield' => 'Full Leaf Ceylon with Bergamot Oil & Blue Cornflower',
                'stock' => 80,
                'stock_status' => 'in_stock',
                'image_tag' => 'Aromatic Floral Citrus Infusion',
                'temperature' => ['Hot', 'Iced'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'honey', 'name' => 'Organic Blossom Honey', 'price' => 4000, 'category' => 'Pemanis Alami'],
                    ['id' => 'oat_milk', 'name' => 'Oat Milk Option (London Fog)', 'price' => 8000, 'category' => 'Susu Alternatif'],
                ],
                'modifiers' => ['Honey Option (+4k)'],
                'badge_color' => 'teal',
            ],
            [
                'id' => 'PRD-TEA-05',
                'image' => '/images/pos/prd-tea-05-dirtychai.png',
                'name' => 'Oatly Spiced Dirty Chai',
                'category' => 'tea_bev',
                'price' => 46000,
                'recipe_yield' => 'Cardamom Cinnamon Infused Black Tea + Oat Milk + Single Shot',
                'stock' => 35,
                'stock_status' => 'in_stock',
                'image_tag' => 'Warm Winter Spices with Roastery Kick',
                'temperature' => ['Hot', 'Iced'],
                'can_custom_shot' => true,
                'base_shots' => 1,
                'extras' => [
                    ['id' => 'cinnamon_dust', 'name' => 'Extra Ceylon Cinnamon Dust', 'price' => 3000, 'category' => 'Topping'],
                    ['id' => 'vanilla_syrup', 'name' => 'Artisan Vanilla Syrup', 'price' => 5000, 'category' => 'Sirup & Flavour'],
                    ['id' => 'whipped_cream', 'name' => 'Whipped Cream', 'price' => 5000, 'category' => 'Topping'],
                ],
                'modifiers' => ['Extra Cinnamon Dust', 'Less Sweet'],
                'badge_color' => 'teal',
            ],

            // Artisan Food
            [
                'id' => 'PRD-FD-01',
                'image' => '/images/pos/prd-fd-01-croissant.png',
                'name' => 'French Butter Croissant AOP',
                'category' => 'food',
                'price' => 28000,
                'recipe_yield' => '100% Elle & Vire Beurre AOP, 27 Flaky Layers',
                'stock' => 22,
                'stock_status' => 'in_stock',
                'image_tag' => 'Flaky Golden Crumb & Honeycomb Interior',
                'temperature' => ['Warmed', 'Room Temp'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'butter', 'name' => 'Artisan Elle & Vire Butter AOP', 'price' => 6000, 'category' => 'Olesan'],
                    ['id' => 'jam', 'name' => 'Organic Strawberry Jam', 'price' => 5000, 'category' => 'Olesan'],
                    ['id' => 'ice_cream', 'name' => 'Vanilla Ice Cream Scoop', 'price' => 8000, 'category' => 'Topping'],
                ],
                'modifiers' => ['Re-heat Crispy', 'Artisan Butter (+6k)', 'Strawberry Jam (+5k)'],
                'badge_color' => 'yellow',
            ],
            [
                'id' => 'PRD-FD-02',
                'image' => '/images/pos/prd-fd-02-kouignamann.png',
                'name' => 'Kouign-Amann Salted Butter Caramel',
                'category' => 'food',
                'price' => 34000,
                'recipe_yield' => 'Breton Caramelized Laminated Pastry with Fleur de Sel',
                'stock' => 14,
                'stock_status' => 'low_stock',
                'image_tag' => 'Glossy Crunchy Caramel Crust',
                'temperature' => ['Warmed', 'Room Temp'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'butter', 'name' => 'Artisan Elle & Vire Butter AOP', 'price' => 6000, 'category' => 'Olesan'],
                    ['id' => 'ice_cream', 'name' => 'Vanilla Ice Cream Scoop', 'price' => 8000, 'category' => 'Topping'],
                ],
                'modifiers' => ['Re-heat Crispy'],
                'badge_color' => 'yellow',
            ],
            [
                'id' => 'PRD-FD-03',
                'image' => '/images/pos/prd-fd-03-painchocolat.png',
                'name' => 'Pain au Chocolat Valrhona',
                'category' => 'food',
                'price' => 32000,
                'recipe_yield' => 'Dual Batons of Semi-Sweet French Chocolate inside Laminated Dough',
                'stock' => 18,
                'stock_status' => 'in_stock',
                'image_tag' => 'Melted Chocolate Center',
                'temperature' => ['Warmed', 'Room Temp'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'butter', 'name' => 'Artisan Elle & Vire Butter AOP', 'price' => 6000, 'category' => 'Olesan'],
                ],
                'modifiers' => ['Re-heat Crispy'],
                'badge_color' => 'yellow',
            ],
            [
                'id' => 'PRD-FD-04',
                'image' => '/images/pos/prd-fd-04-quiche.png',
                'name' => 'Smoked Beef Truffle Quiche',
                'category' => 'food',
                'price' => 45000,
                'recipe_yield' => 'Savory Egg Custard, Gruyere, Smoked Brisket & White Truffle Oil',
                'stock' => 10,
                'stock_status' => 'low_stock',
                'image_tag' => 'Rich Creamy Pie Slice with Side Salad',
                'temperature' => ['Warm Oven Hot'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'truffle', 'name' => 'Extra White Truffle Dressing', 'price' => 8000, 'category' => 'Ekstra'],
                    ['id' => 'chili_oil', 'name' => 'Artisan Spicy Chili Oil', 'price' => 3000, 'category' => 'Ekstra'],
                ],
                'modifiers' => ['Spicy Chili Sauce', 'Extra Truffle Dressing (+8k)'],
                'badge_color' => 'yellow',
            ],
            [
                'id' => 'PRD-FD-05',
                'image' => '/images/pos/prd-fd-05-avocadotoast.png',
                'name' => 'Sourdough Avocado & Poached Egg',
                'category' => 'food',
                'price' => 52000,
                'recipe_yield' => '36h Fermented Sourdough, Hass Avo, Omega-3 Egg, Dukkah Spice',
                'stock' => 12,
                'stock_status' => 'in_stock',
                'image_tag' => 'Nutritious Modern Cafe Brunch',
                'temperature' => ['Fresh Cooked'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'poached_egg', 'name' => 'Extra Omega-3 Poached Egg', 'price' => 8000, 'category' => 'Topping'],
                    ['id' => 'salmon', 'name' => 'Norwegian Smoked Salmon (50g)', 'price' => 25000, 'category' => 'Topping'],
                    ['id' => 'truffle', 'name' => 'White Truffle Oil Drizzle', 'price' => 8000, 'category' => 'Topping'],
                ],
                'modifiers' => ['Extra Poached Egg (+8k)', 'Smoked Salmon (+25k)'],
                'badge_color' => 'yellow',
            ],

            // Roasted Beans Retail (SKU kanonikal selaras dengan /products & BOM /roastery)
            [
                'id' => 'PRD-RET-01',
                'image' => '/images/pos/prd-rt-01-gayobeans.png',
                'name' => 'Aceh Gayo Honey Specialty Whole Beans 250g',
                'category' => 'retail',
                'price' => 135000,
                'recipe_yield' => 'Degassed Ready 250g | SCA Score 86.5 | Valve Pouch',
                'stock' => 45,
                'stock_status' => 'in_stock',
                'image_tag' => 'Valve Bag with Degassing Membrane',
                'temperature' => ['Whole Beans', 'Grind: Espresso', 'Grind: Filter V60', 'Grind: Cold Brew'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'tin_can', 'name' => 'Upgrade Nitrogen Vacuum Tin (+Valve)', 'price' => 15000, 'category' => 'Kemasan'],
                ],
                'modifiers' => ['Whole Beans (Biji Utuh)', 'Grind: Fine (Espresso)', 'Grind: Medium (V60)', 'Grind: Coarse (Cold Brew)'],
                'badge_color' => 'rose',
            ],
            [
                'id' => 'PRD-RET-02',
                'image' => '/images/pos/prd-rt-04-houseblend.png',
                'name' => 'Kafeina Signature House Blend 250g',
                'category' => 'retail',
                'price' => 125000,
                'recipe_yield' => '60% Gayo + 40% Ijen | Medium-Dark Roastery Blend 250g',
                'stock' => 50,
                'stock_status' => 'in_stock',
                'image_tag' => 'Heavy Chocolate Body & Thick Hazelnut Crema',
                'temperature' => ['Whole Beans', 'Grind: Espresso', 'Grind: Mokapot', 'Grind: French Press'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'tin_can', 'name' => 'Upgrade Nitrogen Vacuum Tin (+Valve)', 'price' => 15000, 'category' => 'Kemasan'],
                ],
                'modifiers' => ['Whole Beans', 'Grind: Espresso', 'Grind: Mokapot', 'Grind: French Press'],
                'badge_color' => 'rose',
            ],

            // Merchandise
            [
                'id' => 'PRD-MC-01',
                'image' => '/images/pos/prd-mc-01-v60dripper.png',
                'name' => 'Hario V60 Ceramic Dripper 02 White',
                'category' => 'merch',
                'price' => 245000,
                'recipe_yield' => 'Arita Ware Handcrafted Japanese Ceramic Dripper 1-4 Cups',
                'stock' => 15,
                'stock_status' => 'in_stock',
                'image_tag' => 'Official Hario Japan Certified',
                'temperature' => ['White Matte Ceramic'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'gift_wrap', 'name' => 'Specialty Roastery Gift Box & Bag', 'price' => 15000, 'category' => 'Packaging'],
                ],
                'modifiers' => ['White Matte Ceramic'],
                'badge_color' => 'purple',
            ],
            [
                'id' => 'PRD-MC-02',
                'image' => '/images/pos/prd-mc-02-timemoregrinder.png',
                'name' => 'Timemore Chestnut C3 Pro Hand Grinder',
                'category' => 'merch',
                'price' => 620000,
                'recipe_yield' => 'S2C 660 Stainless Steel Burr, Foldable Crank Handle',
                'stock' => 8,
                'stock_status' => 'in_stock',
                'image_tag' => 'Precision Aluminum Body Matte Black',
                'temperature' => ['Matte Black Precision'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'gift_wrap', 'name' => 'Specialty Roastery Gift Box & Bag', 'price' => 15000, 'category' => 'Packaging'],
                ],
                'modifiers' => ['Matte Black'],
                'badge_color' => 'purple',
            ],
            [
                'id' => 'PRD-MC-03',
                'image' => '/images/pos/prd-mc-03-fellowmug.png',
                'name' => 'Fellow Carter Move Mug 16oz Matte Black',
                'category' => 'merch',
                'price' => 495000,
                'recipe_yield' => 'Ceramic Interior Coating, Snap-in Splash Guard, 12h Heat Retain',
                'stock' => 12,
                'stock_status' => 'in_stock',
                'image_tag' => 'Zero Metallic Taste Preservation',
                'temperature' => ['Matte Black 16oz', 'Warm Amber 16oz'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'gift_wrap', 'name' => 'Specialty Roastery Gift Box & Bag', 'price' => 15000, 'category' => 'Packaging'],
                ],
                'modifiers' => ['Matte Black 16oz', 'Warm Amber 16oz'],
                'badge_color' => 'purple',
            ],
            [
                'id' => 'PRD-MC-04',
                'image' => '/images/pos/prd-mc-04-apron.png',
                'name' => 'Kafeina Heavyweight Canvas Barista Apron',
                'category' => 'merch',
                'price' => 285000,
                'recipe_yield' => '16oz Water-repellent Waxed Canvas with Genuine Leather Straps',
                'stock' => 20,
                'stock_status' => 'in_stock',
                'image_tag' => 'Ergonomic Cross-back Roastery Apron',
                'temperature' => ['Espresso Brown', 'Charcoal Slate'],
                'can_custom_shot' => false,
                'extras' => [
                    ['id' => 'gift_wrap', 'name' => 'Specialty Roastery Gift Box & Bag', 'price' => 15000, 'category' => 'Packaging'],
                ],
                'modifiers' => ['Espresso Brown', 'Charcoal Slate'],
                'badge_color' => 'purple',
            ],
        ];

        $user = auth()->user();

        // 1. Resolve Target Retail Branch / Tasting Bar
        // Terminal Kasir (POS) beroperasi di gerai ritel atau tasting bar roastery (Semua Cabang konsolidasi diblokir dari POS)
        $branchQuery = strtolower(trim((string) $request->query('branch', '')));
        $branch = null;

        if ($branchQuery === 'senopati' || $branchQuery === 'out-seno') {
            $branch = Branch::where('code', 'OUT-SENO')->first();
        } elseif ($branchQuery === 'braga' || $branchQuery === 'out-brg' || $branchQuery === 'out-braga') {
            $branch = Branch::where('code', 'OUT-BRG')->first();
        } elseif ($branchQuery === 'malioboro' || $branchQuery === 'out-mlo' || $branchQuery === 'out-malio') {
            $branch = Branch::where('code', 'OUT-MLO')->first();
        } elseif ($branchQuery === 'central' || $branchQuery === 'hq-central') {
            $branch = Branch::where('code', 'HQ-CENTRAL')->first();
        }

        // If not specified in query, check user's assigned branch if user is a store/roastery employee
        if (! $branch) {
            if ($user && ! $user->canSwitchBranch() && $user->branch) {
                $branch = $user->branch;
            } else {
                // Defaulting to Flagship Store Senopati (Retail Bar)
                $branch = Branch::where('code', 'OUT-SENO')->first()
                    ?? Branch::where('type', 'RETAIL_BRANCH')->first()
                    ?? Branch::first();
            }
        }

        // 2. Resolve On-Duty Cashier for this specific branch
        $onDutyCashier = User::where('branch_id', $branch->id)
            ->whereHas('role', fn ($q) => $q->whereIn('name', ['cashier', 'branch_cashier', 'barista', 'lead_barista', 'head_barista', 'store_supervisor', 'qc', 'roaster']))
            ->orderBy('id')
            ->first();

        // Fallback cashier names based on branch if not found
        $fallbackCashierName = match ($branch->code) {
            'OUT-BRG' => 'Dinda Kirana',
            'OUT-MLO' => 'Bagus Wicaksono',
            'HQ-CENTRAL' => 'Fajar Nugraha',
            default => 'Rizky Ramadhan',
        };

        // Determine cashier name & role to display
        if ($user && $user->branch_id === $branch->id && ! $user->isSuperAdmin()) {
            $cashierName = $user->name;
            $cashierRole = $user->role?->display_name ?? 'Kasir & Barista Bertugas';
        } else {
            $cashierName = $onDutyCashier?->name ?? $fallbackCashierName;
            $cashierRole = match ($branch->code) {
                'HQ-CENTRAL' => 'Licensed Q-Grader & Lab Barista',
                default => ($onDutyCashier?->role?->display_name ?? 'Head Barista & Kasir Bertugas'),
            };
        }

        $activeBranchSlug = match ($branch->code) {
            'OUT-BRG' => 'braga',
            'OUT-MLO' => 'malioboro',
            'HQ-CENTRAL' => 'central',
            default => 'senopati',
        };

        // Synchronize live master products & modifiers from DB (/products)
        $dbProducts = Product::with(['category', 'variants', 'modifiers', 'recipes.ingredientProduct'])
            ->where('is_raw_material', false)
            ->get()
            ->keyBy('sku');

        $dbModifiers = ProductModifier::where('is_active', true)
            ->orderBy('group_name')
            ->orderBy('name')
            ->get();

        $liveBeverageExtras = [];
        foreach ($dbModifiers as $mod) {
            $liveBeverageExtras[] = [
                'id' => 'mod_'.$mod->id,
                'name' => $mod->name,
                'price' => (float) $mod->price,
                'category' => $mod->group_name ?: 'Ekstra',
            ];
        }

        $syncedProducts = [];
        foreach ($products as $item) {
            $dbProd = $dbProducts->get($item['id']);
            if ($dbProd && ! $dbProd->is_active) {
                continue;
            }
            if ($dbProd) {
                $item['name'] = $dbProd->name;
                $item['price'] = (float) $dbProd->selling_price;
                if ($dbProd->variants->isNotEmpty()) {
                    $variantList = [];
                    foreach ($dbProd->variants as $v) {
                        $variantList[] = [
                            'id' => $v->id,
                            'name' => $v->name,
                            'price_adjustment' => (float) $v->price_adjustment,
                        ];
                    }
                    $item['variants'] = $variantList;
                }
            }
            if (in_array($item['category'], ['espresso', 'tea_bev'], true) && count($liveBeverageExtras) > 0) {
                $item['extras'] = $liveBeverageExtras;
            }
            $syncedProducts[] = $item;
        }
        $products = $syncedProducts;

        $existingSkus = array_column($products, 'id');
        foreach ($dbProducts as $sku => $dbProd) {
            if (! $dbProd->is_active || in_array($sku, $existingSkus, true)) {
                continue;
            }
            $catKey = match (true) {
                str_contains(strtolower((string) $dbProd->category?->name), 'manual') => 'manual_brew',
                str_contains(strtolower((string) $dbProd->category?->name), 'retail') => 'retail',
                str_contains(strtolower((string) $dbProd->category?->name), 'tea') => 'tea_bev',
                default => 'espresso',
            };
            $products[] = [
                'id' => $dbProd->sku,
                'image' => '/images/pos/prd-esp-01-espresso.png',
                'name' => $dbProd->name,
                'category' => $catKey,
                'price' => (float) $dbProd->selling_price,
                'recipe_yield' => $dbProd->description ?: 'Menu Katalog Master Kafeina',
                'stock' => 50,
                'stock_status' => 'in_stock',
                'image_tag' => $dbProd->category?->name ?? 'Menu Kafeina',
                'temperature' => ['Hot', 'Iced'],
                'can_custom_shot' => $catKey === 'espresso',
                'base_shots' => 2,
                'extras' => in_array($catKey, ['espresso', 'tea_bev'], true) ? $liveBeverageExtras : [],
                'modifiers' => [],
                'badge_color' => 'amber',
            ];
        }

        // Load real active PosShift & real today's branch sales from DB
        $activeDbShift = PosShift::where('branch_id', $branch->id)
            ->where('status', 'OPEN')
            ->latest('opened_at')
            ->first();

        $todayBranchOrders = PosOrder::where('branch_id', $branch->id)
            ->where('payment_status', 'PAID')
            ->where('order_type', '!=', 'NON_REVENUE')
            ->whereDate('created_at', now('Asia/Jakarta')->toDateString())
            ->get();

        $openingCash = (float) ($activeDbShift?->opening_cash ?? 500000);
        $systemCashSales = (float) $todayBranchOrders->where('payment_method', 'CASH')->sum('total_amount');
        $systemQrisSales = (float) $todayBranchOrders->whereIn('payment_method', ['QRIS_MIDTRANS', 'BANK_TRANSFER'])->sum('total_amount');
        $systemTotalSales = $systemCashSales + $systemQrisSales;

        $shift = [
            'shift_id' => $activeDbShift?->shift_code ?? ('SFT-'.date('Ymd').'-01'),
            'is_open' => true,
            'cashier_name' => $cashierName,
            'cashier_role' => $cashierRole,
            'supervisor_name' => ($user && $user->isSuperAdmin()) ? $user->name : null,
            'outlet_name' => $branch->name,
            'outlet_code' => $branch->code,
            'branch_slug' => $activeBranchSlug,
            'opening_cash' => $openingCash,
            'started_at' => $activeDbShift?->opened_at
                ? Carbon::parse($activeDbShift->opened_at)->setTimezone('Asia/Jakarta')->format('H:i').' WIB'
                : '07:00 WIB',
            'system_cash_sales' => $systemCashSales,
            'system_qris_sales' => $systemQrisSales,
            'system_total_sales' => $systemTotalSales,
            'expected_physical_cash' => $openingCash + $systemCashSales,
            'transaction_count' => $todayBranchOrders->count(),
        ];

        $redeemCodes = RedeemCode::orderBy('created_at', 'desc')->get();
        $taxPb1Rate = (float) SystemSetting::get('tax_pb1_rate', 11.0);
        $serviceChargeRate = (float) SystemSetting::get('service_charge_rate', 0.0);

        return view('pos.index', compact(
            'categories',
            'products',
            'shift',
            'redeemCodes',
            'activeBranchSlug',
            'taxPb1Rate',
            'serviceChargeRate'
        ));
    }

    /**
     * API: Process Order Checkout.
     */
    public function checkout(Request $request): JsonResponse
    {
        $items = $request->input('items', []);
        $paymentMethod = $request->input('payment_method', 'cash'); // cash, qris, non_revenue
        $paidAmount = (float) $request->input('paid_amount', 0);
        $orderType = $request->input('order_type', 'Dine In');
        $tableNumber = trim((string) $request->input('table_number', ''));
        $customerName = trim((string) $request->input('customer_name', ''));

        // Enforce customer name and table number validation for commercial orders
        if (strtolower((string) $paymentMethod) !== 'non_revenue') {
            if ($customerName === '') {
                if (! $request->has('customer_name') && $request->has('table_number')) {
                    $customerName = 'Tamu Kafeina';
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Atas nama pelanggan wajib diisi sebelum pembayaran.',
                    ], 422);
                }
            }

            if ($orderType === 'Dine In') {
                if ($tableNumber === '' || $tableNumber === '-') {
                    if (! $request->has('table_number')) {
                        $tableNumber = '07';
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'Nomor meja wajib diisi untuk pesanan Dine In.',
                        ], 422);
                    }
                }
            } else {
                $tableNumber = '-';
            }
        } else {
            $customerName = $customerName ?: 'Internal Bar / QC';
            $tableNumber = $tableNumber ?: '-';
        }

        $discountAmount = (float) $request->input('discount_amount', 0);
        $nonRevenueReason = $request->input('non_revenue_reason', null);
        $redeemCodeInput = strtoupper(trim($request->input('redeem_code', '')));

        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += ($item['price'] * $item['qty']);
        }

        if ($redeemCodeInput) {
            $rc = RedeemCode::where('code', $redeemCodeInput)->first();
            if ($rc) {
                $check = $rc->validateForSubtotal($subtotal);
                if ($check['valid']) {
                    $discountAmount = $rc->calculateDiscount($subtotal);
                    $rc->increment('times_redeemed');
                }
            }
        }

        $taxPb1Rate = (float) SystemSetting::get('tax_pb1_rate', 11.0);
        $serviceChargeRate = (float) SystemSetting::get('service_charge_rate', 0.0);

        $taxableAmount = max(0, $subtotal - $discountAmount);
        $serviceCharge = $paymentMethod === 'non_revenue' ? 0 : round($taxableAmount * ($serviceChargeRate / 100));
        $pb1Tax = $paymentMethod === 'non_revenue' ? 0 : round(($taxableAmount + $serviceCharge) * ($taxPb1Rate / 100));
        $totalAmount = $paymentMethod === 'non_revenue' ? 0 : ($taxableAmount + $serviceCharge + $pb1Tax);
        $changeAmount = max(0, $paidAmount - $totalAmount);

        // Resolve Cashier & Branch (Strict Branch Isolation for Restricted Users + Dynamic Branch Code Support)
        $authUser = auth()->user();
        $branch = $this->resolveScopedBranch($request, $authUser);

        $user = $authUser
            ?? User::where('branch_id', $branch->id)->whereHas('role', fn ($q) => $q->whereIn('name', ['cashier', 'branch_cashier', 'barista', 'lead_barista']))->first()
            ?? User::whereHas('role', fn ($q) => $q->whereIn('name', ['cashier', 'branch_cashier']))->first()
            ?? User::first();

        // Resolve or create Shift (collision-safe shift_code)
        $shift = PosShift::where('status', 'OPEN')
            ->where('branch_id', $branch->id)
            ->latest()
            ->first();

        if (! $shift) {
            $shiftSeq = PosShift::max('id') ? ((int) PosShift::max('id') + 1) : 1;
            $shiftCode = 'SFT-'.date('Ymd').'-'.str_pad((string) $shiftSeq, 3, '0', STR_PAD_LEFT);
            while (PosShift::where('shift_code', $shiftCode)->exists()) {
                $shiftSeq++;
                $shiftCode = 'SFT-'.date('Ymd').'-'.str_pad((string) $shiftSeq, 3, '0', STR_PAD_LEFT);
            }

            $shift = PosShift::create([
                'shift_code' => $shiftCode,
                'branch_id' => $branch->id,
                'cashier_user_id' => $user->id,
                'status' => 'OPEN',
                'opened_at' => now(),
                'opening_cash' => 500000,
            ]);
        }

        $mappedReason = null;
        if ($nonRevenueReason) {
            $upperReason = strtoupper(trim((string) $nonRevenueReason));
            if (str_contains($upperReason, 'CALIB') || str_contains($upperReason, 'KALIBRASI') || str_contains($upperReason, 'DIAL')) {
                $mappedReason = 'CALIBRATION';
            } elseif (str_contains($upperReason, 'TEST') || str_contains($upperReason, 'ICING')) {
                $mappedReason = 'TESTER';
            } elseif (str_contains($upperReason, 'STAFF') || str_contains($upperReason, 'BARISTA') || str_contains($upperReason, 'QUOTA')) {
                $mappedReason = 'STAFF_QUOTA';
            } elseif (in_array($upperReason, ['CALIBRATION', 'TESTER', 'STAFF_QUOTA'])) {
                $mappedReason = $upperReason;
            } else {
                $mappedReason = 'CALIBRATION';
            }
        }

        $requestedOrderNumber = trim((string) $request->input('order_number', ''));
        if ($requestedOrderNumber !== '' && ! PosOrder::where('order_number', $requestedOrderNumber)->exists()) {
            $orderNumber = $requestedOrderNumber;
        } else {
            $orderSeq = PosOrder::max('id') ? ((int) PosOrder::max('id') + 1) : 1;
            $orderNumber = 'ORD-'.date('Ymd').'-'.str_pad((string) $orderSeq, 4, '0', STR_PAD_LEFT);
            while (PosOrder::where('order_number', $orderNumber)->exists()) {
                $orderSeq++;
                $orderNumber = 'ORD-'.date('Ymd').'-'.str_pad((string) $orderSeq, 4, '0', STR_PAD_LEFT);
            }
        }

        $dbOrder = DB::transaction(function () use (
            $shift, $user, $branch, $orderNumber, $orderType, $customerName, $tableNumber,
            $items, $subtotal, $discountAmount, $pb1Tax, $totalAmount, $paymentMethod,
            $mappedReason, $nonRevenueReason, $request
        ) {
            $rawMethod = strtoupper((string) $paymentMethod);
            $mappedPaymentMethod = match ($rawMethod) {
                'QRIS', 'QRIS_MIDTRANS' => 'QRIS_MIDTRANS',
                'TRANSFER', 'VA', 'BANK_TRANSFER' => 'BANK_TRANSFER',
                'NON_REVENUE' => 'NON_REVENUE',
                'SPLIT_BILL' => 'SPLIT_BILL',
                default => 'CASH',
            };

            // Format Payment Reference
            $paymentReference = $request->input('payment_reference');
            if (! $paymentReference) {
                if ($mappedPaymentMethod === 'QRIS_MIDTRANS') {
                    $paymentReference = $request->input('qris_order_number');
                } elseif ($mappedPaymentMethod === 'BANK_TRANSFER') {
                    $vaBank = $request->input('va_bank', 'VA');
                    $vaNum = $request->input('va_number', '');
                    $paymentReference = "{$vaBank} VA • {$vaNum}";
                }
            }

            $order = PosOrder::create([
                'order_number' => $orderNumber,
                'shift_id' => $shift->id,
                'cashier_id' => $user->id,
                'branch_id' => $branch->id,
                'order_type' => $orderType === 'Take Away' ? 'TAKE_AWAY' : ($orderType === 'Non-Revenue' ? 'NON_REVENUE' : 'DINE_IN'),
                'customer_name' => $customerName ?: 'Tamu Kafeina',
                'table_number' => $tableNumber ?: '-',
                'notes' => $request->input('notes') ?: ($nonRevenueReason !== $mappedReason ? $nonRevenueReason : null),
                'non_revenue_reason' => $mappedReason,
                'subtotal' => $subtotal,
                'tax_amount' => $pb1Tax,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'payment_method' => $mappedPaymentMethod,
                'payment_status' => 'PAID',
                'payment_reference' => $paymentReference,
            ]);

            $barWarehouse = InventoryWarehouse::where('branch_id', $branch->id)
                ->where('type', 'RETAIL_BAR')
                ->first() ?? InventoryWarehouse::where('branch_id', $branch->id)->first();

            foreach ($items as $item) {
                // Find finished product by SKU, ID, or Name (jangan pernah fallback ke RAW-GB bahan mentah!)
                $product = Product::where('sku', $item['id'] ?? '')->first()
                    ?? (is_numeric($item['id'] ?? null) ? Product::find((int) $item['id']) : null)
                    ?? Product::where('is_raw_material', false)->where('name', 'LIKE', '%'.($item['name'] ?? '').'%')->first()
                    ?? Product::where('is_raw_material', false)->first()
                    ?? Product::first();

                if ($product) {
                    $orderItem = PosOrderItem::create([
                        'pos_order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $item['qty'] ?? 1,
                        'unit_price' => $item['price'] ?? $product->selling_price,
                        'subtotal' => ($item['price'] ?? $product->selling_price) * ($item['qty'] ?? 1),
                    ]);

                    // If bar warehouse exists, auto deduct recipes and record stock cards
                    if ($barWarehouse) {
                        $recipes = ProductRecipe::with('ingredientProduct')->where('product_id', $product->id)->get();
                        if ($recipes->isNotEmpty()) {
                            foreach ($recipes as $rec) {
                                $deductQty = (float) $rec->quantity_required * ($item['qty'] ?? 1);
                                $stock = WarehouseStock::where('warehouse_id', $barWarehouse->id)
                                    ->where('product_id', $rec->ingredient_product_id)
                                    ->first();

                                if ($stock) {
                                    $stock->quantity = max(0, $stock->quantity - $deductQty);
                                    $stock->save();

                                    RecipeDeduction::create([
                                        'pos_order_item_id' => $orderItem->id,
                                        'ingredient_product_id' => $rec->ingredient_product_id,
                                        'warehouse_id' => $barWarehouse->id,
                                        'quantity_deducted' => $deductQty,
                                        'unit' => $rec->unit,
                                        'deduction_timestamp' => now(),
                                    ]);

                                    StockCard::create([
                                        'warehouse_id' => $barWarehouse->id,
                                        'product_id' => $rec->ingredient_product_id,
                                        'lot_id' => null,
                                        'reference_type' => 'POS_SALE',
                                        'reference_id' => $order->id,
                                        'quantity_in' => 0,
                                        'quantity_out' => $deductQty,
                                        'balance_after' => $stock->quantity,
                                        'cost_per_unit' => (float) ($rec->ingredientProduct?->cost_price ?? 0),
                                        'notes' => "Auto-deduct POS Resep [{$product->name}] Order [{$order->order_number}]",
                                    ]);
                                }
                            }
                        }

                        // Auto-deduct bahan baku untuk selected extras/modifiers
                        $extras = $item['selectedExtras'] ?? $item['extras'] ?? [];
                        if (! empty($extras) && is_array($extras)) {
                            foreach ($extras as $extra) {
                                $extraName = trim(is_array($extra) ? (string) ($extra['name'] ?? '') : (string) $extra);
                                if ($extraName === '') {
                                    continue;
                                }

                                $mod = ProductModifier::with('ingredientProduct')
                                    ->where('name', $extraName)
                                    ->orWhere('name', 'LIKE', "%{$extraName}%")
                                    ->first();

                                if ($mod && $mod->ingredient_product_id && (float) $mod->quantity_deducted > 0) {
                                    $modDeductQty = (float) $mod->quantity_deducted * ($item['qty'] ?? 1);
                                    $modStock = WarehouseStock::where('warehouse_id', $barWarehouse->id)
                                        ->where('product_id', $mod->ingredient_product_id)
                                        ->first();

                                    if ($modStock) {
                                        $modStock->quantity = max(0, $modStock->quantity - $modDeductQty);
                                        $modStock->save();

                                        RecipeDeduction::create([
                                            'pos_order_item_id' => $orderItem->id,
                                            'ingredient_product_id' => $mod->ingredient_product_id,
                                            'warehouse_id' => $barWarehouse->id,
                                            'quantity_deducted' => $modDeductQty,
                                            'unit' => $mod->unit ?? 'ml',
                                            'deduction_timestamp' => now(),
                                        ]);

                                        StockCard::create([
                                            'warehouse_id' => $barWarehouse->id,
                                            'product_id' => $mod->ingredient_product_id,
                                            'lot_id' => null,
                                            'reference_type' => 'POS_SALE',
                                            'reference_id' => $order->id,
                                            'quantity_in' => 0,
                                            'quantity_out' => $modDeductQty,
                                            'balance_after' => $modStock->quantity,
                                            'cost_per_unit' => (float) ($mod->ingredientProduct?->cost_price ?? 0),
                                            'notes' => "Auto-deduct POS Ekstra [{$mod->name}] Order [{$order->order_number}]",
                                        ]);
                                    }
                                }
                            }
                        }

                        // Auto-deduct bahan baku untuk stepper Extra Espresso Shots (di luar selectedExtras)
                        $extraShotsCount = max(0, (int) ($item['extraShots'] ?? $item['extra_shots'] ?? 0));
                        if ($extraShotsCount > 0) {
                            $shotModifier = ProductModifier::with('ingredientProduct')
                                ->where('name', 'LIKE', '%Extra Espresso Shot%')
                                ->whereNotNull('ingredient_product_id')
                                ->first();

                            $shotIngredient = $shotModifier?->ingredientProduct
                                ?? Product::where('sku', 'RAW-RB-HOUSE')->first();
                            $shotUnitQty = $shotModifier && (float) $shotModifier->quantity_deducted > 0
                                ? (float) $shotModifier->quantity_deducted
                                : 18.0;

                            if ($shotIngredient) {
                                $shotDeductQty = $shotUnitQty * $extraShotsCount * ($item['qty'] ?? 1);
                                $shotStock = WarehouseStock::where('warehouse_id', $barWarehouse->id)
                                    ->where('product_id', $shotIngredient->id)
                                    ->first();

                                if ($shotStock) {
                                    $shotStock->quantity = max(0, $shotStock->quantity - $shotDeductQty);
                                    $shotStock->save();

                                    RecipeDeduction::create([
                                        'pos_order_item_id' => $orderItem->id,
                                        'ingredient_product_id' => $shotIngredient->id,
                                        'warehouse_id' => $barWarehouse->id,
                                        'quantity_deducted' => $shotDeductQty,
                                        'unit' => $shotModifier?->unit ?? $shotIngredient->unit ?? 'gram',
                                        'deduction_timestamp' => now(),
                                    ]);

                                    StockCard::create([
                                        'warehouse_id' => $barWarehouse->id,
                                        'product_id' => $shotIngredient->id,
                                        'lot_id' => null,
                                        'reference_type' => 'POS_SALE',
                                        'reference_id' => $order->id,
                                        'quantity_in' => 0,
                                        'quantity_out' => $shotDeductQty,
                                        'balance_after' => $shotStock->quantity,
                                        'cost_per_unit' => (float) $shotIngredient->cost_price,
                                        'notes' => "Auto-deduct POS +{$extraShotsCount} Extra Shot [{$product->name}] Order [{$order->order_number}]",
                                    ]);
                                }
                            }
                        }

                        if ($recipes->isEmpty()) {
                            // Retail standalone product
                            $stock = WarehouseStock::where('warehouse_id', $barWarehouse->id)
                                ->where('product_id', $product->id)
                                ->first();

                            if ($stock) {
                                $stock->quantity = max(0, $stock->quantity - ($item['qty'] ?? 1));
                                $stock->save();

                                StockCard::create([
                                    'warehouse_id' => $barWarehouse->id,
                                    'product_id' => $product->id,
                                    'lot_id' => null,
                                    'reference_type' => 'POS_SALE',
                                    'reference_id' => $order->id,
                                    'quantity_in' => 0,
                                    'quantity_out' => ($item['qty'] ?? 1),
                                    'balance_after' => $stock->quantity,
                                    'cost_per_unit' => (float) $product->cost_price,
                                    'notes' => "POS Retail Sale [{$order->order_number}]",
                                ]);
                            }
                        }
                    }
                }
            }

            return $order;
        });

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil diproses dan disimpan ke sistem.',
            'order' => [
                'order_number' => $dbOrder->order_number,
                'created_at' => $dbOrder->created_at->format('d M Y, H:i:s').' WIB',
                'cashier' => $user->name,
                'customer_name' => $customerName,
                'order_type' => $orderType,
                'table_number' => $tableNumber,
                'items' => $items,
                'subtotal' => $subtotal,
                'discount' => $discountAmount,
                'service_charge' => $serviceCharge,
                'tax_pb1' => $pb1Tax,
                'tax_pb1_rate' => $taxPb1Rate,
                'total' => $totalAmount,
                'payment_method' => $paymentMethod,
                'paid_amount' => $paymentMethod === 'non_revenue' ? 0 : $paidAmount,
                'change_amount' => $changeAmount,
                'non_revenue_reason' => $nonRevenueReason,
                'drawer_pulse_sent' => $paymentMethod === 'cash',
                'auto_deduct_status' => 'Bahan baku resep (Biji Gayo/Ijen, Susu Fresh, Packaging) terpotong otomatis dari stok cabang.',
            ],
        ]);
    }

    /**
     * API: Open Cashier Shift (persisted to pos_shifts).
     */
    public function openShift(Request $request): JsonResponse
    {
        $openingCash = max(0.0, (float) $request->input('opening_cash', 500000));
        $authUser = auth()->user();
        $user = $authUser ?? User::first();
        $branch = $this->resolveScopedBranch($request, $authUser);

        $shift = DB::transaction(function () use ($branch, $user, $openingCash): PosShift {
            $seq = PosShift::max('id') ? ((int) PosShift::max('id') + 1) : 1;
            $shiftCode = 'SFT-'.date('Ymd').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
            while (PosShift::where('shift_code', $shiftCode)->exists()) {
                $seq++;
                $shiftCode = 'SFT-'.date('Ymd').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
            }

            return PosShift::create([
                'shift_code' => $shiftCode,
                'branch_id' => $branch->id,
                'cashier_user_id' => $user ? $user->id : 1,
                'opening_cash' => $openingCash,
                'opened_at' => now(),
                'status' => 'OPEN',
            ]);
        });

        $cashierName = $user?->name ?? (string) $request->input('cashier_name', 'Kasir Kafeina');

        return response()->json([
            'success' => true,
            'message' => "Sesi shift {$shift->shift_code} berhasil dibuka untuk {$cashierName} dengan modal awal Rp ".number_format($openingCash, 0, ',', '.'),
            'shift' => [
                'id' => $shift->id,
                'shift_id' => $shift->shift_code,
                'cashier_name' => $cashierName,
                'opening_cash' => (float) $shift->opening_cash,
                'opened_at' => Carbon::parse($shift->opened_at)->setTimezone('Asia/Jakarta')->format('H:i').' WIB',
                'is_open' => true,
            ],
        ]);
    }

    /**
     * API: Close Cashier Shift & Reconciliation (persisted to pos_shifts + real Supervisor PIN Hash verification).
     */
    public function closeShift(Request $request): JsonResponse
    {
        $totalPhysicalCash = (float) $request->input('total_physical_cash', 0);
        $supervisorPin = trim((string) $request->input('supervisor_pin', ''));
        $notes = trim((string) $request->input('notes', ''));
        $user = auth()->user();

        $branch = $this->resolveScopedBranch($request, $user);

        $activeShift = PosShift::where('branch_id', $branch->id)
            ->where('status', 'OPEN')
            ->latest('opened_at')
            ->first();

        // Hitung kas sistem riil dari shift aktif bila tersedia; fallback ke input klien untuk backward compatibility
        if ($activeShift && ! $request->has('system_cash_expected')) {
            $cashSales = (float) PosOrder::where('branch_id', $activeShift->branch_id)
                ->where('payment_status', 'PAID')
                ->where('payment_method', 'CASH')
                ->where('order_type', '!=', 'NON_REVENUE')
                ->whereDate('created_at', now('Asia/Jakarta')->toDateString())
                ->sum('total_amount');
            $systemCashExpected = (float) $activeShift->opening_cash + $cashSales;
        } else {
            $systemCashExpected = (float) $request->input('system_cash_expected', $activeShift ? (float) $activeShift->opening_cash : 500000);
        }

        $discrepancy = $totalPhysicalCash - $systemCashExpected;
        $absDiscrepancy = abs($discrepancy);

        // Rule: Discrepancy > Rp 10.000 requires valid 6-digit Supervisor / Super Admin PIN from DB
        $requiresSupervisor = $absDiscrepancy > 10000;
        $supervisorVerified = false;
        $supervisorName = 'Auto-Approved (Toleransi ≤ Rp 10.000)';

        if ($requiresSupervisor) {
            if ($supervisorPin !== '') {
                $supervisors = User::with('role')
                    ->where('is_active', true)
                    ->whereNotNull('pin_code')
                    ->whereHas('role', function ($q) {
                        $q->whereIn('name', ['super_admin', 'supervisor', 'store_supervisor', 'store_manager', 'owner', 'admin']);
                    })
                    ->get();

                foreach ($supervisors as $sup) {
                    if ($sup->pin_code && Hash::check($supervisorPin, (string) $sup->pin_code)) {
                        $supervisorVerified = true;
                        $supervisorName = $sup->name;
                        break;
                    }
                }
            }

            if (! $supervisorVerified) {
                return response()->json([
                    'success' => false,
                    'error' => 'SUPERVISOR_PIN_REQUIRED',
                    'message' => 'Selisih kas melebihi toleransi Rp 10.000 (Selisih: Rp '.number_format($absDiscrepancy, 0, ',', '.').'). Wajib verifikasi PIN Otorisasi Supervisor 6-digit yang valid!',
                    'discrepancy' => $discrepancy,
                    'requires_pin' => true,
                ], 422);
            }
        }

        if ($activeShift) {
            $activeShift->update([
                'closing_cash_actual' => $totalPhysicalCash,
                'closing_cash_system' => $systemCashExpected,
                'cash_difference' => $discrepancy,
                'closed_at' => now(),
                'status' => 'CLOSED',
            ]);
        }

        $status = 'BALANCED';
        if ($discrepancy > 0) {
            $status = 'OVERAGE';
        } elseif ($discrepancy < 0) {
            $status = 'SHORTAGE';
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesi shift kasir berhasil ditutup dan rekonsiliasi kas tersimpan ke database.',
            'reconciliation' => [
                'shift_code' => $activeShift?->shift_code,
                'status' => $status,
                'system_expected' => $systemCashExpected,
                'physical_counted' => $totalPhysicalCash,
                'discrepancy' => $discrepancy,
                'supervisor_authorized' => $supervisorVerified,
                'supervisor_name' => $supervisorName,
                'closed_at' => now('Asia/Jakarta')->format('d M Y, H:i').' WIB',
                'notes' => $notes,
            ],
        ]);
    }

    /**
     * API: Get all active redeem codes.
     */
    public function getRedeemCodes(): JsonResponse
    {
        $codes = RedeemCode::orderBy('created_at', 'desc')->get();

        return response()->json(['success' => true, 'redeem_codes' => $codes]);
    }

    /**
     * API: Validate and calculate discount for a redeem code.
     */
    public function validateRedeemCode(Request $request): JsonResponse
    {
        $code = strtoupper(trim($request->input('code', '')));
        $subtotal = (float) $request->input('subtotal', 0);

        if (! $code) {
            return response()->json(['valid' => false, 'message' => 'Silakan masukkan kode promo.'], 422);
        }

        $redeemCode = RedeemCode::where('code', $code)->first();

        if (! $redeemCode) {
            return response()->json(['valid' => false, 'message' => "Kode promo [{$code}] tidak ditemukan atau salah."], 404);
        }

        $check = $redeemCode->validateForSubtotal($subtotal);
        if (! $check['valid']) {
            return response()->json(['valid' => false, 'message' => $check['message']], 422);
        }

        $discount = $redeemCode->calculateDiscount($subtotal);

        return response()->json([
            'valid' => true,
            'message' => $check['message'],
            'redeem_code' => [
                'id' => $redeemCode->id,
                'code' => $redeemCode->code,
                'description' => $redeemCode->description,
                'discount_type' => $redeemCode->discount_type,
                'discount_value' => (float) $redeemCode->discount_value,
                'min_order_amount' => (float) $redeemCode->min_order_amount,
                'max_discount_amount' => (float) $redeemCode->max_discount_amount,
                'max_redemptions' => $redeemCode->max_redemptions,
                'times_redeemed' => $redeemCode->times_redeemed,
                'discount_amount' => $discount,
            ],
        ]);
    }

    /**
     * API: Create or update a redeem code.
     */
    public function storeRedeemCode(Request $request): JsonResponse
    {
        if (! auth()->user()?->canAccessModule('vouchers')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk membuat atau mengubah voucher promo.',
            ], 403);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'discount_type' => 'required|in:PERCENTAGE,FIXED',
            'discount_value' => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'max_redemptions' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active', true);

        $redeemCode = RedeemCode::updateOrCreate(
            ['code' => $validated['code']],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => "Kode promo [{$redeemCode->code}] berhasil disimpan.",
            'redeem_code' => $redeemCode,
        ]);
    }

    /**
     * API: Generate Dynamic QRIS for POS Checkout via Midtrans.
     */
    public function generateQris(Request $request, MidtransQrisService $qrisService): JsonResponse
    {
        $body = $request->isJson() ? $request->json()->all() : $request->all();
        if (empty($body)) {
            $rawContent = trim($request->getContent(), "\xEF\xBB\xBF \t\n\r\0\x0B");
            $raw = json_decode($rawContent, true) ?: [];
            $body = array_merge($raw, $request->all());
        }

        $amount = (float) ($body['amount'] ?? $request->input('amount', 0));
        $orderNumber = (string) ($body['order_number'] ?? $request->input('order_number', 'KAF-'.date('YmdHis')));
        $items = (array) ($body['items'] ?? $request->input('items', []));
        $customerName = (string) ($body['customer_name'] ?? $request->input('customer_name', 'Tamu Kafeina'));

        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Nominal tagihan harus lebih dari Rp 0.',
            ], 422);
        }

        $result = $qrisService->createQrisCharge($orderNumber, $amount, $items, $customerName);

        return response()->json($result);
    }

    /**
     * API: Poll Dynamic QRIS Payment Status.
     */
    public function checkQrisStatus(string $orderNumber, MidtransQrisService $qrisService): JsonResponse
    {
        $status = $qrisService->checkStatus($orderNumber);

        return response()->json($status);
    }

    /**
     * API: Simulate QRIS Payment Success (for Sandbox Testing Only).
     */
    public function simulateQrisPayment(Request $request, MidtransQrisService $qrisService): JsonResponse
    {
        if (app()->environment('production') && (bool) SystemSetting::get('midtrans_is_production', false)) {
            return response()->json([
                'success' => false,
                'message' => 'Simulasi pembayaran dinonaktifkan pada mode produksi Midtrans.',
            ], 403);
        }

        $orderNumber = (string) $request->input('order_number');
        if (! $orderNumber) {
            return response()->json(['success' => false, 'message' => 'Order number diperlukan.'], 422);
        }

        $result = $qrisService->simulatePayment($orderNumber);

        return response()->json($result);
    }

    /**
     * API: Generate Dynamic Virtual Account / Bank Transfer via Midtrans.
     */
    public function generateVa(Request $request, MidtransQrisService $qrisService): JsonResponse
    {
        $body = $request->isJson() ? $request->json()->all() : $request->all();
        if (empty($body)) {
            $rawContent = trim($request->getContent(), "\xEF\xBB\xBF \t\n\r\0\x0B");
            $raw = json_decode($rawContent, true) ?: [];
            $body = array_merge($raw, $request->all());
        }

        $amount = (float) ($body['amount'] ?? $request->input('amount', 0));
        $orderNumber = (string) ($body['order_number'] ?? $request->input('order_number', 'KAF-'.date('YmdHis')));
        $bank = (string) ($body['bank'] ?? $request->input('bank', 'bca'));
        $customerName = (string) ($body['customer_name'] ?? $request->input('customer_name', 'Tamu Kafeina'));

        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Nominal tagihan harus lebih dari Rp 0.',
            ], 422);
        }

        $result = $qrisService->createVaCharge($orderNumber, $amount, $bank, $customerName);

        return response()->json($result);
    }

    /**
     * API: Poll Dynamic Virtual Account Payment Status.
     */
    public function checkVaStatus(string $orderNumber, MidtransQrisService $qrisService): JsonResponse
    {
        $status = $qrisService->checkStatus($orderNumber);

        return response()->json($status);
    }

    /**
     * API: Simulate Virtual Account Payment Success (for Sandbox Testing Only).
     */
    public function simulateVaPayment(Request $request, MidtransQrisService $qrisService): JsonResponse
    {
        if (app()->environment('production') && (bool) SystemSetting::get('midtrans_is_production', false)) {
            return response()->json([
                'success' => false,
                'message' => 'Simulasi pembayaran dinonaktifkan pada mode produksi Midtrans.',
            ], 403);
        }

        $orderNumber = (string) $request->input('order_number');
        if (! $orderNumber) {
            return response()->json(['success' => false, 'message' => 'Order number diperlukan.'], 422);
        }

        $result = $qrisService->simulatePayment($orderNumber);

        return response()->json($result);
    }

    /**
     * Webhook: Handle Midtrans payment notification.
     */
    public function handleMidtransWebhook(Request $request, MidtransQrisService $qrisService): JsonResponse
    {
        $payload = $request->all();
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');

        if (! $orderId || ! $qrisService->verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        if (in_array($transactionStatus, ['settlement', 'capture'], true)) {
            $qrisService->simulatePayment($orderId);
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Resolve active POS branch while strictly enforcing branch isolation for restricted roles.
     */
    private function resolveScopedBranch(Request $request, ?User $user): Branch
    {
        if ($user && ! $user->canSwitchBranch() && $user->branch) {
            return $user->branch;
        }

        $branchInput = strtolower(trim((string) ($request->input('branch_code') ?? $request->input('branch') ?? $request->query('branch', ''))));
        $branch = null;

        if ($branchInput !== '') {
            if ($branchInput === 'senopati' || str_contains($branchInput, 'seno')) {
                $branch = Branch::where('code', 'OUT-SENO')->first();
            } elseif ($branchInput === 'braga' || str_contains($branchInput, 'brg')) {
                $branch = Branch::where('code', 'OUT-BRG')->first();
            } elseif ($branchInput === 'malioboro' || str_contains($branchInput, 'mlo')) {
                $branch = Branch::where('code', 'OUT-MLO')->first();
            } elseif ($branchInput === 'central' || str_contains($branchInput, 'hq')) {
                $branch = Branch::where('code', 'HQ-CENTRAL')->first();
            } else {
                $branch = Branch::whereRaw('LOWER(code) = ?', [$branchInput])->first();
            }
        }

        return $branch
            ?? ($user && $user->branch && $user->branch->type === 'RETAIL_BRANCH' ? $user->branch : null)
            ?? Branch::where('code', 'OUT-SENO')->first()
            ?? Branch::where('type', 'RETAIL_BRANCH')->first()
            ?? Branch::firstOrFail();
    }
}
