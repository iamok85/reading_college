<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
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
    $email = config('reading_college.demo_user_email');
    $name = config('reading_college.demo_user_name');
    $user = User::firstOrCreate(
        ['email' => $email],
        [
            'name' => $name,
            'password' => Hash::make('demo1234'),
            'email_verified_at' => now(),
        ]
    );

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
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/previous-essays', function (Illuminate\Http\Request $request) {
        if ($request->filled('download')) {
            $essay = DB::table('essay_submissions')
                ->where('user_id', auth()->id())
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

});
