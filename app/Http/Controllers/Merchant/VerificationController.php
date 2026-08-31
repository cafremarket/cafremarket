<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Admin\ConfigController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\MerchantVerifyRequest;
use App\Models\Attachment;
use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VerificationController extends Controller
{
    public function index(MerchantVerifyRequest $request)
    {
        $config = Config::with(['attachments', 'shop'])->findOrFail(Auth::user()->merchantId());

        return view('merchant.verify.index', compact('config'));
    }

    public function submit(MerchantVerifyRequest $request)
    {
        return app(ConfigController::class)->saveVerificationData($request);
    }

    public function saveLocation(Request $request)
    {
        return app(ConfigController::class)->saveStoreLocation($request);
    }

    public function savePhone(Request $request)
    {
        $config = $this->merchantConfig();

        if (! $config->canSubmitVerificationRequest()) {
            return back()->with('error', trans('messages.verification_request_not_allowed'));
        }

        $request->validate([
            'support_phone' => 'required|string|max:32',
        ]);

        $config->update([
            'support_phone' => $request->input('support_phone'),
        ]);

        clearShopConfigCache($config->id);

        return back()->with('success', trans('messages.verification_phone_saved'));
    }

    public function saveEmail(Request $request)
    {
        $config = $this->merchantConfig();

        if (! $config->canSubmitVerificationRequest()) {
            return back()->with('error', trans('messages.verification_request_not_allowed'));
        }

        $request->validate([
            'support_email' => 'required|email|max:255',
        ]);

        $config->update([
            'support_email' => $request->input('support_email'),
        ]);

        clearShopConfigCache($config->id);

        return back()->with('success', trans('messages.verification_email_saved'));
    }

    public function storeDocuments(MerchantVerifyRequest $request)
    {
        $config = $this->merchantConfig();

        if (! $config->canSubmitVerificationRequest()) {
            return back()->with('error', trans('messages.verification_request_not_allowed'));
        }

        $request->validate([
            'documents' => 'required|array|min:1',
            'documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
            'document_type' => 'required|in:person,store',
        ]);

        $created = $config->saveAttachments($request->file('documents'));
        $config->registerVerificationAttachmentIds(
            collect($created)->pluck('id')->all(),
            $request->input('document_type')
        );
        clearShopConfigCache($config->id);

        return back()->with('success', trans('messages.verification_documents_uploaded'));
    }

    public function replaceDocument(Request $request, Attachment $attachment)
    {
        $config = $this->authorizeVerificationDocument($attachment);

        $request->validate([
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $file = $request->file('document');

        if (Storage::exists($attachment->path)) {
            Storage::delete($attachment->path);
        }

        $attachment->update([
            'path' => Storage::putFile(attachment_storage_dir(), $file),
            'name' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'size' => $file->getSize(),
        ]);

        clearShopConfigCache($config->id);

        return back()->with('success', trans('messages.verification_document_replaced'));
    }

    public function deleteDocument(Attachment $attachment)
    {
        $config = $this->authorizeVerificationDocument($attachment);

        $config->deleteAttachment($attachment);
        $config->unregisterVerificationAttachmentId((int) $attachment->id);
        clearShopConfigCache($config->id);

        return back()->with('success', trans('messages.file_deleted'));
    }

    private function merchantConfig(): Config
    {
        return Config::with('attachments')->findOrFail(Auth::user()->merchantId());
    }

    private function authorizeVerificationDocument(Attachment $attachment): Config
    {
        $config = $this->merchantConfig();

        if (! $attachment->attachable instanceof Config) {
            abort(403);
        }

        if ((int) $attachment->attachable_id !== (int) $config->id) {
            abort(403);
        }

        if (! $config->canSubmitVerificationRequest()) {
            abort(403, trans('messages.verification_request_not_allowed'));
        }

        return $config;
    }
}
