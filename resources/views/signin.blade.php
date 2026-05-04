@extends('layouts.master2')
@section('css')
<!-- Sidemenu-respoansive-tabs css -->
<link href="{{URL::asset('assets/plugins/sidemenu-responsive-tabs/css/sidemenu-responsive-tabs.css')}}" rel="stylesheet">
@endsection
@section('content')
		<div class="container-fluid">
			<div class="row no-gutter">
				<!-- The image half -->
				<div class="col-md-6 col-lg-6 col-xl-7 d-none d-md-flex bg-primary-transparent">
					<div class="row wd-100p mx-auto text-center">
						<div class="col-md-12 col-lg-12 col-xl-12 my-auto mx-auto wd-100p">
							<img src="{{URL::asset('assets/img/media/login.png')}}" class="my-auto ht-xl-80p wd-md-100p wd-xl-80p mx-auto" alt="logo">
						</div>
					</div>
				</div>
				<!-- The content half -->
				<div class="col-md-6 col-lg-6 col-xl-5 bg-white">
					<div class="login d-flex align-items-center py-2">
						<!-- Demo content-->
						<div class="container p-0">
							<div class="row">
								<div class="col-md-10 col-lg-10 col-xl-9 mx-auto">
									<div class="card-sigin">
										<div class="mb-5 d-flex"> <a href="{{ route('dashboard') }}"><img src="{{URL::asset('assets/img/brand/favicon.png')}}" class="sign-favicon ht-40" alt="logo"></a><h1 class="main-logo1 ml-1 mr-0 my-auto tx-28">Senso <span>ERP</span></h1></div>
										<div class="card-sigin">
											<div class="main-signup-header">
												<h2>{{ __('auth_pages.signin.welcome') }}</h2>
												<h5 class="font-weight-semibold mb-4">{{ __('auth_pages.signin.subtitle') }}</h5>
												<form action="{{ route('login') }}" method="POST">
													@csrf
													@if($errors->any())
														<div class="alert alert-danger mb-4">
															<ul class="mb-0">@foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
														</div>
													@endif
													<div class="form-group">
														<label>{{ __('auth_pages.signin.email') }}</label> <input class="form-control" name="email" placeholder="{{ __('auth_pages.signin.email_placeholder') }}" type="email" value="{{ old('email') }}" required autofocus>
													</div>
													<div class="form-group">
														<label>{{ __('auth_pages.signin.password') }}</label> <input class="form-control" name="password" placeholder="{{ __('auth_pages.signin.password_placeholder') }}" type="password" required>
													</div>
													<div class="form-group">
														<div class="checkbox">
															<div class="custom-checkbox custom-control">
																<input type="checkbox" name="remember" data-checkboxes="mygroup" class="custom-control-input" id="checkbox-1">
																<label for="checkbox-1" class="custom-control-label mt-1">{{ __('auth_pages.signin.remember') }}</label>
															</div>
														</div>
													</div>
													<button type="submit" class="btn btn-main-primary btn-block">{{ __('auth_pages.signin.submit') }}</button>
												</form>
											</div>
										</div>
												<div class="main-signin-footer mt-5">
													<p><a href="">{{ __('auth_pages.signin.forgot') }}</a></p>
													<p>{{ __('auth_pages.signin.no_account') }} <a href="{{ url('/' . $page='signup') }}">{{ __('auth_pages.signin.create_account') }}</a></p>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div><!-- End -->
					</div>
				</div><!-- End -->
			</div>
		</div>
@endsection
@section('js')
@endsection
