<?php
if (!defined('SITE_TITLE')) {
    define('SITE_TITLE', '📡 Заголовок по умолчанию');
}
?>

<!-- Bootstrap Navbar -->
<header>
  <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom px-4 py-2">
    <a class="navbar-brand fw-bold me-4" href="/adminis/index.php">
      <?= SITE_TITLE ?>
    </a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
      aria-controls="mainNavbar" aria-expanded="false" aria-label="Переключить навигацию">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="/adminis/map">🗺 Карта сети</a></li>
        <li class="nav-item"><a class="nav-link" href="/adminis/rooms/">🏫 Кабинеты</a></li>
        <li class="nav-item"><a class="nav-link" href="/adminis/monitor/">🖥 Мониторинг</a></li>
        <li class="nav-item"><a class="nav-link" href="/adminis/laptops/">💻 Ноутбуки</a></li>
        <li class="nav-item"><a class="nav-link" href="/adminis/docs/">📘 Документация</a></li>
      </ul>
      <span class="navbar-text">
        <a class="btn btn-outline-danger btn-sm" href="/adminis/logout.php">🚪 Выход</a>
      </span>
    </div>
  </nav>
</header>
