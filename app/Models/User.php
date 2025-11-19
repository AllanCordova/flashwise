<?php

namespace App\Models;

use Lib\Validations;
use Core\Database\ActiveRecord\Model;
use Core\Database\ActiveRecord\HasMany;
use Core\Database\ActiveRecord\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $encrypted_password
 * @property string $avatar_name
 * @property string $role
 * @property string $created_at
 * @property string $updated_at
 * @property Deck[] $decks
 * @property Deck[] $shared_decks
 */
class User extends Model
{
    protected static string $table = 'users';
    protected static array $columns = [
        'name',
        'email',
        'encrypted_password',
        'avatar_name',
        'role'
    ];

    protected ?string $role = 'user';

    protected ?string $password = null;
    protected ?string $password_confirmation = null;

    public function decks(): HasMany
    {
        return $this->hasMany(Deck::class, 'user_id');
    }

    public function sharedDecks(): BelongsToMany
    {
        return $this->belongsToMany(Deck::class, 'deck_user_shared', 'user_id', 'deck_id');
    }

    public function validates(): void
    {
        Validations::notEmpty('name', $this);
        Validations::notEmpty('email', $this);
        Validations::notEmpty('password', $this);
        Validations::notEmpty('password_confirmation', $this);

        Validations::isEmail('email', $this);

        Validations::uniqueness('email', $this);

        if ($this->newRecord()) {
            Validations::passwordConfirmation($this);
        }
    }

    public function authenticate(string $password): bool
    {
        if ($this->encrypted_password == null) {
            return false;
        }

        return password_verify($password, $this->encrypted_password);
    }

    public static function findByEmail(string $email): User | null
    {
        return User::findBy(['email' => $email]);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function __set(string $property, mixed $value): void
    {
        parent::__set($property, $value);

        if (
            $property === 'password' &&
            $this->newRecord() &&
            $value !== null && $value !== ''
        ) {
            $this->encrypted_password = password_hash($value, PASSWORD_DEFAULT);
        }
    }
}
