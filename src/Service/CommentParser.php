<?php

namespace App\Service;

final class CommentParser
{
    /**
     * @return array<string>
     */
    public function findFixedLinks(string $text): array
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
}
