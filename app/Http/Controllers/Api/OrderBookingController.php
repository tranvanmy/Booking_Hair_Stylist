<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Contracts\Repositories\OrderBookingRepository;
use App\Contracts\Repositories\RenderBookingRepository;
use App\Contracts\Repositories\UserRepository;
use App\Contracts\Repositories\DepartmentRepository;
use App\Contracts\Repositories\OrderItemRepository;
use App\Contracts\Repositories\LogStatusRepository;
use App\Helpers\Helper;
use App\Eloquents\OrderBooking;
use App\Eloquents\User;
use Carbon\Carbon;
use DB;
use Validator;
use Auth;
use Response;
use Nexmo;

class OrderBookingController extends Controller
{
    protected $orderBooking, $renderBooking, $user, $department, $orderItem, $logStatus;

    public function __construct( 
        OrderBookingRepository $orderBooking, 
        RenderBookingRepository $renderBooking,
        UserRepository $user,
        DepartmentRepository $department,
        OrderItemRepository $orderItem,
        LogStatusRepository $logStatus
    ) 
    {
        $this->orderBooking = $orderBooking;
        $this->renderBooking = $renderBooking;
        $this->user = $user;
        $this->department = $department;
        $this->orderItem = $orderItem;
        $this->logStatus = $logStatus;
    }

    public function userBooking(Request $request)
    {
        $response = Helper::apiFormat();

        $rule = [
            'render_booking_id' => 'required',
            'phone' => 'required|numeric|digits_between:6,25',
            'name' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rule);
        if ($validator->fails()) {
            $response['error'] = true;
            $response['status'] = 403;
            foreach ($rule as $key => $value) {
                if ($validator->messages()->first($key)) {
                    $response['message'][] = $validator->messages()->first($key);
                }
            }

            return Response::json($response, 403);
        }

        $stylist_id = $request->stylist_chosen;
        $renderBooking = $this->renderBooking->find($request->render_booking_id, ['OrderBooking']);

        // Tim stylist ranh nhat
        if (!$stylist_id) {
            $orderCollection = $renderBooking->OrderBooking;

            $allRenderday = $this->renderBooking->getRenderByDate($renderBooking->day, null, ['OrderBooking']);
            $stylists = $this->user->getStylistByDepartmentId($renderBooking->department_id);
            $listStylist = [];
            foreach ($stylists as $stylist) {
                $listStylist[$stylist->id] = 0;
            }

            foreach ($allRenderday as $renderday) {
                foreach ($renderday->OrderBooking as $bookingDay) {
                    if (isset($listStylist[$bookingDay->stylist_id])) {
                        $listStylist[$bookingDay->stylist_id] += 1;
                    }
                }
            }
            asort($listStylist);
            
            foreach ($listStylist as $key => $stylist) {
                if(!$orderCollection->where('stylist_id', $key)->first()) {
                    $stylist_id = $key;
                    break;
                }
            }
        }

        if (!$stylist_id || $renderBooking->OrderBooking->where('stylist_id', $stylist_id)->first()) {
            $response['error'] = true;
            $response['status'] = 403;
            $response['message'][] = __('You can not book because no stylist in your time book!');

            return Response::json($response, $response['status']);
        } 
        // End Tim stylist ranh nhat
    
        $user = Auth::guard('api')->user();
        $user_id = null;
        if ($user) {
            $user_id = $user->id;
        } else {
            // Create user if not exits phone
            $findUser = $this->user->findByPhone($request->phone);
            
            if (!$findUser) {
                for($number = 1; $number < 10000; $number++) {
                    $email = 'email.' . $request->phone . '.' . $number . '@gmail.com';
                    $existUser = $this->user->existEmailOrPhone($email, $request->phone);
                    if (!$existUser) {
                        $user = [
                            'name' => $request->name,
                            'email' => $email,
                            'phone' => $request->phone,
                            'password' => $request->phone,
                        ];
                        $newUser = $this->user->create($user);
                        $user_id = $newUser->id;
                        break;
                    }
                }
            } else {
                $user_id = $findUser->id;
            }
        }

        if (null === $user_id) {
            $user = $this->user->findByPhone($request->phone);
            $user_id = $user ? $user->id : null;
        }

        $bookingChecked = $this->orderBooking->checkLastBookingByPhone($request->phone);

        if ($bookingChecked) {
            $timeUserChosen = $this->renderBooking->find($bookingChecked->render_booking_id);

            $timeSelect = strtotime($timeUserChosen->day . ' ' . $timeUserChosen->time_start);
            $timeSelected = Carbon::createFromTimestamp($timeSelect);

            if ($timeSelected->gt(Carbon::now())) { 
                // Da booking va chua het han, thay doi booking
                $bookingChecked->fill($request->all());
                $bookingChecked->stylist_id = $stylist_id;
                $bookingChecked->user_id = $user_id;
                $bookingChecked->status = OrderBooking::STATUS_PENDING;
                $bookingChecked->save();

                $dataResponse = $this->orderBooking->find($bookingChecked->id);
                $response['message'][] = __('You have changed your booking to new booking!');

            } else {
                // Tao moi booking
                $data = [
                    'render_booking_id' => $request->render_booking_id,
                    'phone' => $request->phone,
                    'name' => $request->name,
                    'stylist_id' => $stylist_id,
                    'user_id' => $user_id,
                    'status' => OrderBooking::STATUS_PENDING,
                ];
                $order = $this->orderBooking->create($data);
                $dataResponse = $this->orderBooking->find($order->id);
                $response['message'][] = __('You have successfully booked!');
            }
            $dataResponse->render_booking = $this->renderBooking->find($request->render_booking_id);

        } else {
            // Tao moi booking
            $order = $this->orderBooking->create([
                'render_booking_id' => $request->render_booking_id,
                'phone' => $request->phone,
                'name' => $request->name,
                'stylist_id' => $stylist_id,
                'user_id' => $user_id,
                'status' => OrderBooking::STATUS_PENDING,
            ]);
            $dataResponse = $this->orderBooking->find($order->id);
            $dataResponse->render_booking = $this->renderBooking->find($request->render_booking_id);
            $response['message'][] = __('You have successfully booked!');
        }

        $dataResponse->department = $this->department->find($renderBooking->department_id);
        $dataResponse->stylist = $stylist_name = $this->user->find($stylist_id);

        $response['data'] = $dataResponse;

        if ($dataResponse) {
            $sms = "FSalon Thank your for your order !\n";
            $sms .= "Order Infomation\n";
            $sms .= "--------------------------------\n";
            $sms .= "Name: " . $dataResponse->name . "\n";
            $sms .= "Date: " . $dataResponse->render_booking->day . " " . $dataResponse->render_booking->time_start . "\n";
            $sms .= "Department: " . $dataResponse->department->name . ". Address: " . $dataResponse->department->address . "\n";
            $sms .= "Stylist: " . $dataResponse->stylist->name . "\n";
            $sms .= "Contact us: 841626373587 \n";
            $sms .= "-------------------------------\n";
            $sms .= "Your account: ";
            $sms .= $dataResponse->phone . "/" . $dataResponse->phone;

            //Send SMS to client
            try {
                Nexmo::message()->send([
                    'to' => $dataResponse->phone,
                    'from' => 'FSalon',
                    'text' => $sms
                ]);
            } catch (\Exception $e) {
                return Response::json($response, $response['status']);
            }
            
        }
       
        return Response::json($response, $response['status']);
    }

    public function filterBooking(Request $request)
    {
        $response = Helper::apiFormat();

        $stylist_id = null;
        $user = Auth::guard('api')->user();
        if ($user && $user->permission == User::PERMISSION_MAIN_WORKER) {
           $stylist_id = $user->id;
        }

        $startDate = Carbon::today()->format('Y-m-d H:i:s');
        $endDate = Carbon::today()->endOfDay();
        $filter_date = $request->date;
        $filter_type = $request->type;//today - week - month //default today
        $filter_department = $request->department_id;

        $date_start = Carbon::createFromTimestamp($request->start_date);
        $date_end = Carbon::createFromTimestamp($request->end_date);
        switch ($filter_type) {
            case 'day':
                $startDate = $date_start->startOfDay()->format('Y-m-d H:i:s');
                $endDate = $date_start->endOfDay();
                break;
            case 'space':
                $startDate = $date_start->startOfDay()->format('Y-m-d H:i:s');
                $endDate = $date_end->endOfDay();
                break;
        }
 
        $filter_status = $request->status; //cancel - finished - pending
        if (null !== $filter_status) {
            $filter_status = explode(',', $filter_status);
        }

        $currentDate = Carbon::now()->timestamp(strtotime($startDate));
        $responseData = [];
        for (;$currentDate->lte($endDate);) {

            $date_filter = $currentDate->format('Y-m-d');

            $renderBookings = $this->renderBooking
                ->getRenderByDate($date_filter, $filter_department, ['OrderBooking', 'Department', 'OrderBooking.Images']);
            
            $data['date_book'] = $currentDate->format(config('default.format_date'));
            $dataBooks = [];
            foreach ($renderBookings as $renderBooking) {

                $department = [
                    'name' => $renderBooking->Department->name,
                    'address' => $renderBooking->Department->address,
                ];
                foreach ($renderBooking->OrderBooking as $orderBooking) {
                    $images = $orderBooking->Images;
                    if (null !== $filter_status && !in_array($orderBooking->status, $filter_status)) {
                        continue;
                    }

                    if (null !== $stylist_id && $orderBooking->stylist_id != $stylist_id) {
                        continue;
                    }

                    $orderBooking->time_start = $renderBooking->day . ' ' . $renderBooking->time_start;
                    $orderBooking->department = $department;
                    $orderBooking->stylist = $this->user
                        ->find($orderBooking->stylist_id, ['name', 'email', 'phone']);

                    $dataBooks[] = $orderBooking;

                }
            }
            $data['list_book'] = $dataBooks;
            $data['startDate'] = $date_start->format('Y-m-d');
            $data['endDate'] = $date_end->format('Y-m-d');
            $responseData[] = $data;

            $currentDate->addDay(1);
        }
        $response['data'] = $responseData;

        return Response::json($response, $response['status']);
    }
    
    public function getBookingbyUserId($user_id)
    {
        $response = Helper::apiFormat();

        $booking = $this->orderBooking->getBookingByCustomerId($user_id);

        if(!$booking) {
            $response['error'] = true;
            $response['status'] = 403;
            $response['message'][] = __('404 not found');

            return Response::json($response);
        }
        
        $response['data'] = $booking;

        return Response::json($response);
    }

    public function getBookingbyPhoneLastest(Request $request)
    {
        $response = Helper::apiFormat();

        $booking = $this->orderBooking->checkLastBookingByPhone($request->phone, ['Images', 'getOrderItems']);
        if(!$booking) {
            $response['error'] = true;
            $response['status'] = '404';
            $response['message'][] = __("There's no booking with this phone!");

            return Response::json($response, $response['status']);
        }        

        $booking->render_booking = $this->renderBooking->find($booking->render_booking_id);
        $booking->department = $this->department->find($booking->render_booking->department_id);
        $booking->stylist = $this->user->find($booking->stylist_id);
        $booking->order_items = $booking->getOrderItems;
        $booking->images = $booking->Images;
        $booking->grand_total = $this->orderItem->getGrandTotal($booking->id);
        foreach ($booking->order_items as $orderItem) {
                $orderItem->stylist = $this->orderItem->find($orderItem->id, ['getStylist'])->getStylist['name'];
                $orderItem->service = $this->orderItem->find($orderItem->id, ['getServiceProduct'])->getServiceProduct;
            }

        $response['data'] = $booking;

        return Response::json($response);
    }

    public function changeStatus(Request $request, $id)
    {
        $response = Helper::apiFormat();
        
        $user = Auth::guard('api')->user();
        if (!$user || $user->permission != User::PERMISSION_ADMIN) {
            $response['error'] = true;
            $response['message'][] = __('You do not have permission to perform this action!');
            $response['status'] = 403;

            return Response::json($response, $response['status']);
        }

        $orderBooking = $this->orderBooking->find($id);
        $old_status = $orderBooking->status;

        if (!$orderBooking) {
            $response['error'] = true;
            $response['status'] = 404;
            $response['message'][] = __('Not found this booking ordered!');

            return Response::json($response);
        }

        $rule = [
            'status' => 'numeric',
        ];
        $validator = Validator::make($request->all(), $rule);
        if ($validator->fails()) {
            $response['error'] = true;
            $response['status'] = 403;
            foreach ($rule as $key => $value) {
                if ($validator->messages()->first($key)) {
                    $response['message'][] = $validator->messages()->first($key);
                }
            }

            return Response::json($response, $response['status']);
        }

        $dataEdit = [
            'status' => $request->status,
        ];

        try {
            $orderBooking->fill($dataEdit)->save();
            $dataLogStatus = [
                'order_booking_id' => $id,
                'user_id' => $user->id,
                'old_status' => $old_status,
                'new_status' => intval($request->status),
                'message' => $request->message,
            ];
            $newLogStatus = $this->logStatus->create($dataLogStatus);

            $response['data'][] = __('Updated Status booking successfully!');
            $response['data']['log_status'] = $newLogStatus;
        } catch (Exception $e) {
            $response['error'] = true;
            $response['status'] = 403;
            $response['message'][] = $e->getMessage();
        }

        return Response::json($response, $response['status']);
    }

    public function getBookingbyId($bookingId)
    {
        $response = Helper::apiFormat();

        $booking = $this->orderBooking->getBookingByBookingId($bookingId, ['Images'], ['*'] );

        if (!$booking)
        {
            $response['error'] = true;
            $response['status'] = '404';
            $response['message'][] = __('404 not found');
            return Response::json($response, $response['status']);
        }
        
        $renderBooking = $this->renderBooking->find($booking->render_booking_id);
        $booking->render_booking = $renderBooking;
        $booking->department = $this->department->find($renderBooking->department_id);
        $booking->stylist =  $this->user->find($booking->stylist_id);
        $booking->order_items = $this->orderBooking->find($booking->id, ['getOrderItems'])->getOrderItems;
        $booking->grand_total = $this->orderItem->getGrandTotal($booking->id);

        foreach ($booking->order_items as $orderItem) {
                $orderItem->stylist = $this->orderItem->find($orderItem->id, ['getStylist'])->getStylist['name'];
                $orderItem->service = $this->orderItem->find($orderItem->id, ['getServiceProduct'])->getServiceProduct;
            }  
        $response['data'] = $booking;

        return Response::json($response, $response['status']);
    }

    public function stylistUploadImage(Request $request)
    {
        $response = Helper::apiFormat();

        $rule = [
            'order_booking_id' => 'required',
        ];
        
        $validator = Validator::make($request->all(), $rule);
        if ($validator->fails()) {
            $response['error'] = true;
            $response['status'] = 403;
            foreach ($rule as $key => $value) {
                if ($validator->messages()->first($key)) {
                    $response['message'][] = $validator->messages()->first($key);
                }
            }

            return Response::json($response, $response['status']);
        }

        $orderBooking = $this->orderBooking->find($request->order_booking_id);
        if (!$orderBooking) {
            $response['error'] = true;
            $response['status'] = 404;
            $response['message'][] = __('Not found this booking ordered!');

            return Response::json($response, $response['status']);
        }

        DB::beginTransaction();
        try {
            $images = json_decode($request->images);
            $images = is_array($images) ? $images : [];
            foreach ($images as $path) {
                if (!$path) {
                    continue;
                }
                $data['path_origin'] = $path;
                $orderBooking->Images()->create($data);
            }
            $response['message'][] = __('Upload images for this booking successfully!');
            $response['data'] = $this->orderBooking->find($request->order_booking_id, 'Images');
            DB::commit();
        } catch (Exception $e) {
            $response['status'] = 403;
            $response['error'] = true;
            $response['message'][] = $e->getMessages();
            DB::rollback();
        }

        return Response::json($response, $response['status']);
    }

    /**
     * Store service add by admin, stylist
     *
     * @param  App\Http\Requests\UserRequest;  $request
     * @return \Illuminate\Http\Response
     */
    public function addBookingService(Request $request)
    {
        $response = Helper::apiFormat();
        $data = $request->all();
        $billItems = json_decode($request->bill_items, true);
        foreach ($billItems['get_order_items'] as $item) {
            $issetServiceBooking = $this->orderItem->findItemWithStylistIdServiceId($item['stylist_id'], $item['service_product_id'], $item['order_booking_id'] );
            if (count($issetServiceBooking) == 0) {
                try {
                    $item['order_booking_id'] = $request->order_booking_id;
                    $serviceBooking = $this->orderItem->create($item);
                    $response['status'] = 201;
                    $response['data'] = $this->orderBooking->find($request->order_booking_id, ['getOrderItems']);
                    $response['message'] = __('Create Service successfully!');
                } catch (Exception $e) {
                    $response['status'] = 403;
                    $response['error'] = true;
                    $response['message'] = "Add Service Failed !";
                }

            } else {
                try {
                    $issetServiceBooking['qty'] = $item['qty'];
                    $issetServiceBooking = $issetServiceBooking->save();
                    $response['status'] = 200;
                    $response['message'] = __('Update Service successfully!');
                } catch (Exception $e) {
                    $response['status'] = 403;
                    $response['error'] = true;
                    $response['message'] = "Update Service Failed !";
                }
            }
        }
                


        return Response::json($response, $response['status']);
    }

    /**
     * Edit service add by admin, stylist
     *
     * @param  App\Http\Requests\UserRequest;  $request
     * @param  int $order_item_id
     * @return \Illuminate\Http\Response
     */
    public function updateBookingService(Request $request)
    {
        $response = Helper::apiFormat();
        $data = $request->all();
        $billItems = json_decode($request->bill_items, true);

        try {
            foreach ($$billItems['get_order_items'] as $billItem) {
                $billItem['order_booking_id'] = $request->order_booking_id;
                $serviceBooking = $this->orderItem->find($billItem['id'], []);
                $serviceBooking = $serviceBooking->update($billItem);
            }
            $response['status'] = 200;
            $response['message'] = __('Update Service successfully!');
        } catch (Exception $e) {
            $response['status'] = 403;
            $response['error'] = true;
            $response['message'] = "Update Service Failed !";
        }

        return Response::json($response, $response['status']);
    }

    /**
     * Destroy service add by admin, stylist
     *
     * @param  int $order_item_id
     * @return \Illuminate\Http\Response
     */
    public function destroyBookingService($order_item_id)
    {
        $response = Helper::apiFormat();

        try {
            $serviceBooking = $this->orderItem->find($order_item_id, []);
            $serviceBooking->delete();
            $response['status'] = 200;
            $response['message'] = __('Destroy Service successfully!');
        } catch (Exception $e) {
            $response['status'] = 403;
            $response['error'] = true;
            $response['message'] = "Destroy Service Failed !";
        }

        return Response::json($response, $response['status']);
    }

    /**
     * list service add by admin, stylist
     *
     * @param  int $order_id
     * @return \Illuminate\Http\Response
     */
    public function showBookingService($order_booking_id)
    {
        $response = Helper::apiFormat();

        try {
            $listServiceBooking = $this->orderBooking->find($order_booking_id, ['getOrderItems']);
            foreach ($listServiceBooking->getOrderItems as $orderItem) {
                $orderItem->stylist_name = $this->orderItem->find($orderItem->id, ['getStylist'])->getStylist->name;
                $orderItem->service = $this->orderItem->find($orderItem->id, ['getServiceProduct'])->getServiceProduct;
            }
            $response['status'] = 200;
            $response['data'] = $listServiceBooking;
        } catch (Exception $e) {
            $response['status'] = 403;
            $response['error'] = true;
            $response['message'] = "The request was valid !";
        }

        return Response::json($response, $response['status']);
    }

    /**
     * Get log status by order booking id
     *
     * @param int $order_booking_id
     * @return \Illuminate\Http\Response
     */
    public function getLogStatus($order_booking_id)
    {
        $response = Helper::apiFormat();

        try {
            $getLogStatus = $this->logStatus->getLogStatus($order_booking_id,['getUser']);
            $response['status'] = 200;
            $response['data'] = $getLogStatus;
        } catch (Exception $e) {
            $response['status'] = 403;
            $response['error'] = true;
            $response['message'] = "The request was valid !";
        }

        return Response::json($response, $response['status']);
    }

    /**
     * Get order_booking by status = inprogress
     *
     * @param int $order_booking_id
     * @return \Illuminate\Http\Response
     */
    public function showBookingInprogress()
    {
        $response = Helper::apiFormat();

        try {
            $bookings = $this->orderBooking->getBookingByStatus('4', ['getStylist']);
            foreach ($bookings as $item) {
                $item->department = $this->user->find($item->stylist_id, ['*'], ['getStylistInDepartment'])->getStylistInDepartment->name;
            }
            $response['status'] = 200;
            $response['data'] = $bookings;
        } catch (Exception $e) {
            $response['status'] = 403;
            $response['error'] = true;
            $response['message'] = "The request was valid !";
        }

        return Response::json($response, $response['status']);
    }

    /**
     * Search bill by name, phone, department_id
     *
     * @return json
     */
    public function search(Request $request)
    {
        $input = $request->all();
        $keywords = [
            'phone' => $input['phone'],
        ];

        $date = Carbon::now()->timestamp(strtotime($input['date']));
        $data['date'] = $date->format(config('default.format_date'));
        $date = $date->format('Y-m-d');

        $response = Helper::apiFormat();

        try {

            $keywords = Helper::handleSearchKeywords($keywords);
            

            $booking = $this->orderBooking->search($date, $keywords, ['Images']);
            foreach ($booking as $item) {
                $renderBooking = $this->renderBooking->find($item->render_booking_id);
                $item->render_booking = $renderBooking;
                $item->department = $this->department->find($renderBooking->department_id);
                $item->stylist =  $this->user->find($item->stylist_id);
                $item->order_items = $this->orderBooking->find($item->id, ['getOrderItems'])->getOrderItems;
                $item->grand_total = $this->orderItem->getGrandTotal($item->id);

                foreach ($item->order_items as $orderItem) {
                    $orderItem->stylist = $this->orderItem->find($orderItem->id, ['getStylist'])->getStylist['name'];
                    $orderItem->service = $this->orderItem->find($orderItem->id, ['getServiceProduct'])->getServiceProduct;
                }  
            }
            
            $response['data'] = $booking;
            $response['status'] = 200;

            return Response::json($response, $response['status']);
        } catch (Exception $e) {
            $response['status'] = 403;

            return Response::json($response, $response['status']);
        }
        
    }
}

