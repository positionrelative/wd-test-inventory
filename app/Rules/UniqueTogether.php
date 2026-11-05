<?php

declare(strict_types=1);

namespace App\Rules;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;

/**
 * Composite uniqueness of (scope field, field), e.g. (vendor_id, contract_number).
 *
 * Checked on the pair as it will be after the change (request value, or the stored
 * one on a partial update), so changing only one of the two fields is validated too.
 * It spans two fields, so it is an after-validation hook (FormRequest::after()):
 * a single-field rule would not run when its field is absent from the request.
 */
final readonly class UniqueTogether
{
    /**
     * @param class-string<Model> $model
     * @param Model|null $current The model being updated; null on create.
     */
    public function __construct(
        private string $model,
        private string $scopeField,
        private string $field,
        private ?Model $current,
        private string $message,
    ) {
    }

    public function __invoke(Validator $validator): void
    {
        // Field errors (missing, wrong type, unknown reference) come first.
        if ($validator->errors()->hasAny([$this->field, $this->scopeField])) {
            return;
        }

        $data = $validator->getData();
        $scope = $data[$this->scopeField] ?? $this->current?->getAttribute($this->scopeField);
        $value = $data[$this->field] ?? $this->current?->getAttribute($this->field);

        if ($scope === null || $value === null || !$this->taken($scope, $value)) {
            return;
        }

        // Report it on the field the client changed.
        $validator->errors()->add(
            array_key_exists($this->field, $data) ? $this->field : $this->scopeField,
            $this->message,
        );
    }

    private function taken(mixed $scope, mixed $value): bool
    {
        return $this->model::query()
            ->where($this->scopeField, $scope)
            ->where($this->field, $value)
            ->when($this->current !== null, fn ($query) => $query->whereKeyNot($this->current->getKey()))
            ->exists();
    }
}
