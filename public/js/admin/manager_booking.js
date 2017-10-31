axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

var manage_service = new Vue({
    el: '#manager_booking',
 
    data: {
        users: {},
        token: {},
        items: [],
        listBookings: [],
        show_input: {},
        formErrors: {},
        showDepartments:{},
        booking: {},
        departments: [],
        stylists:[],
        services:[],
        listSevice: [],
        listBill: [],
        bill: {'customer_id': '',
                'phone': '', 
                'status': 0,
                'customer_name': '',
                'order_booking_id': '',
                'order_id': '',
                'grand_total': 0,
                'service_name': '',
                'department_id': '',
                'stylist_id': '',
                'price': '',
                'service_product_id': '',
                'bill_items': [],
                'images': [],
            },
        changer_status_booking:{'id': '', 'status': '', 'message': ''},
        billItem: {'qty': 1, 'price': '', 'stylist_id': '', 'service_product_id': '', 'order_booking_id': ''},
        isEditBillItem: {'status': false, 'index' : ''},
        billItems: [],
        orderItems: [],
        billSuccess: {},
        logStatus: [],
        formErrorsUpdate: {},
        newItem: {},
        params: {'type': '', 'start_date': '', 'end_date': ''},
        start_date: '',
        end_date: '',
        status: '',
        images: [],
        imageData: {'order_booking_id': '', 'images': []},
        statusCreatedBill: 0,
    },
    mounted : function(){
        this.showDepartment();
        this.show_input.start = false;
        this.show_input.end = false;
        this.users = Vue.ls.get('user', {});
        this.token = Vue.ls.get('token', {});

        var timestamp = new Date().getTime() / 1000 | 0;
        this.params.start_date = timestamp;
        this.params.end_date = timestamp;

        var curentDay = new Date().toISOString().slice(0, 10);
        this.start_date = curentDay;
        this.end_date = curentDay;
        this.getBooking();
        this.showDepar();

        this.showDateFrom();
        this.kanbanBoard();
        // popover
        setTimeout(function(){
            $('[data-toggle="popover"]').popover({
                html : true,
                container: 'body',
                content: "<div class='pop-over-content js-pop-over-content u-fancy-scrollbar js-tab-parent' style='max-height: 163px;'><div><ul class='pop-over-list'><li><a href='javascript:void(0)' v-if='list.status == 4' data-toggle='modal' data-target='#chooseImg' id='uploadimg'>Upload Photos</a></li><li><a href='#' class='js-copy-list'>Edit booking</a></li></ul></div></div>",
            }).children().delegate('#uploadimg', 'click', function() {
            });  
        }, 1000);
        // end popoverS
    },
    
    methods: {
        resetData: function() {
            this.bill = {'customer_id': '',
                'phone': '', 
                'status': 0,
                'customer_name': '',
                'order_booking_id': '',
                'order_id': '',
                'grand_total': 0,
                'service_name': '',
                'department_id': '',
                'stylist_id': '',
                'price': '',
                'service_product_id': '',
                'bill_items': []
            },
            this.billItems = [];

            this.billItem = {'qty': 1, 'price': '', 'stylist_id': '', 'service_product_id': ''};
            this.booking = {};
            this.isEditBillItem = {'status': false, 'index' : ''};
        },
        today: function() {
            var curentDay = new Date().toISOString().slice(0, 10);
            this.start_date = curentDay;
            this.end_date = curentDay;
            var timestamp = new Date().getTime() / 1000 | 0;
            this.params.start_date = timestamp;
            this.params.end_date = timestamp;
            this.getBooking();
        },
        showBooking: function() {
            $('#showBooking').modal('show');
        },
        showImage: function(id) {
            this.imageData.order_booking_id = id;
            $('.popover').hide();
        },
        executeImages: function(e) {
            var files = e.target.files || e.dataTransfer.files;
            var folder = "user-"+ this.imageData.order_booking_id;
            if (!files.length) {
                return
            }
            const formData = new FormData()
            for(var i=0; i< files.length;i++){
                formData.append('images['+i+']', files[i]);
                formData.append('name['+i+']', files[i].name);
            }
            formData.append('order_booking_id', this.imageData.order_booking_id)
            axios.post('/api/v0/media-upload/'+folder, formData)
                .then( response => {
                    var path = [];
                    for(var i=0; i< response.data.data.length;i++){
                        var str = response.data.data[i];
                        var index = str.indexOf("uploads");
                        path.push(str.substring(index));
                    }
                    this.$set(this, 'images', path);
            }).catch(function (error) {
            }); 
        },
        submitImages: function(e) {
            if (!confirm('Do you want to Upload Images for Cusotmer?')) return;
                this.imageData.images = JSON.stringify(this.images);
                var authOptions = {
                    method: 'post',
                    url: '/api/v0/order-booking/stylist-upload-image',
                    params: this.imageData,
                    headers: {
                        'Authorization': "Bearer " + this.token.access_token
                    },
                    json: true
            }
            axios(authOptions).then(response => {
                location.reload();
            }).catch(function (error) {
            });
        },
        curent_time: function(data) {
            let today = new Date().getTime();
            var min = today - (new Date(data[0].time_start).getTime());
            min_current_time = [];
            for(key in data){
                var timestamp = new Date(data[key].time_start).getTime();
                var time = today - timestamp;
                if (time < 0) {
                    data[key]['status_time'] = 0;
                } else {
                    data[key]['status_time'] = 1;
                }
                data[key]['current_time'] = time;
            }

            return data.sort(function(a, b){
                return b.current_time - a.current_time;   
            }).sort(function(a, b){
                return a.status_time - b.status_time;
            });
        },

        convertHourMinute: function(data) {
            data = String(data);
            var split = data.split(' ');
            return split[1];
        },
        
        getBooking: function() {
            $('.list-booking-indicator').removeClass('hide');
            var authOptions = {
                method: 'get',
                url: '/api/v0/filter-order-booking',
                params: this.params,
                headers: {
                    'Authorization': "Bearer " + this.token.access_token
                }
            }
            axios(authOptions).then(response => {
                if(response.data.data[0].list_book.length > 0) {
                    response.data.data[0].list_book = this.curent_time(response.data.data[0].list_book);
                }
                let listBookings = response.data.data;

                let groupBookingFormat = [];

                for (let j = 0; j < listBookings.length; j++) {

                    let listBooking = listBookings[j].list_book;
                    let lengthBooking = listBooking.length;
                    let groupBookings = {list_book: [], date_book: listBookings[j].date_book};
                    let groupBooking = listBooking[0] ? [listBooking[0]] : [];

                    for(let i = 1 ; i < lengthBooking; i++ ) {
                        if (groupBooking[0].time_start == listBooking[i].time_start) {
                            groupBooking.push(listBooking[i])
                        } else {
                            groupBookings.list_book.push(groupBooking);
                            groupBooking = [listBooking[i]];
                        }
                    }

                    groupBooking.length ? groupBookings.list_book.push(groupBooking) : '';
                    groupBookingFormat.push(groupBookings);
                }
                this.start_date = response.data.data[0].startDate;
                this.end_date = response.data.data[0].endDate;

                this.$set(this, 'items', groupBookingFormat);
               
                $('.list-booking-indicator').addClass('hide');
            }).catch(function (error) {
                $('.list-booking-indicator').addClass('hide');
            });
        },
        
        changer_status(targetId, bookingId){
            this.changer_status_booking.status = targetId;
            this.changer_status_booking.id = bookingId;
            $('#update_status').modal('show');
        },

        update_status: function(targetId, bookingId){
            var self = this;
            var authOptions = {
                    method: 'PUT',
                    url: '/api/v0/change-status-booking/' + bookingId,
                    params: {status: targetId, message: this.changer_status_booking.message},
                    headers: {
                        'Authorization': "Bearer " + this.token.access_token,
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    json: true
                }


            axios(authOptions).then((response) => {
                this.changer_status_booking = {'id': '', 'status': ''},

                    toastr.success('Update Booking Success', 'Success', {timeOut: 5000});
                    this.getBooking();
                    $('#update_status').modal('hide');
            }).catch((error) => {
                    if (error.response.status == 403) {
                        self.formErrors = error.response.data.message;
                        for (key in self.formErrors) {
                            toastr.error(self.formErrors[key], '', {timeOut: 10000});
                        }    
                    }
            });
        },

        selectDay: function(event){
            var value = event.target.value;
            this.params.type = value;

            this.show_input.start = false;
            this.show_input.end = false;
            if (value == 'day') {
                this.show_input.start = true;
                this.show_input.end = false;
            }
            if (value == 'space') {
                this.show_input.start = true;
                this.show_input.end = true;
            }
            this.getBooking();
        },

        selectStartDay: function(event) {
            var timestamp = new Date(event.target.value).getTime() / 1000 | 0;
            this.params.start_date = timestamp;
            this.getBooking();
        },

        selectEndDay: function(event) {
            var timestamp = new Date(event.target.value).getTime() / 1000 | 0;
            this.params.end_date = timestamp;
            this.getBooking();
        },

        selectStatus: function(event) {
            var arrStatus = $(event.target).val();
            if (!arrStatus) {
                this.params.status = '';
            } else {
                this.params.status = arrStatus.join(',');
            }
            this.getBooking();
        },
        showDepar: function() {
            axios.get('/api/v0/department').then(response => {
                this.$set(this, 'showDepartments', response.data.data);
            })
        },
        selectDepartment: function(event) {
            this.params.department_id = event.target.value;
            this.getBooking();
        },

        addService: function(){

            var error = false;
            if (!this.billItem.stylist_id) {
                toastr.error('Please select stylist!', '', {timeOut: 5000});
                error = true;
            }
            if (!this.billItem.service_product_id) {
                toastr.error('Please select service!', '', {timeOut: 5000});
                error = true;
            }
            if (this.billItem.qty <= 0) {
                toastr.error('Please add qty service smallest is 1!', '', {timeOut: 5000});
                error = true;
            }
            if (error) return;

            var issetBillItem = false;
            for (var i = this.orderItems.get_order_items.length - 1; i >= 0; i--) {
                var tmpBill = this.orderItems.get_order_items[i];
                if (tmpBill.service_product_id == this.billItem.service_product_id
                    && tmpBill.stylist_id == this.billItem.stylist_id
                ) {
                    tmpBill.qty = parseInt(tmpBill.qty) + parseInt(this.billItem.qty);
                    issetBillItem  = true;
                    this.billItems[i] = tmpBill;
                    break;
                }
            }
            if (!issetBillItem) {
                this.billItem.order_booking_id = this.bill.order_booking_id;
                this.billItem.qty = parseInt(this.billItem.qty);
                this.billItem.row_total = this.billItem.price * parseInt(this.billItem.qty);
                this.orderItems.get_order_items.push(this.billItem);
            }
            
            this.billItem = {'qty': 1, 'price': '', 'stylist_id': '', 'service_product_id': ''};
            this.grand_total();
        },
        grand_total: function(){
            var grand_total = 0;
            for (var i = 0; i < this.billItems.length; i++) {
                this.billItems[i].row_total = parseInt(this.billItems[i].qty) * this.billItems[i].price;
                grand_total += this.billItems[i].row_total;
            }
            this.bill.grand_total = grand_total.toLocaleString('de-DE');
        },
        showDepartment: function(page) {
            axios.get('/api/v0/department').then(response => {
                this.$set(this, 'departments', response.data.data);
            });
        },
        showStylist: function(page) {
            var authOptions = {
                method: 'get',
                url: '/api/v0/get-stylist-by-salonId/' + this.bill.department_id,
                params: this.params,
                headers: {
                    'Authorization': "Bearer " + this.token.access_token
                }
            }
            axios(authOptions).then(response => {
                this.$set(this, 'stylists', response.data.data);
            }).catch(error => {
                this.$set(this, 'stylists', []);
            });
        },
        getNameStylist: function(stylist_id) {
            var stylistName = '';
            for (var i = 0; i < this.stylists.length; i++) {
                if (this.stylists[i].id == stylist_id) {
                    stylistName = this.stylists[i].name;
                    break;
                }
            }

            return stylistName;
        },
        getNameService: function(service_id) {
            var serviceName = '';
            for (var i = 0; i < this.services.length; i++) {
                if (this.services[i].id == service_id) {
                    serviceName = this.services[i].name;
                    break;
                }
            }

            return serviceName;
        },

        showService: function(){
            var authOptions = {
                method: 'GET',
                url: '/api/v0/service',
                headers: {
                    'Authorization': "Bearer " + this.token.access_token,
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                json: true
            }
            axios(authOptions).then(response => {
                this.$set(this, 'services', response.data.data);
            }).catch((error) => {
            });
        },
        select_service: function(event){
            var service_id = event.target.value;
            var service = {};
            for (var i = 0; i < this.services.length; i++) {
                if (this.services[i].id == service_id) {
                    service = this.services[i];
                    break;
                }
            }
            this.bill.service_product_id = service_id;
            this.bill.service_name = service.name;
            this.bill.price = service.price;
            this.billItem.price = service.price;
            this.billItem.service_name = service.name;
        },
        select_stylist: function(event){
            var stylist_id = event.target.value;
            this.bill.stylist_id = stylist_id;
            this.billItem.stylist_name = this.getNameStylist(stylist_id);
            this.billItem.service_id = stylist_id;
        },
        editBillItem: function(key) {
            if (this.isEditBillItem.status) {
                toastr.error('Please update service is editing!', '', {timeOut: 5000});
                return;
            }

            this.isEditBillItem = {'status': true, 'index': key};
            this.orderItems.get_order_items = this.billItems[key];
        },
        submitEditBillItem: function(key) {
            this.isEditBillItem = {'status': false, 'index': ''};

            var issetBillItem = false;
            for (var i = this.orderItems.length - 1; i >= 0; i--) {
                if (i == key) continue;
                var tmpBill = this.orderItems.get_order_items[i];
                if (tmpBill.service_product_id == this.billItem.service_product_id
                    && tmpBill.stylist_id == this.billItem.stylist_id
                ) {
                    tmpBill.qty = parseInt(tmpBill.qty) + parseInt(this.billItem.qty);
                    issetBillItem  = true;
                    this.billItems[i] = tmpBill;
                    this.billItems.splice(key, 1);
                    break;
                }
            }

            if (!issetBillItem) {
                this.billItems[key] = this.billItem;
                this.billItem.row_total = this.billItem.price * parseInt(this.billItem.qty);
            }
            
            this.billItem = {'qty': 1, 'price': '', 'stylist_id': '', 'service_product_id': ''};
            this.grand_total();
        },
        deleteBillItem: function(key, id) {
            if (!confirm('Do you want to delete this service!')) return;
            var order_item_id = id;
            var authOptions = {
                method: 'DELETE',
                url: '/api/v0/add-booking-service/' + order_item_id,
                headers: {
                    'Authorization': "Bearer " + this.token.access_token,
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                json: true
            }
            axios(authOptions).then(response => {
                this.getListBill(this.booking.id);
                toastr.error(response.data.message, '', {timeOut: 5000});
            }).catch((error) => {
                this.orderItems.get_order_items.splice(key, 1);
                toastr.error(response.data.message, '', {timeOut: 5000});
            });
        },

        bookingDetail: function(list) {
            this.showService();
            this.status = list.status
            var ok = list.phone;
            var self = this;
            var authOptions = {
                method: 'GET',
                url: '/api/v0/get_last_booking_by_phone',
                params: {'phone': ok},
                headers: {
                    'Authorization': "Bearer " + this.token.access_token,
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                json: true
            }
            axios(authOptions).then(response => {
                this.bill.customer_name = response.data.data.name;
                this.bill.phone = response.data.data.phone;
                this.bill.department_id = response.data.data.department.id;
                this.booking = response.data.data;
                this.bill.order_booking_id = this.booking.id;
                this.bill.order_id = this.booking.id;
                this.bill.images = response.data.data.images;
                console.log(this.bill.images);
                this.billItem.order_booking_id = this.booking.id;
                this.formErrors.phone = '';
                this.getListBill(this.booking.id);
                $('#showBill').modal('show');
                this.showStylist();
            }).catch((error) => {   
            });
        },
        createBill: function(event){
            if (this.orderItems.get_order_items.length == 0) {
                toastr.error('Please add at least one service!', '', {timeOut: 5000});
                return;
            }

            if (this.isEditBillItem.status) {
                toastr.error('Please update service is editing!', '', {timeOut: 5000});
                return;
            }
            if (!confirm('Do you want to Create this Booking?')) return;
            this.bill.bill_items = JSON.stringify(this.orderItems);
            var authOptions = {
                method: 'POST',
                url: '/api/v0/add-booking-service',
                params: this.bill,
                headers: {
                    'Authorization': "Bearer " + this.token.access_token,
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                json: true
            }

            axios(authOptions).then(response => {
                this.billSuccess = response.data.data;
                toastr.success(response.data.message, '', {timeOut: 5000});
                this.resetData();
                $('#showBill').modal('hide');
            }).catch((error) => {
                for (key in error.response.data.message) {
                    toastr.error(error.response.data.message[key], '', {timeOut: 5000});
                }
            });
        },
        getListBill: function(order_booking_id) {
            var authOptions = {
                method: 'get',
                url: '/api/v0/show-booking-service/' + order_booking_id,
                headers: {
                    'Authorization': "Bearer " + this.token.access_token,
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                json: true
            }
            axios(authOptions).then(response => {
               this.$set(this, 'orderItems', response.data.data);
               this.grand_total();
            })
        },
        editBill: function(bill) {
            this.bill = bill;
            this.billItems = bill.bill_items;
            for (var i = this.billItems.length - 1; i >= 0; i--) {
                this.billItems[i].qty = parseInt(this.billItems[i].qty);
                this.billItems[i].stylist_name = this.billItems[i].stylist.name;
                if (!this.billItems[i].service_name) {
                    this.billItems[i].service_name = this.billItems[i].service_product.name;
                }
            }
            this.showStylist();
            $('#showBill').modal('show');
        },

        showLogStatus: function(order_booking_id) {
            var authOptions = {
                method: 'get',
                url: '/api/v0/log-status/' + order_booking_id,
                headers: {
                    'Authorization': "Bearer " + this.token.access_token,
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                json: true
            }

            axios(authOptions).then(response => {
                this.$set(this, 'logStatus', response.data.data);
                $('#show_log_status').modal('show');
            })
            
        },
        datePrev: function() {
            if (this.params.type == 'space') {
                this.params.start_date = this.params.start_date - 60*24*60;
                
            } else if (this.params.type == 'day' || this.params.type == '') {
                this.params.start_date = this.params.start_date - 60*24*60;
                this.params.end_date = this.params.start_date - 60*24*60;
            }

            this.getBooking();
        },
        dateNext: function() {
            if (this.params.type == 'space') {
                this.params.end_date = this.params.end_date + 60*24*60;
                
            } else if (this.params.type == 'day' || this.params.type == '') {
                this.params.start_date = this.params.start_date + 60*24*60;
                this.params.end_date = this.params.start_date + 60*24*60;
            }
            this.getBooking();
        },
        showDateFrom: function() {
            if ($('#to:checked').val() == 1) {
               $('.date-from').show();
               this.params.type = 'space';
               this.today();
            } else {
                this.today();
                $('.date-from').hide();
                this.params.type = 'day';
            }
        },
        handleDate: function(date) {
            var date = new Date(date);
            var hours = date.getHours();
            var minutes = date.getMinutes();

            if (minutes < 10) {
                minutes = '0' + minutes;
            }
            return hours + ':' + minutes;
        },
        // kanban function
        kanbanBoard: function()
        {
            var kanbanCol = $('.panel-body');
            kanbanCol.css('max-height', (window.innerHeight - 150) + 'px');

            var kanbanColCount = parseInt(kanbanCol.length);
            $('.container-fluid').css('min-width', (kanbanColCount * 350) + 'px');

            this.draggableInit();

            $('body').on('click', '.panel-heading', function() {
                var $panelBody = $(this).parent().children('.panel-body');
                $panelBody.slideToggle();

            });
        },

        draggableInit: function()
        {
            let status = this;
            var sourceId;
            $('body').on('dragstart', '[draggable=true]', function (event) {
                sourceId = $(this).parent().attr('id');
                event.originalEvent.dataTransfer.setData("text/plain", event.target.getAttribute('id'));
            });

            $('body').on('dragover', '.drag-td', function (event) {
                event.preventDefault();
            });
            $('body').on('drop', '.drag-td', function (event) {
                var children = $(this).children().children().children();
                var id = $(this).children().children().children().find('article');
                var targetId = children.attr('id');
                if (sourceId == 1) {
                     if (sourceId != targetId && (targetId == 0 || targetId == 3 || targetId == 4)) {
                        var elementId = event.originalEvent.dataTransfer.getData("text/plain");
                        if (targetId == 0) {
                            status.changer_status(targetId, elementId);
                        } else {
                            status.update_status(targetId, elementId);
                            $('#processing-modal').modal('toggle'); //before post
                            // Post data 
                            setTimeout(function () {
                                var element = document.getElementById(elementId);
                                children.prepend(element);
                                $('#processing-modal').modal('toggle'); // after post
                            }, 1000);
                        }
                    }
                }

                if (sourceId == 4) {
                    if (sourceId != targetId && (targetId == 2)) {
                        
                        var elementId = event.originalEvent.dataTransfer.getData("text/plain");
                        status.addBillBookingInprogress(elementId);
                    }
                }

                if (sourceId == 3) {
                    if (sourceId != targetId && (targetId == 0 || targetId == 4)) {
                        var elementId = event.originalEvent.dataTransfer.getData("text/plain");
                        if (targetId == 0) {
                            status.changer_status(targetId, elementId);
                        } else {
                            status.update_status(targetId, elementId);
                            $('#processing-modal').modal('toggle'); //before post
                            // Post data 
                            setTimeout(function () {
                                var element = document.getElementById(elementId);
                                children.prepend(element);
                                $('#processing-modal').modal('toggle'); // after post
                            }, 1000);
                        }
                        
                    }
                }
            });
        },
        // end kanban function
        addBillBookingInprogress: function(id) {
            var authOptions = {
                method: 'GET',
                url: '/api/v0/get_booking_by_id/' + id,
                headers: {
                    'Authorization': "Bearer " + this.token.access_token,
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                json: true
            };

            axios(authOptions).then(response => {
                this.bill.customer_name = response.data.data.name;
                this.bill.customer_id = response.data.data.user_id;
                this.bill.department_id = response.data.data.department.id;
                this.booking = response.data.data;
                this.bill.service_total = this.booking.order_items.length;

                this.bill.grand_total = response.data.data.grand_total;
                this.bill.order_booking_id = this.booking.id;
                this.bill.phone = response.data.data.phone;
                this.formErrors.phone = '';
            });

            $('#showCreateBill').modal('show');
        },
        storeBill: function(event){
            if (!confirm('Do you want to create this bill!')) return;
            var authOptions = {
                method: 'POST',
                url: '/api/v0/bill',
                params: this.bill,
                headers: {
                    'Authorization': "Bearer " + this.token.access_token,
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                json: true
            }

            if (this.bill.id) {
                authOptions.method = 'PUT';
                authOptions.url = '/api/v0/bill/' + this.bill.id;
            }

            axios(authOptions).then(response => {
                this.billSuccess = response.data.data;
                for (key in response.data.message) {
                    toastr.success(response.data.message[key], '', {timeOut: 5000});
                }
                $('#showCreateBill').modal('hide');
                this.getBooking();
                var authOptions = {
                    method: 'GET',
                    url: '/admin/export_bill/' + response.data.data.id,
                    responseType:'arraybuffer',
                };

                axios(authOptions).then(response => {
                    let blob = new Blob([response.data], { type:   'application/pdf' } )
                    let link = document.createElement('a')
                    link.href = window.URL.createObjectURL(blob)
                    link.download = this.billSuccess.id + '_' + this.billSuccess.department.address + '_Report.pdf'
                    link.click()
                });
                
                this.exportshowBill(this.billSuccess);


            }).catch((error) => {
                for (key in error.response.data.message) {
                    toastr.error(error.response.data.message[key], '', {timeOut: 5000});
                }
            });   
        },
    }

});

function myFunction() {
    var input, filter, table, tr, td, i;
    input = document.getElementById("Myinput");
    filter = input.value.toUpperCase();
    table = document.getElementById("MyTable");
    tr = document.getElementsByTagName("tr");
    for (i = 0; i < tr.length; i++) {
        td = tr[i].getElementsByTagName("td")[1];
        if (td) {
            if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        } 
    }
};

