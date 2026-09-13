<?php

namespace App\Repositories\Dispute;

use App\Models\Dispute;
use App\Repositories\BaseRepository;
use App\Repositories\EloquentRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EloquentDispute extends EloquentRepository implements BaseRepository, DisputeRepository
{
    protected $model;

    public function __construct(Dispute $dispute)
    {
        $this->model = $dispute;
    }

    public function open()
    {
        return $this->model->with('dispute_type', 'order', 'customer.avatarImage', 'shop')
            ->withCount('replies')
            ->open()
            ->orderByDesc('updated_at')
            ->get();
    }

    public function closed()
    {
        return $this->model->with('dispute_type', 'order', 'customer.avatarImage', 'shop')
            ->withCount('replies')
            ->closed()
            ->orderByDesc('updated_at')
            ->get();
    }

    public function store(Request $request)
    {
        $dispute = $this->model->create($request->all());

        if ($request->hasFile('attachments')) {
            $dispute->saveAttachments($request->file('attachments'));
        }

        return $dispute;
    }

    public function show($id)
    {
        return $this->model->with(['replies' => function ($query) {
            $query->with('attachments', 'user', 'customer')->orderBy('id');
        }])->find($id);
    }

    public function storeResponse(Request $request, $dispute)
    {
        if (! $dispute instanceof Dispute) {
            $dispute = $this->model->find($dispute);
        }

        $response = $dispute->replies()->create($request->all());

        if ($request->hasFile('attachments')) {
            $response->saveAttachments($request->file('attachments'));
        }

        return $response;
    }

    public function recentlyUpdated()
    {
        return $this->model->where('updated_at', '>', Carbon::parse('-1 days'))->get();
    }
}
