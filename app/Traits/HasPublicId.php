<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasPublicId
{
    public static function bootHasPublicId(): void
    {
        static::creating(function (Model $model): void {
            $column = $model->publicIdColumn();

            $publicId = $model->getAttribute($column);

            if (blank($publicId) || $model->newQueryWithoutScopes()->where($column, $publicId)->exists()) {
                $model->setAttribute($column, (string) Str::ulid());
            }
        });
    }

    public function publicIdColumn(): string
    {
        return 'public_id';
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $column = $this->publicIdColumn();

        if ($field === null && $this->looksLikePublicId($value)) {
            return $this->where($column, $value)->first();
        }

        return parent::resolveRouteBinding($value, $field);
    }

    protected function looksLikePublicId(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        return (bool) preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', strtoupper($value));
    }
}
