<?php
session_start();
require 'db.php';

$viewCarId = isset($_GET['view']) ? (int)$_GET['view'] : null;

if ($viewCarId) {
    $stmt = $conn->prepare("SELECT * FROM cars WHERE car_id = ?");
    $stmt->bind_param("i", $viewCarId);
    $stmt->execute();
    $car = $stmt->get_result()->fetch_assoc();

    $imgStmt = $conn->prepare("SELECT * FROM car_images WHERE car_id = ?");
    $imgStmt->bind_param("i", $viewCarId);
    $imgStmt->execute();
    $carImages = $imgStmt->get_result();
} else {
    $search = $_GET['search'] ?? '';
    $fuel = $_GET['fuel'] ?? '';
    $trans = $_GET['trans'] ?? '';
    $seater = $_GET['seater'] ?? '';
    $terrain = $_GET['terrain'] ?? '';
    $luxury = $_GET['luxury'] ?? '';

    $query = "SELECT * FROM cars WHERE quantity > 0";
    if ($search) $query .= " AND (brand LIKE '%$search%' OR model LIKE '%$search%')";
    if ($fuel) $query .= " AND fuel_type = '$fuel'";
    if ($trans) $query .= " AND transmission = '$trans'";
    if ($seater) $query .= " AND seater = '$seater'";
    if ($terrain) $query .= " AND terrain = '$terrain'";
    if ($luxury !== '') $query .= " AND luxury = '$luxury'";
    $query .= " ORDER BY created_at DESC";

    $cars = $conn->query($query);
}

$userLoggedIn = isset($_SESSION['name']);
$userName = $_SESSION['name'] ?? '';
$userType = $_SESSION['user_type'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>BroCar Rental</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />
  <style>
    * {margin: 0; padding: 0; box-sizing: border-box;}
    body {font-family: 'Segoe UI', sans-serif; background: #fff; color: #333;}

    .navbar {
      background:rgb(3, 27, 23);
      padding: 15px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    button{
      background: #00cc44;
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
    }
    .navbar .logo {color: white; font-weight: bold; font-size: 22px;}
    .nav-links {list-style: none; display: flex; gap: 20px;}
    .nav-links a {
      color: white; text-decoration: none; font-weight: 500;
    }
    .nav-links a:hover {color: #00cc66;}

    .profile {
      color: white;
      font-weight: 600;
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

    .search-bar {
      max-width: 700px;
      margin: 30px auto;
      display: flex;
      gap: 10px;
    }
    .search-bar input {
      flex: 1;
      padding: 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 1rem;
    }
    .search-bar button {
      padding: 10px 20px;
      background: #00cc44;
      border: none;
      color: white;
      font-weight: bold;
      border-radius: 6px;
      cursor: pointer;
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
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      transition: 0.3s;
    }
    .car-card img {
  width: 100%;
  height: 180px;
  object-fit: contain;
  background-color: #f0f0f0;
  border-radius: 8px;
  display: block;
  margin: 0 auto;
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
    .details-btn {
      display: inline-block;
      margin-top: 10px;
      background: #00796b;
      color: white;
      padding: 8px 14px;
      border-radius: 6px;
      text-decoration: none;
    }

    .car-details {
      max-width: 800px;
      margin: 40px auto;
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    }

    .swiper {
      width: 100%;
      height: 300px;
      margin-bottom: 20px;
      border-radius: 10px;
      overflow: hidden;
    }
    .swiper-wrapper {
      display: flex;
    }

    .swiper-slide img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .car-details h2 {
      color: #004d40;
      margin-bottom: 20px;
    }

    .car-details p {
      margin: 10px 0;
    }

    .btn-back {
      display: inline-block;
      margin-top: 20px;
      background: #00796b;
      color: white;
      padding: 10px 20px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
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
    <div class="logo">BroCar Rental</div>
    <ul class="nav-links">
      <li><a href="index.php">Home</a></li>
      <li><a href="#">Cars</a></li>
      <li><a href="#">Contact</a></li>
    </ul>
    <div class="profile">
      <?php if ($userLoggedIn): ?>
        Hi, <?= htmlspecialchars($userName) ?>
        <?php if ($userType === 'admin'): ?> |
          <a href="admin_dashboard.php" style="color:#00cc66;">Admin</a>
        <?php endif; ?>
        | <button> <a href="logout.php" style="color:#ff4d4d; text-decoration:none;">Logout</a> </button>
      <?php else: ?>
        <button> <a href="login.php" style="text-decoration:none;">Login</a> | <a href="signup.php" style="text-decoration:none;">Signup</a></button>
      <?php endif; ?>
    </div>
  </nav>

  <section class="hero">
    <div>
      <h1>Rent Your Dream Car</h1>
      <p>Explore top cars with ease</p>
    </div>
  </section>

  <?php if (!$viewCarId): ?>
    <form method="GET" class="search-bar">
      <input type="text" name="search" placeholder="Search brand/model..." value="<?= htmlspecialchars($search ?? '') ?>">
      <button type="submit">Search</button>
    </form>
  <?php endif; ?>

  <main>
    <?php if ($viewCarId && $car): ?>
      <div class="car-details">
        <h2><?= htmlspecialchars($car['brand'] . ' ' . $car['model']) ?></h2>

        <?php if ($carImages && $carImages->num_rows > 0): ?>
          <div class="swiper">
            <div class="swiper-wrapper">
              <?php while ($img = $carImages->fetch_assoc()): ?>
                <div class="swiper-slide">
                  <img src="images/<?= htmlspecialchars($img['image_path']) ?>" alt="<?= htmlspecialchars($img['label']) ?>">
                </div>
              <?php endwhile; ?>
            </div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
          </div>
        <?php else: ?>
          <!-- fallback cover image if no additional images -->
          <img src="images/<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['model']) ?>" style="width:100%; height:300px; object-fit:cover; border-radius:10px; margin-bottom:20px;">
        <?php endif; ?>

        <p><strong>Price:</strong> Rs <?= number_format($car['price_per_day']) ?> / day</p>
        <p><strong>Fuel:</strong> <?= htmlspecialchars($car['fuel_type']) ?></p>
        <p><strong>Transmission:</strong> <?= htmlspecialchars($car['transmission']) ?></p>
        <p><strong>Seater:</strong> <?= htmlspecialchars($car['seater']) ?></p>
        <p><strong>Luxury:</strong> <?= ($car['luxury'] == 1) ? 'Yes' : 'No' ?></p>
        <p><strong>Terrain:</strong> <?= htmlspecialchars($car['terrain']) ?></p>
        <p><strong>Details:</strong><br><?= nl2br(htmlspecialchars($car['details'])) ?></p>

        <a href="index.php" class="btn-back">&larr; Back to All Cars</a>
      </div>

    <?php elseif (!$viewCarId && isset($cars)): ?>
      <div class="cars-container">
        <?php while ($car = $cars->fetch_assoc()): ?>
          <div class="car-card">
            <img src="images/<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['model']) ?>">
            <div class="car-content">
              <h3><?= htmlspecialchars($car['brand']) ?> <?= htmlspecialchars($car['model']) ?></h3>
              <div class="price">Rs <?= number_format($car['price_per_day']) ?>/day</div>
              <a href="index.php?view=<?= $car['car_id'] ?>" class="details-btn">See More</a>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p style="text-align:center; margin-top: 40px;">No cars found matching your filters.</p>
    <?php endif; ?>
  </main>

  <footer>&copy; <?= date('Y') ?> BroCar Rental. All rights reserved.</footer>

  <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
  <script>
    const swiper = new Swiper('.swiper', {
      loop: true,
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      autoplay: {
        delay: 3000,
        disableOnInteraction: false
      }
    });
  </script>
</body>
</html>
