<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Laravel\Cashier\Billable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, Billable; // <-- Add Billable here

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

protected $fillable = [
    'name', 'email', 'password', 'has_field_tool', 'trial_ends_at',
];

protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
    'has_field_tool' => 'boolean', // Add this
    'trial_ends_at' => 'datetime', // Add this
];
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
