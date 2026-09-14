<a href="{{ url('admin/utility/page') }}" class="btn btn-default btn-flat">
  <i class="fa fa-file-text-o"></i> {{ trans('nav.pages') }}
</a>
{!! Form::open(['route' => 'admin.utility.policyPage.applyAllDefaults', 'method' => 'POST', 'class' => 'admin-inline-form', 'style' => 'display:inline-block;margin-left:6px;']) !!}
<button type="submit" class="btn btn-warning btn-flat confirm" title="{{ trans('help.policy_apply_all_defaults') }}">
  <i class="fa fa-magic"></i> {{ trans('app.apply_default_policies') }}
</button>
{!! Form::close() !!}
