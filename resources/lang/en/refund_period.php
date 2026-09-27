<?php

return [
    'label' => 'Refund / return period',
    'help' => 'How long after delivery the buyer can ask for a refund or return this item (max 15 days). Choose "No refund / return" for items you do not take back.',
    'variant_help' => 'Each variant can have its own period.',
    'same_as_product' => 'Same as product',
    'no_refund' => 'No refund / return',
    'days_option' => '{1} :count day|[2,*] :count days',
    'days_policy' => '{1} :count-day refund / return|[2,*] :count-day refund / return',
    'after_delivery' => '{1} Refund / return within :count day of delivery|[2,*] Refund / return within :count days of delivery',
    'open_until' => 'Refund / return possible until :date',
    'closed_on' => 'Refund / return period ended on :date',
    'error_no_refund' => 'This item cannot be refunded or returned.',
    'error_closed' => 'The refund / return period for this item ended on :date.',
    'error_items_not_returnable' => 'Some selected items can no longer be returned: :items',
];
