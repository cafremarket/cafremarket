<script>
    var products = [];

    @foreach($order->inventories as $item)
      var product = {
          'sku': '{{ $item->sku }}',
          'title': '{{ $item->title }}',
          'category': {!! json_encode($item->product->subCategories->pluck('name')) !!},
          'price': {{ $item->pivot->unit_price }},
          'customer_id': {{ $order->customer_id ?? null }},
      };

      products.push(product);
    @endforeach

    dataLayer.push({
        'event': 'PurchasedProduct',
        'purchasedProducts': products
    });
</script>
