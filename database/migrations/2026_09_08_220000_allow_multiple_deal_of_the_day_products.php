<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AllowMultipleDealOfTheDayProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('deal_of_the_day')) {
            return;
        }

        // Older installs had a unique on deal_date only; drop it if present.
        foreach ($this->indexNamesOn('deal_date') as $index) {
            if ($index === 'PRIMARY') {
                continue;
            }
            // Skip the composite unique we want to keep.
            if ($index === 'deal_of_the_day_date_inventory_unique') {
                continue;
            }
            // Drop single-column unique / leftover deal_date indexes.
            if ($this->isUniqueOnOnlyDealDate($index)) {
                Schema::table('deal_of_the_day', function (Blueprint $table) use ($index) {
                    $table->dropUnique($index);
                });
            }
        }

        if (! $this->hasIndex('deal_of_the_day_date_inventory_unique')) {
            Schema::table('deal_of_the_day', function (Blueprint $table) {
                $table->unique(['deal_date', 'inventory_id'], 'deal_of_the_day_date_inventory_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('deal_of_the_day')) {
            return;
        }

        if ($this->hasIndex('deal_of_the_day_date_inventory_unique')) {
            Schema::table('deal_of_the_day', function (Blueprint $table) {
                $table->dropUnique('deal_of_the_day_date_inventory_unique');
            });
        }

        if (! $this->hasUniqueOnOnlyDealDate()) {
            Schema::table('deal_of_the_day', function (Blueprint $table) {
                $table->unique('deal_date');
            });
        }
    }

    private function hasIndex(string $name): bool
    {
        return in_array($name, $this->allIndexNames(), true);
    }

    private function allIndexNames(): array
    {
        return collect(DB::select('SHOW INDEX FROM deal_of_the_day'))
            ->pluck('Key_name')
            ->unique()
            ->values()
            ->all();
    }

    private function indexNamesOn(string $column): array
    {
        return collect(DB::select('SHOW INDEX FROM deal_of_the_day'))
            ->filter(function ($row) use ($column) {
                return $row->Column_name === $column;
            })
            ->pluck('Key_name')
            ->unique()
            ->values()
            ->all();
    }

    private function isUniqueOnOnlyDealDate(string $index): bool
    {
        $cols = collect(DB::select('SHOW INDEX FROM deal_of_the_day'))
            ->where('Key_name', $index)
            ->sortBy('Seq_in_index')
            ->pluck('Column_name')
            ->values()
            ->all();

        $nonUnique = collect(DB::select('SHOW INDEX FROM deal_of_the_day'))
            ->firstWhere('Key_name', $index);

        return $cols === ['deal_date'] && $nonUnique && (int) $nonUnique->Non_unique === 0;
    }

    private function hasUniqueOnOnlyDealDate(): bool
    {
        foreach ($this->allIndexNames() as $index) {
            if ($this->isUniqueOnOnlyDealDate($index)) {
                return true;
            }
        }

        return false;
    }
}
