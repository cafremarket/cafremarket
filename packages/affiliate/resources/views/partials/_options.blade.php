<a href="javascript:void(0)" data-link="{{ route('admin.affiliate.show', $affiliate) }}" class="ajax-modal-btn"><em data-toggle="tooltip" data-placement="top" title="{{ trans('app.show') }}" class="fa fa-user-circle"></em></a>&nbsp;

<a href="javascript:void(0)" data-link="{{ route('admin.affiliate.edit', $affiliate) }}" class="ajax-modal-btn"><em data-toggle="tooltip" data-placement="top" title="{{ trans('app.edit') }}" class="fa fa-edit"></em></a>&nbsp;

<a href="{{ route('admin.affiliate.links', $affiliate) }}"><em data-toggle="tooltip" data-placement="top" title="{{ trans('packages.affiliate.affiliate_links') }}" class="fa fa-link"></em></a>&nbsp;

<a href="javascript:void(0)" data-link="{{ route('admin.affiliate.passwordForm', $affiliate) }}" class="ajax-modal-btn"><em data-toggle="tooltip" data-placement="top" title="{{ trans('app.change_password') }}" class="fa fa-lock"></em></a>&nbsp;

{!! Form::open(['route' => ['admin.affiliate.destroy', $affiliate], 'method' => 'delete', 'class' => 'data-form']) !!}
{!! Form::button('<em class="fa fa-trash-o"></em>', ['type' => 'submit', 'class' => 'confirm ajax-silent', 'title' => trans('app.trash'), 'data-toggle' => 'tooltip', 'data-placement' => 'top']) !!}
{!! Form::close() !!}
