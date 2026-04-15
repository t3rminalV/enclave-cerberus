<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
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
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required|string|max:30',
            'tshirt_size' => 'nullable|in:XS,S,M,L,XL,XXL,XXXL',
            'dietary_requirements' => 'nullable|string|max:500',
            'medical_info' => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();
        $user->update($validated);

        AuditLog::record('profile.updated', $user, [], [
            'fields' => array_keys($validated),
        ]);

        return back()->with('success', 'Profile updated.');
    }
}
