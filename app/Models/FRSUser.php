<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\FRSUserFace;


class FRSUser extends Model
{   
    
    protected $table = 'frs_user';

    protected $fillable = [
        'first_name',
        'last_name',
        'mobile',
        'email',
        'created_at',
        'updated_at'
    ];

    protected $hidden = [];

    public $timestamps = TRUE;

    public function face() {
        return $this->hasMany(FRSUserFace::class,"user_id","id");
    }

}
