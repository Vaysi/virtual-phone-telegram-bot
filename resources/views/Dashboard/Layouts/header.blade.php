<nav class="navbar navbar-expand navbar-dark bg-dark static-top">

    <button class="btn btn-link btn-sm text-white order-1 order-sm-0" id="sidebarToggle" href="#">
        <i class="fas fa-bars"></i>
    </button>

    <a class="navbar-brand ml-1" href="index.html">مدیریت بات</a>


    <!-- Navbar -->
    <ul class="navbar-nav mr-auto ml-md-0">
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-user-circle fa-fw"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                <a class="dropdown-item text-right" href="{{ route('logout') }}" data-toggle="modal" data-target="#logoutModal"> خروج</a>
            </div>
        </li>
    </ul>

</nav>