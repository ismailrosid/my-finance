<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $user = $this->getUser();
        $categories = Category::forUser($user?->id ?? 1)->orderBy('category_type')->orderBy('category_name')->get();

        return view('settings.index', compact('user', 'categories'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return redirect()->back()->with('error', 'User not found.');
        }

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
        ]);

        $oldValues = $user->toArray();
        $user->update($validated);

        AuditLog::record($user->id, 'UPDATE', 'users', $user->id, $oldValues, $user->toArray());

        return redirect()->route('settings.index', ['tab' => 'profile'])->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return redirect()->back()->with('error', 'User not found.');
        }

        $validated = $request->validate([
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', Password::defaults()],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return redirect()->route('settings.index', ['tab' => 'security'])->withErrors(['current_password' => 'The provided current password does not match our records.']);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        AuditLog::record($user->id, 'UPDATE_PASSWORD', 'users', $user->id, null, null);

        return redirect()->route('settings.index', ['tab' => 'security'])->with('success', 'Password updated successfully.');
    }

    public function categories(): View
    {
        $userId = $this->getUserId();
        $categories = Category::forUser($userId)->orderBy('category_type')->orderBy('category_name')->get();

        return view('settings.categories', compact('categories'));
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $userId = $this->getUserId();

        $validated = $request->validate([
            'category_name' => 'required|string|max:100',
            'category_type' => 'required|in:income,expense',
            'color'         => 'nullable|string|max:20',
            'icon'          => 'nullable|string|max:50',
        ]);

        Category::create([
            'user_id'       => $userId,
            'category_name' => $validated['category_name'],
            'category_type' => $validated['category_type'],
            'color'         => $validated['color'] ?? '#10B981',
            'icon'          => $validated['icon'] ?? 'heroicon-o-tag',
            'is_system'     => false,
            'is_active'     => true,
        ]);

        return redirect()->route('settings.index', ['tab' => 'categories'])->with('success', 'Category created successfully.');
    }

    public function updateCategory(Request $request, int $id): RedirectResponse
    {
        $userId = $this->getUserId();
        $category = Category::where('user_id', $userId)->where('is_system', false)->findOrFail($id);

        $validated = $request->validate([
            'category_name' => 'required|string|max:100',
            'color'         => 'nullable|string|max:20',
            'is_active'     => 'nullable|boolean',
        ]);

        $oldValues = $category->toArray();

        $category->update([
            'category_name' => $validated['category_name'],
            'color'         => $validated['color'] ?? $category->color,
            'is_active'     => $request->has('is_active'),
        ]);

        AuditLog::record($userId, 'UPDATE', 'categories', $category->id, $oldValues, $category->fresh()->toArray());

        return redirect()->route('settings.index', ['tab' => 'categories'])->with('success', 'Category updated successfully.');
    }

    public function destroyCategory(int $id): RedirectResponse
    {
        $userId = $this->getUserId();
        $category = Category::where('user_id', $userId)->where('is_system', false)->findOrFail($id);

        $oldValues = $category->toArray();
        $category->delete();

        AuditLog::record($userId, 'DELETE', 'categories', $id, $oldValues, null);

        return redirect()->route('settings.index', ['tab' => 'categories'])->with('success', 'Category deleted successfully.');
    }
}
