<?php

namespace App\Service\Handler;

use App\Service\CommentParser;
use App\Service\NodeInfoParser;
use Rikudou\LemmyApi\Enum\Language;
use Rikudou\LemmyApi\LemmyApi;
use Rikudou\LemmyApi\Response\Model\Comment;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 90)]
final readonly class NonLemmyInstanceMentionHandler implements MentionHandler
{
    public function __construct(
        private NodeInfoParser $nodeInfoParser,
        private CommentParser $commentParser,
        private LemmyApi $api,
    ) {
    }

    public function supports(?string $text, Comment $replyTo, string $instance): bool
    {
        assert($text !== null);

        $commentLinks = $this->commentParser->getCommentLinks($text);
        $postLinks = $this->commentParser->getPostLinks($text);

        if (!count($commentLinks) && !count($postLinks)) {
            return false;
        }

        $filter = fn (string $link) => $this->nodeInfoParser->getSoftware($link) === 'lemmy';

        $commentLinksFiltered = array_filter($commentLinks, $filter);
        $postLinksFiltered = array_filter($postLinks, $filter);

        return !count($commentLinksFiltered) && !count($postLinksFiltered);
    }

    public function handle(?string $text, Comment $replyTo, string $instance): void
    {
        assert(is_string($text));
        $links = [...$this->commentParser->getPostLinks($text), ...$this->commentParser->getCommentLinks($text)];
        $links = implode(', ', $links);

        $this->api->comment()->create(
            post: $replyTo->postId,
            content: "While it looks like the links ({$links}) might lead to a Lemmy instance, none of them actually do.",
            language: Language::English,
            parent: $replyTo
        );
    }
}
