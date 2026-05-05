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
							<span class="mb-0 text-muted">
								@if(Auth::user()->tenant_id === null)
									{{ __('messages.sidebar.platform_operator') }}
								@else
									{{ Auth::user()->role?->name ?? __('messages.sidebar.staff_member') }}
								@endif
							</span>
						</div>
					</div>
				</div>
				@php
					$sidebarUser = auth()->user();
					$can = fn (string $permission) => $sidebarUser->isAdmin() || $sidebarUser->hasPermission($permission);
					$canAny = fn (array $permissions) => $sidebarUser->isAdmin() || $sidebarUser->hasAnyPermission($permissions);

					$showAdminSection = $can('users.view')
						|| $can('roles.view')
						|| $can('settings.view')
						|| $sidebarUser->tenant_id === null
						|| $sidebarUser->isAdmin();

					$showPosSection = $canAny(['pos.view', 'pos.create', 'orders.view']);

					$showStockMenu = $canAny(['products.view', 'categories.view', 'warehouses.view']);
					$showUnits = $can('products.view');
					$showMovements = $canAny(['products.view', 'warehouses.view']);

					$showOperationsMenu = $can('suppliers.view') || $can('warehouses.view');

					$showRelationshipsMenu = $canAny(['suppliers.view', 'warehouses.view']);

					$showInventorySection = $showStockMenu || $showOperationsMenu || $showRelationshipsMenu;

					$showStoreSection = $can('orders.view')
						|| $can('settings.edit')
						|| $sidebarUser->isAdmin();

					$showReportsSection = $can('reports.view');

					$showAccountingSection = $can('reports.view');

					$showExternalSection = $sidebarUser->tenant_id !== null;

					$showMainSection = $can('dashboard.view');
				@endphp
				<ul class="side-menu">
					@if($showMainSection)
					<li class="side-item side-item-category">{{ __('messages.sidebar.category_main') }}</li>
					@endif
					@if($can('dashboard.view'))
					<li class="slide">
						<a class="side-menu__item" href="{{ route('dashboard') }}">
							<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24" ><path d="M0 0h24v24H0V0z" fill="none"/><path d="M5 5h4v6H5zm10 8h4v6h-4zM5 17h4v2H5zM15 5h4v2h-4z" opacity=".3"/><path d="M3 13h8V3H3v10zm2-8h4v6H5V5zm8 16h8V11h-8v10zm2-8h4v6h-4v-6zM13 3v6h8V3h-8zm6 4h-4V5h4v2zM3 21h8v-6H3v6zm2-4h4v2H5v-2z"/></svg>
							<span class="side-menu__label">{{ __('messages.sidebar.dashboard') }}</span>
						</a>
					</li>
					@endif

					@if($showAdminSection)
					<li class="side-item side-item-category">{{ __('messages.sidebar.category_admin') }}</li>
					@endif
					@if($can('users.view'))
					<li class="slide">
						<a class="side-menu__item" href="{{ route('admin.users.index') }}">
							<i class="side-menu__icon fe fe-users"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.user_management') }}</span>
						</a>
					</li>
					@endif
					@if($can('roles.view'))
					<li class="slide">
						<a class="side-menu__item" href="{{ route('admin.roles.index') }}">
							<i class="side-menu__icon fe fe-shield"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.role_management') }}</span>
						</a>
					</li>
					@endif
					@if(Auth::user()->tenant_id === null)
					<li class="slide">
						<a class="side-menu__item" href="{{ route('tenants.index') }}">
							<i class="side-menu__icon fe fe-grid"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.tenant_management') }}</span>
						</a>
					</li>
					@endif
					@if($can('settings.view'))
					<li class="slide">
						<a class="side-menu__item" href="{{ route('admin.settings') }}">
							<i class="side-menu__icon fe fe-settings"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.settings') }}</span>
						</a>
					</li>
					@endif
					@if($sidebarUser->isAdmin())
					<li class="slide">
						<a class="side-menu__item" href="{{ route('admin.activity.index') }}">
							<i class="side-menu__icon fe fe-activity"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.activity_log') }}</span>
						</a>
					</li>
					@endif

					@if($showPosSection)
					<li class="side-item side-item-category">{{ __('messages.sidebar.category_pos') }}</li>
					@endif
					@if($can('pos.view'))
					<li class="slide">
						<a class="side-menu__item" href="{{ route('pos.terminal') }}">
							<i class="side-menu__icon fe fe-shopping-bag"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.pos_terminal') }}</span>
						</a>
					</li>
					@endif
					@if($can('orders.view'))
					<li class="slide">
						<a class="side-menu__item" href="{{ route('pos.sales.index') }}">
							<i class="side-menu__icon fe fe-file-text"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.sales_history') }}</span>
						</a>
					</li>
					@endif
					@if($can('pos.view'))
					<li class="slide">
						<a class="side-menu__item" href="{{ route('pos.shifts.index') }}">
							<i class="side-menu__icon fe fe-clock"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.shift_management') }}</span>
						</a>
					</li>
					@endif

					@if($showInventorySection)
					<li class="side-item side-item-category">{{ __('messages.sidebar.category_inventory') }}</li>
					@endif
					@if($showStockMenu)
					<li class="slide">
						<a class="side-menu__item" data-toggle="slide" href="#">
							<i class="side-menu__icon fe fe-package"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.stock_control') }}</span>
							<i class="angle fe fe-chevron-down"></i>
						</a>
						<ul class="slide-menu">
							@if($can('products.view'))
							<li><a class="slide-item" href="{{ route('inventory.products.index') }}">{{ __('messages.sidebar.products') }}</a></li>
							@endif
							@if($can('categories.view'))
							<li><a class="slide-item" href="{{ route('inventory.categories.index') }}">{{ __('messages.sidebar.categories') }}</a></li>
							@endif
							@if($showUnits)
							<li><a class="slide-item" href="{{ route('inventory.units.index') }}">{{ __('messages.sidebar.units_of_measure') }}</a></li>
							@endif
							@if($showMovements)
							<li><a class="slide-item" href="{{ route('inventory.movements.index') }}">{{ __('messages.sidebar.stock_movements') }}</a></li>
							@endif
						</ul>
					</li>
					@endif
					@if($showOperationsMenu)
					<li class="slide">
						<a class="side-menu__item" data-toggle="slide" href="#">
							<i class="side-menu__icon fe fe-truck"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.operations') }}</span>
							<i class="angle fe fe-chevron-down"></i>
						</a>
						<ul class="slide-menu">
							@if($can('suppliers.view'))
							<li><a class="slide-item" href="{{ route('inventory.purchase-orders.index') }}">{{ __('messages.sidebar.purchase_orders') }}</a></li>
							@endif
							@if($can('warehouses.view'))
							<li><a class="slide-item" href="{{ route('inventory.transfers.index') }}">{{ __('messages.sidebar.stock_transfers') }}</a></li>
							@endif
						</ul>
					</li>
					@endif
					@if($showRelationshipsMenu)
					<li class="slide">
						<a class="side-menu__item" data-toggle="slide" href="#">
							<i class="side-menu__icon fe fe-users"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.relationships') }}</span>
							<i class="angle fe fe-chevron-down"></i>
						</a>
						<ul class="slide-menu">
							@if($can('suppliers.view'))
							<li><a class="slide-item" href="{{ route('inventory.suppliers.index') }}">{{ __('messages.sidebar.suppliers') }}</a></li>
							@endif
							@if($can('warehouses.view'))
							<li><a class="slide-item" href="{{ route('inventory.warehouses.index') }}">{{ __('messages.sidebar.warehouses') }}</a></li>
							@endif
						</ul>
					</li>
					@endif

					@if($showStoreSection)
					<li class="side-item side-item-category">{{ __('messages.sidebar.category_store') }}</li>
					@endif
					@if($can('orders.view'))
					<li class="slide">
						<a class="side-menu__item" href="{{ route('admin.orders.index') }}">
							<i class="side-menu__icon fe fe-shopping-cart"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.customer_orders') }}</span>
							@php $pendingCount = \App\Models\Order::withoutGlobalScopes()->when($sidebarUser->tenant_id, fn ($q) => $q->where('tenant_id', $sidebarUser->tenant_id))->where('status', 'pending')->count(); @endphp
							@if($pendingCount > 0)
								<span class="badge badge-danger side-badge">{{ $pendingCount }}</span>
							@endif
						</a>
					</li>
					@endif
					@if($can('settings.edit'))
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
					@endif

					@if($showReportsSection)
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
					@endif


					@if($showAccountingSection)
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
					@endif

					@if($showExternalSection)
					<li class="side-item side-item-category">{{ __('messages.sidebar.category_external') }}</li>
					<li class="slide">
						<a class="side-menu__item" href="{{ route('store.index') }}" target="_blank">
							<i class="side-menu__icon fe fe-external-link"></i>
							<span class="side-menu__label">{{ __('messages.sidebar.view_storefront') }}</span>
						</a>
					</li>
					@endif
					<div style="height: 100px;"></div>
				</ul>
			</div>
		</aside>
<!-- main-sidebar -->
