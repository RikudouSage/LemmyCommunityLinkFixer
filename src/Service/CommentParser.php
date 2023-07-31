<?php

namespace App\Service;

use InvalidArgumentException;

final class CommentParser
{
    /**
     * @return array<string>
     */
    public function findFixedCommunityLinks(string $text): array
    {
        $regex = '#https://(?<DomainName>[^.]+\.[^/]+)/c/(?<CommunitySlug>[a-z0-9_]+)(?<ExistingCommunitySlug>@[^.]+\.\S+)?#';
        if (!preg_match_all($regex, $text, $regexMatches)) {
            return [];
        }

        $result = [];
        $urlsCount = count($regexMatches[0]);
        for ($i = 0; $i < $urlsCount; ++$i) {
            if ($regexMatches['ExistingCommunitySlug'][$i]) {
                $correctedLink = "!{$regexMatches['CommunitySlug'][$i]}{$regexMatches['ExistingCommunitySlug'][$i]}";
            } else {
                $correctedLink = "!{$regexMatches['CommunitySlug'][$i]}@{$regexMatches['DomainName'][$i]}";
            }
            if (str_contains($text, $correctedLink)) {
                continue;
            }

            $result[] = $correctedLink;
        }

        return array_unique($result);
    }

    /**
     * @return array<string>
     */
    public function getPostLinks(string $text): array
    {
        $regex = '@https://(?<DomainName>[^.]+\.[^/]+)/post/(?<PostId>[0-9]+)@';
        if (!preg_match_all($regex, $text, $regexMatches)) {
            return [];
        }

        return $regexMatches[0];
    }

    /**
     * @return array<string>
     */
    public function getCommentLinks(string $text): array
    {
        $regex = '@https://(?<DomainName>[^.]+\.[^/]+)/comment/(?<CommentId>[0-9]+)@';
        if (!preg_match_all($regex, $text, $regexMatches)) {
            return [];
        }

        return $regexMatches[0];
    }

    public function getIdFromLink(string $link): int
    {
        $regex = '@https://(?<DomainName>[^.]+\.[^/]+)/(?:comment|post)/(?<Id>[0-9]+)@';
        if (!preg_match($regex, $link, $matches)) {
            throw new InvalidArgumentException('Invalid link provided');
        }

        return (int) $matches['Id'];
    }
}
