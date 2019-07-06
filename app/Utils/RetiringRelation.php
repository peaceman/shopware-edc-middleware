<?php
/**
 * lel since 2019-07-06
 */

namespace App\Utils;

use Assert\Assert;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RetiringRelation
{
    /** @var Model */
    private $model;

    /** @var string */
    private $relationName;

    /** @var string */
    private $relatedModelClass;

    public function __construct(Model $model, string $relationName)
    {
        $this->model = $model;
        $this->relationName = $relationName;
        $this->relatedModelClass = get_class($this->getRelation()->getRelated());
    }

    protected function getRelation(): HasOne
    {
        return $this->model->{$this->relationName}();
    }

    public function save(Model $model): void
    {
        Assert::that($model)->isInstanceOf($this->relatedModelClass);

        $this->retire();
        $this->getRelation()->save($model);
        $this->model->setRelation($this->relationName, $model);
    }

    protected function retire(): void
    {
        if (!($related = $this->model->{$this->relationName})) return;

        $related->update(['current_until' => now()]);
        $this->model->unsetRelation($this->relationName);
    }
}
