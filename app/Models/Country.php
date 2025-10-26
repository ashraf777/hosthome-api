<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'iso_code',
        'currency_code',
        'language_code',
        'vat_gst_rate',
        'status',
    ];

    protected $casts = [
        'vat_gst_rate' => 'decimal:2',
        'status' => 'integer',
    ];

    /**
     * A Country can be associated with many Hosting Companies.
     */
    public function hostingCompanies()
    {
        return $this->hasMany(HostingCompany::class);
    }
}
