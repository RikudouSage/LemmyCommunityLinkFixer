<?php

namespace App\Service\Handler;

use Rikudou\LemmyApi\Response\View\CommentView;
use Rikudou\LemmyApi\Response\View\PostView;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.reply_handler')]
interface ReplyHandler
{
    public function canHandle(PostView|CommentView $repliable, ?string $software): bool;

    public function handle(PostView|CommentView $repliable, ?string $software): void;
}
