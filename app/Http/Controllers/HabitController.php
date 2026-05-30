<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HabitController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $request->user()->habits()->create($validated);

        return back()->with('status', 'Habit created.');
    }

    public function log(Request $request, Habit $habit): RedirectResponse
    {
        abort_unless($habit->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'log_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $habit->logs()->updateOrCreate(
            [
                'habit_id' => $habit->id,
                'log_date' => $validated['log_date'],
            ],
            [
                'user_id' => $request->user()->id,
                'logged_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ],
        );

        return back()->with('status', 'Habit logged.');
    }
}

