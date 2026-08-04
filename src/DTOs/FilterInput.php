<?php

declare(strict_types=1);

namespace Pradeepdev\SmartFilter\DTOs;

/**
 * Immutable value object representing a single parsed filter unit.
 *
 * After the parser runs, every filter in the request is reduced
 * to a FilterInput: which field, which operator, and what value.
 * This travels through the pipeline untouched.
 */
final readonly class FilterInput
{
    /**
     * @param  mixed  $value  The raw (but sanitised) value from the request.
     */
    public function __construct(
        public string $field,
        public string $operator,
        public mixed $value,
    ) {}

    /**
     * Return a new instance with a different field name.
     * Useful for alias resolution without mutation.
     */
    public function withField(string $field): self
    {
        return new self($field, $this->operator, $this->value);
    }

    /**
     * Return a new instance with a different operator.
     */
    public function withOperator(string $operator): self
    {
        return new self($this->field, $operator, $this->value);
    }

    /**
     * Return a new instance with a different value.
     */
    public function withValue(mixed $value): self
    {
        return new self($this->field, $this->operator, $value);
    }
}
