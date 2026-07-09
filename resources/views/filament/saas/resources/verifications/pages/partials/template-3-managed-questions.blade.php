@if (! empty($questions))
    <div class="uel2-managed-questions">
        @foreach ($questions as $question)
            @include('filament.saas.resources.verifications.pages.partials.template-3-managed-question-row', [
                'question' => $question,
                'templateThreeInput' => $templateThreeInput,
            ])

            @foreach (($question['children'] ?? []) as $childQuestion)
                @include('filament.saas.resources.verifications.pages.partials.template-3-managed-question-row', [
                    'question' => $childQuestion,
                    'templateThreeInput' => $templateThreeInput,
                ])
            @endforeach
        @endforeach
    </div>
@endif
