<?php

namespace App\Service;

use App\Service\Handler\ReplyHandler;
use Rikudou\LemmyApi\Response\View\CommentView;
use Rikudou\LemmyApi\Response\View\PostView;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final readonly class ReplyHandlerCollection
{
    /**
     * @param iterable<ReplyHandler> $handlers
     */
    public function __construct(
        #[TaggedIterator('app.reply_handler')]
        private iterable $handlers,
    ) {
    }

    public function handle(PostView|CommentView $repliable, ?string $software): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($repliable, $software)) {
                $handler->handle($repliable, $software);

                return;
            }
        }
    }
}
