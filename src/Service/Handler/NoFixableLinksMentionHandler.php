<?php

namespace App\Service\Handler;

use App\Service\CommentParser;
use Rikudou\LemmyApi\Enum\Language;
use Rikudou\LemmyApi\LemmyApi;
use Rikudou\LemmyApi\Response\Model\Comment;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 95)]
final readonly class NoFixableLinksMentionHandler implements MentionHandler
{
    public function __construct(
        private CommentParser $commentParser,
        private LemmyApi $api,
    ) {
    }

    public function supports(?string $text, Comment $replyTo, string $instance): bool
    {
        assert($text !== null);

        return !count($this->commentParser->getPostLinks($text)) && !count($this->commentParser->getCommentLinks($text));
    }

    public function handle(?string $text, Comment $replyTo, string $instance): void
    {
        $this->api->comment()->create(
            post: $replyTo->postId,
            content: "I'm sorry, I couldn't find any links that I could fix. If you believe this is a mistake, please contact @rikudou@lemmings.world.",
            language: Language::English,
            parent: $replyTo
        );
    }
}
