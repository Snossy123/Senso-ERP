@extends('layouts.master2')
@section('css')
<link href="{{URL::asset('assets/plugins/sidemenu-responsive-tabs/css/sidemenu-responsive-tabs.css')}}" rel="stylesheet">
@endsection
@section('content')
		<div class="container-fluid">
			<div class="row no-gutter">
				<div class="col-md-6 col-lg-6 col-xl-7 d-none d-md-flex bg-primary-transparent">
					<div class="row wd-100p mx-auto text-center">
						<div class="col-md-12 col-lg-12 col-xl-12 my-auto mx-auto wd-100p">
							<img src="{{URL::asset('assets/img/media/forgot.png')}}" class="my-auto ht-xl-80p wd-md-100p wd-xl-80p mx-auto" alt="logo">
						</div>
					</div>
				</div>
				<div class="col-md-6 col-lg-6 col-xl-5 bg-white">
					<div class="login d-flex align-items-center py-2">
						<div class="container p-0">
							<div class="row">
								<div class="col-md-10 col-lg-10 col-xl-9 mx-auto">
								<div class="mb-5 d-flex"> <a href="{{ url('/' . $page='index') }}"><img src="{{URL::asset('assets/img/brand/favicon.png')}}" class="sign-favicon ht-40" alt="logo"></a><h1 class="main-logo1 ml-1 mr-0 my-auto tx-28">Va<span>le</span>x</h1></div>
									<div class="main-card-signin d-md-flex bg-white">
										<div class="wd-100p">
											<div class="main-signin-header">
												<h2>{{ __('auth_pages.forgot.title') }}</h2>
												<h4>{{ __('auth_pages.forgot.subtitle') }}</h4>
												<form action="#">
													<div class="form-group">
														<label>{{ __('auth_pages.forgot.email') }}</label> <input class="form-control" placeholder="{{ __('auth_pages.forgot.email_placeholder') }}" type="text">
													</div>
													<button class="btn btn-main-primary btn-block">{{ __('auth_pages.forgot.send') }}</button>
												</form>
											</div>
											<div class="main-signup-footer mg-t-20">
												<p>{{ __('auth_pages.forgot.footer') }} <a href="#">{{ __('auth_pages.forgot.back_signin') }}</a> {{ __('auth_pages.forgot.to_signin') }}</p>
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
