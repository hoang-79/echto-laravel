<?php

namespace Echto\Laravel\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Eine zurückgehaltene Einsendung. Sie liegt nur in der Datenbank dieser Anwendung, verschlüsselt mit deren
 * Schlüssel — Echto sieht sie nie. Freigegebene und verworfene Einsendungen werden gelöscht; was liegen bleibt,
 * verfällt nach der eingestellten Frist (`php artisan model:prune --model="Echto\Laravel\Models\QuarantinedSubmission"`).
 *
 * @property int $id
 * @property string $form
 * @property string $method
 * @property string $path
 * @property array<string, mixed> $payload
 * @property list<array{code: string, text: string}> $reasons
 * @property string|null $user_id
 * @property bool $unreachable
 * @property Carbon $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class QuarantinedSubmission extends Model
{
    use MassPrunable;

    protected $table = 'echto_quarantine';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'encrypted:array',
            'reasons' => 'array',
            'unreachable' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('expires_at', '<', now());
    }

    /**
     * Wer die Einsendung geschickt hat, soweit das Formular es verrät: ein Name, sonst eine Mailadresse.
     */
    public function sender(): ?string
    {
        foreach (['name', 'email', 'e-mail', 'mail'] as $field) {
            $value = $this->payload[$field] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * Die ersten Zeichen des längsten Textes — für die Liste.
     */
    public function excerpt(int $length = 90): string
    {
        $texts = array_filter($this->payload, is_string(...));
        usort($texts, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
        $longest = trim((string) ($texts[0] ?? ''));

        return mb_strlen($longest) > $length ? rtrim(mb_substr($longest, 0, $length - 2)).' …' : $longest;
    }
}
