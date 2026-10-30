<?php

namespace App\BrandHealth\Services;

/**
 * PHP mirror of resources/js/utils/bucketColor.js — resolves a ScoreBucket/
 * LabelBucket's stored `color` to a literal {bg, ink} hex pair, for contexts
 * that can't use CSS var() (PDF/Excel exports). Light-mode hex values only —
 * static documents render in one fixed scheme regardless of the viewer's
 * live UI theme, same reasoning as the existing PDF export color choices.
 */
class BucketColorResolver
{
    public const TONES = ['sip', 'bagus', 'cukup', 'kurang', 'parah'];

    private const TONE_HEX = [
        'sip' => ['bg' => 'E2F3EC', 'ink' => '146C48'],
        'bagus' => ['bg' => 'EAF3D9', 'ink' => '4D6B17'],
        'cukup' => ['bg' => 'FDF1D7', 'ink' => '92650A'],
        'kurang' => ['bg' => 'FBE6D8', 'ink' => '9A4816'],
        'parah' => ['bg' => 'FBE2E2', 'ink' => 'A1282A'],
    ];

    /**
     * @param  object|array|null  $bucket  carries ->color / ->custom_color_bg / ->custom_color_ink (or array keys)
     * @param  int  $fallbackRank  rank to fall back to when the bucket has no color set yet
     * @return array{bg: string, ink: string} 6-digit hex, no '#' prefix (Excel/dompdf convention)
     */
    public static function resolve($bucket, int $fallbackRank = 0): array
    {
        $color = is_array($bucket) ? ($bucket['color'] ?? null) : ($bucket->color ?? null);

        if ($color === 'custom') {
            $customBg = is_array($bucket) ? ($bucket['custom_color_bg'] ?? null) : ($bucket->custom_color_bg ?? null);
            $customInk = is_array($bucket) ? ($bucket['custom_color_ink'] ?? null) : ($bucket->custom_color_ink ?? null);
            if ($customBg && $customInk) {
                return ['bg' => ltrim($customBg, '#'), 'ink' => ltrim($customInk, '#')];
            }
        }

        $token = in_array($color, self::TONES, true) ? $color : self::toneByRank($fallbackRank);

        return self::TONE_HEX[$token];
    }

    private static function toneByRank(int $rank): string
    {
        return self::TONES[min($rank, count(self::TONES) - 1)];
    }
}
