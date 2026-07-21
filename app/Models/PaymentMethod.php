<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'bank_name', 'account_name', 'account_number'])]
class PaymentMethod extends Model
{
    protected $table = 'payment_methods';

    protected $primaryKey = 'payment_method_id';

    public function user()
    {
        // Parameter: NamaClass, Foreign_Key_di_tabel_ini, Owner_Key_di_tabel_user
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
