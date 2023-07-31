<?php

namespace App\Service\Handler;

use Rikudou\LemmyApi\Response\Model\Comment;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: -1_000)]
final class DefaultMentionHandler implements MentionHandler
{
    public function supports(?string $text, Comment $replyTo, string $instance): bool
    {
        return true;
    }

    public function handle(?string $text, Comment $replyTo, string $instance): void
    {
        error_log(json_encode([
            'error' => 'unknown mention handler',
            'instance' => $instance,
            'link' => "https://lemmings.world/comment/{$replyTo->id}",
        ], flags: JSON_THROW_ON_ERROR));
    }
}
