<?php

declare(strict_types=1);

namespace MaskuLabs\InertiaYii\Middleware;

use MaskuLabs\InertiaPsr\Helper\RequestHelper;
use MaskuLabs\InertiaPsr\InertiaInterface;
use MaskuLabs\InertiaPsr\Support\Header;
use MaskuLabs\InertiaYii\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Input\Http\InputValidationException;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Validator\Result;

final readonly class ValidationExceptionMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected InertiaInterface $inertia,
        protected FlashInterface $flash,
        protected bool $withAllErrors = false,
        protected bool $indexByPath = true,
    ) {}

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $referer = $request->getHeaderLine('Referer');

        if (!$this->shouldDispatch($request, $referer)) {
            return $handler->handle($request);
        }

        $errorBag = $this->resolveErrorBag($request);

        try {
            return $handler->handle($request);
        } catch (InputValidationException $e) {
            $this->processInputValidationException($e, $errorBag);
        } catch (ValidationException $e) {
            $this->processValidationException($e, $errorBag);
        }

        return $this->inertia->redirect($referer);
    }

    protected function shouldDispatch(ServerRequestInterface $request, string $referer): bool
    {
        return $referer !== '' && RequestHelper::isInertia($request);
    }

    protected function resolveErrorBag(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeader(Header::ErrorBag->value);

        return array_first($header);
    }

    protected function processInputValidationException(InputValidationException $e, ?string $errorBag): void
    {
        $this->flashErrors(
            $e->getResult(),
            $errorBag,
            $this->indexByPath,
            $this->withAllErrors,
        );
    }

    protected function processValidationException(ValidationException $e, ?string $defaultErrorBag): void
    {
        $errorBag = $e->getWithBag() ?? $defaultErrorBag;
        $indexByPath = $e->getIndexByPath() ?? $this->indexByPath;
        $withAllErrors = $e->getWithAllErrors() ?? $this->withAllErrors;

        $this->flashErrors(
            $e->getResult(),
            $errorBag,
            $indexByPath,
            $withAllErrors,
        );
    }

    protected function flashErrors(Result $validationResult, ?string $errorBag, bool $indexByPath, bool $withAllErrors): void
    {
        $errors = match ($withAllErrors) {
            true => match ($indexByPath) {
                true => $validationResult->getErrorMessagesIndexedByPath(),
                false => $validationResult->getErrorMessagesIndexedByProperty(),
            },
            false => match ($indexByPath) {
                true => $validationResult->getFirstErrorMessagesIndexedByPath(),
                false => $validationResult->getFirstErrorMessagesIndexedByProperty(),
            },
        };

        if ($errorBag !== null && $errorBag !== '') {
            $errors = [$errorBag => $errors];
        }

        $this->flash->set(
            'errors',
            $errors,
        );
    }
}
