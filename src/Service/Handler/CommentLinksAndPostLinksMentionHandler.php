<?php

namespace App\Service\Handler;

use App\Service\CommentParser;
use App\Service\NodeInfoParser;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Rikudou\LemmyApi\DefaultLemmyApi;
use Rikudou\LemmyApi\Enum\Language;
use Rikudou\LemmyApi\Enum\LemmyApiVersion;
use Rikudou\LemmyApi\Exception\LemmyApiException;
use Rikudou\LemmyApi\LemmyApi;
use Rikudou\LemmyApi\Response\Model\Comment;

final readonly class CommentLinksAndPostLinksMentionHandler implements MentionHandler
{
    public function __construct(
        private NodeInfoParser $nodeInfoParser,
        private CommentParser $commentParser,
        private LemmyApi $api,
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
    ) {
    }

    public function supports(?string $text, Comment $replyTo, string $instance): bool
    {
        assert($text !== null);

        $commentLinks = array_filter($this->commentParser->getCommentLinks($text), $this->filterValidTargets(...));
        $postLinks = array_filter($this->commentParser->getPostLinks($text), $this->filterValidTargets(...));

        return count($commentLinks) || count($postLinks);
    }

    public function handle(?string $text, Comment $replyTo, string $instance): void
    {
        assert($text !== null);

        $allCommentLinks = $this->commentParser->getCommentLinks($text);
        $validCommentLinks = array_filter($allCommentLinks, $this->filterValidTargets(...));
        $invalidCommentLinks = array_diff($allCommentLinks, $validCommentLinks);
        $failedCommentLinks = [];

        $allPostLink = $this->commentParser->getPostLinks($text);
        $validPostLinks = array_filter($allPostLink, $this->filterValidTargets(...));
        $invalidPostLinks = array_diff($allPostLink, $validPostLinks);
        $failedPostLinks = [];

        $substitutions = [];

        $instanceApi = $this->getApi($instance);
        foreach ($validCommentLinks as $validCommentLink) {
            $domain = parse_url($validCommentLink, PHP_URL_HOST);
            assert(is_string($domain));
            $domainApi = $this->getApi($domain);
            $id = $this->commentParser->getIdFromLink($validCommentLink);
            $apId = $domainApi->comment()->get($id)->comment->apId;

            try {
                $targetInstanceResult = $instanceApi->miscellaneous()->resolveObject($apId);
            } catch (LemmyApiException $e) {
                $failedCommentLinks[] = $validCommentLink;
                continue;
            }
            assert($targetInstanceResult->comment !== null);
            $targetLink = "https://{$instance}/comment/{$targetInstanceResult->comment->comment->id}";
            $substitutions[$validCommentLink] = $targetLink;
        }
        foreach ($validPostLinks as $validPostLink) {
            $domain = parse_url($validPostLink, PHP_URL_HOST);
            assert(is_string($domain));
            $domainApi = $this->getApi($domain);
            $id = $this->commentParser->getIdFromLink($validPostLink);
            $apId = $domainApi->post()->get($id)->post->apId;

            try {
                $targetInstanceResult = $instanceApi->miscellaneous()->resolveObject($apId);
            } catch (LemmyApiException $e) {
                $failedPostLinks[] = $validPostLink;
                continue;
            }
            assert($targetInstanceResult->post !== null);
            $targetLink = "https://{$instance}/post/{$targetInstanceResult->post->post->id}";
            $substitutions[$validPostLink] = $targetLink;
        }

        if (!count($substitutions)) {
            $this->api->comment()->create(
                post: $replyTo->postId,
                content: "Sadly I failed to fetch the correct links. Possibly because no one from your instance is subscribed to the community this comment originates from.\n\nYou may contact @rikudou@lemmings.world and he will check what went wrong.",
                language: Language::English,
                parent: $replyTo,
            );

            return;
        }

        $reply = 'Hi there!';
        if (count($failedPostLinks) || count($failedCommentLinks)) {
            $reply .= ' Here are some of the fixed links for your instance:';
        } else {
            $reply .= ' Here are all the fixed links for your instance:';
        }
        $reply .= "\n";

        foreach ($substitutions as $original => $replaced) {
            $reply .= " - {$original} => {$replaced}\n";
        }

        if (count($failedPostLinks) || count($failedCommentLinks)) {
            $failed = [...$failedPostLinks, ...$failedCommentLinks];
            $reply .= "\n\n---\n\nI sadly failed to fix these links (possibly because no one from your instance has subscribed to those communities):\n";
            foreach ($failed as $link) {
                $reply .= " - {$link}\n";
            }
        }

        if (count($invalidCommentLinks) || count($invalidPostLinks)) {
            $invalid = [...$invalidCommentLinks, ...$invalidPostLinks];
            $reply .= "\n\n---\n\nAdditionally, these links look like they should be a valid Lemmy link, but they aren't:\n";
            foreach ($invalid as $link) {
                $reply .= " - {$link}\n";
            }
        }

        $this->api->comment()->create(
            post: $replyTo->postId,
            content: $reply,
            language: Language::English,
            parent: $replyTo,
        );
    }

    private function filterValidTargets(string $link): bool
    {
        return $this->nodeInfoParser->getSoftware($link) === 'lemmy';
    }

    private function getApi(string $instance): LemmyApi
    {
        return new DefaultLemmyApi(
            "https://{$instance}",
            LemmyApiVersion::Version3,
            $this->httpClient,
            $this->requestFactory,
        );
    }
}
