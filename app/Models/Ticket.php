<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable=[
        'event_id',
        'name',
        'price',
        'quantity',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'description',
        'is_visible',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
