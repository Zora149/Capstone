<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: context/Login.php");
    exit();
}

// Include DB connection
include '../../connection/db_connect.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $fullname = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password_input = $_POST['password'] ?? '';
        $address = trim($_POST['address'] ?? '');
        $mobile = trim($_POST['phone_number'] ?? '');
        $user_id = $_SESSION['user_id'];

        // Validation
        if ($fullname === '' || $username === '' || $address === '' || $mobile === '') {
            throw new Exception("All fields except password are required.");
        }

        // Check if username already exists (except current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :user_id");
        $stmt->execute([':username' => $username, ':user_id' => $user_id]);
        if ($stmt->fetch()) {
            throw new Exception("Username already taken. Please choose another one.");
        }

        // Fetch existing password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new Exception("User not found.");
        $existing_password = $row['password'];

        // Keep current password if left blank
        $new_password = $existing_password;
        if (!empty($password_input)) {
            $new_password = password_hash($password_input, PASSWORD_DEFAULT);
        }

        // Update user record
        $upd = $pdo->prepare("
            UPDATE users 
            SET fullname = :fullname, username = :username, password = :password, address = :address, mobile = :mobile
            WHERE id = :user_id
        ");
        $upd->execute([
            ':fullname' => $fullname,
            ':username' => $username,
            ':password' => $new_password,
            ':address' => $address,
            ':mobile' => $mobile,
            ':user_id' => $user_id
        ]);

        $_SESSION['username'] = $username;
        $success_message = "Profile updated successfully.";
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch updated user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) throw new Exception("User not found.");
} catch (Exception $e) {
    $error_message = "Error loading user: " . $e->getMessage();
    $user = [];
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Settings - EYC</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="css/style.css">
</head>
<body>


<div class="container mt-5">
  <div class="card mx-auto shadow" style="max-width:720px;">
    <div class="card-body">
      <h4 class="card-title mb-4">Account Settings</h4>

      <?php if ($success_message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
      <?php endif; ?>

      <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
        <div class="mb-3">
          <label for="fullname" class="form-label">Full Name</label>
          <input id="fullname" name="fullname" class="form-control" required
                 value="<?= htmlspecialchars($user['fullname'] ?? '') ?>">
        </div>

        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input id="username" name="username" class="form-control" required
                 value="<?= htmlspecialchars($user['username'] ?? '') ?>">
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <input id="password" name="password" type="password" class="form-control" placeholder="Enter new password (leave blank to keep current)">
            <button type="button" id="togglePassword" class="btn btn-outline-secondary">
              <i class="fa fa-eye"></i>
            </button>
          </div>
          <div class="form-text">Leave blank to keep your current password.</div>
        </div>

        <div class="mb-3">
          <label for="address" class="form-label">Address</label>
          <textarea id="address" name="address" class="form-control" rows="2" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
          <label for="phone_number" class="form-label">Phone Number</label>
          <input id="phone_number" name="phone_number" class="form-control" required
                 value="<?= htmlspecialchars($user['mobile'] ?? '') ?>">
        </div>

        <div class="text-end">
          <button class="btn btn-primary">
            <i class="fa fa-save"></i> Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const pwd = document.getElementById('password');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        this.innerHTML = '<i class="fa fa-eye-slash"></i>';
    } else {
        pwd.type = 'password';
        this.innerHTML = '<i class="fa fa-eye"></i>';
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
