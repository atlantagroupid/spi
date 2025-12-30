<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    // DEFINISI ROLE (Biar seragam satu aplikasi)
    const ROLE_MANAGER_OPERASIONAL = 'manager_operasional';
    const ROLE_MANAGER_BISNIS = 'manager_bisnis';
    const ROLE_SALES_STORE = 'sales_store';
    const ROLE_SALES_FIELD = 'sales_field';
    const ROLE_HEAD_WAREHOUSE = 'kepala_gudang';
    const ROLE_ADMIN_WAREHOUSE = 'admin_gudang';
    const ROLE_PURCHASE = 'purchase';
    const ROLE_FINANCE = 'finance';
    const ROLE_CASHIER = 'kasir';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'photo',
        'sales_target',
        'phone',
        'daily_visit_target',
        'credit_limit_quota',
    ];

    // Helper Function
    public function isStoreSales()
    {
        return $this->role === 'sales_store';
    }

    public function isFieldSales()
    {
        return $this->role === 'sales_field';
    }

    // Helper Function untuk Permission
    public function isManager()
    {
        return in_array($this->role, [self::ROLE_MANAGER_OPERASIONAL, self::ROLE_MANAGER_BISNIS]);
    }

    // Helper untuk cek apakah dia "Orang Gudang"?
    public function isWarehouseTeam()
    {
        return in_array($this->role, [self::ROLE_HEAD_WAREHOUSE, self::ROLE_ADMIN_WAREHOUSE]);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    /**
     * Relasi: Satu Sales punya BANYAK Order
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relasi: Satu Sales punya BANYAK Visit (Kunjungan)
     */
    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    /**
     * Relasi: Satu Sales punya BANYAK Customer (Toko Miliknya)
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
}
