<?php

namespace App\Repositories\Popup;

use App\Models\Popup;
use App\Repositories\BaseRepository;
use App\Repositories\EloquentRepository;

class EloquentPopup extends EloquentRepository implements PopupRepository, BaseRepository
{
    protected $model;

    public function __construct(Popup $popup)
    {
        $this->model = $popup;
    }

    public function destroy($id)
    {
        $popup = parent::find($id);

        $popup->flushImages();

        return $popup->forceDelete();
    }

    public function massDestroy($ids)
    {
        foreach ($ids as $id) {
            $this->destroy($id);
        }
    }
}
