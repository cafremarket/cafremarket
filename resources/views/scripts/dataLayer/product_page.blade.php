<script>
  dataLayer.push({
    'event': 'ViewProduct',
    'Product': {
      'sku': '{{ $item->sku }}',
      'title': '{{ $item->title }}',
      'categories': {!! json_encode($item->product->subCategories->pluck('name')) !!},
      'price': {{ $item->current_sale_price() }},
      'user_id': {{ $item->user_id ?? null }},
    }
  });
</script>
