<?php

namespace App\Services;

/**
 * Transformaciones de texto para nombres de grupos y análisis de laboratorio.
 */
class LabotestNameTransformService
{
    public const MODE_UPPERCASE = 'uppercase';
    public const MODE_SENTENCE  = 'sentence';
    public const MODE_TITLE     = 'title';
    public const MODE_SPELL     = 'spell';

    /** @var list<string> */
    private static array $allowedModes = [
        self::MODE_UPPERCASE,
        self::MODE_SENTENCE,
        self::MODE_TITLE,
        self::MODE_SPELL,
    ];

    /** @var array<string, string>|null */
    private static ?array $typoMap = null;

    /** @var array<string, true>|null */
    private static ?array $vocabularyIndex = null;

    public static function isAllowedMode(string $mode): bool
    {
        return in_array($mode, self::$allowedModes, true);
    }

    public static function transform(string $text, string $mode): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($text === '') {
            return '';
        }

        return match ($mode) {
            self::MODE_UPPERCASE => self::toUppercase($text),
            self::MODE_SENTENCE  => self::toSentenceCase($text),
            self::MODE_TITLE     => self::toTitleCase($text),
            self::MODE_SPELL     => self::spellCorrect($text),
            default              => $text,
        };
    }

    public static function toUppercase(string $text): string
    {
        return mb_strtoupper($text, 'UTF-8');
    }

    public static function toSentenceCase(string $text): string
    {
        $lower = mb_strtolower($text, 'UTF-8');
        $first = mb_substr($lower, 0, 1, 'UTF-8');
        $rest  = mb_substr($lower, 1, null, 'UTF-8');

        return mb_strtoupper($first, 'UTF-8') . $rest;
    }

    public static function toTitleCase(string $text): string
    {
        return mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
    }

    public static function spellCorrect(string $text): string
    {
        if (function_exists('pspell_new')) {
            $corrected = self::spellCorrectWithPspell($text);
            if ($corrected !== null) {
                return $corrected;
            }
        }

        if (function_exists('enchant_broker_init')) {
            $corrected = self::spellCorrectWithEnchant($text);
            if ($corrected !== null) {
                return $corrected;
            }
        }

        $lt = self::spellCorrectWithLanguageTool($text);
        if ($lt !== null && $lt !== $text) {
            return $lt;
        }

        return self::spellCorrectLocal($text);
    }

    private static function spellCorrectWithPspell(string $text): ?string
    {
        $link = @pspell_new('es', '', '', 'utf-8');
        if ($link === false) {
            $link = @pspell_new('es');
        }
        if ($link === false) {
            return null;
        }

        return self::spellCorrectWords($text, static function (string $word) use ($link): string {
            $plain = self::stripWordPunctuation($word);
            if ($plain === '' || self::isNumericToken($plain)) {
                return $word;
            }
            if (pspell_check($link, $plain)) {
                return $word;
            }
            $suggestions = pspell_suggest($link, $plain);
            if ($suggestions === [] || ! is_string($suggestions[0] ?? null)) {
                return $word;
            }

            return self::applySuggestionToToken($word, $plain, $suggestions[0]);
        });
    }

    private static function spellCorrectWithEnchant(string $text): ?string
    {
        $broker = @enchant_broker_init();
        if ($broker === false) {
            return null;
        }

        $dict = @enchant_broker_request_dict($broker, 'es_ES');
        if ($dict === false) {
            $dict = @enchant_broker_request_dict($broker, 'es');
        }
        if ($dict === false) {
            return null;
        }

        return self::spellCorrectWords($text, static function (string $word) use ($dict): string {
            $plain = self::stripWordPunctuation($word);
            if ($plain === '' || self::isNumericToken($plain)) {
                return $word;
            }
            if (enchant_dict_check($dict, $plain)) {
                return $word;
            }
            $suggestions = enchant_dict_suggest($dict, $plain);
            if (! is_array($suggestions) || $suggestions === [] || ! is_string($suggestions[0])) {
                return $word;
            }

            return self::applySuggestionToToken($word, $plain, $suggestions[0]);
        });
    }

    private static function spellCorrectWithLanguageTool(string $text): ?string
    {
        if (! function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init('https://api.languagetool.org/v2/check');
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'text'     => $text,
                'language' => 'es',
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);

        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (! is_string($body) || $body === '' || $code !== 200) {
            return null;
        }

        $data = json_decode($body, true);
        if (! is_array($data) || ! is_array($data['matches'] ?? null)) {
            return null;
        }

        $matches = $data['matches'];
        if ($matches === []) {
            return $text;
        }

        usort($matches, static fn(array $a, array $b): int => (int) ($b['offset'] ?? 0) <=> (int) ($a['offset'] ?? 0));

        $result = $text;
        foreach ($matches as $match) {
            $offset = (int) ($match['offset'] ?? -1);
            $length = (int) ($match['length'] ?? 0);
            $replacements = $match['replacements'] ?? [];
            $replacement = is_array($replacements) ? ($replacements[0]['value'] ?? null) : null;
            if ($offset < 0 || $length < 1 || ! is_string($replacement) || $replacement === '') {
                continue;
            }
            $result = mb_substr($result, 0, $offset, 'UTF-8')
                . $replacement
                . mb_substr($result, $offset + $length, null, 'UTF-8');
        }

        return trim(preg_replace('/\s+/u', ' ', $result) ?? $result);
    }

    private static function spellCorrectLocal(string $text): string
    {
        self::loadLocalDictionary();

        return self::spellCorrectWords($text, static function (string $word): string {
            $plain = self::stripWordPunctuation($word);
            if ($plain === '' || self::isNumericToken($plain)) {
                return $word;
            }

            $key = mb_strtolower($plain, 'UTF-8');
            if (isset(self::$typoMap[$key])) {
                return self::applySuggestionToToken($word, $plain, self::$typoMap[$key]);
            }

            if (isset(self::$vocabularyIndex[$key])) {
                return $word;
            }

            $suggestion = self::findClosestVocabularyWord($key);
            if ($suggestion !== null) {
                return self::applySuggestionToToken($word, $plain, $suggestion);
            }

            return $word;
        });
    }

    /**
     * @param callable(string): string $correctWord
     */
    private static function spellCorrectWords(string $text, callable $correctWord): string
    {
        $parts = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (! is_array($parts)) {
            return $text;
        }

        foreach ($parts as $i => $part) {
            if ($part === '' || preg_match('/^\s+$/u', $part)) {
                continue;
            }
            $parts[$i] = $correctWord($part);
        }

        return trim(implode('', $parts));
    }

    private static function applySuggestionToToken(string $token, string $plain, string $suggestion): string
    {
        $prefix = '';
        $suffix = '';
        if ($plain !== $token) {
            $pos = mb_stripos($token, $plain, 0, 'UTF-8');
            if ($pos === false) {
                return $token;
            }
            $prefix = mb_substr($token, 0, $pos, 'UTF-8');
            $suffix = mb_substr($token, $pos + mb_strlen($plain, 'UTF-8'), null, 'UTF-8');
        }

        if (mb_strtoupper($plain, 'UTF-8') === $plain && mb_strtolower($plain, 'UTF-8') !== $plain) {
            $replacement = mb_strtoupper($suggestion, 'UTF-8');
        } elseif (mb_strtoupper(mb_substr($plain, 0, 1, 'UTF-8'), 'UTF-8') === mb_substr($plain, 0, 1, 'UTF-8')
            && mb_strtolower(mb_substr($plain, 1, null, 'UTF-8'), 'UTF-8') === mb_substr($plain, 1, null, 'UTF-8')) {
            $replacement = mb_strtoupper(mb_substr($suggestion, 0, 1, 'UTF-8'), 'UTF-8')
                . mb_substr($suggestion, 1, null, 'UTF-8');
        } else {
            $replacement = $suggestion;
        }

        return $prefix . $replacement . $suffix;
    }

    private static function stripWordPunctuation(string $word): string
    {
        return trim($word, ".,;:!?\"'()[]{}«»/-");
    }

    private static function isNumericToken(string $word): bool
    {
        return (bool) preg_match('/^\d+([.,]\d+)?$/u', $word);
    }

    private static function findClosestVocabularyWord(string $word): ?string
    {
        self::loadLocalDictionary();
        if (mb_strlen($word, 'UTF-8') < 4) {
            return null;
        }

        $best = null;
        $bestDistance = 3;
        foreach (array_keys(self::$vocabularyIndex ?? []) as $candidate) {
            if (abs(mb_strlen($candidate, 'UTF-8') - mb_strlen($word, 'UTF-8')) > 2) {
                continue;
            }
            $distance = levenshtein($word, $candidate);
            if ($distance > 0 && $distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $candidate;
            }
        }

        return $best;
    }

    private static function loadLocalDictionary(): void
    {
        if (self::$typoMap !== null && self::$vocabularyIndex !== null) {
            return;
        }

        $data = require APPPATH . 'Data/labotest_spell_dictionary.php';
        self::$typoMap = [];
        foreach (($data['typos'] ?? []) as $wrong => $correct) {
            self::$typoMap[mb_strtolower((string) $wrong, 'UTF-8')] = (string) $correct;
        }

        self::$vocabularyIndex = [];
        foreach (($data['vocabulary'] ?? []) as $word) {
            $word = (string) $word;
            if ($word === '') {
                continue;
            }
            self::$vocabularyIndex[mb_strtolower($word, 'UTF-8')] = true;
        }
    }
}
