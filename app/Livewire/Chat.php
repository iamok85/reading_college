<?php

namespace App\Livewire;

use App\Neuron\Events\RetrieveEssayCorrection;
use App\Neuron\Events\RetrieveEssayAnalysis;
use App\Neuron\Events\RetrieveEssayImages;
use App\Neuron\Events\RetrieveEssayVideo;
use App\Neuron\Events\RetrieveImageOcr;
use App\Neuron\Events\RetrievePdfOcr;
use App\Neuron\Events\RetrieveReadingRecommendations;
use App\Neuron\Nodes\EssayCorrectionNode;
use App\Neuron\Nodes\EssayAnalysisNode;
use App\Neuron\Nodes\EssayImageNode;
use App\Neuron\Nodes\EssayVideoNode;
use App\Services\CreditService;
use App\Neuron\Workflows\ReadingRecommendationPipeline;
use App\Neuron\Nodes\EssayPipelineStartNode;
use App\Neuron\Nodes\ImageOcrNode;
use App\Neuron\Nodes\PdfOcrNode;
use App\Neuron\Nodes\ReadingRecommendationsNode;
use App\Models\EssayAnalysis;
use App\Models\EssaySubmission;
use App\Models\ReadingRecommendation;
use App\Models\SharedEssay;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use NeuronAI\Workflow\WorkflowState;
use NeuronAI\Workflow\StartEvent;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Chat extends Component
{
    use WithFileUploads;

    public string $input = '';

    public array $images = [];
    public array $queuedFiles = [];
    public array $pdfs = [];
    public array $pdfPaths = [];

    public array $messages = [];

    public bool $thinking = false;

    public ?string $lastResponse = null;
    public ?string $lastSubmittedText = null;

    public ?string $ocrPreview = null;
    public array $ocrImagePaths = [];
    public bool $ocrLoading = false;

    public ?string $ocrTextPanel = null;
    public ?string $correctionTextPanel = null;
    public ?string $analysisTextPanel = null;
    public ?string $analysisEssayText = null;
    public bool $showProgressPanels = false;
    public ?int $lastEssaySubmissionId = null;
    public array $generatedImagePaths = [];
    public bool $showOcrPanel = false;
    public bool $isLastEssayShared = false;
    public ?string $generatedVideoPath = null;
    public ?string $generatedVideoStatus = null;
    public ?int $generatedVideoProgress = null;
    public ?string $generatedVideoError = null;
    public ?string $generatedVideoUrl = null;

    public function render(): View
    {
        return view('livewire.chat');
    }

    public function updatedQueuedFiles(): void
    {
        if (empty($this->queuedFiles)) {
            return;
        }

        $this->validate([
            'queuedFiles' => ['array', 'max:5'],
            'queuedFiles.*' => ['file', 'max:20480', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,image/heic,image/heif,application/pdf'],
        ], [
            'queuedFiles.*.max' => 'Each attachment must be 20 MB or smaller.',
            'queuedFiles.*.mimetypes' => 'Attachments must be images or PDFs.',
        ]);

        foreach ($this->queuedFiles as $file) {
            $extension = strtolower($file->getClientOriginalExtension() ?? '');
            $mime = $file->getMimeType() ?? '';

            if ($extension === 'pdf' || $mime === 'application/pdf') {
                $this->pdfs[] = $file;
            } else {
                $this->images[] = $file;
            }
        }

        $this->queuedFiles = [];

        $this->resetErrorBag();
        $this->ocrPreview = null;
        $this->ocrImagePaths = [];
        $this->pdfPaths = [];
        $this->input = '';
        $this->ocrLoading = false;
        $this->messages = [];
        $this->thinking = false;
        $this->generatedImagePaths = [];
        $this->showOcrPanel = false;
        $this->generatedVideoPath = null;
        $this->generatedVideoStatus = null;
        $this->generatedVideoProgress = null;
        $this->generatedVideoError = null;
        $this->generatedVideoUrl = null;

        $user = auth()->user();
        $username = $user?->name ?? 'user';
        $safeName = Str::slug($username) ?: 'user';
        $demoEmail = config('reading_college.demo_user_email');
        $email = $user?->email ?? '';
        $isDemoUser = $email === $demoEmail || Str::startsWith($email, 'demo+');
        $baseDirectory = $isDemoUser ? 'demo-uploads/' . $user->id : 'ocr/' . $safeName;

        foreach ($this->images as $image) {
            $extension = $image->getClientOriginalExtension() ?: 'png';
            $filename = now()->format('YmdHis') . '-' . Str::random(6) . '.' . $extension;
            $this->ocrImagePaths[] = $image->storeAs($baseDirectory, $filename, 'public');
        }

        foreach ($this->pdfs as $pdf) {
            $filename = now()->format('YmdHis') . '-' . Str::random(6) . '.pdf';
            $storedPath = $pdf->storeAs($baseDirectory, $filename, 'public');
            $this->pdfPaths[] = $storedPath;
        }
    }

    public function chat(): void
    {
        $this->resetErrorBag();
        $this->messages = [];
        $this->lastResponse = null;
        $this->ocrTextPanel = null;
        $this->correctionTextPanel = null;
        $this->analysisTextPanel = null;
        $this->analysisEssayText = null;
        $this->lastEssaySubmissionId = null;
        $this->generatedImagePaths = [];
        $this->showProgressPanels = true;
        $this->showOcrPanel = false;
        $this->isLastEssayShared = false;
        $this->generatedVideoPath = null;
        $this->generatedVideoStatus = null;
        $this->generatedVideoProgress = null;
        $this->generatedVideoError = null;
        $this->generatedVideoUrl = null;

        $this->validate([
            'input' => ['required_without_all:images,pdfs', 'string', 'max:5000'],
            'images' => ['array'],
            'images.*' => ['file', 'max:20480', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,image/heic,image/heif'],
            'pdfs' => ['array'],
            'pdfs.*' => ['file', 'max:20480', 'mimes:pdf'],
        ], [
            'images.*.max' => 'Each image must be 20 MB or smaller.',
            'images.*.mimetypes' => 'Images must be JPG, PNG, WEBP, GIF, HEIC, or HEIF.',
            'pdfs.*.max' => 'Each PDF must be 20 MB or smaller.',
            'pdfs.*.mimes' => 'PDFs must be a valid PDF file.',
        ]);

        if (!empty($this->images) || !empty($this->pdfs)) {
            $this->ocrLoading = true;
            $this->showOcrPanel = true;
        }

        $composedInput = trim($this->input);

        $user = auth()->user();
        if ($user && $this->isDemoUser($user->email ?? '')) {
            $submissionCount = EssaySubmission::where('user_id', $user->id)->count();

            if ($submissionCount >= 2) {
                $this->addError('input', 'Demo users can submit up to 2 essays.');
                $this->thinking = false;
                return;
            }
        }

        if ($user) {
            $credits = new CreditService();
            if (! $credits->charge($user, CreditService::COST_CORRECTION_ANALYSIS)) {
                $this->addError('credits', 'Not enough credits to submit an essay (requires 5).');
                $this->thinking = false;
                return;
            }
        }

        $this->thinking = true;

        $this->dispatch('scroll-bottom');

        if ($this->showOcrPanel) {
            $this->dispatch('getEssayOcrResponse', $composedInput);
        } else {
            $this->dispatch('getEssayCorrectionResponse', $composedInput);
        }
        $this->input = '';
    }

    private function isDemoUser(string $email): bool
    {
        $demoEmail = config('reading_college.demo_user_email');
        return $email === $demoEmail || Str::startsWith(Str::lower($email), 'demo+');
    }

    public function clearAttachments(): void
    {
        $this->images = [];
        $this->queuedFiles = [];
        $this->pdfs = [];
        $this->ocrPreview = null;
        $this->ocrImagePaths = [];
        $this->pdfPaths = [];
        $this->input = '';
        $this->ocrLoading = false;
        $this->showProgressPanels = false;
        $this->generatedImagePaths = [];
        $this->showOcrPanel = false;
        $this->generatedVideoPath = null;
        $this->generatedVideoStatus = null;
        $this->generatedVideoProgress = null;
        $this->generatedVideoError = null;
        $this->generatedVideoUrl = null;
    }

    public function clearInput(): void
    {
        $this->input = '';
        $this->resetErrorBag('input');
    }

    #[On('getEssayOcrResponse')]
    public function getEssayOcrResponse($input): void
    {
        try {
            $composedInput = trim((string) $input);

            if (!empty($this->ocrImagePaths) || !empty($this->pdfPaths)) {
                $state = new WorkflowState([
                    'input_text' => $composedInput,
                    'image_paths' => $this->ocrImagePaths,
                    'pdf_paths' => $this->pdfPaths,
                ]);

                $event = (new EssayPipelineStartNode())(new StartEvent(), $state);
                while ($event instanceof RetrieveImageOcr || $event instanceof RetrievePdfOcr) {
                    if ($event instanceof RetrieveImageOcr) {
                        $event = (new ImageOcrNode())($event, $state);
                        continue;
                    }
                    if ($event instanceof RetrievePdfOcr) {
                        $event = (new PdfOcrNode())($event, $state);
                        continue;
                    }
                }

                $ocrText = $state->get('ocr_text');
                if (!is_string($ocrText) || trim($ocrText) === '') {
                    $this->addError('queuedFiles', 'Unable to extract text from the attachment(s).');
                    $this->thinking = false;
                    $this->ocrLoading = false;
                    return;
                }

                $this->ocrPreview = trim($ocrText);
                $this->ocrTextPanel = $this->ocrPreview;
                $composedInput = $this->ocrPreview;
            }

            $this->dispatch('getEssayCorrectionResponse', $composedInput);
        } catch (\Throwable $exception) {
            $this->messages[] = [
                'who' => 'ai',
                'content' => 'Something went wrong while generating the OCR.',
            ];
            $this->thinking = false;
            $this->ocrLoading = false;
        }
    }

    #[On('getEssayCorrectionResponse')]
    public function getEssayCorrectionResponse($input): void
    {
        try {
            $childId = (int) session('selected_child_id', 0);
            if (!$childId) {
                $childId = (int) auth()->user()?->children()->orderBy('id')->value('id');
                if ($childId) {
                    session(['selected_child_id' => $childId]);
                }
            }

            $composedInput = trim((string) $input);

            $state = new WorkflowState([
                'pipeline_mode' => true,
                'user_id' => (int) (auth()->id() ?? 0),
                'child_id' => $childId,
                'image_paths' => $this->ocrImagePaths,
                'pdf_paths' => $this->pdfPaths,
                'ocr_text' => $this->ocrPreview,
                'analysis_text' => $composedInput,
                'essay_count' => 1,
            ]);
            (new EssayCorrectionNode())(new RetrieveEssayCorrection($composedInput), $state);
            $response = $state->get('essay_correction');
            $this->lastEssaySubmissionId = $state->get('essay_submission_id');
            $this->isLastEssayShared = $this->lastEssaySubmissionId
                ? SharedEssay::where('essay_submission_id', $this->lastEssaySubmissionId)->exists()
                : false;

            if (!is_string($response) || trim($response) === '') {
                throw new \RuntimeException('Essay correction response is empty.');
            }

            $this->messages[] = [
                'who' => 'user',
                'content' => $composedInput,
            ];
            $this->lastSubmittedText = $composedInput;

            $this->messages[] = [
                'who' => 'ai',
                'content' => $response,
            ];
            $this->lastResponse = $response;
            $this->correctionTextPanel = $response;
            $this->thinking = false;
            $this->ocrLoading = false;
            $this->dispatch('scroll-bottom');

            $this->analysisEssayText = $composedInput;
            $parts = EssayCorrectionNode::parseEssayCorrection((string) $response);
            $correctedEssay = $parts['corrected_version'] ?: $composedInput;
            $this->dispatch('essay:after-correction', [
                'essayId' => $this->lastEssaySubmissionId,
                'correctedEssay' => $correctedEssay,
            ]);
        } catch (\Throwable $exception) {
            $this->messages[] = [
                'who' => 'ai',
                'content' => 'Something went wrong while generating the response.',
            ];
            $this->lastResponse = null;
            $this->thinking = false;
            $this->ocrLoading = false;
        }
    }

    #[On('getEssayAnalysisResponse')]
    public function getEssayAnalysisResponse(): void
    {
        try {
            $childId = (int) session('selected_child_id', 0);
            if (!$childId) {
                $childId = (int) auth()->user()?->children()->orderBy('id')->value('id');
                if ($childId) {
                    session(['selected_child_id' => $childId]);
                }
            }

            $analysisText = trim((string) ($this->analysisEssayText ?? $this->lastSubmittedText ?? ''));
            if ($analysisText === '') {
                return;
            }

            $state = new WorkflowState([
                'pipeline_mode' => false,
                'user_id' => (int) (auth()->id() ?? 0),
                'child_id' => $childId,
                'essay_count' => 1,
                'essay_submission_id' => $this->lastEssaySubmissionId,
            ]);
            $event = new RetrieveEssayAnalysis($analysisText, 1);
            (new EssayAnalysisNode())($event, $state);

            $analysis = $state->get('essay_analysis');
            if (is_string($analysis) && trim($analysis) !== '') {
                $this->analysisTextPanel = $analysis;
            }
        } catch (\Throwable $exception) {
            $this->analysisTextPanel = null;
        }
    }

    #[On('getEssayImagesResponse')]
    public function getEssayImagesResponse(int $essayId, string $correctedEssay): void
    {
        try {
            $user = auth()->user();
            if ($user) {
                $credits = new CreditService();
                if (! $credits->charge($user, CreditService::COST_IMAGES)) {
                    $this->addError('credits', 'Not enough credits to generate images (requires 10).');
                    return;
                }
            }
            $imageState = new WorkflowState([
                'pipeline_mode' => false,
            ]);
            (new EssayImageNode())(
                new RetrieveEssayImages($essayId, $correctedEssay),
                $imageState
            );
            $submission = EssaySubmission::find($essayId);
            $this->generatedImagePaths = $submission?->generated_image_paths ?? [];
            $this->dispatch('analysis:after-images');
        } catch (\Throwable $exception) {
            // Keep UI responsive even if image generation fails.
        }
    }

    #[On('getEssayVideoResponse')]
    public function getEssayVideoResponse(int $essayId, string $correctedEssay): void
    {
        try {
            $user = auth()->user();
            if ($user) {
                $credits = new CreditService();
                if (! $credits->charge($user, CreditService::COST_VIDEO)) {
                    $this->addError('credits', 'Not enough credits to generate a video (requires 20).');
                    return;
                }
            }
            $videoState = new WorkflowState([
                'pipeline_mode' => false,
            ]);
            (new EssayVideoNode())(
                new RetrieveEssayVideo($essayId, $correctedEssay),
                $videoState
            );
            $submission = EssaySubmission::find($essayId);
            $this->generatedVideoPath = $submission?->generated_video_path;
            $this->generatedVideoStatus = $submission?->video_status;
            $this->generatedVideoProgress = $submission?->video_progress;
            $this->generatedVideoError = $submission?->video_error;
            $this->generatedVideoUrl = $submission?->video_url;
        } catch (\Throwable $exception) {
            // Keep UI responsive even if video generation fails.
        }
    }

    public function refreshVideoStatus(): void
    {
        if (!$this->lastEssaySubmissionId) {
            return;
        }

        $submission = EssaySubmission::find($this->lastEssaySubmissionId);
        $this->generatedVideoPath = $submission?->generated_video_path;
        $this->generatedVideoStatus = $submission?->video_status;
        $this->generatedVideoProgress = $submission?->video_progress;
        $this->generatedVideoError = $submission?->video_error;
        $this->generatedVideoUrl = $submission?->video_url;
    }

    public function shareLastEssay(): void
    {
        $essayId = $this->lastEssaySubmissionId;
        if (!$essayId) {
            return;
        }

        $essay = EssaySubmission::where('user_id', auth()->id())
            ->where('id', $essayId)
            ->first();

        if (!$essay) {
            return;
        }

        $child = $essay->child;
        $correctedText = $essay->corrected_version ?: $essay->response_text;
        $imagePath = null;
        $generatedPaths = $essay->generated_image_paths ?? [];
        if (is_string($generatedPaths)) {
            $generatedPaths = json_decode($generatedPaths, true) ?: [];
        }
        if (is_array($generatedPaths) && !empty($generatedPaths)) {
            $imagePath = $generatedPaths[0];
        }

        SharedEssay::updateOrCreate(
            ['essay_submission_id' => $essay->id],
            [
                'user_id' => auth()->id(),
                'child_id' => $child?->id,
                'child_name' => $child?->name,
                'child_age' => $child?->age,
                'corrected_text' => $correctedText,
                'image_path' => $imagePath,
                'shared_at' => now(),
            ]
        );

        $this->isLastEssayShared = true;
    }

    public function unshareLastEssay(): void
    {
        $essayId = $this->lastEssaySubmissionId;
        if (!$essayId) {
            return;
        }

        SharedEssay::where('essay_submission_id', $essayId)
            ->where('user_id', auth()->id())
            ->delete();

        $this->isLastEssayShared = false;
    }

    public function downloadPdf(): ?StreamedResponse
    {
        if (!$this->lastResponse) {
            return null;
        }

        $imageData = [];
        foreach ($this->ocrImagePaths as $path) {
            $fullPath = Storage::disk('public')->path($path);
            if (!is_file($fullPath)) {
                continue;
            }
            $mime = mime_content_type($fullPath) ?: 'image/png';
            $imageData[] = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($fullPath));
        }

        $pdf = Pdf::loadView('pdf.chat-report', [
            'user' => auth()->user(),
            'submittedText' => $this->lastSubmittedText,
            'ocrText' => $this->ocrPreview,
            'responseText' => $this->lastResponse,
            'analysisText' => $this->analysisTextPanel,
            'images' => $imageData,
            'generatedAt' => now(),
        ])->setPaper('a4');

        $filename = 'essay-report-' . now()->format('YmdHis') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    private function refreshReadingRecommendationsCache(?int $childId): void
    {
        $userId = auth()->id();
        if (!$userId || !$childId) {
            return;
        }

        $user = auth()->user();
        if ($user) {
            $credits = new CreditService();
            if (! $credits->charge($user, CreditService::COST_READING_RECOMMENDATIONS)) {
                return;
            }
        }

        $essays = EssaySubmission::where('user_id', $userId)
            ->where('child_id', $childId)
            ->orderByDesc('uploaded_at')
            ->limit(5)
            ->get(['ocr_text', 'response_text', 'uploaded_at']);

        $text = $essays
            ->map(fn ($essay) => trim((string) ($essay->ocr_text ?: $essay->response_text)))
            ->filter()
            ->implode("\n\n");

        if (trim($text) === '') {
            return;
        }

        $wordCount = str_word_count(strip_tags($text));
        $targetWords = max(60, min(200, $wordCount ?: 80));

        $child = auth()->user()?->children()->whereKey($childId)->first();
        $childAge = null;
        if ($child?->birth_year) {
            $childAge = now()->year - $child->birth_year;
        } elseif (property_exists($child, 'age') && $child?->age) {
            $childAge = (int) $child->age;
        }

        $event = new RetrieveReadingRecommendations(
            essayText: $text,
            targetWords: $targetWords,
            childName: $child?->name,
            childAge: $childAge,
            childGender: $child?->gender
        );

        $state = new WorkflowState();
        (new ReadingRecommendationsNode())($event, $state);

        $raw = $state->get('reading_recommendations');
        if (!is_string($raw)) {
            return;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['items']) || !is_array($decoded['items'])) {
            return;
        }

        $recommendationLinks = array_map(function (array $item): array {
            return [
                'title' => (string) ($item['title'] ?? ''),
                'type' => (string) ($item['type'] ?? 'Book'),
                'paragraph' => (string) ($item['paragraph'] ?? ''),
            ];
        }, $decoded['items']);

        $recommendationLinks = array_slice($recommendationLinks, 0, 1);

        if (empty($recommendationLinks)) {
            return;
        }

        $latestSubmissionAt = $essays->max('uploaded_at');
        $essayCount = $essays->count();

        ReadingRecommendation::updateOrCreate(
            [
                'user_id' => $userId,
                'child_id' => $childId,
            ],
            [
                'essay_count' => $essayCount,
                'last_submission_at' => $latestSubmissionAt,
                'items' => $recommendationLinks,
            ]
        );

        $recommendation = ReadingRecommendation::where('user_id', $userId)
            ->where('child_id', $childId)
            ->first();

        if ($recommendation) {
            $pipeline = new ReadingRecommendationPipeline($recommendation->id, $recommendationLinks);
            foreach ($pipeline->run() as $event) {
                // Drain generator
            }
        }
    }

    private function refreshEssayAnalysisCache(?int $childId): void
    {
        $userId = auth()->id();
        if (!$userId || !$childId) {
            return;
        }

        $essays = EssaySubmission::where('user_id', $userId)
            ->where('child_id', $childId)
            ->orderByDesc('uploaded_at')
            ->limit(5)
            ->get(['ocr_text', 'response_text', 'uploaded_at']);

        if ($essays->isEmpty()) {
            return;
        }

        $text = $essays
            ->map(fn ($essay) => trim((string) ($essay->ocr_text ?: $essay->response_text)))
            ->filter()
            ->implode("\n\n");

        if (trim($text) === '') {
            return;
        }

        $event = new RetrieveEssayAnalysis(
            essayText: $text,
            essayCount: $essays->count()
        );

        $state = new WorkflowState();
        (new EssayAnalysisNode())($event, $state);

        $analysis = $state->get('essay_analysis');
        if (!is_string($analysis) || trim($analysis) === '') {
            return;
        }

        $latestSubmissionAt = $essays->max('uploaded_at');
        $essayCount = $essays->count();

        EssayAnalysis::updateOrCreate(
            [
                'user_id' => $userId,
                'child_id' => $childId,
            ],
            [
                'essay_count' => $essayCount,
                'last_submission_at' => $latestSubmissionAt,
                'analysis_text' => $analysis,
            ]
        );
    }
}
