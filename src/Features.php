<?php

namespace Echto\Laravel;

/**
 * Merkmale des Inhalts einer Einsendung (F-10). Die Regeln kommen von Echto (`GET /v1/rules`), angewendet werden sie
 * hier, auf dem Server der Kundin. Zu Echto gehen nur eine Zahl und Wahrheitswerte — nie der Text:
 *
 * - `links` — wie viele Links die Felder enthalten
 * - `disposable_mail` — eine Mailadresse eines Wegwerf-Dienstes kommt vor
 * - `pattern_<code>` — ein bekanntes Muster (Wendungen aus Werbung) kommt vor
 * - `lang_<code>` — die erkannte Sprache oder Schrift der Nachricht
 * - `rule_<id>` — das Wort einer eigenen Regel der Betreiberin kommt vor
 */
final class Features
{
    /** Unter so vielen Wörtern bleibt die Sprache unbestimmt. */
    private const int MIN_WORDS = 8;

    /** Unter so vielen Buchstaben bleibt die Schrift unbestimmt. */
    private const int MIN_LETTERS = 20;

    /**
     * @param  array<array-key, mixed>  $input  Felder des Formulars
     * @param  array{disposable_domains?: list<string>, patterns?: array<string, list<string>>, languages?: array<string, list<string>>, words?: array<array-key, string>}  $rules
     * @return array<string, int|bool>
     */
    public static function of(array $input, array $rules): array
    {
        $texts = [];
        array_walk_recursive($input, function (mixed $value) use (&$texts): void {
            if (is_string($value) && $value !== '') {
                $texts[] = $value;
            }
        });

        $text = implode("\n", $texts);
        $lower = mb_strtolower($text);
        $features = [
            'links' => (int) preg_match_all('~https?://|\bwww\.|\[url~i', $text),
            'disposable_mail' => self::hasDisposableMail($text, $rules['disposable_domains'] ?? []),
        ];

        foreach ($rules['patterns'] ?? [] as $code => $phrases) {
            if (self::containsAny($lower, $phrases)) {
                $features['pattern_'.self::key($code)] = true;
            }
        }

        foreach ($rules['words'] ?? [] as $id => $phrase) {
            if (self::containsAny($lower, [$phrase])) {
                $features['rule_'.self::key((string) $id)] = true;
            }
        }

        $language = self::language($text, $rules['languages'] ?? []);

        if ($language !== null) {
            $features['lang_'.self::key($language)] = true;
        }

        return $features;
    }

    /**
     * @param  list<string>  $domains
     */
    private static function hasDisposableMail(string $text, array $domains): bool
    {
        if (preg_match_all('/[^\s@<>]+@([a-z0-9.-]+\.[a-z]{2,})/i', $text, $matches) === 0) {
            return false;
        }

        foreach ($matches[1] as $domain) {
            if (in_array(mb_strtolower($domain), $domains, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kommt eine der Wendungen als ganze Wörter vor? Ohne Gross- und Kleinschreibung; die Wendung wird maskiert, sie
     * ist nie ein Muster für preg.
     *
     * @param  list<string>  $phrases
     */
    private static function containsAny(string $lower, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            $phrase = trim(mb_strtolower((string) $phrase));

            if ($phrase !== '' && preg_match('/(?<![\p{L}\p{N}])'.preg_quote($phrase, '/').'(?![\p{L}\p{N}])/u', $lower) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Die Schrift erkennt das Paket an den Zeichen, die Sprache in lateinischer Schrift an häufigen Wörtern. Ist die
     * Nachricht zu kurz oder nicht eindeutig, bleibt sie unbestimmt — dann gilt keine Regel zur Sprache.
     *
     * @param  array<string, list<string>>  $stopwords
     */
    private static function language(string $text, array $stopwords): ?string
    {
        $letters = (int) preg_match_all('/\p{L}/u', $text);

        if ($letters < self::MIN_LETTERS) {
            return null;
        }

        foreach (['cyrl' => 'Cyrillic', 'hani' => 'Han', 'arab' => 'Arabic'] as $code => $script) {
            if ((int) preg_match_all('/\p{'.$script.'}/u', $text) / $letters > 0.3) {
                return $code;
            }
        }

        $words = preg_split('/[^\p{L}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) < self::MIN_WORDS) {
            return null;
        }

        $scores = [];

        foreach ($stopwords as $code => $list) {
            $set = array_flip(array_map(mb_strtolower(...), $list));
            $scores[$code] = count(array_filter($words, fn (string $word): bool => isset($set[$word])));
        }

        arsort($scores);
        $ranked = array_values($scores);
        $best = array_key_first($scores);

        return $best !== null && $ranked[0] >= 3 && $ranked[0] > ($ranked[1] ?? 0) ? (string) $best : null;
    }

    /**
     * Kennungen aus Echto kommen in die Namen der Merkmale — nur Buchstaben, Ziffern und Unterstrich.
     */
    private static function key(string $value): string
    {
        return (string) preg_replace('/[^a-z0-9_]/', '', strtolower($value));
    }
}
