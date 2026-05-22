<div class="app-sidebar__overlay" data-toggle="sidebar"></div>
<aside class="app-sidebar sidebar-scroll">
	<div class="main-sidebar-header active">
		<a class="desktop-logo logo-light active" href="{{ route('platform.dashboard') }}"><img src="{{ URL::asset('assets/img/brand/logo.png') }}" class="main-logo" alt="logo"></a>
		<a class="desktop-logo logo-dark active" href="{{ route('platform.dashboard') }}"><img src="{{ URL::asset('assets/img/brand/logo-white.png') }}" class="main-logo dark-theme" alt="logo"></a>
		<a class="logo-icon mobile-logo icon-light active" href="{{ route('platform.dashboard') }}"><img src="{{ URL::asset('assets/img/brand/favicon.png') }}" class="logo-icon" alt="logo"></a>
		<a class="logo-icon mobile-logo icon-dark active" href="{{ route('platform.dashboard') }}"><img src="{{ URL::asset('assets/img/brand/favicon-white.png') }}" class="logo-icon dark-theme" alt="logo"></a>
	</div>
	<div class="main-sidemenu">
		<div class="app-sidebar__user clearfix">
			<div class="dropdown user-pro-body">
				<div>
					<img alt="user-img" class="avatar avatar-xl brround" src="{{ URL::asset('assets/img/faces/6.jpg') }}"><span class="avatar-status profile-status bg-green"></span>
				</div>
				<div class="user-info">
					<h4 class="font-weight-semibold mt-3 mb-0">{{ Auth::user()->name }}</h4>
					<span class="mb-0 text-muted">{{ __('platform.sidebar.operator_label') }}</span>
				</div>
			</div>
		</div>
		<ul class="side-menu">
			<li class="side-item side-item-category">{{ __('platform.sidebar.category') }}</li>
			<li class="slide">
				<a class="side-menu__item {{ request()->routeIs('platform.dashboard') ? 'active' : '' }}" href="{{ route('platform.dashboard') }}">
					<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M5 5h4v6H5zm10 8h4v6h-4zM5 17h4v2H5zM15 5h4v2h-4z" opacity=".3"/><path d="M3 13h8V3H3v10zm2-8h4v6H5V5zm8 16h8V11h-8v10zm2-8h4v6h-4v-6zM13 3v6h8V3h-8zm6 4h-4V5h4v2zM3 21h8v-6H3v6zm2-4h4v2H5v-2z"/></svg>
					<span class="side-menu__label">{{ __('platform.sidebar.dashboard') }}</span>
				</a>
			</li>
			<li class="slide">
				<a class="side-menu__item {{ request()->routeIs('platform.tenants.*') ? 'active' : '' }}" href="{{ route('platform.tenants.index') }}">
					<i class="side-menu__icon fe fe-grid"></i>
					<span class="side-menu__label">{{ __('platform.sidebar.tenants') }}</span>
				</a>
			</li>
			<li class="slide">
				<a class="side-menu__item {{ request()->routeIs('platform.plans.*') || request()->routeIs('platform.subscriptions.*') ? 'active' : '' }}" href="{{ route('platform.plans.index') }}">
					<i class="side-menu__icon fe fe-layers"></i>
					<span class="side-menu__label">{{ __('platform.sidebar.subscriptions') }}</span>
				</a>
			</li>
			<li class="slide">
				<a class="side-menu__item {{ request()->routeIs('platform.invoices.*') ? 'active' : '' }}" href="{{ route('platform.invoices.index') }}">
					<i class="side-menu__icon fe fe-file-text"></i>
					<span class="side-menu__label">{{ __('platform.sidebar.invoices') }}</span>
				</a>
			</li>
			<li class="slide">
				<a class="side-menu__item {{ request()->routeIs('platform.modules.*') ? 'active' : '' }}" href="{{ route('platform.modules.index') }}">
					<i class="side-menu__icon fe fe-package"></i>
					<span class="side-menu__label">{{ __('platform.sidebar.modules') }}</span>
				</a>
			</li>
			<li class="slide">
				<a class="side-menu__item {{ request()->routeIs('platform.addons.*') ? 'active' : '' }}" href="{{ route('platform.addons.index') }}">
					<i class="side-menu__icon fe fe-plus-square"></i>
					<span class="side-menu__label">{{ __('platform.sidebar.addons') }}</span>
				</a>
			</li>
			<li class="slide">
				<a class="side-menu__item {{ request()->routeIs('platform.settings.*') ? 'active' : '' }}" href="{{ route('platform.settings.index') }}">
					<i class="side-menu__icon fe fe-settings"></i>
					<span class="side-menu__label">{{ __('platform.sidebar.settings') }}</span>
				</a>
			</li>
			<li class="slide">
				<a class="side-menu__item {{ request()->routeIs('platform.gateways.*') ? 'active' : '' }}" href="{{ route('platform.gateways.index') }}">
					<i class="side-menu__icon fe fe-credit-card"></i>
					<span class="side-menu__label">{{ __('platform.sidebar.gateways') }}</span>
				</a>
			</li>
			<li class="slide">
				<a class="side-menu__item {{ request()->routeIs('platform.logs.*') ? 'active' : '' }}" href="{{ route('platform.logs.index') }}">
					<i class="side-menu__icon fe fe-activity"></i>
					<span class="side-menu__label">{{ __('platform.sidebar.logs') }}</span>
				</a>
			</li>
		</ul>
	</div>
</aside>
