<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tipo',
        'celular'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function lancamento()
    {
        return $this->belongsTo(Lancamentos::class, 'user_id');
    }

    public function transfers()
    {
        return $this->hasMany(Transfer::class);
    }

    public function getWalletBalance()
    {
        $incoming = $this->transfers()->where('to_address', $this->address)->sum('valor');
        $outgoing = $this->transfers()->where('from_address', $this->address)->sum('valor');

        return $incoming - $outgoing;
    }
}
