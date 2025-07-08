<?php
session_start();
require 'db.php';
$userLoggedIn = isset($_SESSION['name']);
$userName     = $_SESSION['name'] ?? '';
$userType     = $_SESSION['user_type'] ?? '';

$search  = $_GET['search'] ?? '';
$fuel    = $_GET['fuel'] ?? '';
$trans   = $_GET['trans'] ?? '';
$seater  = $_GET['seater'] ?? '';
$terrain = $_GET['terrain'] ?? '';
$luxury  = $_GET['luxury'] ?? '';
$sort    = $_GET['sort'] ?? 'newest';

$where = ['quantity > 0'];
if ($search)  $where[] = "(brand LIKE '%{$search}%' OR model LIKE '%{$search}%')";
if ($fuel)    $where[] = "fuel_type = '{$fuel}'";
if ($trans)   $where[] = "transmission = '{$trans}'";
if ($seater)  $where[] = "seater = '{$seater}'";
if ($terrain) $where[] = "terrain = '{$terrain}'";
if ($luxury !== '') $where[] = "luxury = '{$luxury}'";

$whereSql = implode(' AND ', $where);

switch ($sort) {
    case 'price_low': $order = 'price_per_day ASC'; break;
    case 'price_high': $order = 'price_per_day DESC'; break;
    case 'seater': $order = 'seater DESC'; break;
    default: $order = 'created_at DESC'; break;
}

$cars = $conn->query("
    SELECT *
    FROM cars
    WHERE {$whereSql}
    ORDER BY {$order}
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Browse Cars - BroCar Rental</title>
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css"
  />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f9f9f9;
      color: #333;
    }
    .navbar {
      background: #031b17;
      padding: 15px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .navbar .logo a {
      color: white;
      font-size: 22px;
      font-weight: bold;
      text-decoration: none;
    }
    .nav-links {
      list-style: none;
      display: flex;
      gap: 20px;
    }
    .nav-links a {
      color: white;
      text-decoration: none;
      font-weight: 500;
    }
    .nav-links a:hover {
      color: #00cc66;
    }
    .profile {
      color: white;
      position: relative;
      cursor: pointer;
      font-weight: 600;
    }
    .profile-icon {
      background: #00cc66;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      text-align: center;
      line-height: 32px;
      font-weight: bold;
      color: white;
      display: inline-block;
    }
    .dropdown {
      position: absolute;
      top: 40px;
      right: 0;
      background: white;
      border: 1px solid #ccc;
      border-radius: 6px;
      display: none;
      flex-direction: column;
      min-width: 150px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      z-index: 999;
    }
    .dropdown a {
      padding: 10px;
      text-decoration: none;
      color: #333;
      border-bottom: 1px solid #eee;
      display: block;
    }
    .dropdown a:hover {
      background: #f0f0f0;
    }
    .container {
      max-width: 1100px;
      margin: 30px auto;
      padding: 0 20px;
    }
    h2 {
      text-align: center;
      margin-bottom: 30px;
      color: #00796b;
    }
    .controls {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 30px;
    }
    .controls input,
    .controls select {
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      flex: 1;
      min-width: 150px;
    }
    .controls button {
      background: #00796b;
      color: white;
      padding: 10px 18px;
      border: none;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s;
    }
    .controls button:hover {
      background: #005e55;
    }
    .cars-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 20px;
    }
    .car-card {
      background: white;
      border-radius: 10px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
      overflow: hidden;
      transition: 0.3s;
    }
    .car-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 18px rgba(0,0,0,0.15);
    }
    .car-card img {
      width: 100%;
      height: 180px;
      object-fit: contain;
      background: #f0f0f0;
      border-radius: 8px;
    }
    .car-content {
      padding: 15px;
    }
    .car-content h3 {
      font-size: 1.2rem;
      margin-bottom: 8px;
      color: #004d40;
    }
    .car-content .price {
      color: #00cc44;
      font-weight: bold;
      margin-bottom: 10px;
    }
    .btn {
      display: inline-block;
      background: #00796b;
      color: white;
      padding: 8px 14px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
      margin-right: 8px;
      margin-top: 5px;
    }
    .btn:hover {
      background: #005e55;
    }
    .details-section {
      display: none;
      max-width: 800px;
      margin: 40px auto;
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    }
    .details-section.visible {
      display: block;
    }
    .details-section h2 {
      margin-bottom: 20px;
      color: #004d40;
    }
    .details-section .swiper {
      width: 100%;
      height: 300px;
      margin-bottom: 20px;
    }
    .details-section .swiper-slide img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }
    .details-section p {
      margin: 8px 0;
    }
    .details-section .close-details,
    .details-section .book-now {
      display: inline-block;
      margin-top: 20px;
      padding: 8px 14px;
      border-radius: 6px;
      color: white;
      text-decoration: none;
      font-weight: bold;
    }
    .details-section .close-details {
      background: #dc3545;
    }
    .details-section .book-now {
      background: #00cc66;
      margin-left: 10px;
    }
    .details-section .close-details:hover {
      background: #c82333;
    }
    .details-section .book-now:hover {
      background: #00994d;
    }
    footer {
      margin-top: 60px;
      text-align: center;
      padding: 16px;
      background: #222;
      color: #ccc;
    }
    @media (max-width: 768px) {
      .controls {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="logo"><a href="index.php">BroCar Rental</a></div>
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="cars.php">Cars</a></li>
    </ul>
    <div class="profile">
      <?php if ($userLoggedIn): ?>
        <div class="profile-icon" onclick="toggleDropdown()">
          <?= strtoupper(substr($userName,0,1)) ?>
        </div>
        <div id="dropdown" class="dropdown">
          <a href="profile.php">Profile</a>
          <?php if ($userType==='admin'): ?>
            <a href="admin_dashboard.php">Admin Panel</a>
          <?php endif; ?>
          <a href="logout.php" style="color:red;">Logout</a>
        </div>
      <?php else: ?>
        <a href="login.php" style="color:white;">Login</a> |
        <a href="signup.php" style="color:white;">Signup</a>
      <?php endif; ?>
    </div>
  </nav>

  <div class="container">
    <h2>Browse Available Cars</h2>
    <form method="GET" class="controls">
      <input type="text" name="search" placeholder="Search brand or model" value="<?= htmlspecialchars($search) ?>">
      <select name="fuel">
        <option value="">All Fuel Types</option>
        <?php foreach (['petrol'=>'Petrol','diesel'=>'Diesel','ev'=>'Electric'] as $val=>$opt): ?>
          <option value="<?= $val ?>" <?= ($fuel===$val)?'selected':'' ?>><?= $opt ?></option>
        <?php endforeach; ?>
      </select>
      <select name="trans">
        <option value="">All Transmissions</option>
        <?php foreach (['manual'=>'Manual','auto'=>'Automatic'] as $val=>$opt): ?>
          <option value="<?= $val ?>" <?= ($trans===$val)?'selected':'' ?>><?= $opt ?></option>
        <?php endforeach; ?>
      </select>
      <select name="seater">
        <option value="">Any Seater</option>
        <?php foreach ([2,4,5,7] as $opt): ?>
          <option value="<?= $opt ?>" <?= ($seater==$opt)?'selected':'' ?>><?= $opt ?> seats</option>
        <?php endforeach; ?>
      </select>
      <select name="terrain">
        <option value="">All Terrains</option>
        <?php foreach (['city'=>'City','offroad'=>'Offroad'] as $val=>$opt): ?>
          <option value="<?= $val ?>" <?= ($terrain===$val)?'selected':'' ?>><?= $opt ?></option>
        <?php endforeach; ?>
      </select>
      <select name="luxury">
        <option value="">All</option>
        <option value="1" <?= ($luxury==='1')?'selected':'' ?>>Luxury</option>
        <option value="0" <?= ($luxury==='0')?'selected':'' ?>>Standard</option>
      </select>
      <select name="sort">
        <option value="newest" <?= ($sort==='newest')?'selected':'' ?>>Newest</option>
        <option value="price_low" <?= ($sort==='price_low')?'selected':'' ?>>Price ↑</option>
        <option value="price_high" <?= ($sort==='price_high')?'selected':'' ?>>Price ↓</option>
        <option value="seater" <?= ($sort==='seater')?'selected':'' ?>>Seater ↓</option>
      </select>
      <button type="submit">Apply</button>
    </form>

    <?php if ($cars->num_rows>0): ?>
      <div class="cars-grid" id="car-list">
        <?php while($car=$cars->fetch_assoc()): ?>
          <div class="car-card" data-car-id="<?= $car['car_id'] ?>">
            <img src="images/<?= htmlspecialchars($car['image']) ?>" alt="">
            <div class="car-content">
              <h3><?= htmlspecialchars($car['brand'].' '.$car['model']) ?></h3>
              <div class="price">Rs <?= number_format($car['price_per_day']) ?>/day</div>
              <a class="btn see-details">See Details</a>
              <a class="btn" href="booking.php?car_id=<?= $car['car_id'] ?>">Book Now</a>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p style="margin-top:30px;text-align:center;">No cars match your criteria.</p>
    <?php endif; ?>

    <div class="details-section" id="detailsSection">
      <h2 id="detailTitle"></h2>
      <div class="swiper" id="detail-swiper">
        <div class="swiper-wrapper" id="detailSwiperWrapper"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
      </div>
      <div id="detailContent"></div>
      <a href="#" class="close-details" id="closeDetails">Close Details</a>
      <a href="#" class="book-now" id="bookNowBtn">Book Now</a>
    </div>
  </div>

  <footer>&copy; <?= date('Y') ?> BroCar Rental. All rights reserved.</footer>
  <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
  <script>
    function toggleDropdown() {
      const d = document.getElementById('dropdown');
      d.style.display = d.style.display==='block'? 'none':'block';
    }
    window.onclick = e => {
      if (!e.target.closest('.profile')) {
        const d = document.getElementById('dropdown');
        if (d) d.style.display = 'none';
      }
    };

    let detailSwiper;

    document.querySelectorAll('.see-details').forEach(el=>{
      el.addEventListener('click',async()=>{
        const card = el.closest('.car-card');
        const carId = card.getAttribute('data-car-id');
        const resp = await fetch(`get_car_details.php?car_id=${carId}`);
        const car = await resp.json();

        document.getElementById('detailTitle').textContent = car.brand+' '+car.model;

        const wrapper = document.getElementById('detailSwiperWrapper');
        wrapper.innerHTML='';

        if(car.images.length){
          document.getElementById('detail-swiper').style.display='';
          car.images.forEach(img=>{
            const slide = document.createElement('div');
            slide.className='swiper-slide';
            slide.innerHTML=`<img src="images/${img.image_path}" alt="">`;
            wrapper.appendChild(slide);
          });
        } else {
          document.getElementById('detail-swiper').style.display='none';
        }

        document.getElementById('detailContent').innerHTML=`
          <p><strong>Price:</strong> Rs ${car.price_per_day}/day</p>
          <p><strong>Fuel:</strong> ${car.fuel_type}</p>
          <p><strong>Transmission:</strong> ${car.transmission}</p>
          <p><strong>Seater:</strong> ${car.seater}</p>
          <p><strong>Luxury:</strong> ${car.luxury==1?'Yes':'No'}</p>
          <p><strong>Terrain:</strong> ${car.terrain}</p>
          <p><strong>Details:</strong><br>${car.details.replace(/\n/g,'<br>')}</p>
        `;

        document.getElementById('bookNowBtn').href=`booking.php?car_id=${carId}`;

        document.getElementById('car-list').style.display='none';
        document.getElementById('detailsSection').classList.add('visible');

        if(detailSwiper){
          detailSwiper.update();
        } else {
          detailSwiper = new Swiper('#detail-swiper', {
            loop:true,
            navigation:{
              nextEl:'.swiper-button-next',
              prevEl:'.swiper-button-prev',
            },
            autoplay:{
              delay:3000,
              disableOnInteraction:false
            }
          });
        }
      });
    });

    document.getElementById('closeDetails').onclick=e=>{
      e.preventDefault();
      document.getElementById('detailsSection').classList.remove('visible');
      document.getElementById('car-list').style.display='';
    };
  </script>
</body>
</html>
