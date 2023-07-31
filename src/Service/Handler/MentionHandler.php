<?php

namespace App\Service\Handler;

use Rikudou\LemmyApi\Response\Model\Comment;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.mention_handler')]
interface MentionHandler
{
    public function supports(?string $text, Comment $replyTo, string $instance): bool;

    public function handle(?string $text, Comment $replyTo, string $instance): void;
}
