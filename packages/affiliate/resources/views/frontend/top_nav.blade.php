@desktop
  <style>
    #zcart_affiliatetopnav {
      position: relative;
      background: #1d2327;
      background-color: #1d2327;
      color: #c3c4c7;
      font-weight: 400;
      font-size: 13px;
      width: 100%;
      overflow: hidden;
      z-index: 4;
    }

    /* Style the links inside the navigation bar */
    #zcart_affiliatetopnav a {
      float: left;
      color: #f2f2f2;
      text-align: center;
      text-decoration: none;
      padding: 4px 16px;
    }

    /* Change the color of links on hover */
    #zcart_affiliatetopnav a:hover {
      background-color: #04AA6D;
      color: #fff;
    }

    /* Create a right-aligned (split) link inside the navigation bar */
    #zcart_affiliatetopnav a.split {
      float: right;
    }

    #zcart_affiliatetopnav a.split.highlight {
      background-color: #04AA6D;
      color: #fff;
    }
  </style>

  <div id="zcart_affiliatetopnav">
    <a class="active" href="{{ route('affiliate.dashboard') }}">
      <i class="fa fa-fw fa-dashboard"></i> {{ trans('nav.dashboard') }}
    </a>

    <a href="{{ route('affiliate.link.index') }}">
      <i class="fa fa-fw fa-link"></i> {{ trans('packages.affiliate.affiliate_links') }}
    </a>

    <a href="{{ route('affiliate.logout') }}" class="split">
      <i class="fa fa-fw fa-sign-out"></i> {{ trans('nav.logout') }}
    </a>

    <a href="{{ route('affiliate.dashboard') }}" class="split highlight">
      {{ trans('app.welcome') . ' ' . auth()->guard('affiliate')->user()->getName() }}
    </a>
  </div>
@enddesktop
