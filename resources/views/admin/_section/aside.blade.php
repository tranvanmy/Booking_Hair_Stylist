<aside class="main-sidebar" id="aside_amind">
    <section class="sidebar">
        <div class="user-panel">
            <div class="pull-left image">
                <img src={{ asset('bower/AdminLTE/dist/img/user2-160x160.jpg') }} class="img-circle" alt="User Image">
                {{-- <img v-bind:src="users.avatar" class="img-circle" alt="User Image"> --}}
            </div>
            <div class="pull-left info">
                <p>@{{ users.name }}</p>
                <a href="#"><i class="fa fa-circle text-success"></i> {{ __('Online') }}</a>
            </div>
        </div>
        <form action="#" method="get" class="sidebar-form">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Search...">
                <span class="input-group-btn">
                    <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i>
                    </button>
                </span>
            </div>
        </form>
        <ul class="sidebar-menu">
            <li class="header">{{ __('MAIN NAVIGATION') }}</li>
            <li class=" treeview">
             <li><a href="{{ asset('admin/home')  }}"><i class="fa fa-circle-o text-red"></i> <span>{{ __('Sales Report') }}</span></a></li>
            </li>
            <li class="treeview">
                <a href="#">
                    <i class="fa fa-table"></i> <span>{{ __('Manager') }}</span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li>
                        <a href={{ asset('admin/manager_customer')  }}><i class="fa fa-circle-o"></i>
                            {{ __('Manager Customer') }}
                        </a>
                    </li>
                    <li>
                        <a href={{ asset('admin/list_bill') }}><i class="fa fa-circle-o"></i>
                            {{ __('Manager Bill') }}
                        </a>
                    </li>
                </ul>
            </li>
            <li class="treeview">
                <a href="#">
                    <i class="fa fa-table"></i> <span>{{ __('Booking') }}</span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li>
                        <a href={{ asset('admin/manager_booking') }}><i class="fa fa-circle-o"></i>
                            {{ __('Kaban Booking') }}
                        </a>
                    </li>
                    <li>
                        <a href={{ asset('admin/list_booking') }}><i class="fa fa-circle-o"></i>
                            {{ __('Lists Booking') }}
                        </a>
                    </li>
                </ul>
            </li>
            <li class="header">{{ __('SETTING')}}</li>
                <li><a href="{{ asset('admin/manager_setting') }}"><i class="fa fa-wrench" aria-hidden="true"></i> <span>{{ __('Setting') }}</span></a></li>
            </li>
        </ul>
    </section>
</aside>
