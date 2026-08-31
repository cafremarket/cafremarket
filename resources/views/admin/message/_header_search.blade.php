<div class="admin-mailbox__search">
  {!! Form::open(['route' => 'search.message', 'method' => 'get', 'class' => 'admin-mailbox__search-form', 'data-toggle' => 'validator']) !!}
  <div class="input-group input-group-sm">
    {!! Form::text('q', $search_q ?? null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.search'), 'required']) !!}
    <span class="input-group-btn">
      <button class="btn btn-default" type="submit"><i class="fa fa-search"></i></button>
    </span>
  </div>
  <div class="help-block with-errors"></div>
  {!! Form::close() !!}
</div>
