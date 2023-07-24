<?php

namespace App\Command;

use App\Service\NodeInfoParser;
use App\Service\ReplyHandlerCollection;
use DateTimeImmutable;
use Psr\Cache\CacheItemPoolInterface;
use Rikudou\LemmyApi\Enum\CommentSortType;
use Rikudou\LemmyApi\Enum\ListingType;
use Rikudou\LemmyApi\Enum\SortType;
use Rikudou\LemmyApi\LemmyApi;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:run')]
final class RunCommand extends Command
{
    public function __construct(
        private readonly LemmyApi $api,
        private readonly int $commentLimit,
        private readonly int $postLimit,
        private readonly CacheItemPoolInterface $cache,
        private readonly ReplyHandlerCollection $replyHandlers,
        private readonly NodeInfoParser $nodeInfoParser,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lastRepliableTimeCache = $this->cache->getItem('last_comment_time');
        $lastRepliableTime = time() - 600;
        if ($lastRepliableTimeCache->isHit()) {
            $lastRepliableTime = $lastRepliableTimeCache->get();
            assert(is_int($lastRepliableTime));
        }

        //        $community = $this->api->community()->get('bot_playground@lemmings.world');

        $comments = $this->api->comment()->getComments(
            //            community: $community,
            limit: $this->commentLimit,
            sortType: CommentSortType::New,
            listingType: ListingType::All,
        );

        $posts = $this->api->post()->getPosts(
            //            community: $community,
            limit: $this->postLimit,
            sort: SortType::New,
            listingType: ListingType::All
        );

        $newLastRepliableTime = $lastRepliableTime;

        foreach ($posts as $post) {
            if ($post->post->published->getTimestamp() <= $lastRepliableTime) {
                break;
            }
            if ($post->post->published->getTimestamp() > (new DateTimeImmutable())->getTimestamp()) {
                continue;
            }
            $this->replyHandlers->handle(
                $post,
                $this->nodeInfoParser->getSoftware($post->creator->actorId),
            );

            $temporaryNewLastRepliableTime = $post->post->published->getTimestamp();
            if ($temporaryNewLastRepliableTime > $newLastRepliableTime) {
                $newLastRepliableTime = $temporaryNewLastRepliableTime;
                $lastRepliableTimeCache->set($newLastRepliableTime);
                $this->cache->save($lastRepliableTimeCache);
            }
        }

        foreach ($comments as $comment) {
            if ($comment->comment->published->getTimestamp() <= $lastRepliableTime) {
                break;
            }
            if ($comment->comment->published->getTimestamp() > (new DateTimeImmutable())->getTimestamp()) {
                continue;
            }
            $this->replyHandlers->handle(
                $comment,
                $this->nodeInfoParser->getSoftware($comment->creator->actorId),
            );

            $temporaryNewLastRepliableTime = $comment->comment->published->getTimestamp();
            if ($temporaryNewLastRepliableTime > $newLastRepliableTime) {
                $newLastRepliableTime = $temporaryNewLastRepliableTime;
                $lastRepliableTimeCache->set($newLastRepliableTime);
                $this->cache->save($lastRepliableTimeCache);
            }
        }

        return self::SUCCESS;
    }
}
