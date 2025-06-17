<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FinanceRecord extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'financerecord'; // Match the table name from your schema

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */    protected $fillable = [
        'type',
        'category',
        'cost',
        'description',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */    protected $casts = [
        'cost' => 'decimal:2', // Assuming amount is a decimal with 2 places
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who created the finance record.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the finance record.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
