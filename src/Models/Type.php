<?php

namespace dnj\AAA\Models;

use dnj\AAA\Contracts\IType;
use dnj\AAA\Contracts\ITypeManager;
use dnj\AAA\Contracts\IUser;
use dnj\AAA\Database\Factories\TypeFactory;
use dnj\AAA\Models\Concerns\HasAbilities;
use dnj\Localization\Eloquent\HasTranslate;
use dnj\UserLogger\Concerns\Loggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int                       $id
 * @property Collection<TypeTranslate> $translates
 * @property Collection<TypeAbility>   $abilities
 * @property Collection<User>          $users
 * @property Collection<self>          $children
 * @property Collection<self>          $parents
 * @property array<mixed,mixed>        $meta
 *
 * @method ?TypeTranslate          getTranslate(string $locale)
 * @method iterable<TypeTranslate> getTranslates()
 */
class Type extends Model implements IType
{
    use HasAbilities;
    use Loggable;
    use HasFactory;
    use HasTranslate;

    public static function newFactory(): TypeFactory
    {
        return TypeFactory::new();
    }

    public static function ensureId(int|IType $value): int
    {
        return $value instanceof IType ? $value->getId() : $value;
    }

    /**
     * @var string
     */
    protected $table = 'aaa_types';

    protected $casts = [
        'meta' => 'array',
    ];

    protected $fillable = [
        'meta',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function abilities()
    {
        return $this->hasMany(TypeAbility::class);
    }

    public function translates()
    {
        return $this->hasMany(TypeTranslate::class);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function scopeFilter(Builder $query, array $filters): void
    {
        if (isset($filters['id'])) {
            if (is_array($filters['id'])) {
                $query->whereIn('id', $filters['id']);
            } else {
                $query->where('id', $filters['id']);
            }
        }
        if (isset($filters['hasFullAccess']) and $filters['hasFullAccess']) {
            $this->scopeHasFullAccess($query);
        }
        if (isset($filters['userHasAccess']) and $filters['userHasAccess']) {
            $this->scopeUserHasAccess($query, $filters['userHasAccess']);
        }
    }

    public function scopeHasFullAccess(Builder $query): void
    {
        $typesCount = self::query()->count();
        $abilitiesCount = TypeAbility::query()->toBase()->distinct()->count('name');
        $query->has('abilities', $abilitiesCount);
        $query->has('children', $typesCount);
    }

    public function scopeUserHasAccess(Builder $query, IUser $user): void
    {
        if ($user instanceof User) {
            /**
             * @var IType
             */
            $type = $user->type;
        } else {
            /**
             * @var ITypeManager
             */
            $typeManager = app(ITypeManager::class);
            $type = $typeManager->findOrFail($user->getTypeId());
        }
        $query->whereIn('id', $type->getChildIds());
    }

    /**
     * @return string[]
     */
    public function getAbilities(): array
    {
        return $this->abilities->pluck('name')->all();
    }

    /**
     * @return int[]
     */
    public function getChildIds(): array
    {
        return $this->getChildren()->pluck('id')->all();
    }

    /**
     * @return int[]
     */
    public function getParentIds(): array
    {
        return $this->getParents()->pluck('id')->all();
    }

    /**
     * @return Collection<Type>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * @return Collection<Type>
     */
    public function getParents(): Collection
    {
        return $this->parents;
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'aaa_types_children', 'parent_id', 'child_id');
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'aaa_types_children', 'child_id', 'parent_id');
    }

    public function getMeta(): array
    {
        return $this->meta ?? [];
    }

    /**
     * @param string[] $newAbilities
     */
    public function updateAbilities(array $newAbilities): static
    {
        $newAbilities = collect($newAbilities);
        $current = $this->abilities->pluck('name');
        $created = $newAbilities->diff($current);
        $deleted = $current->diff($newAbilities);
        if ($deleted) {
            $this->abilities()->getQuery()->whereIn('name', $deleted)->delete();
        }
        if ($created) {
            $this->abilities()->createMany($created->map(fn (string $name) => ['name' => $name]));
        }

        return $this;
    }

    /**
     * @param int[] $childIds
     */
    public function updateChildIds(array $childIds): static
    {
        $this->children()->sync($childIds);

        return $this;
    }

    public function isParentOf(int|IType $other): bool
    {
        return $this->children->pluck('id')->contains(self::ensureId($other));
    }

    public function isChildOf(int|IType $other): bool
    {
        return $this->parents->pluck('id')->contains(self::ensureId($other));
    }
}
