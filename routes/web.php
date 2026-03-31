<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\EssayMagazine;
use App\Jobs\ProcessReadingRecommendationsJob;
use App\Models\User;
use App\Models\Child;
use App\Models\Contact;
use App\Models\EssaySubmission;
use App\Models\ReadingRecommendation;
use App\Models\EssayAnalysis;
use App\Models\SharedEssay;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Support\Recaptcha;
use App\Neuron\Events\RetrieveReadingRecommendations;
use App\Neuron\Nodes\ReadingRecommendationsNode;
use App\Neuron\Events\RetrieveEssayAnalysis;
use App\Neuron\Nodes\EssayAnalysisNode;
use App\Neuron\Events\RetrieveEssayImages;
use App\Neuron\Nodes\EssayImageNode;
use App\Services\CreditService;
use App\Neuron\Workflows\ReadingRecommendationPipeline;
use NeuronAI\Workflow\WorkflowState;

Route::get('/', function () {
    $items = SharedEssay::query()
        ->orderByDesc('shared_at')
        ->limit(6)
        ->get();

    return view('welcome', [
        'items' => $items,
    ]);
});

Route::view('/about', 'about')->name('about');
Route::view('/plans', 'plans')->name('plans');
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
Route::get('/feeds/{sharedEssay}', function (SharedEssay $sharedEssay) {
    return view('feeds', [
        'items' => collect([$sharedEssay]),
        'shareItem' => $sharedEssay,
    ]);
})->whereNumber('sharedEssay')->name('feeds.show');

Route::get('/feeds', function (Illuminate\Http\Request $request) {
    $query = SharedEssay::query()->orderByDesc('shared_at');

    if ($request->filled('child_name')) {
        $name = trim((string) $request->input('child_name'));
        if ($name !== '') {
            $query->where('child_name', 'like', '%' . $name . '%');
        }
    }

    if ($request->filled('date_from')) {
        $query->whereDate('shared_at', '>=', $request->input('date_from'));
    }

    if ($request->filled('date_to')) {
        $query->whereDate('shared_at', '<=', $request->input('date_to'));
    }

    $items = $query->paginate(20)->withQueryString();

    return view('feeds', [
        'items' => $items,
        'filters' => $request->only(['child_name', 'date_from', 'date_to']),
    ]);
})->name('feeds');
Route::get('/feeds/magazine', function (Illuminate\Http\Request $request) {
    $query = SharedEssay::query()->orderByDesc('shared_at');

    if ($request->filled('child_name')) {
        $name = trim((string) $request->input('child_name'));
        if ($name !== '') {
            $query->where('child_name', 'like', '%' . $name . '%');
        }
    }

    if ($request->filled('date_from')) {
        $query->whereDate('shared_at', '>=', $request->input('date_from'));
    }

    if ($request->filled('date_to')) {
        $query->whereDate('shared_at', '<=', $request->input('date_to'));
    }

    $items = $query->paginate(20)->withQueryString();

    return view('feeds-magazine', [
        'items' => $items,
        'filters' => $request->only(['child_name', 'date_from', 'date_to']),
    ]);
})->name('feeds.magazine');
Route::match(['get', 'post'], '/feeds/magazine/download', function (Illuminate\Http\Request $request) {
    return (new EssayMagazine())->download($request);
})->name('feeds.magazine.download');
Route::get('/login/clean', function (Illuminate\Http\Request $request) {
    if (Auth::check()) {
        Auth::logout();
    }

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->name('login.clean');

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
        $analysis = EssayAnalysis::where('user_id', auth()->id())
            ->when($childId, fn ($query) => $query->where('child_id', $childId))
            ->orderByDesc('last_submission_at')
            ->first();
        $latestEssay = EssaySubmission::where('user_id', auth()->id())
            ->when($childId, fn ($query) => $query->where('child_id', $childId))
            ->orderByDesc('uploaded_at')
            ->first();
        $jobs = EssaySubmission::query()
            ->where('user_id', auth()->id())
            ->when($childId, fn ($query) => $query->where('child_id', $childId))
            ->orderByDesc('id')
            ->limit(5)
            ->get();
        return view('dashboard', [
            'selectedChildId' => $childId,
            'analysis' => $analysis,
            'latestEssay' => $latestEssay,
            'jobs' => $jobs,
        ]);
    })->name('dashboard');

    Route::get('/jobs', function (Illuminate\Http\Request $request) use ($getSelectedChildId) {
        $childId = $getSelectedChildId($request);
        $jobs = EssaySubmission::query()
            ->where('user_id', auth()->id())
            ->when($childId, fn ($query) => $query->where('child_id', $childId))
            ->with('child:id,name')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('jobs', [
            'selectedChildId' => $childId,
            'jobs' => $jobs,
        ]);
    })->name('jobs');

    Route::get('/essay-jobs/current', function (Illuminate\Http\Request $request) {
        $dismissedEssayId = (int) ($request->session()->get('dismissed_essay_job_id') ?? 0);
        $essayId = (int) ($request->query('essay_id') ?: ($request->session()->get('active_essay_job_id') ?? 0));
        $activeStatuses = ['queued', 'processing', 'processing_ocr', 'processing_correction', 'processing_images', 'processing_analysis', 'completed', 'failed'];

        $essay = EssaySubmission::query()
            ->where('user_id', auth()->id())
            ->when($essayId > 0, fn ($query) => $query->where('id', $essayId))
            ->first();

        if (! $essay) {
            $essay = EssaySubmission::query()
                ->where('user_id', auth()->id())
                ->when($dismissedEssayId > 0, fn ($query) => $query->where('id', '!=', $dismissedEssayId))
                ->whereIn('processing_status', $activeStatuses)
                ->latest('id')
                ->first();
        }

        if (!$essay) {
            $request->session()->forget('active_essay_job_id');
            return response()->json(['job' => null]);
        }

        $request->session()->put('active_essay_job_id', (int) $essay->id);

        return response()->json([
            'job' => [
                'id' => $essay->id,
                'status' => $essay->processing_status,
                'error' => $essay->processing_error,
                'view_url' => route('essay-uploaded', ['essay' => $essay->id]),
            ],
        ]);
    })->name('essay-jobs.current');

    Route::post('/essay-jobs/dismiss', function (Illuminate\Http\Request $request) {
        $essayId = (int) ($request->session()->get('active_essay_job_id') ?? 0);
        $request->session()->forget('active_essay_job_id');
        if ($essayId > 0) {
            $request->session()->put('dismissed_essay_job_id', $essayId);
        }

        return response()->json(['ok' => true]);
    })->name('essay-jobs.dismiss');

    Route::get('/essay-uploaded/{essay}', function (Illuminate\Http\Request $request, EssaySubmission $essay) use ($getSelectedChildId) {
        abort_unless($essay->user_id === auth()->id(), 403);
        $childId = $getSelectedChildId($request);

        $imagePaths = is_array($essay->image_paths)
            ? $essay->image_paths
            : (json_decode($essay->image_paths, true) ?: []);
        $generatedImagePaths = is_array($essay->generated_image_paths)
            ? $essay->generated_image_paths
            : (json_decode($essay->generated_image_paths, true) ?: []);

        return view('essay-uploaded', [
            'selectedChildId' => $childId,
            'essay' => $essay,
            'imagePaths' => $imagePaths,
            'generatedImagePaths' => $generatedImagePaths,
        ]);
    })->name('essay-uploaded');

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

            $imagePaths = is_array($essay->image_paths)
                ? $essay->image_paths
                : (json_decode($essay->image_paths, true) ?: []);
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

        $sharedEssays = SharedEssay::where('user_id', auth()->id())
            ->whereIn('essay_submission_id', $essays->pluck('id'))
            ->get()
            ->keyBy('essay_submission_id');

        $sharedEssayIds = $sharedEssays->keys()->all();

        return view('previous-essays', [
            'essays' => $essays,
            'selectedChildId' => $childId,
            'sharedEssayIds' => $sharedEssayIds,
            'sharedEssays' => $sharedEssays,
        ]);
    })->name('previous-essays');

    Route::post('/previous-essays/{essay}/share', function (Illuminate\Http\Request $request, EssaySubmission $essay) use ($getSelectedChildId) {
        $childId = $getSelectedChildId($request);
        $authorized = EssaySubmission::where('user_id', auth()->id())
            ->when($childId, fn ($query) => $query->where('child_id', $childId))
            ->where('id', $essay->id)
            ->exists();

        if (! $authorized) {
            abort(403);
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

        return back();
    })->name('previous-essays.share');

    Route::post('/previous-essays/{essay}/unshare', function (Illuminate\Http\Request $request, EssaySubmission $essay) use ($getSelectedChildId) {
        $childId = $getSelectedChildId($request);
        $authorized = EssaySubmission::where('user_id', auth()->id())
            ->when($childId, fn ($query) => $query->where('child_id', $childId))
            ->where('id', $essay->id)
            ->exists();

        if (! $authorized) {
            abort(403);
        }

        SharedEssay::where('essay_submission_id', $essay->id)->delete();

        return back();
    })->name('previous-essays.unshare');

    Route::delete('/previous-essays', function (Illuminate\Http\Request $request) {
        $data = $request->validate([
            'essay_id' => ['required', 'integer'],
        ]);

        EssaySubmission::where('user_id', auth()->id())
            ->where('id', $data['essay_id'])
            ->delete();

        return redirect()->route('previous-essays');
    })->name('previous-essays.delete');

    Route::post('/previous-essays/{essay}/images/regenerate', function (Illuminate\Http\Request $request, EssaySubmission $essay) use ($getSelectedChildId) {
        $childId = $getSelectedChildId($request);
        $authorized = EssaySubmission::where('user_id', auth()->id())
            ->when($childId, fn ($query) => $query->where('child_id', $childId))
            ->where('id', $essay->id)
            ->exists();

        if (! $authorized) {
            abort(403);
        }

        $credits = new CreditService();
        if (! $credits->charge($request->user(), CreditService::COST_IMAGES)) {
            return back()->withErrors([
                'essay_images' => 'Not enough credits to regenerate images (requires 10).',
            ]);
        }

        $correctedEssay = trim((string) ($essay->corrected_version ?: $essay->ocr_text ?: $essay->response_text));
        if ($correctedEssay === '') {
            return back()->withErrors([
                'essay_images' => 'Unable to regenerate images without corrected text.',
            ]);
        }

        $state = new WorkflowState([
            'pipeline_mode' => false,
        ]);
        (new EssayImageNode())(new RetrieveEssayImages($essay->id, $correctedEssay), $state);

        return back();
    })->name('previous-essays.images.regenerate');

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
        $cachedItems = $cached?->items ?? [];
        $cachedSubmissionKey = $cached?->last_submission_at?->toDateTimeString();
        $activeStatuses = ['queued', 'processing', 'processing_images'];
        $isFresh = $cached
            && ($cached->processing_status ?? 'completed') === 'completed'
            && $cached->essay_count === $essayCount
            && $cachedSubmissionKey === $latestSubmissionKey;
        $isRefreshing = $cached && in_array((string) $cached->processing_status, $activeStatuses, true);

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

        if ($isFresh) {
            return view('reading-recommendations', [
                'child' => $child,
                'essayCount' => $essayCount,
                'recommendations' => $cachedItems,
                'topics' => [],
                'ageBand' => null,
                'isRefreshing' => false,
                'refreshStatus' => $cached?->processing_status,
                'refreshError' => $cached?->processing_error,
                'selectedChildId' => $childId,
            ]);
        }

        $recommendationLinks = $cachedItems;

        if ($essayCount > 0 && !$isFresh && !$isRefreshing) {
            $credits = new CreditService();
            if (! $credits->charge($request->user(), CreditService::COST_READING_RECOMMENDATIONS)) {
                return view('reading-recommendations', [
                    'child' => $child,
                    'essayCount' => $essayCount,
                    'recommendations' => $recommendationLinks,
                    'topics' => [],
                    'ageBand' => null,
                    'isRefreshing' => false,
                    'refreshStatus' => $cached?->processing_status,
                    'refreshError' => $cached?->processing_error,
                    'selectedChildId' => $childId,
                ])->withErrors([
                    'readings' => 'Not enough credits to refresh readings (requires 20).',
                ]);
            }

            $text = $essays
                ->map(fn ($essay) => trim((string) ($essay->ocr_text ?: $essay->response_text)))
                ->filter()
                ->implode("\n\n");

            if (trim($text) !== '') {
                $wordCount = str_word_count(strip_tags($text));
                $targetWords = max(60, min(200, $wordCount ?: 80));
                $childAge = null;
                if ($child?->birth_year) {
                    $childAge = now()->year - $child->birth_year;
                } elseif (property_exists($child, 'age') && $child?->age) {
                    $childAge = (int) $child->age;
                }
                $recommendation = ReadingRecommendation::updateOrCreate(
                    [
                        'user_id' => auth()->id(),
                        'child_id' => $childId,
                    ],
                    [
                        'essay_count' => $essayCount,
                        'last_submission_at' => $latestSubmissionAt,
                        'items' => $recommendationLinks,
                        'processing_status' => 'queued',
                        'processing_error' => null,
                    ]
                );

                ProcessReadingRecommendationsJob::dispatch(
                    (int) $recommendation->id,
                    $text,
                    $targetWords,
                    $child?->name,
                    $childAge,
                    $child?->gender,
                    $essayCount,
                    $latestSubmissionAt?->toDateTimeString()
                );

                $isRefreshing = true;
            }
        }

        return view('reading-recommendations', [
            'child' => $child,
            'essayCount' => $essayCount,
            'recommendations' => $recommendationLinks,
            'topics' => [],
            'ageBand' => null,
            'isRefreshing' => $isRefreshing,
            'refreshStatus' => $cached?->processing_status,
            'refreshError' => $cached?->processing_error,
            'selectedChildId' => $childId,
        ]);
    })->name('reading-recommendations');

    Route::get('/reading-recommendations/status', function (Illuminate\Http\Request $request) use ($getSelectedChildId) {
        $childId = $getSelectedChildId($request);
        $cached = ReadingRecommendation::where('user_id', auth()->id())
            ->where('child_id', $childId)
            ->first();

        return response()->json([
            'items' => $cached?->items ?? [],
            'status' => $cached?->processing_status,
            'error' => $cached?->processing_error,
        ]);
    })->name('reading-recommendations.status');

    Route::post('/reading-recommendations/refresh', function (Illuminate\Http\Request $request) use ($getSelectedChildId) {
        $childId = $getSelectedChildId($request);
        $credits = new CreditService();
        if (! $credits->charge($request->user(), CreditService::COST_READING_RECOMMENDATIONS)) {
            return back()->withErrors([
                'readings' => 'Not enough credits to refresh readings (requires 20).',
            ]);
        }

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
        $cached = ReadingRecommendation::where('user_id', auth()->id())
            ->where('child_id', $childId)
            ->first();

        $child = $childId
            ? Child::where('user_id', auth()->id())->whereKey($childId)->first()
            : null;

        $childAge = null;
        if ($child?->birth_year) {
            $childAge = now()->year - $child->birth_year;
        } elseif (property_exists($child, 'age') && $child?->age) {
            $childAge = (int) $child->age;
        }

        $recommendation = ReadingRecommendation::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'child_id' => $childId,
            ],
            [
                'essay_count' => $essays->count(),
                'last_submission_at' => $essays->max('uploaded_at'),
                'items' => $cached?->items ?? [],
                'processing_status' => 'queued',
                'processing_error' => null,
            ]
        );

        ProcessReadingRecommendationsJob::dispatch(
            (int) $recommendation->id,
            $text,
            $targetWords,
            $child?->name,
            $childAge,
            $child?->gender,
            $essays->count(),
            $essays->max('uploaded_at')?->toDateTimeString()
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
        $credits = new CreditService();
        if (! $credits->charge($request->user(), CreditService::COST_CORRECTION_ANALYSIS)) {
            return back()->withErrors([
                'analysis' => 'Not enough credits to refresh analysis (requires 5).',
            ]);
        }

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
    Route::view('/credits-usage', 'credits-usage')->name('credits.usage');

});
