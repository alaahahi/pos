<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DecorationTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'members',
        'specialties',
        'hourly_rate',
        'is_available',
        'max_orders_per_day',
        'working_hours',
        'notes'
    ];

    protected $casts = [
        'members' => 'array',
        'specialties' => 'array',
        'working_hours' => 'array',
        'hourly_rate' => 'decimal:2',
        'is_available' => 'boolean'
    ];

    // العلاقات
    public function orders()
    {
        return $this->hasMany(DecorationOrder::class, 'assigned_team_id');
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    // Accessors
    public function getMembersCountAttribute()
    {
        return is_array($this->members) ? count($this->members) : 0;
    }

    public function getSpecialtiesListAttribute()
    {
        return is_array($this->specialties) ? implode(', ', $this->specialties) : '';
    }

    // Methods
    public function canTakeOrder($date)
    {
        $ordersCount = $this->orders()
            ->whereDate('event_date', $date)
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->count();

        return $ordersCount < $this->max_orders_per_day;
    }
}
