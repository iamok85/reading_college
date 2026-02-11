<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Child;
use App\Models\Contact;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Support\Recaptcha;
use App\Neuron\Events\RetrieveReadingRecommendations;
use App\Neuron\Nodes\ReadingRecommendationsNode;
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
        ]);

        session()->put('demo_user_id', $user->id);
    }

    Auth::login($user);

    return redirect()->route('dashboard');
})->name('demo');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

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
            $essay = DB::table('essay_submissions')
                ->where('user_id', auth()->id())
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

        $essays = DB::table('essay_submissions')
            ->where('user_id', auth()->id())
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

        DB::table('essay_submissions')
            ->where('user_id', auth()->id())
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
        $child = Child::create([
            'user_id' => $user->id,
            'name' => $data['child_name'],
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

        $child = Child::create([
            'user_id' => $request->user()->id,
            'name' => $data['child_name'],
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

        $child->update([
            'name' => $data['child_name'],
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

    Route::get('/reading-recommendations', function (Illuminate\Http\Request $request) use ($getSelectedChildId) {
        $childId = $getSelectedChildId($request);
        $child = $childId
            ? Child::where('user_id', auth()->id())->whereKey($childId)->first()
            : null;
        $essays = DB::table('essay_submissions')
            ->where('user_id', auth()->id())
            ->when($childId, fn ($query) => $query->where('child_id', $childId))
            ->orderByDesc('uploaded_at')
            ->get(['ocr_text', 'response_text', 'uploaded_at']);

        $latestSubmissionAt = $essays->max('uploaded_at');
        $essayCount = $essays->count();
        $cached = DB::table('reading_recommendations')
            ->where('user_id', auth()->id())
            ->where('child_id', $childId)
            ->first();

        if ($cached && $cached->essay_count === $essayCount && $cached->last_submission_at === $latestSubmissionAt) {
            $items = json_decode((string) $cached->items, true) ?: [];
            return view('reading-recommendations', [
                'child' => $child,
                'essayCount' => $essayCount,
                'recommendations' => $items,
                'topics' => [],
                'ageBand' => null,
                'selectedChildId' => $childId,
            ]);
        }

        $text = $essays
            ->map(fn ($essay) => trim((string) ($essay->ocr_text ?: $essay->response_text)))
            ->filter()
            ->implode("\n\n");
        $recommendationLinks = [];
        if (trim($text) !== '') {
            $wordCount = str_word_count(strip_tags($text));
            $targetWords = max(60, min(200, $wordCount ?: 80));

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
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && isset($decoded['items']) && is_array($decoded['items'])) {
                    $recommendationLinks = array_map(function (array $item): array {
                        return [
                            'title' => (string) ($item['title'] ?? ''),
                            'type' => (string) ($item['type'] ?? 'Book'),
                            'paragraph' => (string) ($item['paragraph'] ?? ''),
                        ];
                    }, $decoded['items']);
                }
            }
        }

        if (!empty($recommendationLinks)) {
            DB::table('reading_recommendations')
                ->updateOrInsert(
                    [
                        'user_id' => auth()->id(),
                        'child_id' => $childId,
                    ],
                    [
                        'essay_count' => $essayCount,
                        'last_submission_at' => $latestSubmissionAt,
                        'items' => json_encode($recommendationLinks),
                        'updated_at' => now(),
                        'created_at' => $cached?->created_at ?? now(),
                    ]
                );
        }

        return view('reading-recommendations', [
            'child' => $child,
            'essayCount' => $essayCount,
            'recommendations' => $recommendationLinks,
            'topics' => [],
            'ageBand' => null,
            'selectedChildId' => $childId,
        ]);
    })->name('reading-recommendations');

});
