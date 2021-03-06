app.component('vehicleInventoryItemList', {
    templateUrl: vehicle_inventory_item_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_vehicle_inventory_item').focus();
        var self = this;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('vehicle-inventory-items')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.add_permission = self.hasPermission('add-vehicle-inventory-item');
        $('.page-main-content.list-page-content').css("overflow-y", "auto");
        var dataTable = $('#vehicle_inventory_items_list').DataTable({
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
                    $('#search_vehicle_inventory_item').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            ajax: {
                url: laravel_routes['getVehicleInventoryItemList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.code = $("#code").val();
                    d.name = $("#name").val();
                    d.field_type = $("#field_type").val();
                    d.status = $("#status").val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'code', name: 'vehicle_inventory_items.code' },
                { data: 'name', name: 'vehicle_inventory_items.name' },
                { data: 'field_type', name: 'field_types.name', searchable: true },
                { data: 'status', name: '', searchable: false },

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
            $('#search_vehicle_inventory_item').val('');
            $('#vehicle_inventory_items_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#vehicle_inventory_items_list').DataTable().ajax.reload();
        });

        var dataTables = $('#vehicle_inventory_items_list').dataTable();
        $("#search_vehicle_inventory_item").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deleteVehicleInventoryItem = function($id) {
            $('#vehicle_inventory_item_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#vehicle_inventory_item_id').val();
            $http.get(
                laravel_routes['deleteVehicleInventoryItem'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Vehicle Inventory Item Deleted Successfully');
                    $('#vehicle_inventory_items_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/gigo-pkg/vehicle-inventory-item/list');
                }
            });
        }

        // FOR FILTER
        $http.get(
            laravel_routes['getVehicleInventoryItemFilterData']
        ).then(function(response) {
            // console.log(response);
            self.extras = response.data.extras;
            self.vehicle_inventory_item = response.data.vehicle_inventory_item;
            self.field_type_list = response.data.field_type_list;
            self.field_type_selected = '';
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

        $scope.onSelectedFieldType = function(field_type_selected) {
            $('#field_type').val(field_type_selected);
        }

        $scope.applyFilter = function() {
            $('#status').val(self.status);
            dataTables.fnFilter();
            $('#vehicle-inventory-item-filter-modal').modal('hide');
        }
        $scope.reset_filter = function() {
            $("#code").val('');
            $("#name").val('');
            $("#field_type").val('');
            $("#status").val('');
            $('#vehicle-inventory-item-filter-modal').modal('hide');
            dataTables.fnFilter();
        }
        // $scope.apply_filter = function() {
        //     dataTables.fnFilter();
        // }

        $rootScope.loading = false;
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('vehicleInventoryItemForm', {
    templateUrl: vehicle_inventory_item_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        $("input:text:visible:first").focus();
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-vehicle-inventory-item') || !self.hasPermission('edit-vehicle-inventory-item')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getVehicleInventoryItemFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            self.vehicle_inventory_item = response.data.vehicle_inventory_item;
            self.extras = response.data.extras;
            // console.log(self.extras);
            // return;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.vehicle_inventory_item.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
            } else {
                self.switch_value = 'Active';
            }
        });

        //Save Form Data 
        var form_id = '#vehicle_inventory_item_form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'code': {
                    required: true,
                    minlength: 3,
                    maxlength: 32,
                },
                'name': {
                    minlength: 3,
                    maxlength: 191,
                },
            },
            messages: {
                'code': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 32 Characters',
                },
                'name': {
                    minlength: 'Minimum 3 Characters',
                    maxlength: 'Maximum 191 Characters',
                },
            },
            // invalidHandler: function(event, validator) {
            //     custom_noty('error', 'You have errors, Please check all tabs');
            // },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveVehicleInventoryItem'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            $location.path('/gigo-pkg/vehicle-inventory-item/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                            } else {
                                $('.submit').button('reset');
                                $location.path('/gigo-pkg/vehicle-inventory-item/list');
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