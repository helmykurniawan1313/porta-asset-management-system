<?php

namespace Database\Seeders;

use App\Models\User;
use App\PerformanceReview\Models\ReviewAssignment;
use App\PerformanceReview\Models\ReviewCycle;
use App\PerformanceReview\Models\ReviewResponse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * One-time demo-data seeder: tops up peer review coverage (every user
 * missing a peer reviewer gets 3 random colleagues assigned), then fills in
 * random but plausible responses across every assignment app-wide so
 * reports/dashboards have real data instead of empty states.
 */
class ReviewDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->topUpPeerReviews();
        $this->seedResponses();
    }

    /**
     * Peer review has no org-chart auto-generation rule (unlike upward/
     * downward), so any user with zero peer assignments as reviewee gets 3
     * random other users assigned as reviewers.
     */
    private function topUpPeerReviews(): void
    {
        $peerCycle = ReviewCycle::where('name', 'like', 'Peer Review%')->first();

        if (! $peerCycle) {
            return;
        }

        $allUserIds = User::pluck('id');

        $coveredRevieweeIds = ReviewAssignment::where('review_cycle_id', $peerCycle->id)
            ->where('review_type', 'peer')
            ->pluck('reviewee_id')
            ->unique();

        $uncoveredIds = $allUserIds->diff($coveredRevieweeIds);

        foreach ($uncoveredIds as $revieweeId) {
            $reviewerPool = $allUserIds->reject(fn ($id) => $id === $revieweeId)->values();
            $reviewers = $reviewerPool->random(min(3, $reviewerPool->count()));

            foreach ($reviewers as $reviewerId) {
                ReviewAssignment::firstOrCreate([
                    'review_cycle_id' => $peerCycle->id,
                    'reviewer_id' => $reviewerId,
                    'reviewee_id' => $revieweeId,
                    'review_type' => 'peer',
                ], [
                    'status' => 'pending',
                ]);
            }
        }
    }

    /**
     * For every assignment with no responses yet, randomly decide a status
     * (mostly submitted, some in_progress, some left pending) and generate
     * matching answers for rating/text questions from that assignment's
     * cycle template.
     */
    private function seedResponses(): void
    {
        $assignments = ReviewAssignment::with(['reviewCycle.reviewTemplates.questions'])
            ->doesntHave('reviewResponses')
            ->get();

        $textAnswerBank = [
            'Great collaboration this cycle, consistently reliable and communicative.',
            'Delivered work on time and proactively flagged blockers early.',
            'Could improve on documenting decisions for the rest of the team.',
            'Always willing to help teammates and share knowledge.',
            'Strong ownership of tasks, but sometimes needs closer coordination.',
            'Positive attitude even under tight deadlines.',
            'Would benefit from more structured status updates.',
            'Excellent problem-solving and attention to detail.',
        ];

        foreach ($assignments as $assignment) {
            $status = $this->randomStatus();

            $assignment->update([
                'status' => $status,
                'submitted_at' => $status === 'submitted' ? now()->subDays(rand(1, 20)) : null,
            ]);

            if ($status === 'pending') {
                continue;
            }

            $template = $assignment->reviewCycle->reviewTemplates->first();

            if (! $template) {
                continue;
            }

            $questions = $template->questions;

            // in_progress: only partially answered, to look realistic.
            $questionsToAnswer = $status === 'in_progress'
                ? $questions->random(min(max(1, intdiv($questions->count(), 2)), $questions->count()))
                : $questions;

            /** @var Collection $questionsToAnswer */
            foreach ($questionsToAnswer as $question) {
                ReviewResponse::create([
                    'review_assignment_id' => $assignment->id,
                    'question_id' => $question->id,
                    'answer_rating' => $question->type === 'rating_scale' ? rand(2, 6) : null,
                    'answer_text' => $question->type === 'text' ? $textAnswerBank[array_rand($textAnswerBank)] : null,
                ]);
            }
        }
    }

    private function randomStatus(): string
    {
        $roll = rand(1, 100);

        return match (true) {
            $roll <= 70 => 'submitted',
            $roll <= 85 => 'in_progress',
            default => 'pending',
        };
    }
}
