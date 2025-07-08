<?php
session_start();
require 'db.php';
$userLoggedIn = isset($_SESSION['name']);
$userName     = $_SESSION['name'] ?? '';
$userType     = $_SESSION['user_type'] ?? '';
$cars = $conn->query("
  SELECT *
  FROM cars
  WHERE quantity > 0
  ORDER BY created_at DESC
  LIMIT 3
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>BroCar Rental</title>
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
      background: #fff;
      color: #333;
    }
    .navbar {
      background: rgb(2, 24, 20);
      padding: 15px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .navbar .logo a {
      color: white;
      font-weight: bold;
      font-size: 22px;
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
      position: relative;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .profile-icon {
      width: 35px;
      height: 35px;
      background: #00cc66;
      color: white;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      font-weight: bold;
      cursor: pointer;
    }
    .dropdown {
      position: absolute;
      top: 45px;
      right: 0;
      background: white;
      border: 1px solid #ccc;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      display: none;
    }
    .dropdown a {
      display: block;
      padding: 10px 15px;
      color: #333;
      text-decoration: none;
      font-weight: 500;
    }
    .dropdown a:hover {
      background: #f0f0f0;
    }
    .hero {
      background: url('images/navbg.jpg') center/cover no-repeat;
      height: 60vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: white;
      padding: 20px;
    }
    .hero h1 {
      font-size: 2.8rem;
    }
    .cars-container {
      max-width: 1100px;
      margin: 40px auto;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      padding: 0 20px;
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
      background-color: #f0f0f0;
      border-radius: 8px;
    }
    .car-card .car-content {
      padding: 15px;
    }
    .car-card h3 {
      font-size: 1.2rem;
      color: #004d40;
    }
    .car-card .price {
      margin-top: 10px;
      font-weight: 700;
      color: #00cc44;
    }
    .btn {
      display: inline-block;
      margin-top: 10px;
      background: #00796b;
      color: white;
      padding: 8px 14px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
    }
    .btn:hover {
      background: #005e55;
    }
    .see-all-btn {
      display: block;
      text-align: center;
      margin: 30px auto;
      width: fit-content;
      background: #004d40;
      padding: 12px 22px;
      border-radius: 8px;
      color: white;
      font-weight: bold;
      text-decoration: none;
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
      text-align: center;
      padding: 16px;
      background: #222;
      color: #ccc;
      margin-top: 60px;
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="logo"><a href="index.php">BroCar Rental</a></div>
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="cars.php">Cars</a></li>
      <li><a href="#">Contact</a></li>
    </ul>
    <div class="profile">
      <?php if ($userLoggedIn): ?>
        <div class="profile-icon" onclick="toggleDropdown()">
          <?= strtoupper(substr($userName,0,1)) ?>
        </div>
        <div id="dropdown" class="dropdown">
          <a href="profile.php">View Profile</a>
          <?php if ($userType === 'admin'): ?>
            <a href="admin_dashboard.php">Admin Panel</a>
          <?php endif; ?>
          <a href="logout.php" style="color:#ff4d4d;">Logout</a>
        </div>
      <?php else: ?>
        <a href="login.php" style="color:white;">Login</a> |
        <a href="signup.php" style="color:white;">Signup</a>
      <?php endif; ?>
    </div>
  </nav>

  <section class="hero">
    <div>
      <h1>Rent Your Dream Car</h1>
      <p>Explore top cars with ease</p>
    </div>
  </section>

  <main>
    <div class="cars-container" id="car-list">
      <?php while ($car = $cars->fetch_assoc()): ?>
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

    <a href="cars.php" class="see-all-btn">See All Cars</a>

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
  </main>

  <footer>&copy; <?= date('Y') ?> BroCar Rental. All rights reserved.</footer>

  <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
  <script>
    function toggleDropdown() {
      const dd = document.getElementById('dropdown');
      dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
    }
    window.onclick = (e) => {
      if (!e.target.closest('.profile')) {
        const dd = document.getElementById('dropdown');
        if (dd) dd.style.display = 'none';
      }
    };

    let detailSwiper;

    document.querySelectorAll('.see-details').forEach(el => {
      el.addEventListener('click', async () => {
        const card = el.closest('.car-card');
        const carId = card.getAttribute('data-car-id');
        const resp = await fetch(`get_car_details.php?car_id=${carId}`);
        const car = await resp.json();

        document.getElementById('detailTitle').textContent = car.brand + ' ' + car.model;

        const wrapper = document.getElementById('detailSwiperWrapper');
        wrapper.innerHTML = '';
        if (car.images.length) {
          document.getElementById('detail-swiper').style.display = '';
          car.images.forEach(img => {
            const slide = document.createElement('div');
            slide.className = 'swiper-slide';
            slide.innerHTML = `<img src="images/${img.image_path}" alt="">`;
            wrapper.appendChild(slide);
          });
        } else {
          document.getElementById('detail-swiper').style.display = 'none';
        }

        document.getElementById('detailContent').innerHTML = `
          <p><strong>Price:</strong> Rs ${car.price_per_day}/day</p>
          <p><strong>Fuel:</strong> ${car.fuel_type}</p>
          <p><strong>Transmission:</strong> ${car.transmission}</p>
          <p><strong>Seater:</strong> ${car.seater}</p>
          <p><strong>Luxury:</strong> ${car.luxury == 1 ? 'Yes' : 'No'}</p>
          <p><strong>Terrain:</strong> ${car.terrain}</p>
          <p><strong>Details:</strong><br>${car.details.replace(/\n/g, '<br>')}</p>
        `;

        document.getElementById('bookNowBtn').href = `booking.php?car_id=${carId}`;

        document.getElementById('car-list').style.display = 'none';
        document.querySelector('.see-all-btn').style.display = 'none';
        document.getElementById('detailsSection').classList.add('visible');

        if (detailSwiper) {
          detailSwiper.update();
        } else {
          detailSwiper = new Swiper('#detail-swiper', {
            loop: true,
            navigation: {
              nextEl: '.swiper-button-next',
              prevEl: '.swiper-button-prev',
            },
            autoplay: {
              delay: 3000,
              disableOnInteraction: false,
            },
          });
        }
      });
    });

    document.getElementById('closeDetails').onclick = e => {
      e.preventDefault();
      document.getElementById('detailsSection').classList.remove('visible');
      document.getElementById('car-list').style.display = '';
      document.querySelector('.see-all-btn').style.display = '';
    };
  </script>
</body>
</html>
