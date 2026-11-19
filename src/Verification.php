<?php

namespace Echto\Laravel;

/**
 * Ergebnis einer Prüfung: das Urteil und die Gründe in Worten.
 */
final readonly class Verification
{
    /**
     * @param  list<array{code: string, text: string}>  $reasons
     * @param  bool  $reachable  `false`, wenn Echto nicht antwortete und das Paket selbst entschieden hat
     */
    public function __construct(
        public Verdict $verdict,
        public array $reasons = [],
        public bool $reachable = true,
    ) {}

    public function passed(): bool
    {
        return $this->verdict === Verdict::Pass;
    }

    public function held(): bool
    {
        return $this->verdict === Verdict::Review;
    }

    public function blocked(): bool
    {
        return $this->verdict === Verdict::Block;
    }

    /**
     * @return list<string>
     */
    public function reasonTexts(): array
    {
        return array_column($this->reasons, 'text');
    }
}
