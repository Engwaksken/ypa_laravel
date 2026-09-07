<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Public activity registration (mirrors activity-registration.php /
 * participant_register.php / process_external_participant_registration.php).
 * No auth required — the legacy pages were public.
 */
class ActivityRegistrationController extends Controller
{
    protected ActivityService $activityService;

    public function __construct()
    {
        $this->activityService = app(ActivityService::class);
    }

    /**
     * Public registration — no auth middleware (legacy pages were public).
     */
    public static function middleware(): array
    {
        return [];
    }

    public function show(Request $request): View
    {
        $activityId = (int) $request->query('activity_id', 0);
        $activity = Activity::query()->with(['type'])->find($activityId);

        if (!$activity) {
            abort(404, 'Activity not found.');
        }

        return view('activity-registration', compact('activity'));
    }

    /**
     * Handle external (non-member) registration. Mirrors
     * process_external_participant_registration.php: validates the posted
     * fields, stores the participant, and redirects back with a success or
     * error flash.
     */
    public function register(Request $request): RedirectResponse
    {
        $activityId = (int) $request->input('activity_id', 0);
        $activity = Activity::query()->find($activityId);

        if (!$activity) {
            return redirect()
                ->route('activity-registration', ['activity_id' => $activityId])
                ->with('error', 'Activity not found.');
        }

        $data = [
            'first_name' => trim((string) $request->input('first_name', '')),
            'last_name' => trim((string) $request->input('last_name', '')),
            'gender' => $this->activityService->normalizeGender((string) $request->input('gender', '')),
            'phone' => trim((string) $request->input('phone', '')),
            'email' => trim((string) $request->input('email', '')) ?: null,
            'address' => trim((string) $request->input('address', '')) ?: null,
            'participant_type' => 'External',
        ];

        $validator = validator($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['Male', 'Female', 'Other'])],
            'phone' => ['required', 'string', 'min:7', 'max:20', 'regex:/^\+?[0-9]{7,15}$/'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'participant_type' => ['required', Rule::in(['External'])],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('activity-registration', ['activity_id' => $activityId])
                ->withErrors($validator)
                ->with('external_error', implode(' ', $validator->errors()->all()))
                ->withInput();
        }

        $validated = $validator->validated();

        // External participants are stored as non-member rows in
        // activity_participants with participant_type = 'External'. The
        // legacy system kept the personal details on the participant row;
        // guard each column in case the production table lacks it.
        $payload = [
            'activity_id' => $activity->id,
            'participant_type' => 'External',
            'attended' => false,
        ];

        $columns = Schema::hasTable('activity_participants')
            ? Schema::getColumnListing('activity_participants')
            : [];

        if (in_array('first_name', $columns, true)) {
            $payload['first_name'] = $validated['first_name'];
        }
        if (in_array('last_name', $columns, true)) {
            $payload['last_name'] = $validated['last_name'];
        }
        if (in_array('gender', $columns, true)) {
            $payload['gender'] = $validated['gender'];
        }
        if (in_array('phone', $columns, true)) {
            $payload['phone'] = $validated['phone'];
        }
        if (in_array('email', $columns, true)) {
            $payload['email'] = !empty($validated['email']) ? $validated['email'] : null;
        }
        if (in_array('address', $columns, true)) {
            $payload['address'] = !empty($validated['address']) ? $validated['address'] : null;
        }

        ActivityParticipant::create($payload);

        return redirect()
            ->route('activity-registration', ['activity_id' => $activityId])
            ->with('success', 'Registration successful. Thank you for registering for ' . $activity->activity_name . '.');
    }
}
