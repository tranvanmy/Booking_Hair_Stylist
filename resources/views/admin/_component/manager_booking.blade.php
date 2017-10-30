@extends('admin.master')
@section('style')
    {{ Html::style('bower/AdminLTE/plugins/datatables/dataTables.bootstrap.css') }}
    {{ Html::style('css/admin/style.css') }}
    {{ Html::style('bower/bootstrap-fileinput/css/fileinput.min.css') }}
@endsection
@section('content')
    <div class="content-wrapper ">
    <section class="content-header">
        <h1>
            {{ __('Booking') }}
        </h1>
            <div class="col-md-2 select_booking">
                <a href="{{ asset('admin/manager_booking') }}" target="_blank" class="btn btn-success"><i class="fa fa-keyboard-o" aria-hidden="true"></i> {{ __('Kanban') }}</a>
            </div>
        <ol class="breadcrumb">
          <li><a href="#"><i class="fa fa-dashboard"></i> {{ __('Home') }} </a></li>
          <li><a href="#"> {{ __('Manager') }}</a></li>
          <li class="active"> {{ __('Booking And Customer') }}</li>
        </ol>
    </section>
    <section class="content " id="manager_booking">
        <div class="row">
          <div class="col-xs-12">
                <div class="box-header" style="position: relative;">
                    <div class="form-group col-md-12 select_booking_manage">
                            <div class="col-md-12 well">
                                <div class="col-md-1">
                                    <a class="btn btn-primary date-prev" v-on:click = "datePrev()">
                                        {{ __('Pre Day') }}
                                    </a>
                                </div>
                                <div class="form-group col-md-8 col-md-offset-1 select_booking_manage">
                                    <div class="col-md-12">
                                        <button v-on:click="today()" class="col-md-2 btn btn-success">Today</button>
                                        <div class="col-md-4">
                                            <input type="date"  class="form-control" v-model="start_date" v-on:change="selectStartDay">
                                        </div>
                                        <div class="col-md-2 date-to">
                                            <input type="checkbox" id="to" value="1" v-on:click="showDateFrom()"> Date To:
                                        </div>
                                        <div class="col-md-4 date-from">
                                            <input type="date" class=" form-control" v-model="end_date" v-on:change="selectEndDay">
                                        </div>
                                    </div>
                                </div>
                                <div class=" col-md-1">
                                    <a class="btn btn-primary next-date" v-on:click = "dateNext()">
                                        {{ __('Next day') }}
                                    </a>
                                </div>
                            </div>  
                        </div>
                </div>
            <div class="box" style="position: relative;">
                <div class="indicator hide list-booking-indicator">
                    <div class="spinner"></div>
                </div>
                <div class="clearfix"></div>
                <div class="box-body over-flow-edit">
                    <div class="panel-group" id="accordion">
                        <div class="panel panel-default" v-for="item in items">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <a data-toggle="collapse" data-parent="#accordion" v-bind:href="'#open-booking-day-' + item.date_book">
                                        {{__('Date Book') }}: @{{ item.date_book }}
                                        <span class="label label-warning">
                                            @{{ item.list_book.length }}
                                        </span>
                                    </a>
                                </h4>
                            </div>
                            <div  v-bind:id="'open-booking-day-' + item.date_book" class="panel-collapse collapse in scoll-time">
                                <div class="panel-body">
                                    <table id="example1" class="table table-bordered table-striped ok">
                                        <thead>
                                            <tr>
                                                <th>{{__('ID') }}</th>
                                                <th>{{ __('NameCustomer') }}</th>
                                                <th>{{ __('Phone') }}</th>
                                                <th>{{ __('Department') }}</th>
                                                <th>{{ __('NameStylist') }}</th>
                                                <th>{{ __('TimeStart') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Action') }}</th>
                                            </tr>   
                                        </thead>
                                        <tbody>
                                           <tr v-for="list in item.list_book">
                                            <td v-bind:id="list.id">@{{ list.id }}</td>
                                            <td>
                                            <a href="javascript:void(0)" v-on:click="bookingDetail(list)">@{{ list.name }}</a></td>
                                            <td>@{{ list.phone }}</td>
                                            <td>@{{ list.department.address }}</td>
                                            <td>@{{ list.stylist.name }}</td>
                                            <td>@{{ convertHourMinute( list.time_start) }}</td>
                                            <td v-if="list.status == 0">
                                                <span class="label label-danger">
                                                    {{ __('Cancel') }}
                                                </span>
                                            </td>
                                            <td v-if="list.status == 1">
                                                <span class="label label-warning">
                                                    {{ __('Waiting') }}
                                                </span>
                                            </td>
                                            <td v-if="list.status == 2">
                                                <span class="label label-success">
                                                    {{ __('Complete') }}
                                                </span>
                                            </td>
                                            <td v-if="list.status == 3">
                                                <span class="label label-primary">
                                                    {{ __('Inlate') }}
                                                </span>
                                            </td>
                                            <td v-if="list.status == 4">
                                                <span class="label label-primary">
                                                    {{ __('Inprogress') }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="javascript:void(0)" v-on:click="changer_status(list)" v-if="list.status !=2 "> <i aria-hidden="true" class="fa fa-pencil-square-o"></i></a>
                                                <a href="javascript:void(0)" v-on:click="showLogStatus(list.id)"> <i class="fa fa-eye" aria-hidden="true"></i></a>
                                                <a href="javascript:void(0)"  v-if="list.status == 4" data-toggle="modal" data-target="#chooseImg" v-on:click="showImage(list.id)"><i class="fa fa-picture-o" aria-hidden="true"></i></a>
                                            </td>
                                        </tr>  
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        </div>
                    </div>
                    <!-- show log status by order_booking_id -->
                    <div class="modal fade" id="show_log_status" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                                    <h4 class="modal-title" id="myModalLabel">{{ __('History') }}</h4>
                                </div>
                                <div class="modal-body">
                                    <table class="table table-bordered table-hover" v-if="logStatus.length != 0">
                                        <thead>
                                            <tr>
                                                <th>{{__('ID') }}</th>
                                                <th>{{ __('User') }}</th>
                                                <th>{{ __('Old Status') }}</th>
                                                <th>{{ __('New Status') }}</th>
                                                <th>{{ __('Message') }}</th>
                                                <th>{{ __('Time') }}</th>
                                            </tr>   
                                        </thead>
                                        <tbody>
                                           <tr v-for="status in logStatus">
                                                <th>@{{ status.id }}</th>
                                                <th>@{{ status.get_user.name }}</th>
                                                <th v-if="status.old_status == 0">
                                                    <span class="label label-danger">{{ __('Cancer') }}</span></th>
                                                <th v-if="status.old_status == 1">
                                                    <span class="label label-warning">{{ __('Waiting') }}</span></th>
                                                <th v-if="status.old_status == 2">
                                                    <span class="label label-success">{{ __('Complete') }}</span></th>
                                                <th v-if="status.old_status == 3">
                                                    <span class="label label-primary">{{ __('Inlate') }}</span></th>
                                                <th v-if="status.old_status == 4">
                                                    <span class="label label-info">{{ __('Inprogress') }}</span></th>
                                                <th v-if="status.new_status == 0">
                                                    <span class="label label-danger">{{ __('Cancer') }}</span></th>
                                                <th v-if="status.new_status == 1">
                                                    <span class="label label-warning">{{ __('Waiting') }}
                                                </th>
                                                <th v-if="status.new_status == 2">
                                                    <span class="label label-success">{{ __('Complete') }}</span>
                                                </th>
                                                <th v-if="status.new_status == 3">
                                                    <span class="label label-primary">{{ __('Inlate') }}</th>
                                                <th v-if="status.new_status == 4">
                                                    <span class="label label-info">{{ __('Inprogress') }}</span></th>
                                                <th>@{{ status.message }}</th>
                                                <th>@{{ status.created_at }}</th>
                                           </tr>
                                        </tbody>
                                </table>
                                    <div class="alert alert-warning text-center"  v-if="logStatus.length == 0">
                                      <strong> {{ __('No history is recorded') }}</strong>.
                                  </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end show log status by order_booking_id -->
                    <div class="modal fade" id="update_status" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                                    <h4 class="modal-title" id="myModalLabel">{{ __('Update Booking') }}</h4>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="update_status(changer_status_booking.id)">
                                        <div class="form-group row">
                                        <label class="col-md-2" v-if=" status != 4 ">{{ __('Status') }}</label>
                                        <div class="form-group col-md-5 select_booking_manage">
                                            <!-- if status is cancel -->
                                            <select  class="form-control select-multi-status" id="sel1" v-if="status == 0" v-model="changer_status_booking.status" disabled="disabled">
                                                <option v-bind:value="0">{{ __('Cancel') }}</option>
                                                <option v-bind:value="1">{{ __('Waiting') }}</option>
                                                <option v-bind:value="2">{{ __('Complete') }}</option>
                                                <option v-bind:value="3">{{ __('Inlate') }}</option>
                                                <option v-bind:value="4">{{ __('Inprogress') }}</option>
                                            </select>

                                            <!-- if status is Complete -->
                                             <select  class="form-control select-multi-status" id="sel1" v-if="status == 2" v-model="changer_status_booking.status" disabled="disabled">
                                                <option v-bind:value="0">{{ __('Cancel') }}</option>
                                                <option v-bind:value="1">{{ __('Waiting') }}</option>
                                                <option v-bind:value="2">{{ __('Complete') }}</option>
                                                <option v-bind:value="3">{{ __('Inlate') }}</option>
                                                <option v-bind:value="4">{{ __('Inprogress') }}</option>
                                            </select>

                                            <!-- if status is wating -->
                                            <select  class="form-control select-multi-status" id="sel1" v-if="status == 1" v-model="changer_status_booking.status">
                                                <option v-bind:value="3">{{ __('Inlate') }}</option>
                                                <option v-bind:value="4">{{ __('Inprogress') }}</option>
                                            </select>

                                            <!-- if status is Inlate -->
                                            <select  class="form-control select-multi-status" id="sel1" v-if="status == 3" v-model="changer_status_booking.status">
                                                <option v-bind:value="4">{{ __('Inprogress') }}</option>
                                                <template v-if="status == 4">
                                                    <option v-bind:value="2">{{ __('Complete') }}</option>
                                                </template>
                                            </select>
                                            </div> 
                                            <div class="alert alert-warning text-center col-md-12" v-if="status == 4">
                                                <strong>{{ __('NOTE!') }} {{ __('Status can not be changed') }}</strong>
                                            </div>
                                        </div>
                                        <div class="form-group row" v-if=" status != 4">
                                            <label class="col-md-2">{{ __('Message') }}</label>
                                            <div class="col-md-9">
                                                <textarea class=" form-control" name="message" id="message" v-model="changer_status_booking.message"></textarea>
                                            </div>  
                                        </div>

                                        <div class="form-group" v-if=" status != 4">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fa fa-plus" aria-hidden="true"></i> {{ __('Edit') }}
                                            </button>
                                            <button class="btn btn-default" data-dismiss="modal">
                                                <i class="fa fa-external-link-square" aria-hidden="true"></i>
                                                {{ __('Close') }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="container">

                          <!-- Modal -->
                        <div class="modal fade" id="chooseImg" role="dialog">
                            <div class="modal-dialog">

                                <!-- Modal content-->
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        <h4 class="modal-title">Modal Header</h4>
                                    </div>
                                    <div class="modal-body">
                                        <label for="input-41">Select File</label>
                                        <input id="input-44" name="images" type="file" multiple data-show-upload="false" data-allowed-file-extensions='["jpg", "png"]' v-on:change="executeImages">
                                        <button type="submit" v-on:click="submitImages" class="btn btn-success">Upload</button>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="showBill" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    <h4 class="modal-title">{{ __('Booking Detail') }}</h4>
                                </div>
                                <div class="modal-body">
                                  <div class="tab-content">
                                    <div id="home" class="tab-pane fade in active">
                                        <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="createItem" class="form-horizontal">
                                    <div class="form-group">
                                        <div class="col-md-2">
                                            <label for="name" class="text-center label_bill">
                                                <i class="fa fa-user" aria-hidden="true"></i>
                                                <strong>
                                                    {{ __('Infor Customer') }}
                                                </strong>
                                            </label>
                                        </div>
                                            <div class="col-md-10 flexbox-annotated-section-content">
                                                <br>
                                                    <div class="col-md-4">
                                                    <div class="col-md-2">
                                                        <strong>{{ __('Name') }}</strong>
                                                    </div>
                                                    <div class="clearfix"></div>
                                                    <div class="col-md-10">
                                                        <div class="input-group">
                                                          <div class="input-group-addon">
                                                            <i class="fa fa-user"></i>
                                                            </div>
                                                            <input class="form-control input-md field-compulsory-before" placeholder="Name" disabled="disabled" type="text" v-model="bill.customer_name">
                                                        </div>
                                                    </div>
                                                    </div>
                                                   
                                                    <div class="col-md-4">
                                                        <div class="col-md-2">
                                                            <strong>{{ __('Phone') }}</strong>
                                                        </div>
                                                        <div class="clearfix"></div>
                                                        <div class="col-md-12   ">
                                                        <div class="input-group">
                                                          <div class="input-group-addon">
                                                            <i class="fa fa-phone" aria-hidden="true"></i>
                                                            </div>
                                                            <input class="form-control input-md field-compulsory-before" placeholder="Name" disabled="disabled" type="text" v-model="bill.phone">
                                                        </div>
                                                        </div>
                                                    </div>
                                                 
                                                    <div class="col-md-4">
                                                        <div class="col-md-2">
                                                            <strong>{{ __('Status') }}</strong>
                                                        </div>
                                                        <div class="clearfix"></div>
                                                        <div class="col-md-10">
                                                            <div class="input-group" v-if="status == 0">
                                                                <div class="input-group-addon">
                                                                <i class="fa fa-bandcamp" aria-hidden="true"></i>
                                                                </div>
                                                                <input class="form-control input-md field-compulsory-before" disabled="disabled" type="text" value="Cancel">
                                                            </div>
                                                            <div class="input-group" v-if="status == 1">
                                                                <div class="input-group-addon">
                                                                <i class="fa fa-bandcamp" aria-hidden="true"></i>
                                                                </div>
                                                                <input class="form-control input-md field-compulsory-before" disabled="disabled" type="text" value="Waiting">
                                                            </div>
                                                            <div class="input-group" v-if="status == 2">
                                                                <div class="input-group-addon">
                                                                <i class="fa fa-bandcamp" aria-hidden="true"></i>
                                                                </div>
                                                                <input class="form-control input-md field-compulsory-before" disabled="disabled" type="text" value="Complete">
                                                            </div>
                                                            <div class="input-group" v-if="status == 3">
                                                                <div class="input-group-addon">
                                                                <i class="fa fa-bandcamp" aria-hidden="true"></i>
                                                                </div>
                                                                <input class="form-control input-md field-compulsory-before" disabled="disabled" type="text" value="Inlate">
                                                            </div>
                                                            <div class="input-group" v-if="status == 4">
                                                                <div class="input-group-addon">
                                                                <i class="fa fa-bandcamp" aria-hidden="true"></i>
                                                                </div>
                                                                <input class="form-control input-md field-compulsory-before" disabled="disabled" type="text" value="Inprogress">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-md-2">
                                                <label for="name" class="text-center label_bill">
                                                    <i class="fa fa-info-circle" aria-hidden="true"></i>
                                                    <strong>
                                                        {{ __('Infor Booking') }}
                                                    </strong>
                                                </label>
                                            </div>
                                            <div class="col-md-10 flexbox-annotated-section-content"  v-if="booking.id">
                                                <br>
                                                    <div class="col-md-6">
                                                    <div class="col-md-2">
                                                        <strong>{{ __('Dep Name')}}</strong>:
                                                    </div>
                                                    <div class="col-md-10">
                                                        <div class="input-group">
                                                          <div class="input-group-addon">
                                                            <i class="fa fa-home" aria-hidden="true"></i>
                                                            </div>
                                                            <input class="form-control input-md field-compulsory-before" placeholder="Name" disabled="disabled" type="text" v-model="booking.department.name">
                                                        </div>
                                                    </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                    <div class="col-md-2">
                                                         <strong>{{__('Address')}}</strong>:
                                                    </div>
                                                    <div class="col-md-10">
                                                        <div class="input-group">
                                                          <div class="input-group-addon">
                                                            <i class="fa fa-calendar-o" aria-hidden="true"></i>
                                                            </div>
                                                            <input class="form-control input-md field-compulsory-before" placeholder="Name" disabled="disabled" type="text" v-model="booking.department.address">
                                                        </div>
                                                    </div>
                                                    </div>
                                                    <div class="clearfix"></div>
                                                    <br>
                                                    <div class="col-md-6">
                                                    <div class="col-md-2">
                                                        <strong> {{ __('Day Booking') }}</strong>
                                                    </div>
                                                    <div class="col-md-10">
                                                        <div class="input-group">
                                                          <div class="input-group-addon">
                                                            <i class="fa fa-calendar-o" aria-hidden="true"></i>
                                                            </div>
                                                            <input class="form-control input-md field-compulsory-before" placeholder="Name" disabled="disabled" type="text" v-model="booking.render_booking.time_start">
                                                        </div>
                                                    </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                    <div class="col-md-2">
                                                        <strong>{{ __('Time start') }}</strong>
                                                    </div>
                                                    <div class="col-md-10">
                                                        <div class="input-group">
                                                          <div class="input-group-addon">
                                                            <i class="fa fa-user-circle" aria-hidden="true"></i>
                                                            </div>
                                                            <input class="form-control input-md field-compulsory-before" placeholder="Name" disabled="disabled" type="text" v-model="booking.stylist.name">
                                                        </div>
                                                    </div>
                                                    </div>
                                                     <div class="clearfix"></div>
                                                    <br>
                                                    <div class="col-md-6">
                                                    <div class="col-md-2">
                                                        <strong>{{ __('Stylist') }}</strong>
                                                    </div>
                                                    <div class="col-md-10">
                                                        <div class="input-group">
                                                          <div class="input-group-addon">
                                                            <i class="fa fa-user-circle" aria-hidden="true"></i> 
                                                            </div>
                                                            <input class="form-control input-md field-compulsory-before" placeholder="Name" disabled="disabled" type="text" v-model="booking.stylist.name">
                                                        </div>
                                                    </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                    <div class="col-md-2">
                                                        <strong>{{ __('Phone Stylist') }}</strong>
                                                    </div>
                                                    <div class="col-md-10">
                                                        <div class="input-group">
                                                          <div class="input-group-addon">
                                                            <i class="fa fa-phone" aria-hidden="true"></i>
                                                            </div>
                                                            <input class="form-control input-md field-compulsory-before" placeholder="Name" disabled="disabled" type="text" v-model="booking.stylist.phone">
                                                        </div>
                                                    </div>
                                                    </div>
                                                    </div>
                                            </div>
                                            <div class="form-group">
                                            <div class="col-md-2">
                                                <label for="name" class="text-center label_bill">
                                                    <i class="fa fa-plus" aria-hidden="true"></i>
                                                    <strong>
                                                        {{ __('Service') }}
                                                    </strong>
                                                </label>
                                            </div>
                                            <div class="col-md-10 flexbox-annotated-section-content"  v-if="booking.id">
                                            <div v-if="status == 4">
                                                <div class="col-sm-3">
                                                    <label>{{ __('Service') }}</label>
                                                    <select  class="form-control" v-model="billItem.service_product_id" v-on:change="select_service">
                                                        <option value=""></option>
                                                        <option v-bind:value="service.id" v-for="service in services">
                                                            @{{ service.name }}
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-3">
                                                    <label>{{ __('Stylist') }}</label>
                                                    <select  class="form-control" v-model="billItem.stylist_id" v-on:change="select_stylist">
                                                        <option value=""></option>
                                                        <option v-bind:value="stylist.id" v-for="stylist in stylists">
                                                            @{{ stylist.name }}
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-2 ">
                                                    <label >{{ __('Price') }}</label>
                                                    <input type="text" disabled readonly v-bind:value="billItem.price" class="form-control"/>
                                                </div>
                                                <div class="col-sm-2">
                                                    <label >{{ __('Qty') }}</label>
                                                    <input type="number" v-model="billItem.qty" value="1" class="form-control"/>
                                                </div>
                                                <div class="col-sm-2">
                                                    <label >{{ __('Action') }}</label>
                                                    <a class="btn btn-success" v-on:click="addService" v-if="!isEditBillItem.status">
                                                        {{__('Add Service') }}
                                                    </a>
                                                    <a class="btn btn-warning" v-on:click="submitEditBillItem(isEditBillItem.index)" v-else>
                                                        {{__('Update Service') }}
                                                    </a>
                                                </div>
                                            </div>
                                                
                                            <div class="col-sm-12">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('STT') }}</th>
                                                            <th>{{ __('Service Name') }}</th>
                                                            <th>{{ __('Stylist Name') }}</th>
                                                            <th>{{ __('Price') }}</th>
                                                            <th>{{ __('Qty') }}</th>
                                                            <th v-if="status == 4">{{ __('Action') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr id ="list_service" v-for="(billItem, keyObject) in orderItems.get_order_items" v-bind:class="{'label-warning': isEditBillItem.status && isEditBillItem.index == keyObject}">
                                                            <td>@{{ keyObject + 1 }}</td>
                                                            <td>@{{ billItem.service_name }}</td>
                                                            <td>@{{ billItem.stylist_name }}</td>
                                                            <td>@{{ (billItem.price).toLocaleString('de-DE') }} VND</td>
                                                            <td>@{{ billItem.qty }}</td>
                                                            <td v-if="status == 4"> <a href="javascript:void(0)" v-on:click="editBillItem(keyObject)">
                                                                <i aria-hidden="true" class="fa fa-pencil-square-o"></i>
                                                            </a>
                                                            <a href="javascript:void(0)" v-on:click="deleteBillItem(keyObject, billItem.id)">
                                                                <i class="fa fa-fw  fa-close get-color-icon-delete"></i>
                                                            </a></td>
                                                        </tr>
                                                        <tr>
                                                            <td></td>
                                                            <td></td>
                                                            <td></td>
                                                            <td></td>
                                                            <td></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="form-group text-center">
                                            <div v-if="status == 4">
                                                <button class="btn btn-success" v-on:click="createBill" v-if="!bill.id">
                                                <i class="fa fa-plus" aria-hidden="true"></i> {{ __('Create') }}
                                                </button>
                                                <button class="btn btn-warning" v-on:click="createBill" v-else>
                                                    <i class="fa fa-plus" aria-hidden="true"></i> {{ __('Update Service') }}
                                                </button>
                                            </div>
                                            </div>     
                                            </div>
                                        </div>
                                    </form>
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
    </section>
</div>
@endsection

@section('script')
    {{ Html::script('js/admin/manage_list_booking.js') }}
    {{ Html::script('bower/AdminLTE/plugins/datatables/jquery.dataTables.min.js') }}
    {{ Html::script('bower/AdminLTE/plugins/datatables/dataTables.bootstrap.min.js') }}
    {{ Html::script('bower/bootstrap-fileinput/js/fileinput.min.js') }}
    {{ Html::script('bower/bootstrap-fileinput/js/plugins/sortable.min.js') }}
    {{ Html::script('bower/bootstrap-fileinput/js/plugins/purify.min.js') }}
    {{ Html::script('https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js') }}
    {{ Html::script('js/upload.js') }}
@endsection
