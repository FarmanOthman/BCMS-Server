<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth; // Import Auth facade

class UserActivityLog extends Model
{
    use HasFactory;

    protected $table = 'useractivitylog';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'action_type',
        'description',
        'table_name',
        'record_id',
        'created_by',
        'updated_by',
        'metadata',
        'should_alert',
        'alert_level',
    ];

    protected $casts = [
        'metadata' => 'json',
        'should_alert' => 'boolean',
        'record_id' => 'string', // Assuming record_id is UUID
        'user_id' => 'string',
        'created_by' => 'string',
        'updated_by' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
            if (Auth::check()) { // Use Auth facade
                if (empty($model->created_by)) {
                    $model->created_by = Auth::id(); // Use Auth facade
                }
                if (empty($model->user_id)) { // Set user_id if not already set
                    $model->user_id = Auth::id(); // Use Auth facade
                }
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) { // Use Auth facade
                if (empty($model->updated_by)) {
                    $model->updated_by = Auth::id(); // Use Auth facade
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
