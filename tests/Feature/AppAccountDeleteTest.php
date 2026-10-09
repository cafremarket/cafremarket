<?php

namespace Tests\Feature;

use App\Models\DeliveryBoy;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AppAccountDeleteTest extends TestCase
{
    use DatabaseTransactions;

    public function test_delivery_app_delete_removes_every_store_account()
    {
        $email = 'rider-delete-test@example.com';
        $rider = DeliveryBoy::factory()->create(['email' => $email]);
        $other = DeliveryBoy::factory()->create(['email' => $email]);

        $jwt = $rider->generateToken('delivery_boy');

        $this->withHeader('Authorization', 'Bearer ' . $jwt)
            ->deleteJson('/api/deliveryboy/account/delete')
            ->assertOk();

        $this->assertNull(DeliveryBoy::find($rider->id));
        $this->assertNull(DeliveryBoy::find($other->id));

        // The old token must stop working.
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer ' . $jwt)
            ->getJson('/api/deliveryboy/profile')
            ->assertStatus(401);
    }

    public function test_web_deletion_page_removes_delivery_account()
    {
        config(['services.recaptcha.key' => null]);

        $rider = DeliveryBoy::factory()->create(['email' => 'rider-web-delete@example.com']);
        $url = rtrim(config('app.url'), '/') . '/account/delete';

        $this->get($url . '?type=delivery')->assertOk();

        $this->post($url, [
            '_token' => session()->token(),
            'type' => 'delivery',
            'email' => $rider->email,
            'password' => '123456',
            'confirm' => '1',
        ])->assertRedirect()->assertSessionHas('account_deleted', true);

        $this->assertNull(DeliveryBoy::find($rider->id));
    }

    public function test_vendor_app_delete_removes_merchant_account()
    {
        $user = User::where('role_id', Role::MERCHANT)->firstOrFail();
        $jwt = $user->generateToken('vendor_api');

        $this->withHeader('Authorization', 'Bearer ' . $jwt)
            ->deleteJson('/api/vendor/user/account/delete')
            ->assertOk();

        $this->assertNull(User::find($user->id));
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_web_deletion_page_removes_seller_account()
    {
        config(['services.recaptcha.key' => null]);

        $user = User::where('role_id', Role::MERCHANT)->firstOrFail();
        $user->forceFill(['password' => bcrypt('secret-123')])->save();
        $url = rtrim(config('app.url'), '/') . '/account/delete';

        $this->get($url . '?type=seller')->assertOk();

        $this->post($url, [
            '_token' => session()->token(),
            'type' => 'seller',
            'email' => $user->email,
            'password' => 'secret-123',
            'confirm' => '1',
        ])->assertRedirect()->assertSessionHas('account_deleted', true);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_alias_urls_redirect_to_deletion_pages()
    {
        $base = rtrim(config('app.url'), '/');

        foreach ([
            'delete-account' => '/account/delete',
            'delivery/delete-account' => '/account/delete?type=delivery',
            'vendor/delete-account' => '/account/delete?type=seller',
            'seller/data-deletion' => '/account/data-deletion?type=seller',
            'delivery/data-deletion' => '/account/data-deletion?type=delivery',
        ] as $from => $to) {
            $this->get($base . '/' . $from)->assertStatus(301)->assertRedirect($base . $to);
        }

        foreach (['customer', 'seller', 'delivery'] as $type) {
            $this->get($base . '/account/delete?type=' . $type)->assertOk();
            $this->get($base . '/account/data-deletion?type=' . $type)->assertOk();
        }
    }
}
