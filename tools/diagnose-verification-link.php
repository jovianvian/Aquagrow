<?php

declare(strict_types=1);

use AquaGrow\Tools\VerificationLinkParser;
use Illuminate\Http\Request;

require __DIR__.'/VerificationLinkParser.php';

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$database = (string) config('database.connections.'.config('database.default').'.database');

echo 'environment='.app()->environment().PHP_EOL;
echo 'database_driver='.config('database.default').PHP_EOL;
echo 'database_name='.$database.PHP_EOL;

if ($database !== 'aquagrow_acceptance') {
    fwrite(STDERR, "Refusing diagnostics: active database is not aquagrow_acceptance.".PHP_EOL);
    exit(2);
}

$email = trim($argv[1] ?? (string) readline('Verification recipient email: '));

if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Refusing diagnostics: invalid recipient email.".PHP_EOL);
    exit(2);
}

$user = App\Models\User::query()->where('email', $email)->first();

if ($user === null) {
    echo 'account=not_found'.PHP_EOL;
    exit(0);
}

$logPath = storage_path('logs/laravel.log');
$contents = is_file($logPath) ? (string) file_get_contents($logPath) : '';
$matching = VerificationLinkParser::messagesForRecipient($contents, $email);

echo 'matching_message_count='.count($matching).PHP_EOL;

if ($matching === []) {
    echo 'verification_message=not_found'.PHP_EOL;
    exit(0);
}

$timestamps = array_map(static function (string $message): int {
    preg_match('/^\[([^]]+)]/m', $message, $match);

    return isset($match[1]) ? (int) strtotime($match[1]) : 0;
}, $matching);
$latestTimestamp = max($timestamps);
$latestIndexes = array_keys(array_filter($timestamps, static fn (int $timestamp): bool => $timestamp === $latestTimestamp));

if (count($latestIndexes) !== 1) {
    echo 'message_selection=ambiguous'.PHP_EOL;
    exit(0);
}

$message = $matching[$latestIndexes[0]];
preg_match('/^\[([^]]+)]/m', $message, $timestampMatch);
echo 'message_timestamp='.($timestampMatch[1] ?? 'unknown').PHP_EOL;

$candidates = VerificationLinkParser::verificationLinkCandidates($message);
$urlGenerator = app('url');

if (count($candidates) !== 1) {
    echo 'verification_link=ambiguous'.PHP_EOL;
    echo 'verification_link_count='.count($candidates).PHP_EOL;

    foreach ($candidates as $index => $candidate) {
        $candidateNumber = $index + 1;
        $candidateUrl = $candidate['url'];
        $candidateParsed = parse_url($candidateUrl);
        $candidateQuery = [];
        parse_str((string) ($candidateParsed['query'] ?? ''), $candidateQuery);
        $candidatePathParts = explode('/', trim((string) ($candidateParsed['path'] ?? ''), '/'));
        $candidateRequest = Request::create($candidateUrl, 'GET');
        $candidateExpires = $candidateQuery['expires'] ?? null;
        $candidateExpired = ! is_string($candidateExpires) || ! ctype_digit($candidateExpires) || (int) $candidateExpires < time();

        echo "candidate_{$candidateNumber}_source={$candidate['source']}".PHP_EOL;
        echo "candidate_{$candidateNumber}_scheme=".($candidateParsed['scheme'] ?? 'missing').PHP_EOL;
        echo "candidate_{$candidateNumber}_host=".($candidateParsed['host'] ?? 'missing').PHP_EOL;
        echo "candidate_{$candidateNumber}_port=".($candidateParsed['port'] ?? 'default').PHP_EOL;
        echo "candidate_{$candidateNumber}_user_id_matches=".(isset($candidatePathParts[2]) && (string) $user->getKey() === $candidatePathParts[2] ? 'true' : 'false').PHP_EOL;
        echo "candidate_{$candidateNumber}_email_hash_matches=".(isset($candidatePathParts[3]) && hash_equals(sha1($email), $candidatePathParts[3]) ? 'true' : 'false').PHP_EOL;
        echo "candidate_{$candidateNumber}_query_parameters=".implode(',', array_keys($candidateQuery)).PHP_EOL;
        echo "candidate_{$candidateNumber}_expires=".($candidateExpires ?? 'missing').PHP_EOL;
        echo "candidate_{$candidateNumber}_expired=".($candidateExpired ? 'true' : 'false').PHP_EOL;
        echo "candidate_{$candidateNumber}_signature_correct=".($urlGenerator->hasCorrectSignature($candidateRequest) ? 'true' : 'false').PHP_EOL;

        if ($index > 0) {
            $firstUrl = $candidates[0]['url'];
            $firstParsed = parse_url($firstUrl);
            $firstQuery = [];
            parse_str((string) ($firstParsed['query'] ?? ''), $firstQuery);
            echo "candidate_{$candidateNumber}_same_path_as_candidate_1=".(($candidateParsed['path'] ?? null) === ($firstParsed['path'] ?? null) ? 'true' : 'false').PHP_EOL;
            echo "candidate_{$candidateNumber}_same_query_as_candidate_1=".($firstQuery === $candidateQuery ? 'true' : 'false').PHP_EOL;
            echo "candidate_{$candidateNumber}_same_signature_as_candidate_1=".(($firstQuery['signature'] ?? null) === ($candidateQuery['signature'] ?? null) ? 'true' : 'false').PHP_EOL;
        }
    }

    exit(0);
}

$link = $candidates[0]['url'];
$parsed = parse_url($link);

if (! is_array($parsed)) {
    echo 'verification_link=invalid'.PHP_EOL;
    exit(0);
}

$query = [];
parse_str((string) ($parsed['query'] ?? ''), $query);
$pathParts = explode('/', trim((string) ($parsed['path'] ?? ''), '/'));
$pathUserId = $pathParts[2] ?? null;
$pathEmailHash = $pathParts[3] ?? null;

$request = Request::create($link, 'GET');
$expires = $query['expires'] ?? null;
$expired = ! is_string($expires) || ! ctype_digit($expires) || (int) $expires < time();

echo 'generated_scheme='.($parsed['scheme'] ?? 'missing').PHP_EOL;
echo 'generated_host='.($parsed['host'] ?? 'missing').PHP_EOL;
echo 'generated_port='.($parsed['port'] ?? 'default').PHP_EOL;
echo 'path_user_id_match='.(is_string($pathUserId) && (string) $user->getKey() === $pathUserId ? 'true' : 'false').PHP_EOL;
echo 'path_email_hash_match='.(is_string($pathEmailHash) && hash_equals(sha1($email), $pathEmailHash) ? 'true' : 'false').PHP_EOL;
echo 'utc_now='.now('UTC')->toIso8601String().PHP_EOL;
echo 'expires='.($expires ?? 'missing').PHP_EOL;
echo 'expired='.($expired ? 'true' : 'false').PHP_EOL;
echo 'signature_correct='.($urlGenerator->hasCorrectSignature($request) ? 'true' : 'false').PHP_EOL;
