<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
  
     protected $fillable = [
        'user_id',
        'product_id',
        'store_id',
        'product_name',
        'product_price',
        'product_unit',
        'product_description',
        'latitude',
        'longitude',
        'quantity',
      


    ];

        public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
