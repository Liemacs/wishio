<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\People\Models\Person;
use App\Domain\Reminders\Models\UserSettings;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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
        'ai_consent_at',
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
            'password'          => 'hashed',
            'birth_date'        => 'date',
            'ai_consent_at'     => 'datetime',
        ];
    }

    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(UserSettings::class);
    }
}
