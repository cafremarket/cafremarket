<!-- a template for each modal -->
<div class="modal-dialog modal-md">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="false">&times;</button>
      {{ trans('app.profile') }} <!--Modal Header-->
    </div>
    <div class="modal-body">
      <div class="card hovercard">
        <div class="card-background">
          <img src="{{ get_storage_file_url(optional($affiliate->image)->path, 'small') }}" class="card-bkimg img-circle" alt="{{ trans('app.avatar') }}">
        </div>
        <div class="useravatar">
          <img src="{{ get_avatar_src($affiliate, 'small') }}" class="img-circle" alt="{{ trans('app.avatar') }}">
        </div>
        <div class="card-info">
          <span class="card-title">{{ $affiliate->getName() }}</span>
        </div>
      </div>

      <table class="table">
        @if ($affiliate->name)
          <tr>
            <th>{{ trans('app.full_name') }}: </th>
            <td>{{ $affiliate->name }}</td>
          </tr>
        @endif

        @if ($affiliate->email)
          <tr>
            <th>{{ trans('app.email') }}: </th>
            <td>{{ $affiliate->email }}</td>
          </tr>
        @endif

        @if ($affiliate->username)
          <tr>
            <th>{{ trans('app.username') }}: </th>
            <td>{{ $affiliate->username }}</td>
          </tr>
        @endif

        @if ($affiliate->created_at)
          <tr>
            <th>{{ trans('app.member_since') }}: </th>
            <td>{{ $affiliate->created_at->diffForHumans() }}</td>
          </tr>
        @endif
      </table>

    </div>
    <div class="modal-footer"> </div>
  </div> <!-- / .modal-content -->
</div> <!-- / .modal-dialog -->";
