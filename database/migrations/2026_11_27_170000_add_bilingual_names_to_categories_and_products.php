<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
            $table->string('name_fa')->nullable()->after('name_en');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
            $table->string('name_fa')->nullable()->after('name_en');
        });

        DB::table('categories')->whereNull('name_en')->update(['name_en' => DB::raw('name')]);
        DB::table('products')->whereNull('name_en')->update(['name_en' => DB::raw('name')]);

        $categoryNames = [
            'starters' => 'پیش‌غذاها',
            'main-course' => 'غذای اصلی',
            'breads' => 'نان‌ها',
            'rice-biryani' => 'برنج و بریانی',
            'beverages' => 'نوشیدنی‌ها',
            'desserts' => 'دسرها',
        ];
        foreach ($categoryNames as $slug => $nameFa) {
            DB::table('categories')->where('slug', $slug)->whereNull('name_fa')->update(['name_fa' => $nameFa]);
        }

        $productNames = [
            'paneer-tikka' => 'پنیر تیکا',
            'chicken-65' => 'چیکن ۶۵',
            'veg-manchurian' => 'منچوری سبزیجات',
            'fish-fry' => 'ماهی سرخ‌شده',
            'butter-paneer-masala' => 'پنیر ماسالا با کره',
            'chicken-curry' => 'کاری مرغ',
            'dal-tadka' => 'دال تادکا',
            'palak-paneer' => 'پنیر اسفناج',
            'mutton-rogan-josh' => 'روغان جوش گوشت گوسفند',
            'butter-naan' => 'نان کره‌ای',
            'garlic-naan' => 'نان سیر',
            'tandoori-roti' => 'روتی تندوری',
            'veg-dum-biryani' => 'بریانی دم سبزیجات',
            'chicken-biryani' => 'بریانی مرغ',
            'mutton-biryani' => 'بریانی گوشت گوسفند',
            'jeera-rice' => 'برنج زیره',
            'masala-chai' => 'چای ماسالا',
            'cold-coffee' => 'قهوه سرد',
            'mango-lassi' => 'لسی انبه',
            'fresh-lime-soda' => 'نوشابه لیموی تازه',
            'gulab-jamun' => 'گلاب جامون',
            'kulfi' => 'کلفی',
            'ice-cream' => 'بستنی',
        ];
        foreach ($productNames as $slug => $nameFa) {
            DB::table('products')->where('slug', $slug)->whereNull('name_fa')->update(['name_fa' => $nameFa]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['name_en', 'name_fa']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['name_en', 'name_fa']);
        });
    }
};
