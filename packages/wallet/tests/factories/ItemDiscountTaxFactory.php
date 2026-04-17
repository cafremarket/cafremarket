<?php

use Faker\Generator as Faker;
use Incevio\Package\Wallet\Test\Models\ItemDiscountTax;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(ItemDiscountTax::class, function (Faker $faker) {
    return [
        'name' => $faker->domainName,
        'price' => 250,
        'quantity' => 90,
    ];
});
