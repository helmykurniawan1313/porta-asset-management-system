<?php

namespace App\BrandHealth\Services;

use App\BrandHealth\Models\BrandAccount;
use App\BrandHealth\Models\BrandInstagramDailySnapshot;
use Illuminate\Http\UploadedFile;

/**
 * Parses Meta Business Suite's per-metric daily export CSVs (Follows, Visits,
 * Link clicks, Interactions, Viewers, Views — each its own file, UTF-16
 * encoded, with a "sep=," line, a title line, then "Date","Primary" rows) and
 * upserts them into instagram_daily_snapshots, so the existing neighboring-
 * cycle autofill (CycleController::resolveInstagramFollowerNear/Totals) picks
 * them up exactly as it would live API-captured snapshots.
 *
 * "Follows" is Meta's daily NET change in followers, not a running total, so
 * it's converted into the table's absolute followers_count by walking the
 * dates in order and accumulating onto a baseline (either the latest snapshot
 * already on record before the import range, or a user-supplied starting
 * count).
 */
class MetaExportImporter
{
    /**
     * @param  array<string, UploadedFile|null>  $files  keyed by: follows, visits, link_clicks, interactions, viewers, views
     * @return array{dates_imported: int, date_from: ?string, date_to: ?string}
     */
    public function import(BrandAccount $account, array $files, ?int $startingFollowers): array
    {
        $follows = $this->parse($files['follows'] ?? null);
        $viewers = $this->parse($files['viewers'] ?? null);
        $views = $this->parse($files['views'] ?? null);
        $interactions = $this->parse($files['interactions'] ?? null);
        $visits = $this->parse($files['visits'] ?? null);
        $linkClicks = $this->parse($files['link_clicks'] ?? null);

        $dates = collect([$follows, $viewers, $views, $interactions, $visits, $linkClicks])
            ->flatMap(fn (array $series) => array_keys($series))
            ->unique()
            ->sort()
            ->values();

        if ($dates->isEmpty()) {
            return ['dates_imported' => 0, 'date_from' => null, 'date_to' => null];
        }

        $firstDate = $dates->first();

        $runningFollowers = $startingFollowers ?? $this->latestKnownFollowers($account, $firstDate);

        foreach ($dates as $date) {
            if (array_key_exists($date, $follows) && $runningFollowers !== null) {
                $runningFollowers += $follows[$date];
            }

            BrandInstagramDailySnapshot::updateOrCreate(
                ['account_id' => $account->id, 'captured_date' => $date],
                [
                    'followers_count' => $runningFollowers,
                    'reach' => $viewers[$date] ?? null,
                    'views' => $views[$date] ?? null,
                    'total_interactions' => $interactions[$date] ?? null,
                    'profile_visits' => $visits[$date] ?? null,
                    'link_clicks' => $linkClicks[$date] ?? null,
                    'captured_at' => now(),
                ],
            );
        }

        return [
            'dates_imported' => $dates->count(),
            'date_from' => $dates->first(),
            'date_to' => $dates->last(),
        ];
    }

    /**
     * The most recent snapshot strictly before the import's first date, used
     * as the baseline to accumulate "Follows" deltas onto when the caller
     * didn't supply an explicit starting count.
     */
    private function latestKnownFollowers(BrandAccount $account, string $beforeDate): ?int
    {
        $snapshot = $account->instagramDailySnapshots()
            ->whereNotNull('followers_count')
            ->where('captured_date', '<', $beforeDate)
            ->orderByDesc('captured_date')
            ->first();

        return $snapshot?->followers_count;
    }

    /**
     * @return array<string, int> captured_date ("Y-m-d") => value
     */
    private function parse(?UploadedFile $file): array
    {
        if (! $file) {
            return [];
        }

        // Meta exports these as UTF-16LE with a BOM; convert to UTF-8 so
        // str_getcsv parses it cleanly instead of choking on the null bytes.
        $raw = file_get_contents($file->getRealPath());
        $encoding = mb_detect_encoding($raw, ['UTF-16LE', 'UTF-16', 'UTF-8'], true) ?: 'UTF-16LE';
        $contents = $encoding === 'UTF-8' ? $raw : mb_convert_encoding($raw, 'UTF-8', $encoding);
        $contents = preg_replace('/^\x{FEFF}/u', '', $contents);

        $lines = preg_split('/\r\n|\r|\n/', trim($contents));

        $series = [];

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            $columns = str_getcsv($line);

            // Skip the "sep=," line, the metric title line, and the
            // "Date","Primary" header — only rows whose first column parses
            // as a date carry data.
            $rawDate = $columns[0] ?? null;
            if (! $rawDate || ! preg_match('/^\d{4}-\d{2}-\d{2}/', $rawDate)) {
                continue;
            }

            $date = substr($rawDate, 0, 10);
            $series[$date] = (int) ($columns[1] ?? 0);
        }

        return $series;
    }
}
