<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            new Middleware('permission:manage_settings'),
            (new Middleware('throttle:30,1'))->only(['update']),
        ];
    }

    public function index(): View
    {
        return view('settings.index', [
            'siteName' => Setting::value('site_name', 'Youth Platform Africa'),
            'backupEmail' => Setting::value('backup_email', ''),
            'addressDetails' => Setting::value('address_details', ''),
            'siteLogo' => Setting::asset('site_logo', ''),
            'siteFavicon' => Setting::asset('site_favicon', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:191'],
            'backup_email' => ['nullable', 'email', 'max:191'],
            'address_details' => ['nullable', 'string', 'max:2000'],
            'site_logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
            'site_favicon' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:1024'],
        ]);

        Setting::updateOrCreate(
            ['setting_key' => 'site_name'],
            ['setting_value' => $data['site_name']]
        );

        Setting::updateOrCreate(
            ['setting_key' => 'backup_email'],
            ['setting_value' => $data['backup_email'] ?? '']
        );

        Setting::updateOrCreate(
            ['setting_key' => 'address_details'],
            ['setting_value' => $data['address_details'] ?? '']
        );

        foreach (['site_logo' => 'branding/logos', 'site_favicon' => 'branding/favicons'] as $field => $dir) {
            if ($request->hasFile($field)) {
                $oldPath = Setting::asset($field, '');
                $storedPath = $request->file($field)->store($dir, 'public');

                if (!$storedPath) {
                    return back()->with('error', 'The branding image could not be stored.');
                }

                Setting::updateOrCreate(
                    ['setting_key' => $field],
                    ['setting_value' => 'storage/' . $storedPath]
                );

                if (str_starts_with($oldPath, 'storage/branding/')) {
                    Storage::disk('public')->delete(substr($oldPath, strlen('storage/')));
                }
            }
        }

        return redirect()
            ->route('settings.index')
            ->with('success', 'Settings updated successfully!');
    }
}
