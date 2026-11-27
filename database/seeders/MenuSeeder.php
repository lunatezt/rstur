<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\TaxCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = Restaurant::where('slug', 'restaurant-main')->firstOrFail();
        $taxCategory = TaxCategory::where('restaurant_id', $restaurant->id)
            ->where('name', 'GST 5%')
            ->firstOrFail();

        $categories = [
            [
                'slug' => 'steak-kebab',
                'name_en' => 'Steak & Kebab',
                'name_fa' => 'استیک و کباب',
                'unit_type' => 'plate',
                'prep_time' => 25,
                'items' => [
                    ['Chopan Kabab', 'چوپان کباب', 450, false],
                    ['Afghany Shamy Kabab', 'افغانی شامی کباب', 350, false],
                    ['Chicken Barbecue', 'چکن باربیکیو', 400, false],
                    ['Turkish Jigar Barbecue', 'ترکی جگر باربیکیو', 350, false],
                    ['Adana Barbecue', 'آدانا باربیکیو', 450, false],
                    ['Kuzu Shish', 'کوزو شیش کباب', 500, false],
                    ['Urfa Pistachio Kebab', 'اورفا پسته‌ای کباب', 550, false],
                    ['Mix Barbecue', 'مکس باربیکیو', 800, false],
                    ['Chicken Barbecue Full', 'چکن باربیکیو فول', 700, false],
                    ['Min Turkish Barbecue', 'مینی ترکی باربیکیو', 250, false],
                    ['Takta Special Mix Steak', 'تکتا سپیشل مکس استیک', 850, false],
                    ['Tika Kabab', 'تکه کباب', 400, false],
                    ['Qabargha Kabab', 'قبرغه کباب', 500, false],
                    ['Chicken Tika Boty', 'چکن تکه بوتی', 400, false],
                    ['Luqma Kabab', 'لقمه کباب', 350, false],
                ],
            ],
            [
                'slug' => 'afghany-foods',
                'name_en' => 'Afghany Foods',
                'name_fa' => 'غذاهای افغانی',
                'unit_type' => 'plate',
                'prep_time' => 30,
                'items' => [
                    ['Mahicha Qabil', 'ماهیچه قابلی', 550, false],
                    ['Kobida Chalaw', 'کوبیده چلو', 350, false],
                    ['Tika Kabab Chalaw', 'تکه کباب چلو', 400, false],
                    ['Shola Ghorbandi with Meat', 'شوله غوربندی با گوشت', 300, false],
                    ['Mandi Grilled Chicken', 'مندی گریل چکن', 500, false],
                    ['Lamb Qabili Palao', 'قابلی پلو با گوشت گوسفند', 600, false],
                    ['Mulung Qabili', 'مغلنگ قابلی', 500, false],
                    ['Chicken Barbecue Chalaw', 'چکن باربیکیو چلو', 450, false],
                    ['Zirishk Palao with Lamb Meat', 'زرشک پلو با گوشت گوسفند', 600, false],
                    ['Mandi Palao', 'مندی پلو', 650, false],
                    ['Medium Yamani Mandi', 'مندی یمنی متوسط', 400, false],
                    ['Small Yamani Mandi', 'مندی یمنی کوچک', 250, false],
                    ['Complete Chicken', 'چکن مکمل', 650, false],
                    ['Large Yamani Mandi', 'مندی یمنی بزرگ', 850, false],
                    ['Qabili Digi with Lamb Meat', 'قابلی دیگی با گوشت گوسفند', 700, false],
                    ['Naring Palao with Sheep Meat', 'نارنج پلو با گوشت گوسفند', 600, false],
                    ['Manto & Ashak', 'منتو و آشک', 450, false],
                    ['Simple Chalaw', 'چلو ساده', 250, true],
                    ['Spicy Lamb Dashi Qurma', 'دشی قورمه تند گوشت گوسفند', 400, false],
                    ['Qurma Dal Nakhod with Meatball', 'قورمه دال نخود با کوفته', 450, false],
                    ['Sheep Karaye', 'کرایی گوسفندی', 500, false],
                    ['Chicken Karaye', 'کرایی مرغ', 400, false],
                    ['Eggplant Burani', 'بورانی بانجان', 250, true],
                    ['Takta Tandori Rosh', 'تکتا تنوری روش', 400, false],
                    ['Chicken Biryani', 'چکن بریانی', 450, false],
                ],
            ],
            [
                'slug' => 'pakistani-foods',
                'name_en' => 'Pakistani Foods',
                'name_fa' => 'غذاهای پاکستانی',
                'unit_type' => 'plate',
                'prep_time' => 25,
                'items' => [
                    ['Maghz Masala', 'مغز مصالحه', 350, false],
                    ['Chicken Achari Handi', 'چکن اچاری هندی', 400, false],
                    ['Matn Handi', 'مٹن هندی', 450, false],
                    ['Simple Dal Mash', 'دال ماش ساده', 250, true],
                    ['Meat Palak', 'گوشت پالک', 450, false],
                    ['Special Kima', 'قیمه مخصوص', 400, false],
                    ['Chicken Jalfrezy', 'چکن جلفریزی', 400, false],
                    ['Takta Special Handi', 'تکتا سپیشل هندی', 550, false],
                    ['Chicken Handi', 'چکن هندی', 450, false],
                    ['Chicken Makni Handi', 'چکن مکھنی هندی', 450, false],
                    ['Matn Achar Handi', 'مٹن اچاری هندی', 450, false],
                    ['Special Dal Mash', 'دال ماش مخصوص', 300, true],
                    ['Palak Panir', 'پالک پنیر', 350, true],
                    ['Simple Palak', 'پالک ساده', 250, true],
                    ['Chicken Jinger', 'چکن جنجر', 400, false],
                ],
            ],
            [
                'slug' => 'chinese-foods',
                'name_en' => 'Chinese Foods',
                'name_fa' => 'غذاهای چینی',
                'unit_type' => 'plate',
                'prep_time' => 20,
                'items' => [
                    ['Chicken Chomain', 'چکن چومین', 350, false],
                    ['Beef Chomain', 'بیف چومین', 400, false],
                    ['Beef Chili Rice', 'بیف چیلی همرای برنج', 400, false],
                    ['Chicken Shishli with Rice', 'چکن شیشلی همرای برنج', 350, false],
                    ['Takta Special Chomain', 'تکتا سپیشل چومین', 450, false],
                    ['Vegetables Chomain', 'ویجیبل چومین', 300, true],
                    ['Chicken Chili with Rice', 'چکن چیلی همرای برنج', 350, false],
                    ['Chicken Manchorin with Rice', 'چکن منچورین همرای برنج', 400, false],
                ],
            ],
            [
                'slug' => 'platters',
                'name_en' => 'Platters',
                'name_fa' => 'پلیترها',
                'unit_type' => 'plate',
                'prep_time' => 30,
                'items' => [
                    ['Takta Platter for One', 'پلیتر یک نفره تکتا', 550, false],
                    ['Takta Platter for Two', 'پلیتر دو نفره تکتا', 1000, false],
                    ['Takta Platter for Six', 'پلیتر شش نفره تکتا', 2400, false],
                ],
            ],
            [
                'slug' => 'adana-special-barbecue',
                'name_en' => 'Adana Special Barbecue',
                'name_fa' => 'باربیکیو ویژه آدانا',
                'unit_type' => 'plate',
                'prep_time' => 30,
                'items' => [
                    ['Adana Barbecue 400g', 'آدانا کباب ۴۰۰ گرام', 500, false],
                    ['Adana Barbecue 600g', 'آدانا کباب ۶۰۰ گرام', 700, false],
                    ['Adana Barbecue 2kg', 'آدانا کباب ۲ کیلو', 2100, false],
                ],
            ],
            [
                'slug' => 'tea',
                'name_en' => 'Tea',
                'name_fa' => 'چای',
                'unit_type' => 'glass',
                'prep_time' => 5,
                'items' => [
                    ['Green Tea', 'چای سبز', 50, true],
                    ['Black Tea', 'چای سیاه', 50, true],
                    ['Saffron Tea', 'چای زعفران', 100, true],
                    ['Lemon Honey Tea', 'چای لیمو عسل', 80, true],
                    ['Mint Tea', 'چای نعناع', 60, true],
                ],
            ],
            [
                'slug' => 'pizza',
                'name_en' => 'Pizza',
                'name_fa' => 'پیتزا',
                'unit_type' => 'piece',
                'prep_time' => 20,
                'items' => [
                    ['Takta Special Pizza', 'پیتزا سپیشل تکتا', 500, false],
                    ['Beef Pizza', 'پیتزا بیف', 400, false],
                    ['Chicken Pizza', 'پیتزا چکن', 400, false],
                    ['Vegetable Pizza', 'پیتزا سبزیجات', 350, true],
                    ['Four Cheese Pizza', 'پیتزا چهار پنیر', 450, true],
                    ['Pepperoni Pizza', 'پیتزا پپرونی', 500, false],
                    ['Special Beef Pizza', 'پیتزا بیف مخصوص', 500, false],
                    ['Chicken Tikka Pizza', 'پیتزا چکن تکه', 450, false],
                    ['Margarita Pizza', 'پیتزا مارگاریتا', 350, true],
                    ['Barbecue Chicken Pizza', 'پیتزا باربیکیو چکن', 450, false],
                    ['Italian Pizza', 'پیتزا ایتالیایی', 500, false],
                    ['Meat Feast Pizza', 'پیتزا میت فیست', 550, false],
                ],
            ],
            [
                'slug' => 'sandwich',
                'name_en' => 'Sandwich',
                'name_fa' => 'ساندویچ',
                'unit_type' => 'piece',
                'prep_time' => 15,
                'items' => [
                    ['Club Sandwich', 'کلپ ساندویچ', 250, false],
                    ['Beef Sandwich', 'بیف ساندویچ', 220, false],
                    ['Chicken Sandwich', 'چکن ساندویچ', 220, false],
                    ['Chicken Burger Sandwich', 'چکن برگر ساندویچ', 250, false],
                    ['Crispy Chicken Sandwich', 'کریسپی چکن ساندویچ', 250, false],
                    ['Special Sandwich', 'ساندویچ مخصوص', 250, false],
                    ['Cheese Sandwich', 'ساندویچ پنیر', 180, true],
                    ['Chicken Cheese Sandwich', 'ساندویچ چکن چیز', 270, false],
                ],
            ],
            [
                'slug' => 'sweets',
                'name_en' => 'Sweets',
                'name_fa' => 'شیرینی و دسر',
                'unit_type' => 'piece',
                'prep_time' => 5,
                'items' => [
                    ['Special Chocolate Cake', 'کیک چاکلیت مخصوص', 250, true],
                    ['Special Pastry Cake', 'کیک سفری خاص', 220, true],
                    ['Turkish Cream Cake', 'ترکی کریم کیک', 250, true],
                    ['Pistachio Cheese Cake', 'پسته‌ای چیز کیک', 300, true],
                    ['Chocolate Brownie', 'چاکلیت براونی', 180, true],
                    ['Turkish Rice Milk', 'ترکی رایس ملک', 200, true],
                    ['Takta Special Baklava', 'باقلوا خاص تکتا', 350, true],
                ],
            ],
            [
                'slug' => 'burger',
                'name_en' => 'Burger',
                'name_fa' => 'برگر',
                'unit_type' => 'piece',
                'prep_time' => 15,
                'items' => [
                    ['Takta Special Burger', 'تکتا اسپیشل برگر', 400, false],
                    ['Cheese Beef Burger', 'بیف برگر همرای پنیر', 300, false],
                    ['Beef Burger', 'بیف برگر', 250, false],
                    ['Cheese Chicken Burger', 'چکن برگر همرای پنیر', 300, false],
                    ['Chicken Burger', 'چکن برگر', 250, false],
                    ['Zinger Cheese Burger', 'زنگر برگر همرای پنیر', 350, false],
                    ['Zinger Burger', 'زنگر برگر', 300, false],
                    ['Chips', 'چپس', 100, true],
                ],
            ],
            [
                'slug' => 'coffee',
                'name_en' => 'Coffee',
                'name_fa' => 'قهوه',
                'unit_type' => 'glass',
                'prep_time' => 5,
                'items' => [
                    ['Takta Special Coffee', 'کافی مخصوص تکتا', 180, true],
                    ['Turki Coffee', 'کافی ترکی', 120, true],
                    ['Cappuccino Coffee', 'کافی کاپوچینو', 150, true],
                    ['Espresso Coffee', 'کافی اسپرسو', 100, true],
                    ['American Coffee', 'کافی امریکایی', 120, true],
                ],
            ],
            [
                'slug' => 'soup',
                'name_en' => 'Soup',
                'name_fa' => 'سوپ',
                'unit_type' => 'bowl',
                'prep_time' => 15,
                'items' => [
                    ['Takta Special Soup', 'تکتا سپیشل سوپ', 250, false],
                    ['Hot & Sour Soup', 'سوپ هات اند ساور', 200, false],
                    ['Chicken Caron Soup', 'سوپ چکن کارون', 220, false],
                    ['Takta Special Vegetable Soup', 'سوپ سبزیجات مخصوص تکتا', 200, true],
                    ['Chicken Soup', 'چکن سوپ', 180, false],
                    ['Vegetable Soup', 'سوپ سبزیجات', 150, true],
                ],
            ],
            [
                'slug' => 'turki-salad',
                'name_en' => 'Turki Salad',
                'name_fa' => 'سالاد ترکی',
                'unit_type' => 'plate',
                'prep_time' => 10,
                'items' => [
                    ['Turki Salad', 'سلاد ترکی', 180, true],
                    ['Shame Chicken Salad', 'سلاد چکن شامی', 250, false],
                    ['Afghani Salad', 'سلاد افغانی', 180, true],
                    ['Greek Salad', 'سلاد یونانی', 220, true],
                    ['Arabic Garden Salad', 'سلاد خاص عربی', 250, true],
                    ['Persian Salad', 'سلاد ایرانی', 200, true],
                    ['Special Pakistani Salad', 'سلاد مخصوص پاکستانی', 250, true],
                ],
            ],
            [
                'slug' => 'shawarma',
                'name_en' => 'Shawarma',
                'name_fa' => 'شاورما',
                'unit_type' => 'piece',
                'prep_time' => 15,
                'items' => [
                    ['Shawarma with Paratha Bread', 'شورمه با نان پراته', 250, false],
                    ['Shawarma with Special Bread', 'شورمه با نان مخصوص خود', 300, false],
                    ['Takta Special Shawarma', 'تکتا سپیشل شورمه', 350, false],
                ],
            ],
        ];

        foreach ($categories as $sortOrder => $categoryData) {
            $category = Category::updateOrCreate(
                ['restaurant_id' => $restaurant->id, 'slug' => $categoryData['slug']],
                [
                    'name' => $categoryData['name_en'],
                    'name_en' => $categoryData['name_en'],
                    'name_fa' => $categoryData['name_fa'],
                    'sort_order' => $sortOrder + 1,
                    'is_active' => true,
                    'display_in_pos' => true,
                    'display_in_kds' => true,
                ]
            );

            foreach ($categoryData['items'] as $displayOrder => [$nameEn, $nameFa, $price, $isVeg]) {
                $slug = Str::slug($nameEn);
                Product::updateOrCreate(
                    ['restaurant_id' => $restaurant->id, 'slug' => $slug],
                    [
                        'category_id' => $category->id,
                        'tax_category_id' => $taxCategory->id,
                        'name' => $nameEn,
                        'name_en' => $nameEn,
                        'name_fa' => $nameFa,
                        'price' => $price,
                        'cost_price' => round($price * 0.6, 2),
                        'unit_type' => $categoryData['unit_type'],
                        'is_veg' => $isVeg,
                        'is_available' => true,
                        'is_active' => true,
                        'preparation_time_minutes' => $categoryData['prep_time'],
                        'display_order' => $displayOrder + 1,
                    ]
                );
            }
        }
    }
}
