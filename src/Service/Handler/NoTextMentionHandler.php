<?php

namespace App\Service\Handler;

use Rikudou\LemmyApi\Enum\Language;
use Rikudou\LemmyApi\Exception\LanguageNotAllowedException;
use Rikudou\LemmyApi\LemmyApi;
use Rikudou\LemmyApi\Response\Model\Comment;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 100)]
final readonly class NoTextMentionHandler implements MentionHandler
{
    public function __construct(
        private LemmyApi $api,
    ) {
    }

    public function supports(?string $text, Comment $replyTo, string $instance): bool
    {
        return $text === null;
    }

    public function handle(?string $text, Comment $replyTo, string $instance): void
    {
        $response = "I'm sorry, the post doesn't contain any text, there's nothing I can help with.";

        try {
            $this->api->comment()->create(
                post: $replyTo->postId,
                content: $response,
                language: Language::English,
                parent: $replyTo
            );
        } catch (LanguageNotAllowedException) {
            $this->api->comment()->create(
                post: $replyTo->postId,
                content: $response,
                language: Language::Undetermined,
                parent: $replyTo
            );
        }
    }
}
