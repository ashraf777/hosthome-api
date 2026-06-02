<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\Multitenant;

class Checklist extends Model
{
    use HasFactory, Multitenant;

    protected $fillable = [
        'hosting_company_id',
        'checklist_name',
        'description',
    ];

    public function hostingCompany(): BelongsTo
    {
        return $this->belongsTo(HostingCompany::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class)->orderBy('item_order');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
