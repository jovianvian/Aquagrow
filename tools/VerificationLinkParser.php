<?php

declare(strict_types=1);

namespace AquaGrow\Tools;

final class VerificationLinkParser
{
    /**
     * @return list<string>
     */
    public static function messagesForRecipient(string $contents, string $email): array
    {
        $messages = preg_split(
            '/(?=^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/m',
            trim($contents),
            -1,
            PREG_SPLIT_NO_EMPTY,
        ) ?: [];

        $address = preg_quote($email, '/');

        return array_values(array_filter($messages, static function (string $message) use ($address): bool {
            foreach (preg_split('/\R/', $message) ?: [] as $lineNumber => $line) {
                if ($lineNumber === 0) {
                    $line = preg_replace('/^\[[^\]]+\]\s+[^:]+:\s*/', '', $line) ?? $line;
                }

                if (preg_match('/^To:\s*(?:[^<\r\n]*<)?'.$address.'(?:>|[,;\s]|$)/i', $line) === 1) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * @return list<string>
     */
    public static function verificationLinks(string $message): array
    {
        return array_column(self::verificationLinkCandidates($message), 'url');
    }

    /**
     * @return list<array{url: string, source: string}>
     */
    public static function verificationLinkCandidates(string $message): array
    {
        $decoded = self::decodeMimeContent($message);
        preg_match_all('~https?://[^\s<>"\']+~', $decoded, $matches, PREG_OFFSET_CAPTURE);

        /** @var array<string, array{url: string, sources: array<string, true>}> $candidates */
        $candidates = [];

        foreach ($matches[0] ?? [] as [$url, $offset]) {
            if (! str_contains($url, '/email/verify/')) {
                continue;
            }

            $source = self::sourceForOffset($decoded, (int) $offset);
            $candidates[$url]['url'] = $url;
            $candidates[$url]['sources'][$source] = true;
        }

        return array_values(array_map(
            static fn (array $candidate): array => [
                'url' => $candidate['url'],
                'source' => implode('+', array_keys($candidate['sources'])),
            ],
            $candidates,
        ));
    }

    private static function decodeMimeContent(string $message): string
    {
        $message = preg_replace('/=\r?\n/', '', $message) ?? $message;

        if (preg_match('/^Content-Transfer-Encoding:\s*quoted-printable\s*$/mi', $message) === 1) {
            $message = quoted_printable_decode($message);
        }

        return html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private static function sourceForOffset(string $message, int $offset): string
    {
        $before = substr($message, 0, $offset);
        $lastTag = strrpos($before, '<');
        $lastClose = strrpos($before, '>');

        return $lastTag !== false && ($lastClose === false || $lastTag > $lastClose) ? 'html' : 'plain';
    }
}
