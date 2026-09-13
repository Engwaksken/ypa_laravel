<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'customer_id', 'customer_name', 'phone', 'email',
        'delivery_location', 'branch_id', 'payment_method', 'total_amount',
        'orders_count', 'status', 'notes', 'is_guest_order', 'created_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'is_guest_order' => 'boolean',
    ];

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany { return $this->hasMany(OrderItem::class); }
}
