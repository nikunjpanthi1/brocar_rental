<?php
session_start();
require 'db.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$msg = "";

// DELETE IMAGE (from additional images)
if (isset($_GET['delete_image_id'])) {
    $delImgId = (int)$_GET['delete_image_id'];

    // Fetch filename to delete physical file
    $res = $conn->prepare("SELECT image_path FROM car_images WHERE image_id = ?");
    $res->bind_param("i", $delImgId);
    $res->execute();
    $result = $res->get_result();
    if ($row = $result->fetch_assoc()) {
        @unlink("images/" . $row['image_path']); // suppress warning if missing
    }

    // Delete record
    $stmtDel = $conn->prepare("DELETE FROM car_images WHERE image_id = ?");
    $stmtDel->bind_param("i", $delImgId);
    $stmtDel->execute();

    header("Location: admin_dashboard.php?msg=" . urlencode("Image deleted!"));
    exit;
}

// ADD CAR
if (isset($_POST['add_car'])) {
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $manufacturer = $_POST['manufacturer'];
    $mfgyear = (int)$_POST['mfgyear'];
    $seater = (int)$_POST['seater'];
    $fuel = $_POST['fuel_type'];
    $trans = $_POST['transmission'];
    $price = (float)$_POST['price_per_day'];
    $luxury = (int)$_POST['luxury'];
    $terrain = $_POST['terrain'];
    $quantity = (int)$_POST['quantity'];
    $details = $_POST['details'] ?? '';

    // Handle cover image upload
    $coverImage = "";
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
        $coverImage = uniqid('cover_') . '_' . basename($_FILES['cover_image']['name']);
        move_uploaded_file($_FILES['cover_image']['tmp_name'], "images/" . $coverImage);
    }

    $stmt = $conn->prepare("INSERT INTO cars (brand, model, manufacturer, mfgyear, seater, fuel_type, transmission, price_per_day, image, quantity, luxury, terrain, details) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssisssdsiiss", $brand, $model, $manufacturer, $mfgyear, $seater, $fuel, $trans, $price, $coverImage, $quantity, $luxury, $terrain, $details);
    $stmt->execute();

    $car_id = $stmt->insert_id;

    // Handle multiple additional images
    if (isset($_FILES['additional_images'])) {
        $files = $_FILES['additional_images'];
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === 0) {
                $tmp_name = $files['tmp_name'][$i];
                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $filename = uniqid('carimg_') . '.' . $ext;
                move_uploaded_file($tmp_name, "images/" . $filename);

                // For now label empty
                $label = "";

                $stmtImg = $conn->prepare("INSERT INTO car_images (car_id, image_path, label) VALUES (?, ?, ?)");
                $stmtImg->bind_param("iss", $car_id, $filename, $label);
                $stmtImg->execute();
            }
        }
    }

    $msg = "Car added successfully!";
}

// EDIT CAR
if (isset($_POST['edit_car'])) {
    $car_id = (int)$_POST['car_id'];
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $manufacturer = $_POST['manufacturer'];
    $mfgyear = (int)$_POST['mfgyear'];
    $seater = (int)$_POST['seater'];
    $fuel = $_POST['fuel_type'];
    $trans = $_POST['transmission'];
    $price = (float)$_POST['price_per_day'];
    $luxury = (int)$_POST['luxury'];
    $terrain = $_POST['terrain'];
    $quantity = (int)$_POST['quantity'];
    $details = $_POST['details'] ?? '';

    // Check if new cover image uploaded
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
        $coverImage = uniqid('cover_') . '_' . basename($_FILES['cover_image']['name']);
        move_uploaded_file($_FILES['cover_image']['tmp_name'], "images/" . $coverImage);
        // Update with new image
        $stmt = $conn->prepare("UPDATE cars SET brand=?, model=?, manufacturer=?, mfgyear=?, seater=?, fuel_type=?, transmission=?, price_per_day=?, image=?, quantity=?, luxury=?, terrain=?, details=? WHERE car_id=?");
        $stmt->bind_param("sssisssdsiissi", $brand, $model, $manufacturer, $mfgyear, $seater, $fuel, $trans, $price, $coverImage, $quantity, $luxury, $terrain, $details, $car_id);
    } else {
        // No new image uploaded, keep old image
        $stmt = $conn->prepare("UPDATE cars SET brand=?, model=?, manufacturer=?, mfgyear=?, seater=?, fuel_type=?, transmission=?, price_per_day=?, quantity=?, luxury=?, terrain=?, details=? WHERE car_id=?");
        $stmt->bind_param("sssisssdsiisi", $brand, $model, $manufacturer, $mfgyear, $seater, $fuel, $trans, $price, $quantity, $luxury, $terrain, $details, $car_id);
    }

    $stmt->execute();

    // Handle additional images upload (optional)
    if (isset($_FILES['additional_images'])) {
        $files = $_FILES['additional_images'];
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === 0) {
                $tmp_name = $files['tmp_name'][$i];
                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $filename = uniqid('carimg_') . '.' . $ext;
                move_uploaded_file($tmp_name, "images/" . $filename);

                $label = "";

                $stmtImg = $conn->prepare("INSERT INTO car_images (car_id, image_path, label) VALUES (?, ?, ?)");
                $stmtImg->bind_param("iss", $car_id, $filename, $label);
                $stmtImg->execute();
            }
        }
    }

    $msg = "Car updated successfully!";
}

// DELETE CAR
if (isset($_GET['delete_car'])) {
    $id = (int)$_GET['delete_car'];

    // Delete cover image file from server
    $resImg = $conn->prepare("SELECT image FROM cars WHERE car_id = ?");
    $resImg->bind_param("i", $id);
    $resImg->execute();
    $resultImg = $resImg->get_result();
    if ($row = $resultImg->fetch_assoc()) {
        @unlink("images/" . $row['image']);
    }

    // Delete additional images files from server
    $resImgs = $conn->prepare("SELECT image_path FROM car_images WHERE car_id = ?");
    $resImgs->bind_param("i", $id);
    $resImgs->execute();
    $resultImgs = $resImgs->get_result();
    while ($imgRow = $resultImgs->fetch_assoc()) {
        @unlink("images/" . $imgRow['image_path']);
    }

    // Delete DB records
    $conn->query("DELETE FROM car_images WHERE car_id = $id");
    $conn->query("DELETE FROM cars WHERE car_id = $id");

    $msg = "Car deleted!";
}

// Fetch cars + their additional images count for display
$cars = $conn->query("SELECT * FROM cars ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Admin Dashboard - Manage Cars</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f7fa;
        margin: 0;
        padding: 0;
    }
    nav {
        background-color: #007bff;
        padding: 15px 30px;
        display: flex;
        align-items: center;
        color: white;
    }
    nav .nav-item {
        margin-right: 20px;
        cursor: pointer;
        font-weight: bold;
    }
    nav .nav-item.active {
        text-decoration: underline;
    }
    nav .logout {
        margin-left: auto;
        background: #dc3545;
        padding: 8px 16px;
        border-radius: 6px;
        color: white;
        text-decoration: none;
        font-weight: bold;
    }
    nav .logout:hover {
        background: #c82333;
    }
    .container {
        max-width: 1000px;
        margin: 30px auto;
        background: white;
        padding: 25px 30px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        position: relative;
    }
    h1 {
        margin-top: 0;
        color: #333;
    }
    #flash {
        background-color: #d4edda;
        color: #155724;
        padding: 12px 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        display: none;
        font-weight: bold;
        text-align: center;
    }
    .btn {
        background-color: #007bff;
        border: none;
        color: white;
        padding: 12px 25px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 16px;
    }
    .btn:hover {
        opacity: 0.9;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 30px;
    }
    th, td {
        border: 1px solid #ddd;
        padding: 12px 15px;
        text-align: center;
    }
    th {
        background-color: #007bff;
        color: white;
        user-select: none;
    }
    td img {
        max-width: 80px;
        border-radius: 6px;
    }
    .actions a {
        margin: 0 8px;
        font-size: 20px;
        text-decoration: none;
        cursor: pointer;
        color: #007bff;
    }
    .actions a.delete {
        color: #dc3545;
    }

    /* Hide add car form initially */
    #addCarForm {
        display: none;
        margin-top: 30px;
    }
    /* Container form style */
#addCarForm {
  background: #fff;
  border-radius: 10px;
  padding: 25px 30px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  max-width: 600px;
  margin: 25px auto;
  display: none; /* Initially hidden, show on clicking Add Car */
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Form inputs and textarea */
#addCarForm input[type="text"],
#addCarForm input[type="number"],
#addCarForm input[type="file"],
#addCarForm select,
#addCarForm textarea {
  width: 100%;
  padding: 12px 15px;
  margin: 10px 0 20px 0;
  border-radius: 6px;
  border: 1.8px solid #ccc;
  font-size: 16px;
  font-weight: 400;
  transition: border-color 0.3s ease;
  box-sizing: border-box;
  outline-offset: 2px;
}

/* Highlight input on focus */
#addCarForm input[type="text"]:focus,
#addCarForm input[type="number"]:focus,
#addCarForm input[type="file"]:focus,
#addCarForm select:focus,
#addCarForm textarea:focus {
  border-color: #007bff;
  box-shadow: 0 0 5px rgba(0,123,255,0.5);
  outline: none;
}

/* Labels */
#addCarForm label {
  display: block;
  font-weight: 600;
  margin-bottom: 6px;
  color: #333;
  font-size: 15px;
}

/* Textarea customization */
#addCarForm textarea {
  resize: vertical;
  min-height: 80px;
  font-family: inherit;
}

/* Submit button */
#addCarForm button[type="submit"] {
  background-color: #007bff;
  color: white;
  border: none;
  padding: 14px 25px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 18px;
  cursor: pointer;
  transition: background-color 0.3s ease;
  width: 100%;
  box-shadow: 0 3px 8px rgba(0,123,255,0.3);
  margin-top: 15px;
}

#addCarForm button[type="submit"]:hover {
  background-color: #0056b3;
  box-shadow: 0 4px 15px rgba(0,86,179,0.4);
}

/* Responsive adjustments */
@media screen and (max-width: 650px) {
  #addCarForm {
    padding: 20px 20px;
    max-width: 90%;
  }
}

    /* Modal styles */
    #editModal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0; top: 0;
        width: 100%; height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.5);
    }
    #editModal .modal-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 20px 30px;
        border-radius: 10px;
        width: 600px;
        max-width: 90%;
        position: relative;
        max-height: 90vh;
        overflow-y: auto;
    }
    #editModal h2 {
        margin-top: 0;
    }
    #editModal .close {
        position: absolute;
        top: 12px;
        right: 18px;
        font-size: 24px;
        font-weight: bold;
        color: #555;
        cursor: pointer;
    }
    #editModal form input, #editModal form select, #editModal form textarea {
        width: 100%;
        padding: 8px;
        margin: 10px 0 15px 0;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 14px;
        box-sizing: border-box;
    }
    #editModal form label {
        font-weight: bold;
        display: block;
    }
    #editModal form button {
        background-color: #28a745;
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        font-size: 16px;
    }
    #editModal form button:hover {
        opacity: 0.9;
    }
    #existingImages div {
        margin-bottom: 10px;
        display: flex;
        align-items: center;
    }
    #existingImages img {
        max-width: 80px;
        border-radius: 6px;
        margin-right: 15px;
    }
    #existingImages a.delete-image {
        color: #dc3545;
        cursor: pointer;
        margin-left: auto;
        font-weight: bold;
        text-decoration: none;
    }
    #existingImages a.delete-image:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>

<nav>
    <div class="nav-item active">Manage Cars</div>
    <a href="logout.php" class="logout">Logout</a>
</nav>

<div class="container">
    <div id="flash"><?= htmlspecialchars($msg) ?></div>

    <h1>Manage Cars</h1>

    <button id="showAddCarBtn" class="btn">+ Add Car</button>

    <form method="POST" enctype="multipart/form-data" id="addCarForm">
        <input name="brand" placeholder="Brand" required>
        <input name="model" placeholder="Model" required>
        <input name="manufacturer" placeholder="Manufacturer" required>
        <input name="mfgyear" type="number" placeholder="Manufacturing Year" required>
        <input name="seater" type="number" placeholder="Seater" required>

        <label for="fuel_type">Fuel Type</label>
        <select name="fuel_type" id="fuel_type" required>
            <option value="">Select Fuel Type</option>
            <option value="petrol">Petrol</option>
            <option value="diesel">Diesel</option>
            <option value="ev">Electric</option>
        </select>

        <label for="transmission">Transmission</label>
        <select name="transmission" id="transmission" required>
            <option value="">Select Transmission</option>
            <option value="manual">Manual</option>
            <option value="auto">Automatic</option>
        </select>

        <label for="luxury">Luxury</label>
        <select name="luxury" id="luxury" required>
            <option value="0">No</option>
            <option value="1">Yes</option>
        </select>

        <label for="terrain">Terrain</label>
        <select name="terrain" id="terrain" required>
            <option value="">Select Terrain</option>
            <option value="city">City</option>
            <option value="highway">Highway</option>
            <option value="offroad">Offroad</option>
        </select>

        <input name="quantity" type="number" placeholder="Quantity" required>
        <input name="price_per_day" type="number" placeholder="Price Per Day (Rs)" step="0.01" required>
        <textarea name="details" placeholder="More Details (optional)" rows="3"></textarea>

        <label>Cover Image</label>
        <input type="file" name="cover_image" required>

        <label>Additional Images (you can select multiple)</label>
        <input type="file" name="additional_images[]" multiple>

        <button type="submit" class="btn" name="add_car">Add Car</button>
    </form>

    <h2>All Cars</h2>
    <table>
        <thead>
            <tr>
                <th>Image</th><th>Brand</th><th>Model</th><th>Manufacturer</th><th>Year</th><th>Price</th><th>Quantity</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($car = $cars->fetch_assoc()):
            // Fetch additional images for this car
            $stmtImgs = $conn->prepare("SELECT * FROM car_images WHERE car_id = ?");
            $stmtImgs->bind_param("i", $car['car_id']);
            $stmtImgs->execute();
            $resultImgs = $stmtImgs->get_result();
            $images = [];
            while ($rowImg = $resultImgs->fetch_assoc()) {
                $images[] = $rowImg;
            }
        ?>
            <tr data-car='<?= json_encode($car, JSON_HEX_APOS | JSON_HEX_QUOT) ?>' data-images='<?= json_encode($images, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                <td><img src="images/<?= htmlspecialchars($car['image']) ?>" alt="Car Image"></td>
                <td><?= htmlspecialchars($car['brand']) ?></td>
                <td><?= htmlspecialchars($car['model']) ?></td>
                <td><?= htmlspecialchars($car['manufacturer']) ?></td>
                <td><?= htmlspecialchars($car['mfgyear']) ?></td>
                <td>Rs <?= number_format($car['price_per_day'], 2) ?></td>
                <td><?= $car['quantity'] ?></td>
                <td class="actions">
                    <a href="javascript:void(0)" class="edit" title="Edit">✏️</a>
                    <a href="?delete_car=<?= $car['car_id'] ?>" class="delete" onclick="return confirm('Delete this car?')">🗑</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Edit Modal -->
<div id="editModal">
    <div class="modal-content">
        <span class="close" title="Close">&times;</span>
        <h2>Edit Car</h2>
        <form method="POST" enctype="multipart/form-data" id="editCarForm">
            <input type="hidden" name="car_id" id="edit_car_id">
            <input name="brand" id="edit_brand" placeholder="Brand" required>
            <input name="model" id="edit_model" placeholder="Model" required>
            <input name="manufacturer" id="edit_manufacturer" placeholder="Manufacturer" required>
            <input name="mfgyear" id="edit_mfgyear" type="number" placeholder="Manufacturing Year" required>
            <input name="seater" id="edit_seater" type="number" placeholder="Seater" required>

            <label for="edit_fuel_type">Fuel Type</label>
            <select name="fuel_type" id="edit_fuel_type" required>
                <option value="">Select Fuel Type</option>
                <option value="petrol">Petrol</option>
                <option value="diesel">Diesel</option>
                <option value="ev">Electric</option>
            </select>

            <label for="edit_transmission">Transmission</label>
            <select name="transmission" id="edit_transmission" required>
                <option value="">Select Transmission</option>
                <option value="manual">Manual</option>
                <option value="auto">Automatic</option>
            </select>

            <label for="edit_luxury">Luxury</label>
            <select name="luxury" id="edit_luxury" required>
                <option value="0">No</option>
                <option value="1">Yes</option>
            </select>

            <label for="edit_terrain">Terrain</label>
            <select name="terrain" id="edit_terrain" required>
                <option value="">Select Terrain</option>
                <option value="city">City</option>
                <option value="highway">Highway</option>
                <option value="offroad">Offroad</option>
            </select>

            <input name="quantity" id="edit_quantity" type="number" placeholder="Quantity" required>
            <input name="price_per_day" id="edit_price_per_day" type="number" placeholder="Price Per Day (Rs)" step="0.01" required>
            <textarea name="details" id="edit_details" placeholder="More Details (optional)" rows="3"></textarea>

            <label>Cover Image (leave empty to keep current)</label>
            <input type="file" name="cover_image" id="edit_cover_image">

            <label>Additional Images (you can select multiple)</label>
            <input type="file" name="additional_images[]" id="edit_additional_images" multiple>

            <h3>Existing Additional Images</h3>
            <div id="existingImages">
                <!-- Existing images will be loaded here dynamically -->
            </div>

            <button type="submit" class="btn" name="edit_car">Save Changes</button>
        </form>
    </div>
</div>

<script>
window.onload = function() {
    // Flash message show/hide
    const flash = document.getElementById('flash');
    if (flash.textContent.trim() !== "") {
        flash.style.display = "block";
        setTimeout(() => { flash.style.display = "none"; }, 3500);
    }

    // Toggle add car form
    const addCarBtn = document.getElementById('showAddCarBtn');
    const addCarForm = document.getElementById('addCarForm');
    addCarBtn.addEventListener('click', () => {
        if (addCarForm.style.display === 'none' || addCarForm.style.display === '') {
            addCarForm.style.display = 'block';
            addCarBtn.textContent = ' Back to admin dashboard';
        } else {
            addCarForm.style.display = 'none';
            addCarBtn.textContent = '+ Add Car';
        }
    });

    // Modal controls
    const editModal = document.getElementById('editModal');
    const closeModalBtn = editModal.querySelector('.close');
    const existingImagesDiv = document.getElementById('existingImages');

    // Fill modal with car data and show
    document.querySelectorAll('a.edit').forEach(btn => {
        btn.addEventListener('click', e => {
            const tr = e.target.closest('tr');
            const carData = JSON.parse(tr.getAttribute('data-car'));
            const imagesData = JSON.parse(tr.getAttribute('data-images'));

            document.getElementById('edit_car_id').value = carData.car_id;
            document.getElementById('edit_brand').value = carData.brand;
            document.getElementById('edit_model').value = carData.model;
            document.getElementById('edit_manufacturer').value = carData.manufacturer;
            document.getElementById('edit_mfgyear').value = carData.mfgyear;
            document.getElementById('edit_seater').value = carData.seater;
            document.getElementById('edit_fuel_type').value = carData.fuel_type;
            document.getElementById('edit_transmission').value = carData.transmission;
            document.getElementById('edit_luxury').value = carData.luxury;
            document.getElementById('edit_terrain').value = carData.terrain;
            document.getElementById('edit_quantity').value = carData.quantity;
            document.getElementById('edit_price_per_day').value = carData.price_per_day;
            document.getElementById('edit_details').value = carData.details;

            // Show existing additional images with delete links
            existingImagesDiv.innerHTML = '';
            if(imagesData.length === 0) {
                existingImagesDiv.innerHTML = '<p>No additional images.</p>';
            } else {
                imagesData.forEach(img => {
                    const div = document.createElement('div');
                    div.innerHTML = `
                        <img src="images/${img.image_path}" alt="Additional Image">
                        <a href="?delete_image_id=${img.image_id}" class="delete-image" onclick="return confirm('Delete this image?')">Delete</a>
                    `;
                    existingImagesDiv.appendChild(div);
                });
            }

            editModal.style.display = 'block';
        });
    });

    // Close modal
    closeModalBtn.addEventListener('click', () => {
        editModal.style.display = 'none';
        document.getElementById('editCarForm').reset();
        existingImagesDiv.innerHTML = '';
    });

    // Close modal when clicking outside modal content
    window.addEventListener('click', e => {
        if (e.target === editModal) {
            editModal.style.display = 'none';
            document.getElementById('editCarForm').reset();
            existingImagesDiv.innerHTML = '';
        }
    });
};
</script>

</body>
</html>
