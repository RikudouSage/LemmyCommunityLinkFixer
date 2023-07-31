<?php

namespace App\Service;

use App\Service\Handler\MentionHandler;
use Rikudou\LemmyApi\Response\Model\Comment;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final readonly class MentionHandlerCollection
{
    /**
     * @param iterable<MentionHandler> $handlers
     */
    public function __construct(
        #[TaggedIterator('app.mention_handler')]
        private iterable $handlers,
    ) {
    }

    public function handle(?string $text, Comment $replyTo, string $instance): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($text, $replyTo, $instance)) {
                $handler->handle($text, $replyTo, $instance);

                return;
            }
        }
    }
}
