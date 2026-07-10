<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['requester_id', 'location_label', 'location', 'item_name', 'category', 'arrival_time', 'total_votes'])]
class Request extends Model
{
    protected $table = 'requests';
    protected $primaryKey = 'request_id';

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id', 'user_id');
    }
}