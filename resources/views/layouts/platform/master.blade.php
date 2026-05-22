<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $dir ?? 'ltr' }}">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="Description" content="Platform Console">
		@include('layouts.head')
	</head>
	<body class="main-body app sidebar-mini @if(!empty($isRtl)) rtl @endif">
		<div id="global-loader">
			<img src="{{ URL::asset('assets/img/loader.svg') }}" class="loader-img" alt="Loader">
		</div>
		@include('layouts.platform.sidebar')
		<div class="main-content app-content">
			@include('layouts.main-header')
			<div class="container-fluid">
				@yield('page-header')
				@yield('content')
				@include('layouts.footer')
				@include('layouts.footer-scripts')
			</div>
		</div>
	</body>
</html>
