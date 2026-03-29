<!-- main-sidebar -->
		<div class="app-sidebar__overlay" data-toggle="sidebar"></div>
		<aside class="app-sidebar sidebar-scroll">
			<div class="main-sidebar-header active">
				<a class="desktop-logo logo-light active" href="{{ route('dashboard') }}"><img src="{{URL::asset('assets/img/brand/logo.png')}}" class="main-logo" alt="logo"></a>
				<a class="desktop-logo logo-dark active" href="{{ route('dashboard') }}"><img src="{{URL::asset('assets/img/brand/logo-white.png')}}" class="main-logo dark-theme" alt="logo"></a>
				<a class="logo-icon mobile-logo icon-light active" href="{{ route('dashboard') }}"><img src="{{URL::asset('assets/img/brand/favicon.png')}}" class="logo-icon" alt="logo"></a>
				<a class="logo-icon mobile-logo icon-dark active" href="{{ route('dashboard') }}"><img src="{{URL::asset('assets/img/brand/favicon-white.png')}}" class="logo-icon dark-theme" alt="logo"></a>
			</div>
			<div class="main-sidemenu">
				<div class="app-sidebar__user clearfix">
					<div class="dropdown user-pro-body">
						<div class="">
							<img alt="user-img" class="avatar avatar-xl brround" src="{{URL::asset('assets/img/faces/6.jpg')}}"><span class="avatar-status profile-status bg-green"></span>
						</div>
						<div class="user-info">
							<h4 class="font-weight-semibold mt-3 mb-0">{{ Auth::user()->name }}</h4>
							<span class="mb-0 text-muted">ERP Administrator</span>
						</div>
					</div>
				</div>
				<ul class="side-menu">
					<li class="side-item side-item-category">Main</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('dashboard') }}">
							<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24" ><path d="M0 0h24v24H0V0z" fill="none"/><path d="M5 5h4v6H5zm10 8h4v6h-4zM5 17h4v2H5zM15 5h4v2h-4z" opacity=".3"/><path d="M3 13h8V3H3v10zm2-8h4v6H5V5zm8 16h8V11h-8v10zm2-8h4v6h-4v-6zM13 3v6h8V3h-8zm6 4h-4V5h4v2zM3 21h8v-6H3v6zm2-4h4v2H5v-2z"/></svg>
							<span class="side-menu__label">Dashboard</span>
						</a>
					</li>

					<li class="side-item side-item-category">Administration</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('admin.users.index') }}">
							<i class="side-menu__icon fe fe-users"></i>
							<span class="side-menu__label">User Management</span>
						</a>
					</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('admin.roles.index') }}">
							<i class="side-menu__icon fe fe-shield"></i>
							<span class="side-menu__label">Role Management</span>
						</a>
					</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('tenants.index') }}">
							<i class="side-menu__icon fe fe-grid"></i>
							<span class="side-menu__label">Tenant Management</span>
						</a>
					</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('admin.settings') }}">
							<i class="side-menu__icon fe fe-settings"></i>
							<span class="side-menu__label">Settings</span>
						</a>
					</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('admin.activity.index') }}">
							<i class="side-menu__icon fe fe-activity"></i>
							<span class="side-menu__label">Activity Log</span>
						</a>
					</li>

					<li class="side-item side-item-category">Point of Sale</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('pos.terminal') }}">
							<i class="side-menu__icon fe fe-shopping-bag"></i>
							<span class="side-menu__label">POS Terminal</span>
						</a>
					</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('pos.sales.index') }}">
							<i class="side-menu__icon fe fe-file-text"></i>
							<span class="side-menu__label">Sales History</span>
						</a>
					</li>

					<li class="side-item side-item-category">Inventory Management</li>
					<li class="slide">
						<a class="side-menu__item" data-toggle="slide" href="#">
							<i class="side-menu__icon fe fe-package"></i>
							<span class="side-menu__label">Stock Control</span>
							<i class="angle fe fe-chevron-down"></i>
						</a>
						<ul class="slide-menu">
							<li><a class="slide-item" href="{{ route('inventory.products.index') }}">Products</a></li>
							<li><a class="slide-item" href="{{ route('inventory.categories.index') }}">Categories</a></li>
							<li><a class="slide-item" href="{{ route('inventory.movements.index') }}">Stock Movements</a></li>
						</ul>
					</li>
					<li class="slide">
						<a class="side-menu__item" data-toggle="slide" href="#">
							<i class="side-menu__icon fe fe-users"></i>
							<span class="side-menu__label">Relationships</span>
							<i class="angle fe fe-chevron-down"></i>
						</a>
						<ul class="slide-menu">
							<li><a class="slide-item" href="{{ route('inventory.suppliers.index') }}">Suppliers</a></li>
							<li><a class="slide-item" href="{{ route('inventory.warehouses.index') }}">Warehouses</a></li>
						</ul>
					</li>

					<li class="side-item side-item-category">Store Portal Admin</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('admin.orders.index') }}">
							<i class="side-menu__icon fe fe-shopping-cart"></i>
							<span class="side-menu__label">Customer Orders</span>
							@php $pendingCount = \App\Models\Order::where('status', 'pending')->count(); @endphp
							@if($pendingCount > 0)
								<span class="badge badge-danger side-badge">{{ $pendingCount }}</span>
							@endif
						</a>
					</li>

					<li class="side-item side-item-category">Reports</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('reports.index') }}">
							<i class="side-menu__icon fe fe-bar-chart-2"></i>
							<span class="side-menu__label">Reports Overview</span>
						</a>
					</li>
					<li class="slide">
						<a class="side-menu__item" data-toggle="slide" href="#">
							<i class="side-menu__icon fe fe-file-text"></i>
							<span class="side-menu__label">Detailed Reports</span>
							<i class="angle fe fe-chevron-down"></i>
						</a>
						<ul class="slide-menu">
							<li><a class="slide-item" href="{{ route('reports.sales') }}">Sales Report</a></li>
							<li><a class="slide-item" href="{{ route('reports.inventory') }}">Inventory Report</a></li>
							<li><a class="slide-item" href="{{ route('reports.profit') }}">Profit Analysis</a></li>
							<li><a class="slide-item" href="{{ route('reports.customers') }}">Customer Report</a></li>
						</ul>
					</li>

					<li class="side-item side-item-category">External</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('store.index') }}" target="_blank">
							<i class="side-menu__icon fe fe-external-link"></i>
							<span class="side-menu__label">View Storefront</span>
						</a>
					</li>
				</ul>
			</div>
		</aside>
<!-- main-sidebar -->
