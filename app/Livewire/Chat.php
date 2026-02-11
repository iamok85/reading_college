<?php

namespace App\Livewire;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Neuron\Agents\EssayCorrectionAgent;
use App\Neuron\Agents\ImageOcrAgent;
use App\Neuron\Agents\PdfOcrAgent;
use NeuronAI\Chat\Attachments\Document;
use NeuronAI\Chat\Attachments\Image;
use NeuronAI\Chat\Enums\AttachmentContentType;
use NeuronAI\Chat\Messages\UserMessage;
use Illuminate\Support\Facades\DB;

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

        if ((!empty($this->images) || !empty($this->pdfs)) && $this->ocrPreview === null) {
            $this->ocrLoading = true;
            $combinedText = [];

            foreach ($this->ocrImagePaths as $path) {
                $fullPath = Storage::disk('public')->path($path);
                $text = $this->runImageOcr($fullPath);
                if ($text) {
                    $combinedText[] = $text;
                }
            }

            foreach ($this->pdfPaths as $path) {
                $fullPath = Storage::disk('public')->path($path);
                $text = $this->runPdfOcr($fullPath);
                if ($text) {
                    $combinedText[] = $text;
                }
            }

            $this->ocrPreview = trim(implode("\n\n", $combinedText));
            $this->ocrLoading = false;

            if ($this->ocrPreview === '') {
                $this->ocrPreview = null;
                $this->addError('queuedFiles', 'Unable to extract text from the attachment(s).');
                return;
            }

            // Keep user input unchanged; do not auto-fill OCR text into input.
        }

        $composedInput = trim($this->input);
        if (!empty($this->images) || !empty($this->pdfs)) {
            $composedInput = trim((string) $this->ocrPreview);
        }

        $user = auth()->user();
        if ($user && $this->isDemoUser($user->email ?? '')) {
            $submissionCount = DB::table('essay_submissions')
                ->where('user_id', $user->id)
                ->count();

            if ($submissionCount >= 2) {
                $this->addError('input', 'Demo users can submit up to 2 essays.');
                $this->thinking = false;
                return;
            }
        }

        $this->messages[] = [
            'who' => 'user',
            'content' => $composedInput,
        ];
        $this->lastSubmittedText = $composedInput;

        $this->thinking = true;

        $this->dispatch('scroll-bottom');

        $this->dispatch('getEssayCorrectionResponse', $composedInput);
        $this->input = '';
        $this->ocrLoading = false;
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
    }

    public function clearInput(): void
    {
        $this->input = '';
        $this->resetErrorBag('input');
    }

    #[On('getEssayCorrectionResponse')]
    public function getEssayCorrectionResponse($input): void
    {
        try {
            $this->messages[] = [
                'who' => 'ai',
                'content' => $response = EssayCorrectionAgent::make()
                    ->chat(new UserMessage($input))
                    ->getContent(),
            ];
            $this->lastResponse = $response;
            $this->thinking = false;
            $this->dispatch('scroll-bottom');

            $childId = (int) session('selected_child_id', 0);
            if (!$childId) {
                $childId = (int) auth()->user()?->children()->orderBy('id')->value('id');
                if ($childId) {
                    session(['selected_child_id' => $childId]);
                }
            }

            DB::table('essay_submissions')->insert([
                'user_id' => auth()->id(),
                'child_id' => $childId ?: null,
                'image_paths' => json_encode(array_values(array_merge($this->ocrImagePaths, $this->pdfPaths))),
                'uploaded_at' => now(),
                'ocr_text' => $this->ocrPreview ?? $this->lastSubmittedText,
                'response_text' => $response,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $this->messages[] = [
                'who' => 'ai',
                'content' => 'Something went wrong while generating the response.',
            ];
            $this->lastResponse = null;
            $this->thinking = false;
        }
    }

    public function downloadPdf()
    {
        if (!$this->lastResponse) {
            return;
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
            'images' => $imageData,
            'generatedAt' => now(),
        ])->setPaper('a4');

        $filename = 'essay-report-' . now()->format('YmdHis') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    private function runImageOcr(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';
        $base64 = base64_encode((string) file_get_contents($path));
        $message = new UserMessage('Extract all text from this image. Return only the text, preserving line breaks.');
        $message->addAttachment(new Image($base64, AttachmentContentType::BASE64, $mime));

        $response = ImageOcrAgent::make()->chat($message);
        $text = trim((string) $response->getContent());

        return $text !== '' ? $text : null;
    }

    private function runPdfOcr(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $base64 = base64_encode((string) file_get_contents($path));
        $message = new UserMessage('Extract all text from this PDF. Return only the text, preserving line breaks.');
        $message->addAttachment(new Document($base64, AttachmentContentType::BASE64, 'application/pdf'));

        $response = PdfOcrAgent::make()->chat($message);
        $text = trim((string) $response->getContent());

        return $text !== '' ? $text : null;
    }

}
