<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as EloquentModel; // Alias Illuminate\Database\Eloquent\Model to avoid conflict

class Make extends EloquentModel
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
    ];

    /**
     * Get the models associated with the make.
     */
    public function models()
    {
        return $this->hasMany(Model::class);
    }

    /**
     * Get the cars associated with the make.
     */
    public function cars()
    {
        return $this->hasMany(Car::class);
    }
}
