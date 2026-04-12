<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit()
    {
        return Inertia::render('Profile/Edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'nullable|string|max:30',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'tshirt_size' => 'nullable|in:XS,S,M,L,XL,XXL,XXXL',
            'dietary_requirements' => 'nullable|string|max:500',
            'medical_info' => 'nullable|string|max:1000',
        ]);

        auth()->user()->update($validated);

        return back()->with('success', 'Profile updated.');
    }
}
