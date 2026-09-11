<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TeamController extends Controller 
{
    public function index(Request $request)
    {
        $members = User::where('owner_user_id', $request->user()->organisationOwnerId())
            ->where('user_type', 'teacher')
            ->latest()
            ->get();
        
        return view('team.index', compact('members'));
    }

    public function store(Request $request)
    {
        $owner = $request->user()->organisationOwnerId();
        $limit = $request->user()->limit('teacher_limit', 1);
        
        if (User::where('owner_user_id', $owner)->where('user_type', 'teacher')->count() >= $limit) {
            return back()->with('error', 'Teacher limit reached.');
        }
        
        $data = $request->validate([
            'name' => 'required|max:120',
            'email' => 'required|email|unique:users,email',
            'mobile' => 'nullable|max:30',
            'role_title' => 'nullable|max:100',
            'password' => 'required|min:8',
        ]);
        
        User::create($data + [
            'owner_user_id' => $owner,
            'user_type' => 'teacher',
            'plan_id' => $request->user()->plan_id,
            'account_status' => 'active'
        ]);
        
        return back()->with('success', 'Teacher account created and can now log in.');
    }

    public function update(Request $request, User $teamMember)
    {
        // Authorization check
        abort_unless(
            $teamMember->owner_user_id === $request->user()->organisationOwnerId() && 
            $teamMember->user_type === 'teacher',
            403,
            'Unauthorized action.'
        );
        
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email,' . $teamMember->id,
            'mobile' => 'nullable|string|max:30',
            'role_title' => 'nullable|string|max:100',
            'account_status' => 'required|in:active,inactive,suspended',
            'password' => 'nullable|string|min:8',
        ]);
        
        // Only update password if provided
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        
        $teamMember->update($data);
        
        return back()->with('success', 'Teacher account updated successfully.');
    }

    public function destroy(Request $request, User $teamMember)
    {
        abort_unless(
            $teamMember->owner_user_id === $request->user()->organisationOwnerId() && 
            $teamMember->user_type === 'teacher',
            403,
            'Unauthorized action.'
        );
        
        $teamMember->delete();
        
        return back()->with('success', 'Teacher account removed.');
    }
}