<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Modules\Location\App\Models\Location;
use Spatie\Permission\Traits\HasRoles;

/**
 * sdd.md §4: Passport (HasApiTokens) answers *who you are*; spatie's
 * HasRoles answers *what you can do*. Location-scoping (Showroom Staff
 * sees only their own showroom) is a plain `location_id` column, checked
 * in policies/queries — it is NOT modeled as a role or permission.
 *
 * failed_doc.md §2: `role`, `is_admin`-style fields are deliberately kept
 * OUT of $fillable-by-attribute — role assignment only ever happens through
 * the dedicated Admin-only AssignRole endpoint (Modules/User), never via a
 * generic profile-update mass assignment.
 */
#[Fillable(['name', 'email', 'password', 'phone', 'location_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    // No explicit $guard_name override: config/auth.php sets the app's
    // default guard to "api" (Passport), which spatie/laravel-permission
    // picks up automatically for role/permission checks on this model.

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
            'is_active' => 'boolean',
        ];
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
