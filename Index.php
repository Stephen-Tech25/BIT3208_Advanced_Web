<?php
// Initialize a variable to remember which section to display after a POST request
$current_section = 'home'; 

// 1. DATABASE CONNECTION (Using PDO for security)
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "crime_reporting_system";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// 2. REGISTER USER logic
if (isset($_POST['register'])) {
    $current_section = 'register'; // Keep user on registration container if something happens
    
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Securely hash the password before saving
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$fullname, $email, $hashed_password])) {
            echo "<script>alert('Registration Successful!');</script>";
            $current_section = 'login'; // Move them to login automatically
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Integrity constraint violation (Duplicate Email)
            echo "<script>alert('Error: This email is already registered.');</script>";
        } else {
            echo "<script>alert('Registration Failed. Try again.');</script>";
        }
    }
}

// 3. LOGIN USER logic
if (isset($_POST['login'])) {
    $current_section = 'login';
    
    $email = trim($_POST['loginEmail']);
    $password = $_POST['loginPassword'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify hashed password
    if ($user && password_verify($password, $user['password'])) {
        echo "<script>alert('Login Successful! Welcome back, " . addslashes($user['fullname']) . "');</script>";
        $current_section = 'home';
    } else {
        echo "<script>alert('Invalid Email or Password');</script>";
    }
}

// 4. SUBMIT CRIME REPORT logic
if (isset($_POST['submit_report'])) {
    $current_section = 'report';

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    
    $evidence = $_FILES['evidence']['name'];
    $temp_name = $_FILES['evidence']['tmp_name'];

    // Handle file upload safely
    if (!empty($evidence)) {
        // Create uploads directory if it doesn't exist
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        // Append unique timestamp to prevent overriding files with identical names
        $evidence = time() . "_" . basename($evidence);
        move_uploaded_file($temp_name, "uploads/" . $evidence);
    } else {
        $evidence = null;
    }

    $stmt = $pdo->prepare("INSERT INTO reports (title, description, location, evidence) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$title, $description, $location, $evidence])) {
        echo "<script>alert('Crime Report Submitted Successfully!');</script>";
        $current_section = 'home';
    } else {
        echo "<script>alert('Submission Failed.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crime Reporting System</title>
    <style>
        * {
            margin: 0; padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body { background: #f4f4f4; }
        nav {
            background: #111; color: white;
            display: flex; justify-content: space-between;
            align-items: center; padding: 15px 40px;
        }
        nav ul { display: flex; list-style: none; }
        nav ul li { margin-left: 20px; }
        nav ul li a { color: white; text-decoration: none; cursor: pointer; }
        .hero { text-align: center; margin-top: 80px; }
        .hero h1 { font-size: 45px; margin-bottom: 20px; }
        .hero p { font-size: 20px; margin-bottom: 30px; }
        button {
            padding: 12px 25px; border: none;
            background: #007BFF; color: white;
            border-radius: 5px; cursor: pointer;
        }
        .container {
            width: 400px; background: white;
            margin: 40px auto; padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
        }
        .container h2 { margin-bottom: 20px; text-align: center; }
        input, textarea {
            width: 100%; padding: 12px;
            margin-bottom: 15px; border: 1px solid #ccc;
            border-radius: 5px;
        }
        textarea { height: 120px; }
        .hidden { display: none; }
    </style>
</head>
<body>

    <nav>
        <h2>Crime Reporting System</h2>
        <ul>
            <li><a onclick="showSection('home')">Home</a></li>
            <li><a onclick="showSection('login')">Login</a></li>
            <li><a onclick="showSection('register')">Register</a></li>
            <li><a onclick="showSection('report')">Report Crime</a></li>
        </ul>
    </nav>

    <section id="homeSection" class="hero hidden">
        <h1>Report Crimes Safely Online</h1>
        <p>Citizens can report incidents securely with evidence and location details.</p>
        <button onclick="showSection('report')">Report Incident</button>
    </section>

    <div id="loginSection" class="container hidden">
        <h2>Login</h2>
        <form method="POST" action="index.php" onsubmit="return validateLogin()">
            <input type="email" name="loginEmail" id="loginEmail" placeholder="Enter Email" required>
            <input type="password" name="loginPassword" id="loginPassword" placeholder="Enter Password" required>
            <button type="submit" name="login">Login</button>
        </form>
    </div>

    <div id="registerSection" class="container hidden">
        <h2>Create Account</h2>
        <form method="POST" action="index.php" onsubmit="return validateRegister()">
            <input type="text" name="fullname" id="fullname" placeholder="Full Name" required>
            <input type="email" name="email" id="registerEmail" placeholder="Email" required>
            <input type="password" name="password" id="registerPassword" placeholder="Password" required>
            <button type="submit" name="register">Register</button>
        </form>
    </div>

    <div id="reportSection" class="container hidden">
        <h2>Report Crime</h2>
        <form method="POST" action="index.php" enctype="multipart/form-data" onsubmit="return validateReport()">
            <input type="text" name="title" id="title" placeholder="Incident Title" required>
            <textarea name="description" id="description" placeholder="Describe Incident" required></textarea>
            <input type="text" name="location" id="location" placeholder="Incident Location" required>
            <input type="file" name="evidence" id="evidence">
            <button type="submit" name="submit_report">Submit Report</button>
        </form>
    </div>

    <script>
        function showSection(section){
            document.getElementById("homeSection").classList.add("hidden");
            document.getElementById("loginSection").classList.add("hidden");
            document.getElementById("registerSection").classList.add("hidden");
            document.getElementById("reportSection").classList.add("hidden");

            if(section === "home") document.getElementById("homeSection").classList.remove("hidden");
            if(section === "login") document.getElementById("loginSection").classList.remove("hidden");
            if(section === "register") document.getElementById("registerSection").classList.remove("hidden");
            if(section === "report") document.getElementById("reportSection").classList.remove("hidden");
        }

        // On page load, dynamically display whichever section PHP tells us to.
        window.onload = function() {
            showSection('<?php echo $current_section; ?>');
        };

        // Client-side JS Validations
        function validateLogin(){
            let email = document.getElementById("loginEmail").value;
            let password = document.getElementById("loginPassword").value;
            if(email === "" || password === ""){
                alert("All login fields are required");
                return false;
            }
            return true;
        }

        function validateRegister(){
            let fullname = document.getElementById("fullname").value;
            let email = document.getElementById("registerEmail").value;
            let password = document.getElementById("registerPassword").value;
            if(fullname === "" || email === "" || password === ""){
                alert("All registration fields are required");
                return false;
            }
            if(password.length < 6){
                alert("Password must be at least 6 characters");
                return false;
            }
            return true;
        }

        function validateReport(){
            let title = document.getElementById("title").value;
            let description = document.getElementById("description").value;
            let location = document.getElementById("location").value;
            if(title === "" || description === "" || location === ""){
                alert("Please fill all report fields");
                return false;
            }
            return true;
        }
    </script>
</body>
</html>