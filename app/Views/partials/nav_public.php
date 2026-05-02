<header class="navbar public-navbar">
    <div class="container nav-inner public-nav-inner">
        <a href="<?= esc(site_url('/')) ?>" class="nav-logo">
            <img class="nav-logo-image" src="<?= esc(base_url('assets/img/logo-header.png')) ?>" alt="<?= esc($siteName ?? 'ConvertHub') ?>">
            <span class="nav-logo-text"><?= esc($siteName ?? 'ConvertHub') ?></span>
        </a>

        <div class="nav-center<?= session()->get('isLoggedIn') ? '' : ' nav-center-single' ?>">
            <a class="nav-tab active" href="<?= esc(site_url('/')) ?>">Converter</a>
            <?php if (session()->get('isLoggedIn')): ?>
                <a class="nav-tab" href="<?= esc(site_url('/user/orders')) ?>">Order Saya</a>
            <?php endif; ?>
        </div>

        <nav class="nav-right">
            <?php if (session()->get('isLoggedIn')): ?>
                <span class="mono nav-user"><?= esc((string) session()->get('email')) ?></span>
                <a class="btn-ghost" href="<?= esc(site_url('/logout')) ?>">Logout</a>
            <?php else: ?>
                <a class="btn-ghost" href="<?= esc(site_url('/login')) ?>">Masuk</a>
                <a class="btn-primary" href="<?= esc(site_url('/register')) ?>">Daftar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
