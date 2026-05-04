<meta name="csrf-token" content="{{ csrf_token() }}">
<title> @yield('title') - Senso ERP </title>
<!-- Favicon -->
<link rel="icon" href="{{URL::asset('assets/img/brand/favicon.png')}}" type="image/x-icon"/>
<!-- Icons css -->
<link href="@localizedAsset('icons.css')" rel="stylesheet">
<!--  Custom Scroll bar-->
<link href="{{URL::asset('assets/plugins/mscrollbar/jquery.mCustomScrollbar.css')}}" rel="stylesheet"/>
<!--  Sidebar css -->
<link href="{{URL::asset('assets/plugins/sidebar/sidebar.css')}}" rel="stylesheet">
<!-- Sidemenu css -->
<link rel="stylesheet" href="@localizedAsset('sidemenu.css')">
@yield('css')
@if(!empty($isRtl))
<link href="{{URL::asset('assets/plugins/sumoselect/sumoselect-rtl.css')}}" rel="stylesheet">
<link href="{{URL::asset('assets/plugins/telephoneinput/telephoneinput-rtl.css')}}" rel="stylesheet">
<link href="{{URL::asset('assets/plugins/treeview/treeview-rtl.css')}}" rel="stylesheet">
<link href="{{URL::asset('assets/plugins/fullcalendar/fullcalendar.min-rtl.css')}}" rel="stylesheet">
@endif
<!--- Style css -->
<link href="@localizedAsset('style.css')" rel="stylesheet">
<!--- Dark-mode css -->
<link href="@localizedAsset('style-dark.css')" rel="stylesheet">
<!---Skinmodes css-->
<link href="@localizedAsset('skin-modes.css')" rel="stylesheet">
<!-- Alpine.js -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
