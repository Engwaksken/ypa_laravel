<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class NotificationsController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            new Middleware('permission:notifications'),
            (new Middleware('throttle:30,1'))->only(['markRead', 'markAllRead', 'destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $typeFilter = trim((string) $request->query('type', ''));
        $readFilter = trim((string) $request->query('read', ''));

        $query = Notification::query()
            ->forUser(auth()->id())
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('title', 'like', $like)
                    ->orWhere('message', 'like', $like);
            });
        }

        if ($typeFilter !== '') {
            $query->where('type', $typeFilter);
        }

        if ($readFilter === 'unread') {
            $query->where('is_read', false);
        } elseif ($readFilter === 'read') {
            $query->where('is_read', true);
        }

        $notifications = $query->paginate(20)->withQueryString();

        $types = Notification::query()->forUser(auth()->id())->select('type')->distinct()->orderBy('type')->pluck('type')->all();

        $stats = [
            'total' => Notification::forUser(auth()->id())->count(),
            'unread' => Notification::forUser(auth()->id())->unread()->count(),
        ];

        return view('notifications.index', compact(
            'notifications',
            'search',
            'typeFilter',
            'readFilter',
            'types',
            'stats'
        ));
    }

    public function markRead(Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === auth()->id(), 404);

        $notification->update(['is_read' => true]);

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead(): RedirectResponse
    {
        Notification::query()
            ->where('user_id', auth()->id())
            ->unread()
            ->update(['is_read' => true]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function destroy(Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === auth()->id(), 404);

        $notification->delete();

        return back()->with('success', 'Notification deleted successfully!');
    }
}
