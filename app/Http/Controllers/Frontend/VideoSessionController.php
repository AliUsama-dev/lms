<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\VideoSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Modules\CourseSetting\Entities\Lesson;

class VideoSessionController extends Controller
{
    /**
     * Stream a lesson video using a secure session token.
     *
     * @param  string  $token
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
     */
    public function play(string $token)
    {
        $session = VideoSession::where('token', $token)->first();

        if (!$session || !$session->isValid()) {
            abort(403);
        }

        if (Auth::check() && $session->user_id && $session->user_id !== Auth::id()) {
            abort(403);
        }

        // Mark the session as used on first successful access
        if (is_null($session->used_at)) {
            $session->used_at = Carbon::now();
            $session->save();
        }

        $lesson = Lesson::findOrFail($session->lesson_id);

        // For now we only proxy "Self" and "Storage" videos directly.
        if ($lesson->host === 'Self' || $lesson->host === 'Storage') {
            $path = $lesson->video_url;

            if (empty($path)) {
                abort(404);
            }

            // When stored via Laravel storage, fall back to public asset URL.
            return redirect()->to(asset($path));
        }

        // For other hosts we currently redirect to the original URL.
        if (!empty($lesson->video_url)) {
            return redirect()->to($lesson->video_url);
        }

        abort(404);
    }

    /**
     * Receive and store progress updates from the video player.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function progress(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'current_time' => 'nullable|numeric|min:0',
            'duration' => 'nullable|numeric|min:0',
        ]);

        $session = VideoSession::where('token', $request->input('token'))->first();

        if (!$session || !$session->isValid()) {
            return Response::json(['status' => 'error', 'message' => 'Invalid session'], 403);
        }

        if (Auth::check() && $session->user_id && $session->user_id !== Auth::id()) {
            return Response::json(['status' => 'error', 'message' => 'Invalid user'], 403);
        }

        $current = (int) $request->input('current_time', 0);
        $duration = (int) $request->input('duration', 0);

        if ($current > $session->last_position_seconds) {
            $session->last_position_seconds = $current;
        }

        if ($duration > 0 && $duration >= $session->duration_seconds) {
            $session->duration_seconds = $duration;
        }

        if ($session->duration_seconds > 0) {
            $percent = (int) floor(($session->last_position_seconds / $session->duration_seconds) * 100);
            $session->percent_watched = max($session->percent_watched, min($percent, 100));
        }

        // Optionally mark session inactive when finished
        if ($request->boolean('ended')) {
            $session->is_active = false;
        }

        $session->save();

        return Response::json([
            'status' => 'ok',
            'percent_watched' => $session->percent_watched,
        ]);
    }
}

