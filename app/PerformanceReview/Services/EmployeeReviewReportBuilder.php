<?php

namespace App\PerformanceReview\Services;

use App\Models\User;
use App\PerformanceReview\Models\ReviewAssignment;
use Illuminate\Support\Collection;

class EmployeeReviewReportBuilder
{
    private const REVIEW_TYPES = ['self', 'peer', 'upward', 'downward'];

    /**
     * For one employee: for every review type they've received at least one
     * submitted review for, one section per distinct template used (a review
     * type can be reused across multiple cycles/templates over time — e.g.
     * "Peer Review" run again next quarter with revised questions — and each
     * template's answers must stay in their own rating matrix rather than
     * being merged, since column/question sets can differ). Each section is
     * a rating matrix (question rows x reviewer columns, with a row average)
     * plus the free-text answers from each reviewer. Shared by the on-screen
     * report, the PDF export, and the Excel export so all three stay in sync.
     *
     * @param  array<string>  $anonymizeTypes  Review types (e.g. ['upward']) whose
     *   reviewer columns/answers should be shown as "Reviewer 1", "Reviewer 2", etc.
     *   instead of real names — used for the self-service report so a Head of
     *   Department can't identify which staff member gave which upward feedback.
     */
    public function build(User $employee, array $anonymizeTypes = []): Collection
    {
        $assignments = ReviewAssignment::where('reviewee_id', $employee->id)
            ->where('status', 'submitted')
            ->with([
                'reviewer:id,name',
                'reviewResponses.question:id,review_template_id,text,type,order',
                'reviewCycle:id,name,starts_at',
                'reviewCycle.reviewTemplates.questions' => fn ($q) => $q->orderBy('order'),
            ])
            ->get()
            ->groupBy('review_type');

        return collect(self::REVIEW_TYPES)
            ->flatMap(fn ($type) => $this->buildSectionsForType(
                $type,
                $assignments->get($type, collect()),
                in_array($type, $anonymizeTypes, true),
            ))
            ->values();
    }

    /**
     * Splits one review type's assignments into one section per distinct
     * template (identified by the review cycle's first template's id, since
     * that's what determines the question set each assignment was answered
     * against).
     */
    private function buildSectionsForType(string $type, Collection $assignments, bool $anonymize): Collection
    {
        return $assignments
            ->groupBy(fn ($assignment) => $assignment->reviewCycle->reviewTemplates->first()?->id)
            ->map(fn ($templateAssignments) => $this->buildSection($type, $templateAssignments, $anonymize))
            ->filter(fn ($section) => $section !== null)
            ->values();
    }

    private function buildSection(string $type, Collection $assignments, bool $anonymize = false): ?array
    {
        if ($assignments->isEmpty()) {
            return null;
        }

        // All assignments in this group share the same template (grouped by
        // template id in buildSectionsForType), so any one of them tells us
        // the question set and cycle name for this section.
        $template = $assignments->first()->reviewCycle->reviewTemplates->first();
        $cycleName = $assignments->first()->reviewCycle->name;
        $cycleStartsAt = $assignments->first()->reviewCycle->starts_at;
        $questions = $template?->questions ?? collect();

        $ratingQuestions = $questions->where('type', 'rating_scale')->values();
        $textQuestions = $questions->where('type', 'text')->values();

        // Anonymized reviewer order is shuffled so column position can't be
        // used to infer identity from a known submission order.
        $orderedAssignments = $anonymize ? $assignments->shuffle()->values() : $assignments->values();

        $reviewers = $orderedAssignments->values()->map(fn ($a, $index) => [
            'id' => $a->reviewer->id,
            'name' => $anonymize ? 'Reviewer '.($index + 1) : $a->reviewer->name,
        ])->values();

        $reviewerLabelById = $reviewers->pluck('name', 'id');

        $ratingRows = $ratingQuestions->map(function ($question) use ($assignments, $reviewers) {
            $scores = [];

            foreach ($assignments as $assignment) {
                $response = $assignment->reviewResponses->firstWhere('question_id', $question->id);
                $scores[$assignment->reviewer_id] = $response?->answer_rating;
            }

            $numericScores = array_filter($scores, fn ($v) => $v !== null);

            return [
                'criteria' => $question->text,
                'scores' => $reviewers->map(fn ($r) => $scores[$r['id']] ?? null)->values(),
                'scoresByReviewerId' => $scores,
                'average' => count($numericScores) > 0
                    ? round(array_sum($numericScores) / count($numericScores), 2)
                    : null,
            ];
        })->values();

        $textAnswers = $textQuestions->map(function ($question) use ($assignments, $reviewerLabelById) {
            $answers = $assignments->map(function ($assignment) use ($question, $reviewerLabelById) {
                $response = $assignment->reviewResponses->firstWhere('question_id', $question->id);

                return [
                    'reviewer' => $reviewerLabelById[$assignment->reviewer_id] ?? $assignment->reviewer->name,
                    'answer' => $response?->answer_text,
                ];
            })->filter(fn ($a) => filled($a['answer']))->values();

            return [
                'question' => $question->text,
                'answers' => $answers,
            ];
        })->filter(fn ($q) => $q['answers']->isNotEmpty())->values();

        return [
            'type' => $type,
            'cycleName' => $cycleName,
            'cycleStartsAt' => $cycleStartsAt,
            'templateName' => $template?->name,
            'reviewers' => $reviewers,
            'ratingRows' => $ratingRows,
            'textAnswers' => $textAnswers,
        ];
    }
}
