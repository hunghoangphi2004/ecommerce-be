<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'description',
        'price',
        'discount_percentage',
        'stock',
        'thumbnail',
        'status',
        'is_featured',
        'position',
    ];


    protected $casts = [
        'price' => 'decimal:2',
        'status' => 'boolean',
        'is_featured' => 'boolean',
    ];
    

    public function category(){
        return $this->belongsTo(Category::class);
    }
}
