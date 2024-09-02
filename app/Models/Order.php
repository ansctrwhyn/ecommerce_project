<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';
    protected $fillable = ['user_id', 'product_id', 'quantity', 'total_price', 'order_date'];
    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // If you have a relationship with Product, add it here as well
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
