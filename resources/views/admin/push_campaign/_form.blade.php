<div class="form-group">
  {!! Form::label('title', 'Title *') !!}
  {!! Form::text('title', null, ['class' => 'form-control', 'required', 'maxlength' => 120, 'placeholder' => 'Flash sale this weekend!']) !!}
  <div class="help-block with-errors"></div>
</div>

<div class="form-group">
  {!! Form::label('body', 'Message *') !!}
  {!! Form::textarea('body', null, ['class' => 'form-control', 'rows' => 4, 'required', 'maxlength' => 500, 'placeholder' => 'Short message shown on lock screen']) !!}
  <div class="help-block with-errors"></div>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="form-group">
      {!! Form::label('audience', 'Audience *') !!}
      {!! Form::select('audience', $audiences, null, ['class' => 'form-control select2-normal', 'required']) !!}
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-group">
      {!! Form::label('type', 'Type *') !!}
      {!! Form::select('type', $types, null, ['class' => 'form-control select2-normal', 'required']) !!}
    </div>
  </div>
</div>

<div class="form-group">
  {!! Form::label('image_url', 'Image URL (optional)') !!}
  {!! Form::text('image_url', null, ['class' => 'form-control', 'placeholder' => 'https://...']) !!}
  <p class="help-block">HTTPS image recommended for rich notifications on Android/iOS.</p>
</div>

<div class="form-group">
  {!! Form::label('deep_link', 'Deep link / path (optional)') !!}
  {!! Form::text('deep_link', null, ['class' => 'form-control', 'placeholder' => 'e.g. /deals or shop slug']) !!}
</div>
