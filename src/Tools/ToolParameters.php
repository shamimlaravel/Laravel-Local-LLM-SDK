<?php

declare(strict_types=1);

namespace LaravelLocalLlm\Tools;

use InvalidArgumentException;

abstract class ToolParameter
{
    public function __construct(
        public string $name,
        public string $description,
        public bool $required = false,
    ) {}

    abstract public function toArray(): array;
}

final class StringParameter extends ToolParameter
{
    public function __construct(
        string $name,
        string $description,
        bool $required = false,
        public ?string $default = null,
    ) {
        parent::__construct($name, $description, $required);
    }

    public function toArray(): array
    {
        $schema = [
            'type' => 'string',
            'description' => $this->description,
        ];

        if ($this->default !== null) {
            $schema['default'] = $this->default;
        }

        return $schema;
    }
}

final class IntegerParameter extends ToolParameter
{
    public function __construct(
        string $name,
        string $description,
        bool $required = false,
        public ?int $default = null,
        public ?int $minimum = null,
        public ?int $maximum = null,
    ) {
        parent::__construct($name, $description, $required);
    }

    public function toArray(): array
    {
        $schema = [
            'type' => 'integer',
            'description' => $this->description,
        ];

        if ($this->default !== null) {
            $schema['default'] = $this->default;
        }

        if ($this->minimum !== null) {
            $schema['minimum'] = $this->minimum;
        }

        if ($this->maximum !== null) {
            $schema['maximum'] = $this->maximum;
        }

        return $schema;
    }
}

final class NumberParameter extends ToolParameter
{
    public function __construct(
        string $name,
        string $description,
        bool $required = false,
        public ?float $default = null,
        public ?float $minimum = null,
        public ?float $maximum = null,
    ) {
        parent::__construct($name, $description, $required);
    }

    public function toArray(): array
    {
        $schema = [
            'type' => 'number',
            'description' => $this->description,
        ];

        if ($this->default !== null) {
            $schema['default'] = $this->default;
        }

        if ($this->minimum !== null) {
            $schema['minimum'] = $this->minimum;
        }

        if ($this->maximum !== null) {
            $schema['maximum'] = $this->maximum;
        }

        return $schema;
    }
}

final class BooleanParameter extends ToolParameter
{
    public function __construct(
        string $name,
        string $description,
        bool $required = false,
        public ?bool $default = null,
    ) {
        parent::__construct($name, $description, $required);
    }

    public function toArray(): array
    {
        $schema = [
            'type' => 'boolean',
            'description' => $this->description,
        ];

        if ($this->default !== null) {
            $schema['default'] = $this->default;
        }

        return $schema;
    }
}

final class EnumParameter extends ToolParameter
{
    /**
     * @param array<string> $enumValues
     */
    public function __construct(
        string $name,
        string $description,
        bool $required = false,
        public array $enumValues = [],
        public ?string $default = null,
    ) {
        parent::__construct($name, $description, $required);
    }

    public function toArray(): array
    {
        if (empty($this->enumValues)) {
            throw new InvalidArgumentException('EnumParameter must have at least one value');
        }

        $schema = [
            'type' => 'string',
            'description' => $this->description,
            'enum' => $this->enumValues,
        ];

        if ($this->default !== null) {
            $schema['default'] = $this->default;
        }

        return $schema;
    }
}

final class ArrayParameter extends ToolParameter
{
    /**
     * @param ToolParameter $items
     */
    public function __construct(
        string $name,
        string $description,
        bool $required = false,
        public ToolParameter $items,
        public ?int $minItems = null,
        public ?int $maxItems = null,
    ) {
        parent::__construct($name, $description, $required);
    }

    public function toArray(): array
    {
        $schema = [
            'type' => 'array',
            'description' => $this->description,
            'items' => $this->items->toArray(),
        ];

        if ($this->minItems !== null) {
            $schema['minItems'] = $this->minItems;
        }

        if ($this->maxItems !== null) {
            $schema['maxItems'] = $this->maxItems;
        }

        return $schema;
    }
}

final class ObjectParameter extends ToolParameter
{
    /**
     * @param array<ToolParameter> $properties
     */
    public function __construct(
        string $name,
        string $description,
        bool $required = false,
        public array $properties = [],
    ) {
        parent::__construct($name, $description, $required);
    }

    public function toArray(): array
    {
        $properties = [];
        $required = [];

        foreach ($this->properties as $property) {
            $properties[$property->name] = $property->toArray();

            if ($property->required) {
                $required[] = $property->name;
            }
        }

        $schema = [
            'type' => 'object',
            'description' => $this->description,
            'properties' => $properties,
        ];

        if (!empty($required)) {
            $schema['required'] = $required;
        }

        return $schema;
    }
}
