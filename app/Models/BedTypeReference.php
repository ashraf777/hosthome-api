<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BedTypeReference extends Model
{
    use HasFactory;
    
    // Define the table name explicitly
    protected $table = 'bed_type_references';

    // Since your table does not have created_at/updated_at columns
    public $timestamps = false; 

    // Allow mass assignment for name and hosting_company_id
    protected $fillable = [
        'hosting_company_id',
        'name',
    ];
}