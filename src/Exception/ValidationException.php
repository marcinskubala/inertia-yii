<?php

declare(strict_types=1);

namespace MaskuLabs\InertiaYii\Exception;

use Yiisoft\Validator\Result;
use Exception;

final class ValidationException extends Exception
{
    public function __construct(
        private readonly Result $result,
        private readonly ?string $withBag = null,
        private readonly ?bool $withAllErrors = null,
        private readonly ?bool $indexByPath = null,
    ) {
        parent::__construct('Validation error.');
    }

    /**
     * @return Result Validation result.
     */
    public function getResult(): Result
    {
        return $this->result;
    }

    /**
     * @return null|string Error Bag
     */
    public function getWithBag(): ?string
    {
        return $this->withBag;
    }

    /**
     * @return null|bool Return all errors
     */
    public function getWithAllErrors(): ?bool
    {
        return $this->withAllErrors;
    }

    /**
     * @return null|bool false to return errors indexed by a property name, true to return errors indexed by a path.
     */
    public function getIndexByPath(): ?bool
    {
        return $this->indexByPath;
    }
}
