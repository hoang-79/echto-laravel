<?php

namespace Echto\Laravel\Testing;

use Echto\Laravel\Client;
use Echto\Laravel\Verdict;
use Echto\Laravel\Verification;
use PHPUnit\Framework\Assert;

/**
 * Attrappe für Tests (`Echto::fake()`): antwortet mit einem festen Urteil und merkt sich, was geprüft wurde. Es geht
 * keine Anfrage an Echto.
 */
class FakeClient extends Client
{
    /** @var list<array{form: string, action: string, token: string|null, features: array<string, int|float|bool>}> */
    public array $checks = [];

    /** @var list<array{form: string, released: bool}> */
    public array $outcomes = [];

    public function __construct(private Verdict $verdict) {}

    public function verify(string $form, string $action, ?string $token, ?string $ip, array $features): Verification
    {
        $this->checks[] = ['form' => $form, 'action' => $action, 'token' => $token, 'features' => $features];

        return new Verification($this->verdict, $this->verdict === Verdict::Pass ? [] : [[
            'code' => 'fake.'.$this->verdict->value,
            'text' => 'Attrappe für Tests: festes Urteil.',
        ]]);
    }

    /**
     * Die Attrappe holt keine Regeln: Es gelten die Listen aus der Konfiguration des Pakets.
     *
     * @return array{disposable_domains: list<string>}
     */
    public function rules(): array
    {
        return ['disposable_domains' => array_values(array_filter((array) config('echto.disposable_domains'), is_string(...)))];
    }

    public function reportOutcome(string $form, bool $released): void
    {
        $this->outcomes[] = ['form' => $form, 'released' => $released];
    }

    public function assertChecked(string $form, int $times = 1): void
    {
        $count = count(array_filter($this->checks, fn (array $check): bool => $check['form'] === $form));

        Assert::assertSame($times, $count, "Das Formular «{$form}» wurde {$count}-mal geprüft, erwartet war {$times}-mal.");
    }

    public function assertNothingChecked(): void
    {
        Assert::assertSame([], $this->checks, 'Es wurde eine Einsendung geprüft, obwohl keine erwartet war.');
    }
}
