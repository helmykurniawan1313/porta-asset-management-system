<?php

namespace App\PerformanceReview\Http\Controllers;

use App\Http\Controllers\Controller;
use App\PerformanceReview\Http\Requests\ReorderQuestionsRequest;
use App\PerformanceReview\Http\Requests\StoreQuestionRequest;
use App\PerformanceReview\Http\Requests\UpdateQuestionRequest;
use App\PerformanceReview\Models\Question;
use App\PerformanceReview\Models\ReviewTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class QuestionController extends Controller
{
    public function store(StoreQuestionRequest $request, ReviewTemplate $reviewTemplate): RedirectResponse
    {
        $data = $request->validated();
        $data['review_template_id'] = $reviewTemplate->id;
        $data['order'] = $data['order'] ?? 0;

        Question::create($data);

        return Redirect::back()->with('status', 'question-created');
    }

    public function update(UpdateQuestionRequest $request, ReviewTemplate $reviewTemplate, Question $question): RedirectResponse
    {
        $data = $request->validated();

        $question->update($data);

        return Redirect::back()->with('status', 'question-updated');
    }

    public function destroy(ReviewTemplate $reviewTemplate, Question $question): RedirectResponse
    {
        $question->delete();

        return Redirect::back()->with('status', 'question-deleted');
    }

    public function reorder(ReorderQuestionsRequest $request, ReviewTemplate $reviewTemplate): RedirectResponse|JsonResponse
    {
        $questions = $request->validated()['questions'];

        DB::transaction(function () use ($questions, $reviewTemplate) {
            foreach ($questions as $item) {
                Question::where('id', $item['id'])
                    ->where('review_template_id', $reviewTemplate->id)
                    ->update(['order' => $item['order']]);
            }
        });

        return Redirect::back()->with('status', 'questions-reordered');
    }
}
