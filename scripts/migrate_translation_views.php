<?php
/**
 * Migrates translation bulk upload review + import failed views to card_start pattern.
 * Run: php scripts/migrate_translation_views.php
 */

$base = dirname(__DIR__) . '/resources/views/admin';

$reviews = [
    'category/category_translation_bulk_upload_review.blade.php' => [
        'icon' => 'fa-sitemap',
        'model' => "trans('app.model.category')",
        'importRoute' => 'admin.catalog.category.translate.bulk.import',
        'columns' => ['slug', 'lang', 'name', 'description'],
    ],
    'category/categoryGroup_translation_bulk_upload_review.blade.php' => [
        'icon' => 'fa-folder',
        'model' => "trans('app.model.category_group')",
        'importRoute' => 'admin.catalog.categoryGroup.translate.bulk.import',
        'columns' => ['slug', 'lang', 'name', 'description'],
    ],
    'category/subGroup/_translation_bulk_upload_review.blade.php' => [
        'icon' => 'fa-folder-open',
        'model' => "trans('app.model.category_sub_group')",
        'importRoute' => 'admin.catalog.categorySubGroup.translate.bulk.import',
        'columns' => ['slug', 'lang', 'name'],
    ],
    'manufacturer/_translation_bulk_upload_review.blade.php' => [
        'icon' => 'fa-industry',
        'model' => "trans('app.model.manufacturer')",
        'importRoute' => 'admin.catalog.manufacturer.translate.bulk.import',
        'columns' => ['slug', 'lang', 'name', 'description'],
    ],
    'shop/_translation_bulk_upload_review.blade.php' => [
        'icon' => 'fa-store',
        'model' => "trans('app.model.shop')",
        'importRoute' => 'admin.vendor.shop.translate.bulk.import',
        'columns' => ['slug', 'lang', 'name', 'description'],
    ],
    'product/_translation_bulk_upload_review.blade.php' => [
        'icon' => 'fa-cube',
        'model' => "trans('app.model.product')",
        'importRoute' => 'admin.catalog.product.translate.bulk.import',
        'columns' => ['slug', 'lang', 'name', 'brand', 'description'],
    ],
    'inventory/_translation_bulk_upload_review.blade.php' => [
        'icon' => 'fa-cubes',
        'model' => "trans('app.model.inventory')",
        'importRoute' => 'admin.stock.inventory.translate.bulk.import',
        'columns' => ['slug', 'lang', 'title', 'description', 'key_features', 'condition_note'],
    ],
];

$columnLabels = [
    'slug' => 'app.slug',
    'lang' => 'app.language',
    'name' => 'app.name',
    'title' => 'app.title',
    'brand' => 'app.brand',
    'description' => 'app.description',
    'key_features' => 'app.key_features',
    'condition_note' => 'app.condition_note',
];

foreach ($reviews as $path => $cfg) {
    $headers = '';
    $cells = '';
    foreach ($cfg['columns'] as $col) {
        $label = $columnLabels[$col];
        $headers .= "        <th>{{ trans('{$label}') }}</th>\n";
        $cells .= "          <td>{{ \$row['{$col}'] }}</td>\n";
    }

    $content = <<<BLADE
@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.preview') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.model_translations_bulk_upload', ['model' => {$cfg['model']}]) . ' — ' . trans('app.preview'),
    'icon' => '{$cfg['icon']}',
    'headerExtra' => '<small class="text-muted">(' . e(trans('app.total_number_of_rows', ['value' => count(\$rows)])) . ')</small>',
    'bodyClass' => 'responsive-table',
  ])

  <table class="table table-striped admin-table">
    <thead>
      <tr>
{$headers}      </tr>
    </thead>
    <tbody>
      @foreach (\$rows as \$row)
        <tr>
{$cells}        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @php
    \$hiddenFields = '';
    foreach (\$rows as \$row) {
      \$hiddenFields .= Form::hidden('data[]', serialize(\$row));
    }
  @endphp
  @include('admin.partials.ui.import_footer', [
    'cancelUrl' => url()->previous(),
    'rowCount' => count(\$rows),
    'formRoute' => '{$cfg['importRoute']}',
    'hiddenFields' => \$hiddenFields,
    'submitLabel' => trans('app.looks_good'),
  ])
@endsection

BLADE;

    file_put_contents($base . '/' . str_replace('/', DIRECTORY_SEPARATOR, $path), $content);
    echo "Updated review: {$path}\n";
}

$failed = [
    'category/category_translation_import_failed.blade.php' => [
        'icon' => 'fa-sitemap',
        'downloadRoute' => 'admin.catalog.category.translate.download.failedRows',
        'columns' => ['slug', 'lang', 'name', 'description'],
        'rowPrefix' => "\$row['data']",
    ],
    'category/categoryGroup_translation_import_failed.blade.php' => [
        'icon' => 'fa-folder',
        'downloadRoute' => 'admin.catalog.categoryGroup.translate.download.failedRows',
        'columns' => ['slug', 'lang', 'name', 'description'],
        'rowPrefix' => "\$row['data']",
    ],
    'category/subGroup/_translation_import_failed.blade.php' => [
        'icon' => 'fa-folder-open',
        'downloadRoute' => 'admin.catalog.categorySubGroup.translate.download.failedRows',
        'columns' => ['slug', 'lang', 'name'],
        'rowPrefix' => "\$row['data']",
    ],
    'manufacturer/_translation_import_failed.blade.php' => [
        'icon' => 'fa-industry',
        'downloadRoute' => 'admin.catalog.manufacturer.translate.download.failedRows',
        'columns' => ['slug', 'lang', 'name', 'description'],
        'rowPrefix' => "\$row['data']",
    ],
    'shop/_translation_import_failed.blade.php' => [
        'icon' => 'fa-store',
        'downloadRoute' => 'admin.vendor.shop.translate.download.failedRows',
        'columns' => ['slug', 'lang', 'name', 'description'],
        'rowPrefix' => "\$row['data']",
    ],
    'product/translation_import_failed.blade.php' => [
        'icon' => 'fa-cube',
        'downloadRoute' => 'admin.catalog.product.translate.download.failedRows',
        'columns' => ['slug', 'lang', 'name', 'brand', 'description'],
        'rowPrefix' => "\$row['data']",
    ],
    'inventory/translation_import_failed.blade.php' => [
        'icon' => 'fa-cubes',
        'downloadRoute' => 'admin.stock.inventory.translate.download.failedRows',
        'columns' => ['slug', 'lang', 'title', 'description', 'key_features', 'condition_note'],
        'rowPrefix' => "\$row['data']",
    ],
];

foreach ($failed as $path => $cfg) {
    $headers = '';
    $cells = '';
    $prefix = $cfg['rowPrefix'];
    foreach ($cfg['columns'] as $col) {
        $label = $columnLabels[$col];
        $headers .= "        <th>{{ trans('{$label}') }}</th>\n";
        $cells .= "          <td>{{ {$prefix}['{$col}'] }}</td>\n";
    }

    $content = <<<BLADE
@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.import_failed') }}
@endsection

@section('content')
  <div class="alert alert-danger">
    <strong><i class="icon fa fa-info-circle"></i> {{ trans('app.notice') }}</strong>
    {{ trans('messages.import_ignored') }}
  </div>

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.import_failed'),
    'icon' => '{$cfg['icon']}',
    'class' => 'admin-card--danger',
    'headerExtra' => '<small class="text-muted">(' . e(trans('app.total_number_of_rows', ['value' => count(\$failed_rows)])) . ')</small>',
    'bodyClass' => 'responsive-table',
  ])

  <table class="table table-striped admin-table">
    <thead>
      <tr>
{$headers}      </tr>
    </thead>
    <tbody>
      @foreach (\$failed_rows as \$row)
        <tr>
{$cells}        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @php
    \$hiddenFields = '';
    foreach (\$failed_rows as \$row) {
      \$hiddenFields .= '<input type="hidden" name="data[]" value="' . e(serialize(\$row['data'])) . '">';
    }
  @endphp
  @include('admin.partials.ui.import_footer', [
    'cancelUrl' => url()->previous(),
    'cancelClass' => 'btn-danger',
    'cancelLabel' => trans('app.dismiss'),
    'rowCount' => count(\$failed_rows),
    'formRoute' => '{$cfg['downloadRoute']}',
    'hiddenFields' => \$hiddenFields,
    'submitLabel' => trans('app.download_failed_rows'),
    'submitClass' => 'btn btn-new btn-flat',
  ])
@endsection

BLADE;

    file_put_contents($base . '/' . str_replace('/', DIRECTORY_SEPARATOR, $path), $content);
    echo "Updated failed: {$path}\n";
}

echo "Done.\n";
