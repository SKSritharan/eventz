<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug'];

    public function subcategories():HasMany
    {
        return $this->hasMany(SubCategory::class);
    }

    public function events():HasMany
    {
        return $this->hasMany(Event::class);
    }
}
