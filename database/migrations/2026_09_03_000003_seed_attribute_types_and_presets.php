<?php

use App\Models\Attribute;
use App\Models\AttributeValue;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed attribute types + useful shop attribute presets (Size, Colour, etc.).
     * Attribute Type is hidden in UI; types remain as internal FK defaults.
     */
    public function up(): void
    {
        $now = Carbon::now();

        $types = [
            1 => 'Color/Pattern',
            2 => 'Radio',
            3 => 'Select',
        ];

        foreach ($types as $id => $type) {
            $exists = DB::table('attribute_types')->where('id', $id)->exists();
            if (! $exists) {
                DB::table('attribute_types')->insert([
                    'id' => $id,
                    'type' => $type,
                ]);
            }
        }

        // Seed presets for every existing shop (store-scoped attributes).
        $shopIds = DB::table('shops')->pluck('id');

        foreach ($shopIds as $shopId) {
            $this->seedShopPresets((int) $shopId, $now);
        }
    }

    private function seedShopPresets(int $shopId, Carbon $now): void
    {
        $presets = [
            [
                'name' => 'Colour',
                'attribute_type_id' => Attribute::TYPE_COLOR,
                'order' => 1,
                'values' => [
                    ['value' => 'Black', 'color' => '#000000'],
                    ['value' => 'White', 'color' => '#ffffff'],
                    ['value' => 'Red', 'color' => '#e53935'],
                    ['value' => 'Blue', 'color' => '#1e88e5'],
                    ['value' => 'Green', 'color' => '#43a047'],
                    ['value' => 'Yellow', 'color' => '#fdd835'],
                    ['value' => 'Pink', 'color' => '#ec407a'],
                    ['value' => 'Grey', 'color' => '#9e9e9e'],
                    ['value' => 'Brown', 'color' => '#6d4c41'],
                    ['value' => 'Orange', 'color' => '#fb8c00'],
                ],
            ],
            [
                'name' => 'Size',
                'attribute_type_id' => Attribute::TYPE_SELECT,
                'order' => 2,
                'values' => [
                    ['value' => 'XS', 'color' => null],
                    ['value' => 'S', 'color' => null],
                    ['value' => 'M', 'color' => null],
                    ['value' => 'L', 'color' => null],
                    ['value' => 'XL', 'color' => null],
                    ['value' => 'XXL', 'color' => null],
                    ['value' => '3XL', 'color' => null],
                ],
            ],
            [
                'name' => 'Material',
                'attribute_type_id' => Attribute::TYPE_SELECT,
                'order' => 3,
                'values' => [
                    ['value' => 'Cotton', 'color' => null],
                    ['value' => 'Polyester', 'color' => null],
                    ['value' => 'Leather', 'color' => null],
                    ['value' => 'Wool', 'color' => null],
                    ['value' => 'Silk', 'color' => null],
                    ['value' => 'Denim', 'color' => null],
                    ['value' => 'Metal', 'color' => null],
                    ['value' => 'Plastic', 'color' => null],
                ],
            ],
            [
                'name' => 'Style',
                'attribute_type_id' => Attribute::TYPE_SELECT,
                'order' => 4,
                'values' => [
                    ['value' => 'Casual', 'color' => null],
                    ['value' => 'Formal', 'color' => null],
                    ['value' => 'Sport', 'color' => null],
                    ['value' => 'Classic', 'color' => null],
                    ['value' => 'Modern', 'color' => null],
                ],
            ],
            [
                'name' => 'Gender',
                'attribute_type_id' => Attribute::TYPE_RADIO,
                'order' => 5,
                'values' => [
                    ['value' => 'Men', 'color' => null],
                    ['value' => 'Women', 'color' => null],
                    ['value' => 'Unisex', 'color' => null],
                    ['value' => 'Kids', 'color' => null],
                ],
            ],
            [
                'name' => 'Storage',
                'attribute_type_id' => Attribute::TYPE_SELECT,
                'order' => 6,
                'values' => [
                    ['value' => '32GB', 'color' => null],
                    ['value' => '64GB', 'color' => null],
                    ['value' => '128GB', 'color' => null],
                    ['value' => '256GB', 'color' => null],
                    ['value' => '512GB', 'color' => null],
                    ['value' => '1TB', 'color' => null],
                ],
            ],
        ];

        foreach ($presets as $preset) {
            $attribute = Attribute::withTrashed()
                ->where('shop_id', $shopId)
                ->where('name', $preset['name'])
                ->first();

            if (! $attribute) {
                $attribute = Attribute::create([
                    'shop_id' => $shopId,
                    'name' => $preset['name'],
                    'attribute_type_id' => $preset['attribute_type_id'],
                    'order' => $preset['order'],
                ]);
            } elseif ($attribute->trashed()) {
                $attribute->restore();
                $attribute->update([
                    'attribute_type_id' => $preset['attribute_type_id'],
                    'order' => $preset['order'],
                ]);
            }

            foreach ($preset['values'] as $i => $value) {
                $exists = AttributeValue::withTrashed()
                    ->where('attribute_id', $attribute->id)
                    ->where('value', $value['value'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                AttributeValue::create([
                    'shop_id' => $shopId,
                    'attribute_id' => $attribute->id,
                    'value' => $value['value'],
                    'color' => $value['color'],
                    'order' => $i + 1,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Keep seeded data; types are required by FK.
    }
};
