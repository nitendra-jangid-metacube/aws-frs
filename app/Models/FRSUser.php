<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class FRSUser extends Model
{   
    
    protected $table = 'frs_user';

    protected $fillable = [
        'first_name',
        'last_name',
        'mobile',
        'email',
        'photo',
        'img_token',
        'face_plus_detect_response',
        'face_plus_compare_response',
        'last_compare_confidence',
        'created_at',
        'updated_at',
        'face_id',
        'image_id',
        'aws_face_index_response',
    ];

    protected $hidden = [];

    public $timestamps = TRUE;

}
