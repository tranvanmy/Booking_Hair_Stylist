<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Contracts\Repositories\UserRepository;
use App\Contracts\Repositories\DepartmentRepository;
use App\Contracts\Repositories\BillRepository;
use App\Contracts\Repositories\BillItemRepository;
use App\Contracts\Repositories\MediaRepository;
use App\Contracts\Repositories\OrderBookingRepository;
use App\Eloquents\Bill;
use App\Eloquents\OrderBooking;
use App\Helpers\Helper;
use App\Eloquents\User;
use Response;
use Validator;
use Auth;

class AdminController extends Controller
{
    protected $bill, $billItem, $user, $orderBooking, $media;

    public function __construct(
        BillRepository $bill,
        BillItemRepository $billItem,
        UserRepository $user,
        OrderBookingRepository $orderBooking,
        MediaRepository $media
    ) {
        $this->bill = $bill;
        $this->billItem = $billItem;
        $this->user = $user;
        $this->orderBooking = $orderBooking;
        $this->media = $media;
    }


    public function home()
    {
        return view('admin._component.home');
    } 

    public function manager_customer()
    {
        return view('admin._component.manager_customer');
    }

    public function manager_booking()
    {
        return view('admin._component.manager_booking2');
    }

    public function list_booking()
    {
        return view('admin._component.manager_booking');
    }
    
    public function profile($id)
    { 
        $total = 0;
        $user = $this->user->find($id);
        $billByCustomerId = $this->bill->getListBillByCustomerId($id, ['Department', 'OrderBooking']);
        $countBill = count($billByCustomerId);
        foreach ($billByCustomerId as $item) {
            $total = $total + $item['grand_total'];
        }
        
        return view('admin._component.profile', compact('user', 'billByCustomerId', 'total', 'countBill'));
    }

    public function bill()
    {
        return view('admin._component.bill');
    }

    public function manager_service()
    {
        return view('admin._component.manage_service');
    }

    public function list_bill()
    {
        return view('admin._component.manager_bill');
    }

    public function manager_department()
    {
        return view('admin._component.manager_department');
    }
}

