<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

/**
 * ==============================================================================
 * 🌿 ระบบ CHILL42x Dispensary - หน้าเข้าสู่ระบบ (Login Page)
 * ==============================================================================
 * 
 * 💡 คำอธิบายภาพรวมสำหรับนำเสนอหน้าชั้นเรียน (สำหรับคนไม่มีพื้นฐาน PHP):
 * 1. ไฟล์นี้เป็นภาษา "PHP" ซึ่งทำงานที่ฝั่ง Server (เครื่องแม่ข่าย) ก่อนส่งผลลัพธ์เป็น HTML มาแสดงผลที่หน้าจอของผู้ใช้ (Browser)
 * 2. หน้านี้ทำหน้าที่ 2 บทบาทในไฟล์เดียว:
 *    - บทบาทที่ 1 (GET Request): เมื่อเปิดเข้ามาครั้งแรก -> แสดงแบบฟอร์มให้กรอก Username และ Password
 *    - บทบาทที่ 2 (POST Request): เมื่อผู้ใช้กดปุ่ม "เข้าสู่ระบบ" -> นำข้อมูลมาตรวจความถูกต้อง แล้วส่งต่อไปยังหน้าถัดไปตามสิทธิ์ (Admin หรือ Staff)
 * 3. มีระบบจำสถานะ (Session) ว่าใครเข้าสู่ระบบอยู่ เพื่อป้องกันไม่ให้คนนอกแอบเข้าใช้งาน
 */

// ==============================================================================
// 📌 ส่วนที่ 1: การนำเข้าไฟล์เสริม (Import / Dependencies)
// ==============================================================================
// 'require_once' คือคำสั่งดึงโค้ดจากไฟล์อื่นเข้ามาใช้งานในไฟล์นี้ (ถ้าไม่มีไฟล์นี้อยู่จริง ระบบจะหยุดทำงานทันทีเพื่อความปลอดภัย)
// '__DIR__' คือตัวแปรพิเศษของ PHP ที่เก็บตำแหน่งโฟลเดอร์ปัจจุบันของไฟล์นี้ (ทำให้ path ไม่เพี้ยนไม่ว่าจะรันจากที่ไหน)
require_once __DIR__ . '/includes/auth.php'; // ดึงระบบ Session และฟังก์ชันตรวจสอบการล็อกอิน
require_once __DIR__ . '/classes/User.php';     // ดึงคลาส User (OOP) สำหรับตรวจสอบผู้ใช้งาน

// ==============================================================================
// 📌 ส่วนที่ 2: ตรวจสอบว่า "ล็อกอินค้างไว้อยู่แล้วหรือไม่?"
// ==============================================================================
// ถ้ามีผู้ใช้งานล็อกอินค้างไว้อยู่แล้ว (เช่น เปิดแท็บใหม่ หรือกดย้อนกลับมา)
// เราจะไม่ให้เขากรอกซ้ำ แต่จะส่ง (Redirect) เขาไปยังหน้าที่ถูกต้องตามตำแหน่งทันที
if (isLoggedIn()) {
    if (isAdmin()) {
        // ถ้าเป็นผู้จัดการ (Admin) -> ส่งไปหน้ารายการสินค้า/จัดการสต็อก
        header('Location: admin/product-list.php');
    } else {
        // ถ้าเป็นพนักงานขายทั่วไป (Staff) -> ส่งไปหน้าระบบขายหน้าร้าน (POS)
        header('Location: pos.php');
    }
    // 'exit()' สั่งให้ PHP หยุดทำงานทันทีหลังจากสั่งเปลี่ยนหน้า เพื่อไม่ให้ส่งโค้ดด้านล่างไปประมวลผลต่อ
    exit();
}

// ==============================================================================
// 📌 ส่วนที่ 3: จัดการข้อความแจ้งเตือนความผิดพลาด (Flash Message)
// ==============================================================================
// สร้างตัวแปร $errorMessage ไว้เก็บข้อความเตือน (เริ่มต้นเป็นค่าว่าง '')
$errorMessage = '';

// ตรวจสอบว่ามีข้อความเตือนที่ส่งข้ามหน้ามาจากหน้าอื่นหรือไม่ (ผ่านตัวแปร Session)
// ตัวอย่างเช่น ถ้าผู้ใช้พยายามแอบพิมพ์ URL เข้าหน้า Admin ตรงๆ ระบบจะเตือนว่า "กรุณาเข้าสู่ระบบก่อน"
if (isset($_SESSION['flash_error'])) {
    $errorMessage = $_SESSION['flash_error'];
    // ใช้คำสั่ง 'unset' เพื่อลบข้อความทิ้งหลังอ่านเสร็จ เมื่อผู้ใช้รีเฟรชหน้าจอ ข้อความเตือนจะได้ไม่ค้าง
    unset($_SESSION['flash_error']);
}

// ==============================================================================
// 📌 ส่วนที่ 4: การประมวลผลเมื่อผู้ใช้ "กดปุ่มส่งแบบฟอร์ม" (Form Submission)
// ==============================================================================
// $_SERVER['REQUEST_METHOD'] คือตัวแปรที่บอกว่าผู้ใช้เรียกหน้านี้แบบไหน
// - 'GET' = เปิดหน้าเว็บขึ้นมาดูเฉยๆ
// - 'POST' = ผู้ใช้กดปุ่ม Submit เพื่อส่งข้อมูลจากฟอร์มมาให้ Server ตรวจสอบ
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {

    // รับค่าจากช่อง input ที่มีชื่อ name="username" และ name="password"
    // ฟังก์ชัน 'trim()' ใช้ตัดช่องว่าง (Spacebar) ที่อาจเผลอกดหน้าหรือหลังข้อความออก
    // เครื่องหมาย '?? ''' เรียกว่า Null Coalescing Operator ถ้าไม่มีค่าส่งมา ให้ถือว่าเป็นข้อความว่าง
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // ตรวจสอบความถูกต้องเบื้องต้น (Validation): ช่องใดช่องหนึ่งว่างหรือไม่?
    if (empty($username) || empty($password)) {
        $errorMessage = 'กรุณากรอกชื่อผู้ใช้งานและรหัสผ่านให้ครบถ้วน';
    } else {
        // ใช้โครงสร้าง 'try ... catch' เพื่อดักจับข้อผิดพลาด (Exception)
        // เพื่อป้องกันไม่ให้เว็บขึ้นหน้าจอ Error ขาวโพลน หากระบบฐานข้อมูลมีปัญหา
        try {
            // เรียกใช้ Method สถิตย์ (Static Method) ของคลาส User ตามหลัก OOP
            // ไปเช็คกับฐานข้อมูลว่ามี Username นี้จริงไหม และรหัสผ่านถูกต้องหรือไม่
            $user = User::authenticate($username, $password);

            if ($user) {
                // 🟢 กรณีที่ 1: ล็อกอินผ่าน (ข้อมูลถูกต้อง)
                // เราจะเก็บข้อมูลสำคัญของผู้ใช้ลงใน '$_SESSION' (เปรียบเสมือนป้ายชื่อ/บัตรผ่านประจำตัว)
                // เพื่อให้ทุกหน้าที่เปิดต่อไปรู้ว่าผู้ใช้คนนี้คือใคร
                $_SESSION['user_id']   = $user->getId();
                $_SESSION['username']  = $user->getUsername();
                $_SESSION['full_name'] = $user->getFullName();
                $_SESSION['role']      = $user->getRole(); // เก็บสิทธิ์ เช่น 'admin' หรือ 'staff'

                // ตรวจสอบสิทธิ์ (Role-based Redirection) เพื่อส่งไปยังหน้าที่เหมาะสม
                if ($user->getRole() === 'admin') {
                    // แอดมิน -> ไปหน้าจัดการสต็อกหลังร้าน
                    header('Location: admin/product-list.php');
                } else {
                    // พนักงานขาย -> ไปหน้าแคชเชียร์ขายหน้าร้าน (POS)
                    header('Location: pos.php');
                }
                exit(); // จบการทำงานทันทีหลังจากสั่งเปลี่ยนหน้า

            } else {
                // 🔴 กรณีที่ 2: ล็อกอินไม่ผ่าน (ชื่อหรือรหัสผ่านผิด)
                $errorMessage = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (ลองใช้ admin/admin123 หรือ staff/staff123)';
            }
        } catch (\Throwable $e) {
            // ดักจับกรณีเกิดข้อผิดพลาดฉุกเฉิน เช่น ฐานข้อมูลดับ
            $errorMessage = 'เกิดข้อผิดพลาดของระบบ: ' . $e->getMessage();
        }
    }
}
?>
<!-- ==============================================================================
     📌 ส่วนที่ 5: หน้าตาการแสดงผล (User Interface - HTML5 / CSS)
     ส่วนนี้จะถูกส่งไปแสดงผลที่เว็บเบราว์เซอร์ของผู้ใช้งาน
     ============================================================================== -->
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - CHILL42x Dispensary</title>
    <!-- ลิงก์ไฟล์ตกแต่งหน้าตา (CSS) ของระบบ -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            
            <!-- โลโก้และหัวข้อของร้านค้า -->
            <div class="login-header">
                <div class="login-logo">🌿</div>
                <h1 class="login-title">CHILL42x<span class="brand-highlight">.shop</span></h1>
                <p class="login-subtitle">Craft Dispensary, Top Clones & Tincture Oil Drops สายหยอด</p>
            </div>

            <!-- กล่องแจ้งเตือนสีแดง: จะแสดงผลก็ต่อเมื่อมีข้อผิดพลาด ($errorMessage ไม่ว่าง) -->
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger">
                    <!-- ไอคอนตกแต่ง SVG -->
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <!-- 'htmlspecialchars' ใช้แปลงอักขระพิเศษเพื่อป้องกันการถูกแฮกด้วยช่องโหว่ XSS (Cross-Site Scripting) -->
                    <span><?= htmlspecialchars($errorMessage) ?></span>
                </div>
            <?php endif; ?>

            <!-- ฟอร์มเข้าสู่ระบบ: ส่งข้อมูลแบบ POST กลับมาประมวลผลที่ไฟล์เดิม (login.php) -->
            <form action="login.php" method="POST" id="loginForm">
                
                <!-- ช่องกรอกชื่อผู้ใช้ (Username) -->
                <div class="form-group">
                    <label for="username" class="form-label">ชื่อผู้ใช้งาน (Username) <span class="required">*</span></label>
                    <!-- 'value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"' ช่วยจำค่าชื่อผู้ใช้ไว้ ไม่ต้องพิมพ์ใหม่ถ้ากรอกรหัสผิด -->
                    <input type="text" id="username" name="username" class="form-control" required placeholder="เช่น admin หรือ staff" autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <!-- ช่องกรอกรหัสผ่าน (Password) ชนิด type="password" เพื่อซ่อนข้อความให้เป็นจุดดำ -->
                <div class="form-group">
                    <label for="password" class="form-label">รหัสผ่าน (Password) <span class="required">*</span></label>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="กรอกรหัสผ่าน">
                </div>

                <!-- ปุ่ม Submit สำหรับส่งแบบฟอร์ม -->
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                    เข้าสู่ระบบ CHILL42x
                </button>
            </form>

            <!-- ==============================================================================
                 📌 ส่วนที่ 6: ปุ่มอำนวยความสะดวกสำหรับอาจารย์/ผู้ตรวจ (Demo Quick Fill)
                 กดปุ่มเดียวแล้วจะกรอก Username และ Password ให้อัตโนมัติทันที
                 ============================================================================== -->
            <div class="demo-account-hint">
                <div style="font-weight: 600; color: var(--accent-gold); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                    <span>🔑</span> บัญชีทดสอบระบบทันที (Quick Fill):
                </div>
                <div style="display: flex; justify-content: space-between; gap: 8px;">
                    <!-- เมื่อกดปุ่ม จะเรียกคำสั่ง JavaScript ชื่อ quickFill(...) ด้านล่าง -->
                    <button type="button" class="btn btn-secondary btn-sm" style="flex: 1;" onclick="quickFill('admin', 'admin123')">
                        ผู้จัดการ (Admin)
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" style="flex: 1;" onclick="quickFill('staff', 'staff123')">
                        พนักงานขาย (Staff)
                    </button>
                </div>
                <div style="margin-top: 10px; text-align: center;">
                    <a href="index.php" style="color: var(--text-muted); font-size: 0.8rem; text-decoration: none;">← กลับสู่หน้าแรก CHILL42x Overview</a>
                </div>
            </div>

        </div>
    </div>

    <!-- ==============================================================================
         📌 ส่วนที่ 7: JavaScript ฝั่ง Client (ทำงานบนเบราว์เซอร์ของผู้ใช้)
         ทำหน้าที่ใส่ค่าลงในช่อง input อัตโนมัติเมื่อกดปุ่ม Demo Account
         ============================================================================== -->
    <script>
        function quickFill(user, pass) {
            // ค้นหาช่อง input ตาม ID แล้วใส่ค่าเข้าไป
            document.getElementById('username').value = user;
            document.getElementById('password').value = pass;
        }
    </script>
</body>
</html>

