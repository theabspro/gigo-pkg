@if(config('gigo-pkg.DEV'))
    <?php $gigo_pkg_prefix = '/packages/abs/gigo-pkg/src';?>
@else
    <?php $gigo_pkg_prefix = '';?>
@endif

<script type="text/javascript">
	app.config(['$routeProvider', function($routeProvider) {

	    $routeProvider.
	    //Mobile Simulation
	    when('/gigo-pkg/mobile/login', {
	        template: '<mobile-login></mobile-login>',
	        title: 'Mobile Login',
	    }).
	    when('/gigo-pkg/mobile/dashboard', {
	        template: '<mobile-dashboard></mobile-dashboard>',
	        title: 'Mobile Dashboard',
	    }).
	    when('/gigo-pkg/mobile/menus', {
	        template: '<mobile-menus></mobile-menus>',
	        title: 'Mobile Menus',
	    }).
	    when('/gigo-pkg/mobile/kanban-dashboard', {
	        template: '<mobile-kanban-dashboard></mobile-kanban-dashboard>',
	        title: 'KANBAN Dashboard',
	    }).
	    when('/gigo-pkg/mobile/attendance/scan-qr', {
	        template: '<mobile-attendance-scan-qr></mobile-attendance-scan-qr>',
	        title: 'Mobile Dashboard',
	    }).

	     //Repair Order Types
	    when('/gigo-pkg/repair-order-type/list', {
	        template: '<repair-order-type-list></repair-order-type-list>',
	        title: 'Repair Order Type',
	    }).
	    when('/gigo-pkg/repair-order-type/add', {
	        template: '<repair-order-type-form></repair-order-type-form>',
	        title: 'Add Repair Order Type',
	    }).
	    when('/gigo-pkg/repair-order-type/edit/:id', {
	        template: '<repair-order-type-form></repair-order-type-form>',
	        title: 'Edit Repair Order Type',
	    }).
	    when('/gigo-pkg/repair-order-type/view/:id', {
	        template: '<repair-order-type-view></repair-order-type-view>',
	        title: 'View Repair Order Type',
	    }).

	    //Job Cards
	    when('/gigo-pkg/job-card/list', {
	        template: '<job-card-list></job-card-list>',
	        title: 'Job Cards',
	    }).
	    when('/gigo-pkg/job-card/add', {
	        template: '<job-card-form></job-card-form>',
	        title: 'Add Job Card',
	    }).
	    when('/gigo-pkg/job-card/edit/:id', {
	        template: '<job-card-form></job-card-form>',
	        title: 'Edit Job Card',
	    }).
	    when('/gigo-pkg/job-card/view/:id', {
	        template: '<job-card-view></job-card-view>',
	        title: 'View Job Card',
	    }).

	    //SHIFTS
	    when('/gigo-pkg/shift/list', {
	        template: '<shift-list></shift-list>',
	        title: 'Shifts',
	    }).
	    when('/gigo-pkg/shift/add', {
	        template: '<shift-form></shift-form>',
	        title: 'Add Shift',
	    }).
	    when('/gigo-pkg/shift/edit/:id', {
	        template: '<shift-form></shift-form>',
	        title: 'Edit Shift',
	    });	   

	}]);

    var mobile_login_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/login.html')}}";
    var mobile_dashboard_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/dashboard.html')}}";
    var mobile_menus_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/menus.html')}}";
    var mobile_kanban_dashboard_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/kanban-dashboard.html')}}";
    var mobile_attendance_scan_qr_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/attendance/scan-qr.html')}}";

	//Repair Orders
    /*var repair_order_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order/list.html')}}";
    var repair_order_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order/form.html')}}";*/

	//Job Cards
    var job_card_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/list.html')}}";
    var job_card_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/form.html')}}";
    var job_card_view_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/view.html')}}";

    //Repair Order Types
    var repair_order_type_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order-type/list.html')}}";
    var repair_order_type_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order-type/form.html')}}";
    var repair_order_type_view_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order-type/view.html')}}";

    //SHIFTS
    var shift_list_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/shift/list.html')}}";
    var shift_form_template_url = "{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/shift/form.html')}}";

</script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/job-card/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order-type/controller.js')}}"></script>
<!-- <script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/repair-order/controller.js')}}"></script> -->
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/mobile/controller.js')}}"></script>
<script type="text/javascript" src="{{asset($gigo_pkg_prefix.'/public/themes/'.$theme.'/gigo-pkg/shift/controller.js')}}"></script>

