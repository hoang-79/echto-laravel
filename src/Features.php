<?php

namespace Echto\Laravel;

/**
 * Merkmale des Inhalts einer Einsendung. Gezählt und erkannt wird hier, auf dem Server der Kundin; zu Echto gehen
 * nur eine Zahl und Wahrheitswerte — nie der Text.
 */
final class Features
{
    /**
     * @param  array<array-key, mixed>  $input  Felder des Formulars
     * @param  list<string>  $disposableDomains
     * @return array{links: int, disposable_mail: bool}
     */
    public static function of(array $input, array $disposableDomains): array
    {
        $links = 0;
        $disposable = false;

        array_walk_recursive($input, function (mixed $value) use (&$links, &$disposable, $disposableDomains): void {
            if (! is_string($value)) {
                return;
            }

            $links += (int) preg_match_all('~https?://|\bwww\.|\[url~i', $value);

            if (preg_match_all('/[^\s@<>]+@([a-z0-9.-]+\.[a-z]{2,})/i', $value, $matches) > 0) {
                foreach ($matches[1] as $domain) {
                    $disposable = $disposable || in_array(mb_strtolower($domain), $disposableDomains, true);
                }
            }
        });

        return ['links' => $links, 'disposable_mail' => $disposable];
    }
}
