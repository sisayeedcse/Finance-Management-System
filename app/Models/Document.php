<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'original_filename', 'stored_filename', 'file_path', 'mime_type', 'file_size', 'uploaded_by'
    ];

    public $timestamps = false;
}
