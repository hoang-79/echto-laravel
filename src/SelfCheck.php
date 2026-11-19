<?php

namespace Echto\Laravel;

use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Prüft ein Token ohne Echto: Signatur mit dem öffentlichen Schlüssel, Bindung, Ablauf, Einmaligkeit. Dafür hält
 * das Paket die öffentlichen Schlüssel einen Tag im Zwischenspeicher dieser Anwendung.
 *
 * Die Befunde im Token sind verschlüsselt und bleiben dem Paket verborgen; es kann deshalb nur sagen, ob das Token
 * echt ist — nicht, wie Echto geurteilt hätte.
 */
final class SelfCheck
{
    public const string KEYS = 'echto:keys';

    public function __construct(private readonly Cache $cache) {}

    /**
     * @param  list<array{kid: string, public_key: string}>  $keys
     */
    public function remember(array $keys): void
    {
        $this->cache->put(self::KEYS, $keys, now()->addDay());
    }

    public function hasKeys(): bool
    {
        return $this->cache->has(self::KEYS);
    }

    /**
     * `true`: echt, passend und zum ersten Mal vorgelegt. `false`: gefälscht, abgelaufen, für etwas anderes
     * ausgestellt oder schon verwendet. `null`: lässt sich nicht sagen, weil kein Schlüssel vorliegt.
     */
    public function accepts(string $token, string $site, string $form, string $action): ?bool
    {
        /** @var list<array{kid: string, public_key: string}>|null $keys */
        $keys = $this->cache->get(self::KEYS);

        if (! is_array($keys) || $keys === []) {
            return null;
        }

        [$body, $signature] = array_pad(explode('.', $token, 2), 2, '');
        $claims = json_decode((string) $this->decode($body), true);
        $signature = $this->decode($signature);

        if (! is_array($claims) || $signature === null || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES || $body === '') {
            return false;
        }

        $key = collect($keys)->firstWhere('kid', $claims['kid'] ?? null);
        $public = is_array($key) ? base64_decode((string) $key['public_key'], true) : false;

        if (! is_string($public) || strlen($public) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES || ! sodium_crypto_sign_verify_detached($signature, $body, $public)) {
            return false;
        }

        if (($claims['site'] ?? null) !== $site || ($claims['form'] ?? null) !== $form || ($claims['action'] ?? null) !== $action) {
            return false;
        }

        $expiresAt = (int) ($claims['exp'] ?? 0);

        if ($expiresAt <= now()->getTimestamp()) {
            return false;
        }

        // Jede Kennung nur einmal — gemerkt, bis das Token ohnehin abgelaufen ist.
        return $this->cache->add('echto:jti:'.($claims['jti'] ?? ''), true, max(1, $expiresAt - now()->getTimestamp()));
    }

    private function decode(string $text): ?string
    {
        if ($text === '' || preg_match('/^[A-Za-z0-9_-]+$/', $text) !== 1) {
            return null;
        }

        $binary = base64_decode(strtr($text, '-_', '+/'), true);

        return $binary === false ? null : $binary;
    }
}
