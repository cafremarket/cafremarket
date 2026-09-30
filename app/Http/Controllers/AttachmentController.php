<?php

namespace App\Http\Controllers;

use App\Http\Requests\Validations\DeleteAttachmentRequest;
use App\Models\Attachment;
use App\Support\AttachmentAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    /**
     * download attachment file
     *
     *
     * @return file
     */
    public function download(Request $request, Attachment $attachment)
    {
        abort_unless(AttachmentAccess::allows($attachment), 403);

        if (Storage::exists($attachment->path)) {
            return Storage::download($attachment->path, $attachment->name);
        }

        return back()->with('error', trans('messages.file_not_exist'));
    }

    /**
     * View attachment file in browser (inline).
     *
     * @return \Illuminate\Http\Response
     */
    public function view(Request $request, Attachment $attachment)
    {
        abort_unless(AttachmentAccess::allows($attachment), 403);

        if (! Storage::exists($attachment->path)) {
            return back()->with('error', trans('messages.file_not_exist'));
        }

        $mime = Storage::mimeType($attachment->path) ?: 'application/octet-stream';

        // Active content (HTML/SVG/XML) is downloaded, never rendered on this origin.
        $inline = ! preg_match('#(html|svg|xml|javascript)#i', $mime);

        return Storage::response($attachment->path, $attachment->name, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => 'sandbox',
        ], $inline ? 'inline' : 'attachment');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeleteAttachmentRequest $request, Attachment $attachment)
    {
        if (Storage::exists($attachment->path)) {
            Storage::delete($attachment->path);
        }

        if ($attachment->forceDelete()) {
            return back()->with('success', trans('messages.file_deleted'));
        }

        return back()->with('error', trans('messages.failed'));
    }
}
