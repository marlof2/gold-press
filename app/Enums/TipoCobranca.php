<?php

namespace App\Enums;

enum TipoCobranca: string
{
    case Unitario = 'unitario';

    case MetroQuadrado = 'm2';

    public function label(): string
    {
        return match ($this) {
            self::Unitario => 'Unitário',
            self::MetroQuadrado => 'Metro quadrado (m²)',
        };
    }

    public function isMetroQuadrado(): bool
    {
        return $this === self::MetroQuadrado;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function opcoes(): array
    {
        return array_map(fn (self $tipo) => ['value' => $tipo->value, 'label' => $tipo->label()], self::cases());
    }
}
