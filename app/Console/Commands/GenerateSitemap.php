<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Sitemap\SitemapGenerator;

class GenerateSitemap extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'seo:generate-sitemap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $sitemap = SitemapGenerator::create(url('/'))->getSitemap();

        // products
        DB::table('products')->select('slug', 'shop_id')
            ->orderBy('id')->chunk(100, function ($items) use ($sitemap) {
                foreach ($items as $item) {
                    $sitemap->add(storefront_product_offers_url($item));
                }
            });

        // inventories
        DB::table('inventories')->select('slug', 'shop_id')
            ->orderBy('id')->chunk(100, function ($items) use ($sitemap) {
                foreach ($items as $item) {
                    $sitemap->add(storefront_product_url($item));
                }
            });

        // shops
        DB::table('shops')->select('slug')
            ->orderBy('id')->chunk(100, function ($items) use ($sitemap) {
                foreach ($items as $item) {
                    $sitemap->add(route('show.store', $item->slug));
                }
            });

        // categories (store-scoped when shop_id is set)
        DB::table('categories')->select('slug', 'shop_id')
            ->orderBy('id')->chunk(100, function ($cats) use ($sitemap) {
                $shopSlugs = DB::table('shops')
                    ->whereIn('id', collect($cats)->pluck('shop_id')->filter()->unique())
                    ->pluck('slug', 'id');

                foreach ($cats as $cat) {
                    if ($cat->shop_id && isset($shopSlugs[$cat->shop_id])) {
                        $sitemap->add(route('shop.category.browse', [
                            'slug' => $shopSlugs[$cat->shop_id],
                            'category' => $cat->slug,
                        ]));
                    } else {
                        $sitemap->add(route('category.browse', $cat->slug));
                    }
                }
            });

        $sitemap->writeToFile(public_path('sitemap.xml'));
    }
}
