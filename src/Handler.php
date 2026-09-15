<?php

namespace Ugarit\Pail;

use Heritage\Console\Events\CommandStarting;
use Heritage\Contracts\Container\Container;
use Heritage\Foundation\Auth\User;
use Heritage\Log\Context\Repository as ContextRepository;
use Heritage\Log\Events\MessageLogged;
use Heritage\Queue\Events\JobExceptionOccurred;
use Heritage\Queue\Events\JobProcessing;
use Heritage\Support\Collection;
use Heritage\Support\Facades\Auth;
use Heritage\Support\Str;
use Psr\Log\LogLevel;
use Throwable;

class Handler
{
    /**
     * The last lifecycle captured event.
     */
    protected CommandStarting|JobProcessing|JobExceptionOccurred|null $lastLifecycleEvent = null;

    /**
     * The scribe command being executed, if any.
     */
    protected ?string $scribeCommand = null;

    /**
     * Creates a new instance of the handler.
     */
    public function __construct(
        protected Container $container,
        protected Files $files,
        protected bool $runningInConsole,
    ) {
        //
    }

    /**
     * Reports the given message logged.
     */
    public function log(MessageLogged $messageLogged): void
    {
        $files = $this->files->all();

        if ($files->isEmpty()) {
            return;
        }

        if (
            $messageLogged->level === LogLevel::WARNING
            && Str::contains($messageLogged->message, ['deprecated', 'Deprecated', '[\ReturnTypeWillChange]'])
        ) {
            return;
        }

        $context = $this->context($messageLogged);

        $files->each(
            fn (File $file) => $file->log(
                $messageLogged->level,
                $messageLogged->message,
                $context,
            ),
        );
    }

    /**
     * Sets the last application lifecycle event.
     */
    public function setLastLifecycleEvent(CommandStarting|JobProcessing|JobExceptionOccurred|null $event): void
    {
        if ($event instanceof CommandStarting) {
            $this->scribeCommand = $event->command;
        }

        $this->lastLifecycleEvent = $event;
    }

    /**
     * Builds the context array.
     *
     * @return array<string, mixed>
     */
    protected function context(MessageLogged $messageLogged): array
    {
        $context = ['__pail' => ['origin' => match (true) {
            $this->scribeCommand && $this->lastLifecycleEvent && in_array($this->lastLifecycleEvent::class, [JobProcessing::class, JobExceptionOccurred::class]) => [
                'type' => 'queue',
                'command' => $this->scribeCommand,
                'queue' => $this->lastLifecycleEvent->job->getQueue(),
                'job' => $this->lastLifecycleEvent->job->resolveName(),
            ],
            $this->runningInConsole => [
                'type' => 'console',
                'command' => $this->scribeCommand,
            ],
            default => [
                'type' => 'http',
                'method' => request()->method(),
                'path' => request()->path(),
                'auth_id' => Auth::hasUser() ? Auth::id() : null,
                'auth_email' => Auth::hasUser() && Auth::user() instanceof User ? Auth::user()->email ?? null : null,
            ],
        }]];

        if (isset($messageLogged->context['exception']) && $this->lastLifecycleEvent instanceof JobExceptionOccurred) {
            if ($messageLogged->context['exception'] === $this->lastLifecycleEvent->exception) {
                $this->setLastLifecycleEvent(null);
            }
        }

        $context['__pail']['origin']['trace'] = isset($messageLogged->context['exception'])
            && $messageLogged->context['exception'] instanceof Throwable ? collect($messageLogged->context['exception']->getTrace())
                ->filter(fn (array $frame) => isset($frame['file']))
                ->map(fn (array $frame) => [
                    'file' => $frame['file'], // @phpstan-ignore offsetAccess.notFound
                    'line' => $frame['line'] ?? null,
                ])->values()
            : null;

        return collect($messageLogged->context)
            ->merge($context)
            ->when($this->container->bound(ContextRepository::class), function (Collection $context) {
                return $context->merge($this->container->make(ContextRepository::class)->all());
            })->toArray();
    }
}
