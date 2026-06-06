<?php

namespace App\Models;

use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Model;

class FaqItem extends Model
{
    use HasActiveScope;

    protected $table = 'faq_items';

    protected $fillable = [
        'question', 'answer', 'sort_order', 'active'
    ];
}
