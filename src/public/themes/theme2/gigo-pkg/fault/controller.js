app.component('faultList', {
    templateUrl: fault_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_fault').focus();
        var self = this;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('faults')) {
            window.location = "#!/permission-denied";
            return false;
        }
        self.add_permission = self.hasPermission('faults');
        $('.page-main-content.list-page-content').css("overflow-y", "auto");
        var dataTable = $('#faults_list').DataTable({
            "dom": cndn_dom_structure,
            "language": {
                // "search": "",
                // "searchPlaceholder": "Search",
                "lengthMenu": "Rows _MENU_",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            pageLength: 10,
            processing: true,
            stateSaveCallback: function(settings, data) {
                localStorage.setItem('CDataTables_' + settings.sInstance, JSON.stringify(data));
            },
            stateLoadCallback: function(settings) {
                var state_save_val = JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
                if (state_save_val) {
                    $('#search_fault').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            ajax: {
                url: laravel_routes['getFaultList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.code = $("#code").val();
                    d.name = $("#name").val();
                    d.status = $("#status").val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'code', name: 'faults.code' },
                { data: 'name', name: 'faults.name' },
                { data: 'status', name: '' },

            ],
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_infos').html(total)
                $('.foot_info').html('Showing ' + start + ' to ' + end + ' of ' + max + ' entries')
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        $('.dataTables_length select').select2();

        $scope.clear_search = function() {
            $('#search_fault').val('');
            $('#faults_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#faults_list').DataTable().ajax.reload();
        });

        var dataTables = $('#faults_list').dataTable();
        $("#search_fault").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deleteFault = function($id) {
            $('#fault_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#fault_id').val();
            $http.get(
                laravel_routes['deleteFault'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Fault Deleted Successfully');
                    $('#faults_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/gigo-pkg/fault/list');
                }
            });
        }

        // FOR FILTER
        $http.get(
            laravel_routes['getFaultFilter']
        ).then(function(response) {
            // console.log(response);
            self.extras = response.data.extras;
        });
        $element.find('input').on('keydown', function(ev) {
            ev.stopPropagation();
        });
        $scope.clearSearchTerm = function() {
            $scope.searchTerm = '';
            $scope.searchTerm1 = '';
            $scope.searchTerm2 = '';
            $scope.searchTerm3 = '';
        };
        /* Modal Md Select Hide */
        $('.modal').bind('click', function(event) {
            if ($('.md-select-menu-container').hasClass('md-active')) {
                $mdSelect.hide();
            }
        });

        $scope.applyFilter = function() {
            $('#status').val(self.status);
            dataTables.fnFilter();
            $('#fault-filter-modal').modal('hide');
        }

        $scope.reset_filter = function() {
            $("#code").val('');
            $("#name").val('');
            $("#status").val('');
            dataTables.fnFilter();
            $('#fault-filter-modal').modal('hide');
        }
        $rootScope.loading = false;
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('faultForm', {
    templateUrl: fault_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        $("input:text:visible:first").focus();

        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-fault') && !self.hasPermission('edit-fault')) {
            window.location = "#!/permission-denied";
            return false;
        }

        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getFaultFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            self.fault = response.data.fault;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.fault.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
            } else {
                self.switch_value = 'Active';
            }
        });

        //Save Form Data 
        var form_id = '#fault_form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'code': {
                    required: true,
                    minlength: 2,
                    maxlength: 32,
                },
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 191,
                },
            },
            messages: {
                'code': {
                    minlength: 'Minimum 2 Characters',
                    maxlength: 'Maximum 32 Characters',
                },
                'name': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 191 Characters',
                },
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check the tab');
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveFault'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            $location.path('/gigo-pkg/fault/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                            } else {
                                $('.submit').button('reset');
                                $location.path('/gigo-pkg/fault/list');
                                $scope.$apply();
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------