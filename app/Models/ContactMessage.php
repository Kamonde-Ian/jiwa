<?php

namespace App\Models;

class ContactMessage extends BaseModel
{
    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'is_read',
        'ip_address',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];
}
