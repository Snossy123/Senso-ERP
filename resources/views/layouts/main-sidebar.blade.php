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
							<span class="mb-0 text-muted">{{ __('messages.sidebar.erp_administrator') }}</span>
						</div>
					</div>
				</div>
				<ul class="side-menu">
					<li class="side-item side-item-category">{{ __('messages.sidebar.category_main') }}</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('dashboard') }}">
							<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24" ><path d="M0 0h24v24H0V0z" fill="none"/><path d="M5 5h4v6H5zm10 8h4v6h-4zM5 17h4v2H5zM15 5h4v2h-4z" opacity=".3"/><path d="M3 13h8V3H3v10zm2-8h4v6H5V5zm8 16h8V11h-8v10zm2-8h4v6h-4v-6zM13 3v6h8V3h-8zm6 4h-4V5h4v2zM3 21h8v-6H3v6zm2-4h4v2H5v-2z"/></svg>
							<span class="side-menu__label">{{ __('messages.sidebar.dashboard') }}</span>
						</a>
					</li>

					<li class="side-item side-item-category">{{ __('messages.sidebar.category_admin') }}</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('admin.users.index') }}">
							<i class="side-menu__icon fe fe-users"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.user_management') }}</span>
						</a>
					</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('admin.roles.index') }}">
							<i class="side-menu__icon fe fe-shield"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.role_management') }}</span>
						</a>
					</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('tenants.index') }}">
							<i class="side-menu__icon fe fe-grid"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.tenant_management') }}</span>
						</a>
					</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('admin.settings') }}">
							<i class="side-menu__icon fe fe-settings"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.settings') }}</span>
						</a>
					</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('admin.activity.index') }}">
							<i class="side-menu__icon fe fe-activity"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.activity_log') }}</span>
						</a>
					</li>

					<li class="side-item side-item-category">{{ __('messages.sidebar.category_pos') }}</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('pos.terminal') }}">
							<i class="side-menu__icon fe fe-shopping-bag"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.pos_terminal') }}</span>
						</a>
					</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('pos.sales.index') }}">
							<i class="side-menu__icon fe fe-file-text"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.sales_history') }}</span>
						</a>
					</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('pos.shifts.index') }}">
							<i class="side-menu__icon fe fe-clock"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.shift_management') }}</span>
						</a>
					</li>

					<li class="side-item side-item-category">{{ __('messages.sidebar.category_inventory') }}</li>
					<li class="slide">
						<a class="side-menu__item" data-toggle="slide" href="#">
							<i class="side-menu__icon fe fe-package"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.stock_control') }}</span>
							<i class="angle fe fe-chevron-down"></i>
						</a>
						<ul class="slide-menu">
							<li><a class="slide-item" href="{{ route('inventory.products.index') }}">{{ __('messages.sidebar.products') }}</a></li>
							<li><a class="slide-item" href="{{ route('inventory.categories.index') }}">{{ __('messages.sidebar.categories') }}</a></li>
							<li><a class="slide-item" href="{{ route('inventory.units.index') }}">{{ __('messages.sidebar.units_of_measure') }}</a></li>
							<li><a class="slide-item" href="{{ route('inventory.movements.index') }}">{{ __('messages.sidebar.stock_movements') }}</a></li>
						</ul>
					</li>
					<li class="slide">
						<a class="side-menu__item" data-toggle="slide" href="#">
							<i class="side-menu__icon fe fe-truck"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.operations') }}</span>
							<i class="angle fe fe-chevron-down"></i>
						</a>
						<ul class="slide-menu">
							<li><a class="slide-item" href="{{ route('inventory.purchase-orders.index') }}">{{ __('messages.sidebar.purchase_orders') }}</a></li>
							<li><a class="slide-item" href="{{ route('inventory.transfers.index') }}">{{ __('messages.sidebar.stock_transfers') }}</a></li>
						</ul>
					</li>
					<li class="slide">
						<a class="side-menu__item" data-toggle="slide" href="#">
							<i class="side-menu__icon fe fe-users"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.relationships') }}</span>
							<i class="angle fe fe-chevron-down"></i>
						</a>
						<ul class="slide-menu">
							<li><a class="slide-item" href="{{ route('inventory.suppliers.index') }}">{{ __('messages.sidebar.suppliers') }}</a></li>
							<li><a class="slide-item" href="{{ route('inventory.warehouses.index') }}">{{ __('messages.sidebar.warehouses') }}</a></li>
						</ul>
					</li>

					<li class="side-item side-item-category">{{ __('messages.sidebar.category_store') }}</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('admin.orders.index') }}">
							<i class="side-menu__icon fe fe-shopping-cart"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.customer_orders') }}</span>
							@php $pendingCount = \App\Models\Order::where('status', 'pending')->count(); @endphp
							@if($pendingCount > 0)
								<span class="badge badge-danger side-badge">{{ $pendingCount }}</span>
							@endif
						</a>
					</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('admin.storefront-builder.index') }}">
							<i class="side-menu__icon fe fe-layout"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.storefront_builder') }}</span>
						</a>
					</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('admin.storefront-studio.index') }}">
							<i class="side-menu__icon fe fe-monitor"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.visual_store_studio') }}</span>
						</a>
					</li>

					<li class="side-item side-item-category">{{ __('messages.sidebar.category_reports') }}</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('reports.index') }}">
							<i class="side-menu__icon fe fe-bar-chart-2"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.reports_overview') }}</span>
						</a>
					</li>
					<li class="slide">
						<a class="side-menu__item" data-toggle="slide" href="#">
							<i class="side-menu__icon fe fe-file-text"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.detailed_reports') }}</span>
							<i class="angle fe fe-chevron-down"></i>
						</a>
						<ul class="slide-menu">
							<li><a class="slide-item" href="{{ route('reports.sales') }}">{{ __('messages.sidebar.sales_report') }}</a></li>
							<li><a class="slide-item" href="{{ route('reports.inventory') }}">{{ __('messages.sidebar.inventory_report') }}</a></li>
							<li><a class="slide-item" href="{{ route('reports.profit') }}">{{ __('messages.sidebar.profit_analysis') }}</a></li>
							<li><a class="slide-item" href="{{ route('reports.customers') }}">{{ __('messages.sidebar.customer_report') }}</a></li>
						</ul>
					</li>


					<li class="side-item side-item-category">{{ __('messages.sidebar.category_accounting') }}</li>
					<li class="slide">
						<a class="side-menu__item" data-toggle="slide" href="#">
							<i class="side-menu__icon fe fe-dollar-sign"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.accounting') }}</span>
							<i class="angle fe fe-chevron-down"></i>
						</a>
						<ul class="slide-menu">
							<li><a class="slide-item" href="{{ route('accounting.dashboard') }}">{{ __('messages.sidebar.financial_dashboard') }}</a></li>
							<li><a class="slide-item" href="{{ route('accounting.accounts') }}">{{ __('messages.sidebar.chart_of_accounts') }}</a></li>
							<li><a class="slide-item" href="{{ route('accounting.journal-entries') }}">{{ __('messages.sidebar.journal_entries') }}</a></li>
							<li><a class="slide-item" href="{{ route('accounting.reports') }}">{{ __('messages.sidebar.financial_reports') }}</a></li>
							<li><a class="slide-item" href="{{ route('accounting.settings') }}">{{ __('messages.sidebar.accounting_setup') }}</a></li>
						</ul>
					</li>

					<li class="side-item side-item-category">{{ __('messages.sidebar.category_external') }}</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('store.index') }}" target="_blank">
							<i class="side-menu__icon fe fe-external-link"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.view_storefront') }}</span>
						</a>
					</li>
					<div style="height: 100px;"></div>
				</ul>
			</div>
		</aside>
<!-- main-sidebar -->
