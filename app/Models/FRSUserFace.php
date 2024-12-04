<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\FRSUser;


class FRSUserFace extends Model
{   
    
    protected $table = 'frs_user_face';

    protected $fillable = [
        'user_id',
        'face_id',
        'image_id',
        'face_index_confidence',
        'last_search_confidence',
        'aws_face_index_response',
        'last_search_response',
        'created_at',
        'updated_at',
        'photo',
    ];

    protected $hidden = [];

    public $timestamps = TRUE;
    
    public function user() {
        return $this->belongsTo(FRSUser::class,"user_id","id");
    }

}
