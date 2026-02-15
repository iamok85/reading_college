<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Child;
use App\Models\Contact;
use App\Models\EssaySubmission;
use App\Models\ReadingRecommendation;
use App\Models\EssaySong;
use App\Models\EssayAnalysis;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Support\Recaptcha;
use App\Neuron\Events\RetrieveReadingRecommendations;
use App\Neuron\Nodes\ReadingRecommendationsNode;
use App\Neuron\Events\RetrieveEssaySong;
use App\Neuron\Nodes\EssaySongNode;
use App\Neuron\Events\RetrieveEssayAnalysis;
use App\Neuron\Nodes\EssayAnalysisNode;
use NeuronAI\Workflow\WorkflowState;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/about', 'about')->name('about');
Route::view('/contact', 'contact')->name('contact');
Route::post('/contact', function (Illuminate\Http\Request $request) {
    $data = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'message' => ['required', 'string', 'max:5000'],
        'recaptcha_token' => ['required', 'string'],
    ]);

    if (! Recaptcha::verify($data['recaptcha_token'] ?? null, 'contact', $request->ip())) {
        return back()->withErrors([
            'recaptcha_token' => 'reCAPTCHA verification failed.',
        ])->withInput();
    }

    Contact::create($data);

    return back()->with('status', 'Thanks! We received your message.');
})->name('contact.store');
Route::get('/demo', function () {
    $demoUserId = session()->get('demo_user_id');
    $user = $demoUserId ? User::find($demoUserId) : null;

    if (! $user) {
        $user = User::create([
            'name' => config('reading_college.demo_user_name'),
            'email' => 'demo+' . Str::lower(Str::random(12)) . '@readingcollege.edu',
            'password' => Hash::make('demo1234'),
            'email_verified_at' => now(),
            'plan_type' => 'free',
            'free_trial_used_at' => now(),
            'free_trial_ends_at' => now()->addMonth(),
        ]);

        session()->put('demo_user_id', $user->id);
    }

    Auth::login($user);

    return redirect()->route('dashboard');
})->name('demo');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::view('/investor', 'investor')->name('investor');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    $getSelectedChildId = function (Illuminate\Http\Request $request): ?int {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $requestedChildId = (int) $request->query('child_id', 0);
        if ($requestedChildId && $user->children()->whereKey($requestedChildId)->exists()) {
            $request->session()->put('selected_child_id', $requestedChildId);
            return $requestedChildId;
        }

        $selected = (int) ($request->session()->get('selected_child_id') ?? 0);
        if ($selected && $user->children()->whereKey($selected)->exists()) {
            return $selected;
        }

        $firstChildId = $user->children()->orderBy('id')->value('id');
        if ($firstChildId) {
            $request->session()->put('selected_child_id', $firstChildId);
        }

        return $firstChildId;
    };

    Route::get('/dashboard', function (Illuminate\Http\Request $request) use ($getSelectedChildId) {
        $childId = $getSelectedChildId($request);

        return view('dashboard', [
            'selectedChildId' => $childId,
        ]);
    })->name('dashboard');

    Route::get('/previous-essays', function (Illuminate\Http\Request $request) use ($getSelectedChildId) {
        $childId = $getSelectedChildId($request);
        if ($request->filled('download')) {
            $essay = EssaySubmission::where('user_id', auth()->id())
                ->when($childId, fn ($query) => $query->where('child_id', $childId))
                ->where('id', $request->input('download'))
                ->first();

            if (!$essay) {
                abort(404);
            }

            $imagePaths = json_decode($essay->image_paths, true) ?: [];
            $imageData = [];
            foreach ($imagePaths as $path) {
                $fullPath = Storage::disk('public')->path($path);
                if (!is_file($fullPath)) {
                    continue;
                }
                $mime = mime_content_type($fullPath) ?: 'image/png';
                $imageData[] = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($fullPath));
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.chat-report', [
                'user' => auth()->user(),
                'submittedText' => null,
                'ocrText' => null,
                'responseText' => $essay->response_text,
                'images' => $imageData,
                'generatedAt' => now(),
            ])->setPaper('a4');

            $filename = 'essay-report-' . $essay->id . '.pdf';

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename);
        }

        $essays = EssaySubmission::where('user_id', auth()->id())
            ->when($childId, fn ($query) => $query->where('child_id', $childId))
            ->orderByDesc('uploaded_at')
            ->paginate(1)
            ->withQueryString();

        return view('previous-essays', [
            'essays' => $essays,
            'selectedChildId' => $childId,
        ]);
    })->name('previous-essays');

    Route::delete('/previous-essays', function (Illuminate\Http\Request $request) {
        $data = $request->validate([
            'essay_id' => ['required', 'integer'],
        ]);

        EssaySubmission::where('user_id', auth()->id())
            ->where('id', $data['essay_id'])
            ->delete();

        return redirect()->route('previous-essays');
    })->name('previous-essays.delete');

    Route::post('/child-profile', function (Illuminate\Http\Request $request) {
        $minYear = now()->year - 18;
        $maxYear = now()->year - 1;
        $data = $request->validate([
            'child_name' => ['required', 'string', 'max:255'],
            'child_birth_year' => ['required', 'integer', 'min:' . $minYear, 'max:' . $maxYear],
            'child_gender' => ['required', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $age = now()->year - (int) $data['child_birth_year'];
        $child = Child::create([
            'user_id' => $user->id,
            'name' => $data['child_name'],
            'age' => $age,
            'birth_year' => $data['child_birth_year'],
            'gender' => $data['child_gender'],
        ]);

        $request->session()->put('selected_child_id', $child->id);

        return back();
    })->name('child-profile.update');

    Route::post('/children', function (Illuminate\Http\Request $request) {
        $minYear = now()->year - 18;
        $maxYear = now()->year - 1;
        $data = $request->validate([
            'child_name' => ['required', 'string', 'max:255'],
            'child_birth_year' => ['required', 'integer', 'min:' . $minYear, 'max:' . $maxYear],
            'child_gender' => ['required', 'string', 'max:50'],
        ]);

        $age = now()->year - (int) $data['child_birth_year'];
        $child = Child::create([
            'user_id' => $request->user()->id,
            'name' => $data['child_name'],
            'age' => $age,
            'birth_year' => $data['child_birth_year'],
            'gender' => $data['child_gender'],
        ]);

        $request->session()->put('selected_child_id', $child->id);

        return back();
    })->name('children.store');

    Route::put('/children/{child}', function (Illuminate\Http\Request $request, Child $child) {
        $minYear = now()->year - 18;
        $maxYear = now()->year - 1;
        $data = $request->validate([
            'child_name' => ['required', 'string', 'max:255'],
            'child_birth_year' => ['required', 'integer', 'min:' . $minYear, 'max:' . $maxYear],
            'child_gender' => ['required', 'string', 'max:50'],
        ]);

        if ($child->user_id !== $request->user()->id) {
            abort(403);
        }

        $age = now()->year - (int) $data['child_birth_year'];
        $child->update([
            'name' => $data['child_name'],
            'age' => $age,
            'birth_year' => $data['child_birth_year'],
            'gender' => $data['child_gender'],
        ]);

        return back();
    })->name('children.update');

    Route::post('/children/select', function (Illuminate\Http\Request $request) {
        $data = $request->validate([
            'child_id' => ['required', 'integer'],
        ]);

        $childId = (int) $data['child_id'];
        $user = $request->user();
        if ($user && $user->children()->whereKey($childId)->exists()) {
            $request->session()->put('selected_child_id', $childId);
        }

        return back();
    })->name('children.select');

    Route::post('/profile/plan', function (Illuminate\Http\Request $request) {
        $data = $request->validate([
            'plan_type' => ['required', 'string', 'in:free,sliver,gold,premium'],
        ]);

        $request->user()->update([
            'plan_type' => $data['plan_type'],
        ]);

        return back();
    })->name('profile.plan.update');

    Route::get('/reading-recommendations', function (Illuminate\Http\Request $request) use ($getSelectedChildId) {
        $childId = $getSelectedChildId($request);
        $child = $childId
            ? Child::where('user_id', auth()->id())->whereKey($childId)->first()
            : null;
        $essays = EssaySubmission::where('user_id', auth()->id())
            ->when($childId, fn ($query) => $query->where('child_id', $childId))
            ->orderByDesc('uploaded_at')
            ->get(['ocr_text', 'response_text', 'uploaded_at']);

        $latestSubmissionAt = $essays->max('uploaded_at');
        $latestSubmissionKey = $latestSubmissionAt?->toDateTimeString();
        $essayCount = $essays->count();
        $cached = ReadingRecommendation::where('user_id', auth()->id())
            ->where('child_id', $childId)
            ->first();

        if ($request->boolean('download')) {
            $items = [];
            if ($cached) {
                $items = $cached->items ?? [];
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reading-recommendations', [
                'items' => $items,
                'generatedAt' => now(),
            ])->setPaper('a4');

            $filename = 'reading-recommendations-' . now()->format('YmdHis') . '.pdf';

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename);
        }

        if ($request->filled('download_item')) {
            $index = (int) $request->input('download_item');
            $items = [];
            if ($cached) {
                $items = $cached->items ?? [];
            }

            $item = $items[$index] ?? null;
            if (!$item) {
                abort(404);
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf/reading-recommendation-item', [
                'item' => $item,
                'generatedAt' => now(),
            ])->setPaper('a4');

            $filename = 'reading-recommendation-' . $index . '-' . now()->format('YmdHis') . '.pdf';

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename);
        }

        $cachedSubmissionKey = $cached?->last_submission_at?->toDateTimeString();
        if ($cached && $cached->essay_count === $essayCount && $cachedSubmissionKey === $latestSubmissionKey) {
            $items = $cached->items ?? [];
            return view('reading-recommendations', [
                'child' => $child,
                'essayCount' => $essayCount,
                'recommendations' => $items,
                'topics' => [],
                'ageBand' => null,
                'selectedChildId' => $childId,
            ]);
        }

        $recommendationLinks = [];
        $isRefreshing = false;
        if ($cached) {
            $recommendationLinks = $cached->items ?? [];
            $isRefreshing = !($cached->essay_count === $essayCount && $cachedSubmissionKey === $latestSubmissionKey);
        } elseif ($essayCount > 0) {
            $isRefreshing = true;
        }

        return view('reading-recommendations', [
            'child' => $child,
            'essayCount' => $essayCount,
            'recommendations' => $recommendationLinks,
            'topics' => [],
            'ageBand' => null,
            'isRefreshing' => $isRefreshing,
            'selectedChildId' => $childId,
        ]);
    })->name('reading-recommendations');

    Route::post('/reading-recommendations/refresh', function (Illuminate\Http\Request $request) use ($getSelectedChildId) {
        $childId = $getSelectedChildId($request);

        $essays = EssaySubmission::where('user_id', auth()->id())
            ->when($childId, fn ($query) => $query->where('child_id', $childId))
            ->orderByDesc('uploaded_at')
            ->get(['ocr_text', 'response_text', 'uploaded_at']);

        $text = $essays
            ->map(fn ($essay) => trim((string) ($essay->ocr_text ?: $essay->response_text)))
            ->filter()
            ->implode("\n\n");

        if (trim($text) === '') {
            return back()->withErrors([
                'readings' => 'No essay content available to refresh.',
            ]);
        }

        $wordCount = str_word_count(strip_tags($text));
        $targetWords = max(60, min(200, $wordCount ?: 80));

        $child = $childId
            ? Child::where('user_id', auth()->id())->whereKey($childId)->first()
            : null;

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
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($decoded) || !isset($decoded['items']) || !is_array($decoded['items'])) {
            return back()->withErrors([
                'readings' => 'Failed to refresh readings.',
            ]);
        }

        $recommendationLinks = array_map(function (array $item): array {
            return [
                'title' => (string) ($item['title'] ?? ''),
                'type' => (string) ($item['type'] ?? 'Book'),
                'paragraph' => (string) ($item['paragraph'] ?? ''),
            ];
        }, $decoded['items']);

        ReadingRecommendation::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'child_id' => $childId,
            ],
            [
                'essay_count' => $essays->count(),
                'last_submission_at' => $essays->max('uploaded_at'),
                'items' => $recommendationLinks,
            ]
        );

        return back();
    })->name('reading-recommendations.refresh');

    Route::get('/analysis', function (Illuminate\Http\Request $request) use ($getSelectedChildId) {
        $childId = $getSelectedChildId($request);

        $essays = EssaySubmission::where('user_id', auth()->id())
            ->when($childId, fn ($query) => $query->where('child_id', $childId))
            ->orderByDesc('uploaded_at')
            ->limit(5)
            ->get(['uploaded_at']);

        $latestSubmissionAt = $essays->max('uploaded_at');
        $latestSubmissionKey = $latestSubmissionAt?->toDateTimeString();
        $essayCount = $essays->count();

        $cached = EssayAnalysis::where('user_id', auth()->id())
            ->where('child_id', $childId)
            ->first();

        $analysis = $cached?->analysis_text;
        $isRefreshing = false;

        if ($essayCount > 0) {
            if (! $cached) {
                $isRefreshing = true;
            } else {
                $cachedSubmissionKey = $cached->last_submission_at?->toDateTimeString();
                $isRefreshing = $cached->essay_count !== $essayCount || $cachedSubmissionKey !== $latestSubmissionKey;
            }
        }

        if ($request->boolean('download')) {
            if (! $analysis) {
                return back()->withErrors([
                    'analysis' => 'No analysis available to download yet.',
                ]);
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf/analysis', [
                'analysis' => $analysis,
                'generatedAt' => now(),
            ])->setPaper('a4');

            $filename = 'analysis-' . now()->format('YmdHis') . '.pdf';

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename);
        }

        return view('analysis', [
            'analysis' => $analysis,
            'error' => null,
            'essayCount' => $essayCount,
            'isRefreshing' => $isRefreshing,
            'selectedChildId' => $childId,
        ]);
    })->name('analysis');

    Route::post('/analysis/refresh', function (Illuminate\Http\Request $request) use ($getSelectedChildId) {
        $childId = $getSelectedChildId($request);

        $essays = EssaySubmission::where('user_id', auth()->id())
            ->when($childId, fn ($query) => $query->where('child_id', $childId))
            ->orderByDesc('uploaded_at')
            ->limit(5)
            ->get(['ocr_text', 'response_text', 'uploaded_at']);

        $text = $essays
            ->map(fn ($essay) => trim((string) ($essay->ocr_text ?: $essay->response_text)))
            ->filter()
            ->implode("\n\n");

        if (trim($text) === '') {
            return back()->withErrors([
                'analysis' => 'No essay content available to refresh.',
            ]);
        }

        $event = new RetrieveEssayAnalysis(
            essayText: $text,
            essayCount: $essays->count()
        );
        $state = new WorkflowState();
        (new EssayAnalysisNode())($event, $state);

        $analysis = $state->get('essay_analysis');
        if (!is_string($analysis) || trim($analysis) === '') {
            return back()->withErrors([
                'analysis' => 'Failed to refresh analysis.',
            ]);
        }

        EssayAnalysis::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'child_id' => $childId,
            ],
            [
                'essay_count' => $essays->count(),
                'last_submission_at' => $essays->max('uploaded_at'),
                'analysis_text' => $analysis,
            ]
        );

        return back();
    })->name('analysis.refresh');

    Route::view('/billing', 'billing')->name('billing');
    Route::get('/songs', function (Illuminate\Http\Request $request) use ($getSelectedChildId) {
        $childId = $getSelectedChildId($request);

        $essays = EssaySubmission::where('user_id', auth()->id())
            ->when($childId, fn ($query) => $query->where('child_id', $childId))
            ->orderByDesc('uploaded_at')
            ->get();

        $songs = EssaySong::where('user_id', auth()->id())
            ->when($childId, fn ($query) => $query->where('child_id', $childId))
            ->get()
            ->keyBy('essay_submission_id');

        return view('songs', [
            'essays' => $essays,
            'songs' => $songs,
            'selectedChildId' => $childId,
        ]);
    })->name('songs');

    Route::post('/essay-songs/{essay}', function (Illuminate\Http\Request $request, int $essay) {
        $essayRecord = EssaySubmission::where('id', $essay)
            ->where('user_id', auth()->id())
            ->first();

        if (! $essayRecord) {
            abort(404);
        }

        $lyrics = trim((string) ($essayRecord->response_text ?: $essayRecord->ocr_text));
        if ($lyrics === '') {
            return back()->withErrors([
                'song' => 'No essay text available to generate a song.',
            ]);
        }

        $existing = EssaySong::where('essay_submission_id', $essayRecord->id)->first();

        if ($existing && $existing->status === 'ready') {
            return back();
        }

        $now = now();
        if (! $existing) {
            EssaySong::create([
                'essay_submission_id' => $essayRecord->id,
                'user_id' => auth()->id(),
                'child_id' => $essayRecord->child_id ?? null,
                'status' => 'pending',
                'provider' => 'suno',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $existing->update([
                'status' => 'pending',
                'error_message' => null,
                'updated_at' => $now,
            ]);
        }

        try {
            $event = new RetrieveEssaySong(
                essayId: (int) $essayRecord->id,
                title: 'Essay Song #' . $essayRecord->id,
                lyrics: $lyrics,
            );
            $state = new WorkflowState();
            (new EssaySongNode())($event, $state);

            $payload = $state->get('song_payload') ?? [];
            $audioUrl = $payload['audio_url'] ?? null;

            if (! $audioUrl) {
                throw new \RuntimeException('Suno response missing audio URL.');
            }

            $audioResponse = Http::timeout(60)->get($audioUrl);
            if (! $audioResponse->successful()) {
                throw new \RuntimeException('Failed to download song audio.');
            }

            $filename = 'songs/essay-' . $essayRecord->id . '-' . Str::random(6) . '.mp3';
            Storage::disk('public')->put($filename, $audioResponse->body());

            $songName = $payload['title'] ?? ('Essay Song #' . $essayRecord->id);

            EssaySong::where('essay_submission_id', $essayRecord->id)
                ->update([
                    'status' => 'ready',
                    'song_name' => $songName,
                    'song_path' => $filename,
                    'provider_song_id' => (string) ($payload['provider_song_id'] ?? ''),
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $exception) {
            EssaySong::where('essay_submission_id', $essayRecord->id)
                ->update([
                    'status' => 'failed',
                    'error_message' => $exception->getMessage(),
                    'updated_at' => now(),
                ]);

            return back()->withErrors([
                'song' => 'Song generation failed. Please try again.',
            ]);
        }

        return back();
    })->name('songs.generate');

});
