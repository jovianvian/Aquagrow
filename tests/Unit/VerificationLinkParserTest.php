<?php

declare(strict_types=1);

namespace Tests\Unit;

use AquaGrow\Tools\VerificationLinkParser;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/tools/VerificationLinkParser.php';

class VerificationLinkParserTest extends TestCase
{
    public function test_it_decodes_ampersands_and_deduplicates_html_and_plain_text_links(): void
    {
        $link = 'https://127.0.0.1:8091/email/verify/42/hash?expires=123&signature=abc';
        $htmlLink = str_replace('&', '&amp;', $link);
        $message = "To: owner@example.test\nOther: https://example.test/help\n<a href=\"{$htmlLink}\">verify</a>\n{$link}";

        $this->assertSame([$link], VerificationLinkParser::verificationLinks($message));
    }

    public function test_it_reports_no_link_when_a_message_has_no_verification_url(): void
    {
        $this->assertSame([], VerificationLinkParser::verificationLinks("To: owner@example.test\nhttps://example.test/help"));
    }

    public function test_it_keeps_different_verification_links_explicitly_ambiguous(): void
    {
        $message = "https://example.test/email/verify/42/a?expires=1&signature=x\nhttps://example.test/email/verify/42/b?expires=1&signature=y";

        $this->assertCount(2, VerificationLinkParser::verificationLinks($message));
    }

    public function test_it_decodes_quoted_printable_soft_breaks_without_reconstructing_the_url(): void
    {
        $message = "Content-Transfer-Encoding: quoted-printable\n\nhttps://example.test/email/verify/42/hash?expires=123&signature=ab=\nc";

        $candidates = VerificationLinkParser::verificationLinkCandidates($message);

        $this->assertCount(1, $candidates);
        $this->assertSame('plain', $candidates[0]['source']);
        $this->assertSame('https://example.test/email/verify/42/hash?expires=123&signature=abc', $candidates[0]['url']);
    }

    public function test_it_selects_only_messages_for_the_exact_recipient(): void
    {
        $contents = "[2026-09-09 10:00:00] local.DEBUG: To: other@example.test\nhttps://example.test/email/verify/1/a\n[2026-09-09 10:01:00] local.DEBUG: To: owner@example.test\nhttps://example.test/email/verify/2/b";

        $messages = VerificationLinkParser::messagesForRecipient($contents, 'owner@example.test');

        $this->assertCount(1, $messages);
        $this->assertStringContainsString('owner@example.test', $messages[0]);
    }

    public function test_it_supports_mime_to_headers_after_the_log_prefix(): void
    {
        $contents = "[2026-09-09 10:01:00] local.DEBUG: From: noreply@example.test\nTo: <owner@example.test>\nSubject: Verify\n\nBody";

        $this->assertCount(1, VerificationLinkParser::messagesForRecipient($contents, 'owner@example.test'));
    }

    public function test_it_does_not_match_similar_addresses_or_body_mentions(): void
    {
        $contents = "[2026-09-09 10:00:00] local.DEBUG: To: owner@example.test.evil\nBody mentions owner@example.test\n[2026-09-09 10:01:00] local.DEBUG: Subject: Verify\nBody mentions owner@example.test";

        $this->assertSame([], VerificationLinkParser::messagesForRecipient($contents, 'owner@example.test'));
    }
}
