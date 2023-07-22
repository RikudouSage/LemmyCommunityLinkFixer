<?php

namespace App\Service\Handler;

use Rikudou\LemmyApi\Response\View\CommentView;
use Rikudou\LemmyApi\Response\View\PostView;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: -1_000)]
final readonly class DefaultReplyHandler implements ReplyHandler
{
    public function canHandle(CommentView|PostView $repliable, ?string $software): bool
    {
        return $software !== null;
    }

    public function handle(CommentView|PostView $repliable, ?string $software): void
    {
        error_log(json_encode([
            'error' => 'unsupported software',
            'software' => $software,
            'link' => $repliable instanceof PostView
                ? "https://lemmings.world/post/{$repliable->post->id}"
                : "https://lemmings.world/comment/{$repliable->comment->id}",
        ], flags: JSON_THROW_ON_ERROR));
    }
}
