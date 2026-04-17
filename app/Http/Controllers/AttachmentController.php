<?php

namespace App\Http\Controllers;

use App\Http\Requests\Validations\DeleteAttachmentRequest;
use App\Models\Attachment;
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
        if (! Storage::exists($attachment->path)) {
            return back()->with('error', trans('messages.file_not_exist'));
        }

        $mime = Storage::mimeType($attachment->path) ?: 'application/octet-stream';

        return Storage::response($attachment->path, $attachment->name, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$attachment->name.'"',
        ]);
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
