<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class Customer extends Model
{
    use HasFactory, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',          // اسم العميل
        'phone',         // رقم الهاتف
        'address',       // عنوان العميل
        'is_active',     // حالة النشاط
        'last_purchase_date', // تاريخ آخر عملية شراء
        'notes',         // ملاحظات العميل
        'avatar',        // الصورة الرمزية
    ];

    /**
     * The attributes that should be appended.
     *
     * @var array
     */
    protected $appends = ['avatar_url'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'last_purchase_date' => 'datetime',
        'created_at'         => 'date:Y-m-d',
        'updated_at'         => 'date:Y-m-d',
    ];

    /**
     * Get the avatar URL attribute.
     */
    public function getAvatarUrlAttribute()
    {
        return asset("storage/{$this->attributes['avatar']}");
    }

    /**
     * Get the formatted created_at attribute.
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('d M Y');
    }

    /**
     * Define a relationship to the logs.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(
            \App\Models\Log::class,
            'by_user_id' // افترض أن العلاقة تشير إلى حقل customer_id
        );
    }
}
