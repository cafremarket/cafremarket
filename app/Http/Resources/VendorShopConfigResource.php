<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorShopConfigResource extends JsonResource
{
    /**
     * Vendor app shop configuration (configs table).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'shop_id' => (int) $this->shop_id,
            'support_phone' => $this->support_phone,
            'support_phone_toll_free' => $this->support_phone_toll_free,
            'support_email' => $this->support_email,
            'support_agent' => (int) ($this->support_agent ?? 0),
            'default_sender_email_address' => $this->default_sender_email_address,
            'default_email_sender_name' => $this->default_email_sender_name,
            'return_refund' => $this->return_refund,
            'order_number_prefix' => $this->order_number_prefix,
            'order_number_suffix' => $this->order_number_suffix,
            'default_tax_id' => (int) ($this->default_tax_id ?? 0),
            'order_handling_cost' => $this->order_handling_cost,
            'auto_archive_order' => (bool) $this->auto_archive_order,
            'default_payment_method_id' => $this->default_payment_method_id,
            'pagination' => (int) ($this->pagination ?? 0),
            'show_shop_desc_with_listing' => (bool) $this->show_shop_desc_with_listing,
            'show_refund_policy_with_listing' => (bool) $this->show_refund_policy_with_listing,
            'alert_quantity' => (int) ($this->alert_quantity ?? 0),
            'digital_goods_only' => (bool) $this->digital_goods_only,
            'default_warehouse_id' => $this->default_warehouse_id,
            'default_supplier_id' => $this->default_supplier_id,
            'default_packaging_ids' => $this->default_packaging_ids,
            'notify_new_message' => (bool) $this->notify_new_message,
            'notify_alert_quantity' => (bool) $this->notify_alert_quantity,
            'notify_inventory_out' => (bool) $this->notify_inventory_out,
            'notify_new_order' => (bool) $this->notify_new_order,
            'notify_abandoned_checkout' => (bool) $this->notify_abandoned_checkout,
            'notify_new_disput' => (bool) $this->notify_new_disput,
            'enable_live_chat' => true,
            'notify_new_chat' => (bool) $this->notify_new_chat,
            'maintenance_mode' => (bool) $this->maintenance_mode,
            'pending_verification' => (bool) $this->pending_verification,
            'verification_request_status' => $this->verificationRequestStatus(),
            'verification_rejection_reason' => $this->verification_rejection_reason,
            'verification_rejected_at' => optional($this->verification_rejected_at)->toIso8601String(),
            'shop_verified' => (bool) optional($this->shop)->isVerified(),
            'active_ecommerce' => (bool) $this->active_ecommerce,
            'pay_online' => (bool) $this->pay_online,
            'pay_in_person' => (bool) $this->pay_in_person,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
