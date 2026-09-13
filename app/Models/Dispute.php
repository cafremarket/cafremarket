<?php

namespace App\Models;

use App\Common\Attachable;
use App\Common\Loggable;
use App\Common\Repliable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Dispute extends BaseModel
{
    use Attachable, HasFactory, Loggable, Repliable;

    const STATUS_NEW = 1;

    const STATUS_OPEN = 2;

    const STATUS_WAITING = 3;

    const STATUS_APPEALED = 4; // legacy: treated as waiting for admin

    const STATUS_SOLVED = 5; // resolved, not closed

    const STATUS_CLOSED = 6;

    const STATUS_CLOSE_REQUESTED = 7;

    const RAISED_BY_CUSTOMER = 'customer';

    const RAISED_BY_VENDOR = 'vendor';

    const RAISED_BY_ADMIN = 'admin';

    protected $table = 'disputes';

    protected static $logName = 'disput';

    protected $casts = [
        'order_received' => 'boolean',
        'return_goods' => 'boolean',
        'resolved_at' => 'datetime',
        'close_requested_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected $fillable = [
        'shop_id',
        'dispute_type_id',
        'customer_id',
        'raised_by',
        'ticket_number',
        'order_id',
        'product_id',
        'description',
        'order_received',
        'return_goods',
        'refund_amount',
        'status',
        'resolved_by',
        'resolved_at',
        'close_requested_by',
        'close_requested_at',
        'closed_by',
        'closed_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Dispute $dispute) {
            if (empty($dispute->ticket_number)) {
                $dispute->ticket_number = static::generateTicketNumber();
            }
            if (empty($dispute->raised_by)) {
                $dispute->raised_by = static::RAISED_BY_CUSTOMER;
            }
            if (empty($dispute->status)) {
                $dispute->status = static::STATUS_NEW;
            }
        });
    }

    public static function generateTicketNumber(): string
    {
        return 'DSP-'.strtoupper(dechex(time())).'-'.random_int(100, 999);
    }

    public function raisedByLabel(): string
    {
        switch ($this->raised_by) {
            case static::RAISED_BY_VENDOR:
                return trans('app.vendor') ?: 'Vendor';
            case static::RAISED_BY_ADMIN:
                return trans('app.admin') ?: 'Admin';
            default:
                return trans('app.customer') ?: 'Customer';
        }
    }

    public function ticketRef(): string
    {
        return $this->ticket_number ?: ('#'.$this->id);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class)->withDefault();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withDefault();
    }

    public function dispute_type()
    {
        return $this->belongsTo(DisputeType::class, 'dispute_type_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by')->withDefault();
    }

    public function setProductIdAttribute($value)
    {
        $this->attributes['product_id'] = is_numeric($value) ? $value : null;
    }

    public function scopeOpen($query)
    {
        return $query->where('status', '!=', static::STATUS_CLOSED);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', static::STATUS_CLOSED);
    }

    public function scopeCloseRequested($query)
    {
        return $query->where('status', static::STATUS_CLOSE_REQUESTED);
    }

    public function scopeStatusOf($query, $status)
    {
        return $query->where('status', $status);
    }

    public function isOpen()
    {
        return (int) $this->status !== static::STATUS_CLOSED;
    }

    public function isClosed()
    {
        return (int) $this->status === static::STATUS_CLOSED;
    }

    public function isResolved()
    {
        return in_array((int) $this->status, [
            static::STATUS_SOLVED,
            static::STATUS_CLOSE_REQUESTED,
        ], true) || ! empty($this->resolved_at);
    }

    public function isCloseRequested()
    {
        return (int) $this->status === static::STATUS_CLOSE_REQUESTED;
    }

    public function canMarkResolved()
    {
        return $this->isOpen() && ! $this->isResolved() && ! $this->isCloseRequested();
    }

    public function canRequestClose()
    {
        return $this->isOpen() && $this->isResolved() && ! $this->isCloseRequested();
    }

    public function canReply()
    {
        return $this->isOpen();
    }

    public function statusName($plain = false)
    {
        $status = strtoupper(get_dispute_status_name($this->status));

        if ($plain) {
            return $status;
        }

        switch ((int) $this->status) {
            case static::STATUS_NEW:
                return '<span class="label label-outline">'.$status.'</span>';
            case static::STATUS_OPEN:
                return '<span class="label label-primary">'.$status.'</span>';
            case static::STATUS_WAITING:
            case static::STATUS_APPEALED:
                return '<span class="label label-info">'.$status.'</span>';
            case static::STATUS_SOLVED:
                return '<span class="label label-success">'.$status.'</span>';
            case static::STATUS_CLOSE_REQUESTED:
                return '<span class="label label-warning">'.$status.'</span>';
            case static::STATUS_CLOSED:
                return '<span class="label label-default">'.$status.'</span>';
            default:
                return '<span class="label label-outline">'.$status.'</span>';
        }
    }

    public function progress()
    {
        switch ((int) $this->status) {
            case static::STATUS_NEW:
                return 0;
            case static::STATUS_OPEN:
            case static::STATUS_WAITING:
            case static::STATUS_APPEALED:
                return 33;
            case static::STATUS_SOLVED:
                return 66;
            case static::STATUS_CLOSE_REQUESTED:
                return 85;
            case static::STATUS_CLOSED:
                return 100;
            default:
                return 0;
        }
    }
}
