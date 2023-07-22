<?php

namespace App\Service\Handler;

use App\Service\CommentParser;
use App\Service\NodeInfoParser;
use Rikudou\LemmyApi\Enum\Language;
use Rikudou\LemmyApi\LemmyApi;
use Rikudou\LemmyApi\Response\View\CommentView;
use Rikudou\LemmyApi\Response\View\PostView;

final readonly class MastodonReplyHandler implements ReplyHandler
{
    public function __construct(
        private CommentParser $commentParser,
        private NodeInfoParser $nodeInfoParser,
        private LemmyApi $api,
    ) {
    }

    public function canHandle(CommentView|PostView $repliable, ?string $software): bool
    {
        return $software === 'mastodon';
    }

    public function handle(CommentView|PostView $repliable, ?string $software): void
    {
        if ($repliable->creator->botAccount) {
            return;
        }

        $text = $repliable instanceof CommentView
            ? $repliable->comment->content
            : $repliable->post->body
        ;
        if ($text === null) {
            return;
        }

        $currentCommunityUrl = $repliable->community->actorId;
        $fixedCurrentUrl = $this->commentParser->findFixedLinks($currentCommunityUrl)[0] ?? null;
        if ($fixedCurrentUrl === null) {
            return;
        }

        $fixedLinks = $this->commentParser->findFixedLinks($text);
        $fixedLinks = array_filter(
            $fixedLinks,
            fn (string $link)
                => $link !== $fixedCurrentUrl
                && $this->nodeInfoParser->getSoftware("lemmy://{$link}") === 'lemmy',
        );
        if (!count($fixedLinks)) {
            return;
        }
        $response = 'Hi there! Your text contains links to other Lemmy communities, here are correct links for Lemmy users: %s';
        $this->api->comment()->create(
            post: $repliable->post,
            content: sprintf($response, implode(', ', $fixedLinks)),
            language: Language::English,
            parent: $repliable instanceof CommentView ? $repliable->comment : null,
        );

        $replyUrl = $repliable instanceof PostView
            ? "https://lemmings.world/post/{$repliable->post->id}"
            : "https://lemmings.world/comment/{$repliable->comment->id}";
        error_log("Sending reply to {$replyUrl}");
    }
}
