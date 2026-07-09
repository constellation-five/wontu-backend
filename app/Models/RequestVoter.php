<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['request_id', 'user_id'])]
class RequestVoter extends Model
{
    protected $table = 'request_voters';
    public $timestamps = false; 
    public $incrementing = false; 
}