<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use Illuminate\Http\Request;

class EmailLogController extends Controller
{
    /**
     * Display outbound mail delivery log.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (! auth()->user() || ! auth()->user()->isAdmin()) {
            abort(403);
        }

        $logs = EmailLog::query()
            ->filter($request->only(['status', 'q']))
            ->orderByDesc('id')
            ->paginate(50)
            ->appends($request->query());

        return view('admin.email-log.index', compact('logs'));
    }

    /**
     * Show a single log entry (modal).
     *
     * @return \Illuminate\Http\Response
     */
    public function show(EmailLog $emailLog)
    {
        if (! auth()->user() || ! auth()->user()->isAdmin()) {
            abort(403);
        }

        return view('admin.email-log._show', compact('emailLog'));
    }

    /**
     * Delete a log entry.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(EmailLog $emailLog)
    {
        if (! auth()->user() || ! auth()->user()->isAdmin()) {
            abort(403);
        }

        $emailLog->delete();

        return back()->with('success', trans('messages.deleted', ['model' => trans('nav.email_logs')]));
    }

    /**
     * Clear all logs (optional filter by status).
     *
     * @return \Illuminate\Http\Response
     */
    public function clear(Request $request)
    {
        if (! auth()->user() || ! auth()->user()->isAdmin()) {
            abort(403);
        }

        $query = EmailLog::query();

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $query->delete();

        return back()->with('success', trans('messages.deleted', ['model' => trans('nav.email_logs')]));
    }
}
