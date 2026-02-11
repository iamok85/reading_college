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

    Route::get('/dashboard', function () {
        return view('dashboard');
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
        $data = $request->validate([
            'child_name' => ['required', 'string', 'max:255'],
            'child_age' => ['required', 'integer', 'min:1', 'max:18'],
            'child_gender' => ['required', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $child = Child::create([
            'user_id' => $user->id,
            'name' => $data['child_name'],
            'age' => $data['child_age'],
            'gender' => $data['child_gender'],
        ]);

        $request->session()->put('selected_child_id', $child->id);

        return back();
    })->name('child-profile.update');

    Route::post('/children', function (Illuminate\Http\Request $request) {
        $data = $request->validate([
            'child_name' => ['required', 'string', 'max:255'],
            'child_age' => ['required', 'integer', 'min:1', 'max:18'],
            'child_gender' => ['required', 'string', 'max:50'],
        ]);

        $child = Child::create([
            'user_id' => $request->user()->id,
            'name' => $data['child_name'],
            'age' => $data['child_age'],
            'gender' => $data['child_gender'],
        ]);

        $request->session()->put('selected_child_id', $child->id);

        return back();
    })->name('children.store');

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
        $child = $childId ? $request->user()?->children()->whereKey($childId)->first() : null;
        $essays = DB::table('essay_submissions')
            ->where('user_id', auth()->id())
            ->when($childId, fn ($query) => $query->where('child_id', $childId))
            ->orderByDesc('uploaded_at')
            ->get(['ocr_text', 'response_text', 'uploaded_at']);

        $text = $essays
            ->map(fn ($essay) => trim((string) ($essay->ocr_text ?: $essay->response_text)))
            ->filter()
            ->implode("\n\n");
        $haystack = Str::lower($text);

        $topics = [
            'space' => ['space', 'planet', 'moon', 'star', 'galaxy', 'astronaut'],
            'animals' => ['animal', 'pet', 'dog', 'cat', 'zoo', 'wildlife'],
            'nature' => ['forest', 'tree', 'mountain', 'river', 'nature', 'garden'],
            'ocean' => ['ocean', 'sea', 'beach', 'fish', 'whale', 'shark'],
            'sports' => ['sport', 'soccer', 'football', 'basketball', 'tennis', 'game'],
            'friendship' => ['friend', 'friendship', 'kindness', 'share', 'help'],
            'family' => ['family', 'mother', 'father', 'sister', 'brother', 'home'],
            'school' => ['school', 'teacher', 'class', 'homework', 'lesson'],
            'adventure' => ['adventure', 'journey', 'explore', 'discover', 'travel'],
            'fantasy' => ['magic', 'dragon', 'wizard', 'fairy', 'kingdom'],
            'history' => ['history', 'ancient', 'king', 'queen', 'castle', 'war'],
            'science' => ['science', 'experiment', 'robot', 'invention', 'energy'],
        ];

        $catalog = [
            'space' => [
                'Space Explorer: A Kid’s Guide to the Cosmos',
                'The Little Astronaut',
                'Stars, Planets, and Beyond',
            ],
            'animals' => [
                'Wildlife Wonders',
                'Amazing Animal Facts for Kids',
                'My First Pet Care Guide',
            ],
            'nature' => [
                'Forests and Mountains',
                'Rivers, Rain, and Weather',
                'Nature Adventures for Kids',
            ],
            'ocean' => [
                'Ocean Deep: Life Underwater',
                'Sea Creatures and Coral Reefs',
                'A Day at the Beach',
            ],
            'sports' => [
                'Teamwork on the Field',
                'The Big Game: A Sports Story',
                'Practice Makes Progress',
            ],
            'friendship' => [
                'The Kind Friend',
                'Sharing and Caring',
                'Friends Who Help',
            ],
            'family' => [
                'My Family, My Home',
                'Stories We Share',
                'A Day With My Family',
            ],
            'school' => [
                'My First School Day',
                'Learning Is Fun',
                'Classroom Adventures',
            ],
            'adventure' => [
                'The Great Adventure Map',
                'Journey to the Hidden Island',
                'Explorers in the Wild',
            ],
            'fantasy' => [
                'The Magic Garden',
                'Dragons and Brave Knights',
                'The Secret Wizard School',
            ],
            'history' => [
                'Castles and Kings',
                'Tales From the Past',
                'History for Young Readers',
            ],
            'science' => [
                'Easy Experiments for Kids',
                'Robots and Inventions',
                'Science Adventures',
            ],
        ];

        $age = $child?->age;
        $gender = Str::lower((string) ($child?->gender ?? ''));
        $ageBand = match (true) {
            $age !== null && $age <= 7 => 'early',
            $age !== null && $age <= 10 => 'middle',
            $age !== null && $age <= 13 => 'upper',
            default => 'teen',
        };

        $agePicks = [
            'early' => [
                'Early Readers: Short Stories for Young Kids',
                'Big Picture Books: Learning Through Stories',
                'Fun Rhymes and Word Play',
            ],
            'middle' => [
                'Chapter Starters: Short Chapter Books',
                'Mystery Club for Young Readers',
                'Adventure Tales for Growing Readers',
            ],
            'upper' => [
                'Middle Grade Adventures',
                'Science and History for Curious Kids',
                'Mystery and Problem Solving Stories',
            ],
            'teen' => [
                'Young Adult Adventures',
                'Inspiring Biographies for Teens',
                'Classic Stories for Advanced Readers',
            ],
        ];

        $genderPicks = [
            'female' => ['Stories with brave girls and role models'],
            'male' => ['Stories with kind, thoughtful boys and role models'],
            'non-binary' => ['Stories with diverse and inclusive characters'],
            'prefer-not-to-say' => ['Stories with diverse and inclusive characters'],
        ];

        $matchedTopics = [];
        foreach ($topics as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($haystack, $keyword)) {
                    $matchedTopics[] = $topic;
                    break;
                }
            }
        }

        if (empty($matchedTopics)) {
            $matchedTopics = ['friendship', 'adventure', 'science'];
        }

        $recommendations = [];
        foreach ($matchedTopics as $topic) {
            foreach ($catalog[$topic] as $title) {
                $recommendations[] = $title;
            }
        }

        foreach ($agePicks[$ageBand] ?? [] as $title) {
            $recommendations[] = $title;
        }

        foreach ($genderPicks[$gender] ?? ['Stories with diverse and inclusive characters'] as $title) {
            $recommendations[] = $title;
        }

        $recommendations = array_values(array_unique($recommendations));
        $recommendationLinks = array_map(function (string $title): array {
            return [
                'title' => $title,
                'type' => 'Reading',
                'url' => 'https://www.google.com/search?q=' . urlencode($title . ' book'),
            ];
        }, $recommendations);

        $movieCatalog = [
            'space' => ['Space Explorers (Family)', 'Journey to the Moon (Animated)'],
            'animals' => ['Wildlife Rescue (Family)', 'The Jungle Friends (Animated)'],
            'nature' => ['Forest Adventures (Family)', 'Mountains & Rivers (Documentary)'],
            'ocean' => ['Ocean Wonders (Family)', 'Deep Sea Friends (Animated)'],
            'sports' => ['Teamwork Wins (Family)', 'The Big Match (Family)'],
            'friendship' => ['Best Friends Forever (Family)', 'The Kindness Club (Family)'],
            'family' => ['My Family Story (Family)', 'Home Sweet Home (Family)'],
            'school' => ['School Days (Family)', 'The New Class (Family)'],
            'adventure' => ['The Great Adventure (Family)', 'Treasure Island Journey (Family)'],
            'fantasy' => ['The Magic Kingdom (Family)', 'Dragons and Dreams (Family)'],
            'history' => ['Time Travelers (Family)', 'Castles and Crowns (Family)'],
            'science' => ['Robots & Inventions (Family)', 'The Science Quest (Family)'],
        ];

        $ageMoviePicks = [
            'early' => ['Short Animated Stories (Kids)', 'Sing-Along Movie (Kids)'],
            'middle' => ['Adventure Animations (Kids)', 'Family Comedy (Kids)'],
            'upper' => ['Family Adventure (Kids)', 'Science Documentary (Kids)'],
            'teen' => ['Teen Adventure (Family)', 'Inspiring True Stories (Teen)'],
        ];

        $genderMoviePicks = [
            'female' => ['Stories with brave girls (Family)'],
            'male' => ['Stories with kind boys (Family)'],
            'non-binary' => ['Inclusive stories with diverse heroes (Family)'],
            'prefer-not-to-say' => ['Inclusive stories with diverse heroes (Family)'],
        ];

        $movieRecommendations = [];
        foreach ($matchedTopics as $topic) {
            foreach ($movieCatalog[$topic] ?? [] as $title) {
                $movieRecommendations[] = $title;
            }
        }

        foreach ($ageMoviePicks[$ageBand] ?? [] as $title) {
            $movieRecommendations[] = $title;
        }

        foreach ($genderMoviePicks[$gender] ?? ['Inclusive stories with diverse heroes (Family)'] as $title) {
            $movieRecommendations[] = $title;
        }

        $movieRecommendations = array_values(array_unique($movieRecommendations));
        $movieLinks = array_map(function (string $title): array {
            return [
                'title' => $title,
                'type' => 'Movie',
                'url' => 'https://www.google.com/search?q=' . urlencode($title . ' family movie'),
            ];
        }, $movieRecommendations);

        $recommendationLinks = array_merge($recommendationLinks, $movieLinks);
        $recommendationLinks = array_values($recommendationLinks);

        return view('reading-recommendations', [
            'child' => $child,
            'essayCount' => $essays->count(),
            'recommendations' => $recommendationLinks,
            'topics' => $matchedTopics,
            'ageBand' => $ageBand,
        ]);
    })->name('reading-recommendations');

});
