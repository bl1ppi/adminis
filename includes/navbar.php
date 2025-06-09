<head>
	<style>
		nav {
		  display: flex;
		  align-items: center;
		  justify-content: flex-start; 
		  padding: 10px 20px;
		  background-color: #f5f5f5;
		}
		
		nav h1 {
		  margin: 0;
		  font-size: 20px;
		  margin-right: 20px; 
		}
		
		nav .nav-links {
		  display: flex;
		  gap: 15px;
		}
		
		nav .nav-links a {
		  text-decoration: none;
		  color: #0077cc;
		  font-weight: 500;
		}
		
		nav .nav-links a:hover {
		  text-decoration: underline;
		}
	</style>
</head>
<header>
  <nav>
    <h1>
      <a href="/adminis/index.php" style="text-decoration: none; color: inherit;">
        <?= defined('SITE_TITLE') ? SITE_TITLE : '📡 Заголовок по умолчанию' ?>
      </a>
    </h1>
    <div class="nav-links">
      <a href="/adminis/map">🗺 Карта сети</a>
      <a href="/adminis/rooms/">🏫 Кабинеты</a>
      <a href="/adminis/laptops/">💻 Ноутбуки</a>
      <a href="/adminis/docs/">📘 Документация</a>
      <a href="/adminis/logout.php">🚪 Выход</a> 
    </div>
  </nav>
  <hr>
</header>

