app.component('compaignsList', {
    templateUrl: compaigns_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element, $mdSelect) {
        $scope.loading = true;
        $('#search_service_type').focus();
        var self = this;
        $('li').removeClass('active');
        $('.master_link').addClass('active').trigger('click');
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('service-types')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.add_permission = self.hasPermission('add-service-type');
        var table_scroll;

        table_scroll = $('.page-main-content.list-page-content').height() - 37;
        var dataTable = $('#campaigns_list').DataTable({
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
                    $('#search_service_type').val(state_save_val.search.search);
                }
                return JSON.parse(localStorage.getItem('CDataTables_' + settings.sInstance));
            },
            serverSide: true,
            paging: true,
            stateSave: true,
            scrollY: table_scroll + "px",
            scrollCollapse: true,
            ajax: {
                url: laravel_routes['getCampaignList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.authorization_code = $("#authorization_code").val();
                    d.complaint_code = $("#complaint_code").val();
                    d.fault_code = $("#fault_code").val();
                    d.status = $("#status").val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'vehicle_model', name: 'models.model_name', searchable: true },
                { data: 'authorisation_no', name: 'compaigns.authorisation_no', searchable: true },
                { data: 'complaint_type', name: 'complaints.name', searchable: true },
                { data: 'fault_type', name: 'faults.name', searchable: true },
                { data: 'claim_type_name', name: 'configs.name', searchable: true },
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
            $('#search_service_type').val('');
            $('#campaigns_list').DataTable().search('').draw();
        }
        $('.refresh_table').on("click", function() {
            $('#campaigns_list').DataTable().ajax.reload();
        });

        var dataTables = $('#campaigns_list').dataTable();
        $("#search_service_type").keyup(function() {
            dataTables.fnFilter(this.value);
        });

        //DELETE
        $scope.deleteCampaign = function($id) {
            $('#campaign_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#campaign_id').val();
            $http.get(
                laravel_routes['deleteCampaign'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Compaign Deleted Successfully');
                    $('#campaigns_list').DataTable().ajax.reload(function(json) {});
                    $location.path('/gigo-pkg/compaigns/list');
                }
            });
        }

        // FOR FILTER
        $http.get(
            laravel_routes['getCampaignFilterData']
        ).then(function(response) {
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
            $('#service-type-filter-modal').modal('hide');
        }
        $scope.reset_filter = function() {
            $("#authorization_code").val('');
            $("#complaint_code").val('');
            $("#fault_code").val('');
            $("#status").val('');
            //dataTables.fnFilter();
        }
        $rootScope.loading = false;
    }
});

//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------

app.component('compaignsForm', {
    templateUrl: compaigns_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $element) {
        var self = this;
        $("input:text:visible:first").focus();
        self.hasPermission = HelperService.hasPermission;
        if (!self.hasPermission('add-service-type') || !self.hasPermission('edit-service-type')) {
            window.location = "#!/page-permission-denied";
            return false;
        }
        self.angular_routes = angular_routes;
        $http.get(
            laravel_routes['getCampaignFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            self.campaign = response.data.campaign;
            self.claim_types = response.data.claim_types;
            self.fault_types = response.data.fault_types;
            self.complaint_types = response.data.complaint_types;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                if (self.campaign.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
            } else {
                self.switch_value = 'Active';
            }
        });

        //Search Vehicle Model
        self.searchVehicleModel = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getVehicleModelSearchList'], {
                                key: query,
                            }
                        )
                        .then(function(response) {
                            resolve(response.data);
                        });
                });
            } else {
                return [];
            }
        }

        //Add New Labour
        self.addNewLabour = function() {
            self.campaign.campaign_labours.push({
                pivot: [],
            });
        }

        //Search Labour
        self.searchLabour = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getLabourSearchList'], {
                                key: query,
                            }
                        )
                        .then(function(response) {
                            resolve(response.data);
                        });
                });
            } else {
                return [];
            }
        }

        $scope.getSelectedLabour = function(index, labour_detail) {
            if (labour_detail) {
                $('.labour_type' + index).html(labour_detail.repair_order_type);
                $('#labour_amount' + index).val('');
            } else {
                $('.labour_type' + index).html('-');
                $('#labour_amount' + index).val('');
            }
        }

        self.removeLabour = function(index) {
            self.campaign.campaign_labours.splice(index, 1);
        }

        //Add New Part
        self.addNewPart = function() {
            self.campaign.campaign_parts.push({
                pivot: [],
            });
        }
        //Search Part
        self.searchPart = function(query) {
            if (query) {
                return new Promise(function(resolve, reject) {
                    $http
                        .post(
                            laravel_routes['getPartSearchList'], {
                                key: query,
                            }
                        )
                        .then(function(response) {
                            resolve(response.data);
                        });
                });
            } else {
                return [];
            }
        }

        $scope.getSelectedPart = function(index, part_detail) {
            if (part_detail) {
                $('.part_type' + index).html(part_detail.tax_code_type);
            } else {
                $('.part_type' + index).html('-');
            }
        }

        self.removePart = function(index) {
            self.campaign.campaign_parts.splice(index, 1);
        }

        //Save Form Data 
        var form_id = '#campaign_form';
        var v = jQuery(form_id).validate({
            ignore: '',
            rules: {
                'authorisation_no': {
                    required: true,
                    minlength: 3,
                    maxlength: 32,
                },
                'complaint_id': {
                    required: true,
                },
                'fault_id': {
                    required: true,
                },
                'claim_type_id': {
                    required: true,
                },
                'vehicle_model_id': {
                    required: true,
                },
                'manufacture_date': {
                    required: true,
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
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors, Please check all tabs');
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveCampaign'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            $location.path('/gigo-pkg/compaigns/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('.submit').button('reset');
                                showErrorNoty(res);
                            } else {
                                $('.submit').button('reset');
                                $location.path('/gigo-pkg/compaigns/list');
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