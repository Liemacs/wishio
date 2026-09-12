<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'country_code',
        'timezone',
        'name_day_calendar',
        'birth_date',
    ];

    /**
     * Valorile implicite stau AICI, nu doar în migrare.
     *
     * Un `create()` întoarce modelul construit în memorie, fără valorile
     * implicite ale bazei. Fără acestea, `locale` era `null` pentru un
     * utilizator nou și pica orice cod care se baza pe el.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'locale'            => 'ro',
        'country_code'      => 'MD',
        'timezone'          => 'Europe/Chisinau',
        'name_day_calendar' => 'orthodox_new',
    ];

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
            'birth_date' => 'date',
        ];
    }

    public function people(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Domain\People\Models\Person::class);
    }

    public function settings(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Domain\Reminders\Models\UserSettings::class);
    }
}
