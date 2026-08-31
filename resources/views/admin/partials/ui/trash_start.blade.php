{{-- Collapsible trash section. Pass: title, actions (optional) --}}
<div class="box admin-card admin-card--trash collapsed-box">
  <div class="box-header with-border admin-card__header">
    <div class="admin-card__header-main">
      <h3 class="box-title admin-card__title--trash">
        <span class="admin-card__icon-wrap admin-card__icon-wrap--muted"><i class="fa fa-trash-o"></i></span>
        {!! $title ?? trans('app.trash') !!}
      </h3>
    </div>
    <div class="box-tools pull-right admin-card__actions">
      <button type="button" class="btn btn-box-tool admin-card__collapse-btn" data-widget="collapse" title="{{ trans('app.expand') ?? 'Expand' }}">
        <i class="fa fa-plus"></i>
      </button>
    </div>
  </div>
  <div class="box-body admin-card__body responsive-table">
