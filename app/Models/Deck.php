<?php

namespace App\Models;

use Lib\Validations;
use Core\Database\ActiveRecord\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $path_img
 * @property string $category
 * @property string $created_at
 * @property string $updated_at
 */
class Deck extends Model
{
    protected static string $table = 'decks';
    protected static array $columns = [
        'name',
        'description',
        'path_img',
        'category_id',
    ];


    public function validates(): void
    {
        Validations::notEmpty('name', $this);
        Validations::notEmpty('description', $this);

        Validations::uniqueness('name', $this);
    }
}
