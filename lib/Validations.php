<?php

namespace Lib;

use Core\Database\Database;

class Validations
{
    public static function notEmpty($attribute, $obj)
    {
        if ($obj->$attribute === null || $obj->$attribute === '') {
            $obj->addError($attribute, 'não pode ser vazio!');
            return false;
        }

        return true;
    }

    public static function passwordConfirmation($obj)
    {
        if ($obj->password !== $obj->password_confirmation) {
            $obj->addError('password', 'as senhas devem ser idênticas!');
            return false;
        }

        return true;
    }

    public static function isEmail($attribute, $obj)
    {
        $email = $obj->$attribute;

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $obj->addError($attribute, 'precisa ser um endereço de e-mail válido.');
            return false;
        }

        return true;
    }

    public static function uniqueness($fields, $object)
    {
        $dbFieldsValues = [];
        $objFieldValues = [];

        if (!is_array($fields)) {
            $fields = [$fields];
        }

        if (!$object->newRecord()) {
            $dbObject = $object::findById($object->id);

            foreach ($fields as $field) {
                $dbFieldsValues[] = $dbObject->$field;
                $objFieldValues[] = $object->$field;
            }

            if (
                !empty($dbFieldsValues) &&
                !empty($objFieldValues) &&
                $dbFieldsValues === $objFieldValues
            ) {
                return true;
            }
        }

        $table = $object::table();
        $conditions = implode(' AND ', array_map(fn($field) => "{$field} = :{$field}", $fields));

        $sql = <<<SQL
            SELECT id FROM {$table} WHERE {$conditions};
        SQL;

        $pdo = Database::getDatabaseConn();
        $stmt = $pdo->prepare($sql);

        foreach ($fields as $field) {
            $stmt->bindValue($field, $object->$field);
        }

        $stmt->execute();

        if ($stmt->rowCount() !== 0) {
            foreach ($fields as $field) {
                $object->addError($field, 'já existe um registro com esse dado');
            }
            return false;
        }

        return true;
    }


    public static function maxFileSize(string $attribute, $obj, int $maxSizeInBytes): bool
    {
        $size = $obj->$attribute;

        if (is_numeric($size) && $size > $maxSizeInBytes) {
            $formattedSize = self::formatBytes($maxSizeInBytes);
            $obj->addError($attribute, "é muito grande. O tamanho máximo permitido é {$formattedSize}.");
            return false;
        }
        return true;
    }

    public static function allowedMimeTypes(string $attribute, $obj, array $allowedMimeTypes): bool
    {
        $mime = $obj->$attribute;

        if (empty($mime)) {
            return true;
        }

        $allowed = array_map('strtolower', $allowedMimeTypes);

        if (!in_array(strtolower($mime), $allowed)) {
            $obj->addError($attribute, "é um tipo de arquivo não permitido.");
            return false;
        }
        return true;
    }

    private static function formatBytes(int $bytes, int $precision = 2): string
    {
        if ($bytes === 0) {
            return '0 Bytes';
        }
        $units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
