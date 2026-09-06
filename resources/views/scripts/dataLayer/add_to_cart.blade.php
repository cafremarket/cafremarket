var price;
if(responseData?.item){
if (
(responseData.item.offer_price > 0) &&
(responseData.item.offer_price < responseData.item.sale_price)
) {
price = responseData.item.offer_price;
} else {
price = responseData.item.sale_price;
}

dataLayer.push({
'event': 'AddToCart',
'addToCartProduct': {
'sku': responseData.item.sku,
'title': responseData.item.title,
'quantity': qtt ? qtt : 1,
'price': price
}
});
}

