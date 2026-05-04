@extends('layouts.master2')
@section('css')
<!-- Sidemenu-respoansive-tabs css -->
<link href="{{URL::asset('assets/plugins/sidemenu-responsive-tabs/css/sidemenu-responsive-tabs.css')}}" rel="stylesheet">
@endsection
@section('content')
		<div class="container-fluid">
			<div class="row no-gutter">
				<div class="col-md-6 col-lg-6 col-xl-7 d-none d-md-flex bg-primary-transparent">
					<div class="row wd-100p mx-auto text-center">
						<div class="col-md-12 col-lg-12 col-xl-12 my-auto mx-auto wd-100p">
							<img src="{{URL::asset('assets/img/media/login.png')}}" class="my-auto ht-xl-80p wd-md-100p wd-xl-80p mx-auto" alt="logo">
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-6 col-xl-5 bg-white">
					<div class="login d-flex align-items-center py-2">
						<div class="container p-0">
							<div class="row">
								<div class="col-md-10 col-lg-10 col-xl-9 mx-auto">
									<div class="card-sigin">
										<div class="mb-5 d-flex"> <a href="{{ url('/' . $page='index') }}"><img src="{{URL::asset('assets/img/brand/favicon.png')}}" class="sign-favicon ht-40" alt="logo"></a><h1 class="main-logo1 ml-1 mr-0 my-auto tx-28">Va<span>le</span>x</h1></div>
										<div class="main-signup-header">
											<h2 class="text-primary">{{ __('auth_pages.signup.title') }}</h2>
											<h5 class="font-weight-normal mb-4">{{ __('auth_pages.signup.subtitle') }}</h5>
											<form action="#">
												<div class="form-group">
													<label>{{ __('auth_pages.signup.fullname') }}</label> <input class="form-control" placeholder="{{ __('auth_pages.signup.fullname_placeholder') }}" type="text">
												</div>
												<div class="form-group">
													<label>{{ __('auth_pages.signup.email') }}</label> <input class="form-control" placeholder="{{ __('auth_pages.signup.email_placeholder') }}" type="text">
												</div>
												<div class="form-group">
													<label>{{ __('auth_pages.signup.password') }}</label> <input class="form-control" placeholder="{{ __('auth_pages.signup.password_placeholder') }}" type="password">
												</div><button class="btn btn-main-primary btn-block">{{ __('auth_pages.signup.submit') }}</button>
												<div class="row row-xs">
													<div class="col-sm-6">
														<button class="btn btn-block"><i class="fab fa-facebook-f"></i> {{ __('auth_pages.signup.facebook') }}</button>
													</div>
													<div class="col-sm-6 mg-t-10 mg-sm-t-0">
														<button class="btn btn-info btn-block"><i class="fab fa-twitter"></i> {{ __('auth_pages.signup.twitter') }}</button>
													</div>
												</div>
											</form>
											<div class="main-signup-footer mt-5">
												<p>{{ __('auth_pages.signup.have_account') }} <a href="{{ url('/' . $page='signin') }}">{{ __('auth_pages.signup.sign_in_link') }}</a></p>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
@endsection
@section('js')
@endsection
