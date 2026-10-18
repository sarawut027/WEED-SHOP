<?php
/**
 * ==============================================================================
 * 🔐 Authentication & Session Helper (ระบบจัดการสิทธิ์และ Session)
 * ==============================================================================
 * 
 * 💡 คำอธิบายภาพรวมสำหรับนำเสนอหน้าชั้นเรียน:
 * 1. "HTTP เป็น Stateless": ปกติเวลาเปิดเว็บ หน้าเว็บจะไม่รู้ว่าคำขอ (Request) แต่ละครั้งมาจากใคร
 * 2. "Session คืออะไร?": PHP มีระบบ Session เปรียบเสมือน "บัตรผ่าน/ริสต์แบนด์" ที่แปะไว้ที่ Server
 *    เมื่อผู้ใช้ล็อกอินสำเร็จ Server จะสร้างรหัส Session ID แล้วส่งคุกกี้ไปเก็บไว้ใน Browser ของผู้ใช้
 *    ทำให้ผู้ใช้เปิดหน้าไหน ระบบก็จำได้ตลอดว่าผู้ใช้นี้ล็อกอินแล้ว และมีสิทธิ์อะไร (Admin หรือ Staff)
 */

// ตรวจสอบว่ามี Session เริ่มทำงานอยู่หรือยัง ถ้ายังไม่มีให้สั่ง 'session_start()'
// เปรียบเสมือนการเปิดแฟ้มบันทึกข้อมูลของผู้ใช้ในรอบการเข้าชมปัจจุบัน
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * 🔍 ฟังก์ชันตรวจสอบว่า "ผู้ใช้ล็อกอินอยู่หรือไม่?"
 * คืนค่า: true (ล็อกอินแล้ว) / false (ยังไม่ได้ล็อกอิน)
 * หลักการ: ตรวจสอบว่าใน $_SESSION มีตัวแปร 'user_id' บันทึกไว้และไม่เป็นค่าว่างหรือไม่
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * 👑 ฟังก์ชันตรวจสอบว่า "ผู้ใช้เป็นผู้จัดการ (Admin) หรือไม่?"
 * คืนค่า: true (เป็น Admin) / false (เป็น Staff ทั่วไปหรือคนนอก)
 * หลักการ: ต้องล็อกอินก่อน (isLoggedIn) และสิทธิ์ (role) ใน Session ต้องระบุว่าเป็น 'admin'
 */
function isAdmin(): bool {
    return isLoggedIn() && (($_SESSION['role'] ?? '') === 'admin');
}

/**
 * 👤 ฟังก์ชันดึงข้อมูลผู้ใช้งานปัจจุบันที่กำลังล็อกอินอยู่
 * คืนค่า: Array ข้อมูล เช่น id, username, full_name, role
 */
function getCurrentUser(): array {
    if (isLoggedIn()) {
        return [
            'id'        => (int)$_SESSION['user_id'],
            'username'  => $_SESSION['username'] ?? 'user',
            'full_name' => $_SESSION['full_name'] ?? 'ผู้ใช้งาน',
            'role'      => $_SESSION['role'] ?? 'staff',
        ];
    }

    // ข้อมูลจำลองสำรอง (Default fallback) กรณีรันเพื่อทดสอบหน้าร้านโดยไม่ต้องล็อกอิน
    return [
        'id'        => 2,
        'username'  => 'staff',
        'full_name' => 'พนักงานขายหน้าร้าน (Staff)',
        'role'      => 'staff',
    ];
}

/**
 * 🛑 ฟังก์ชันบังคับล็อกอิน (Guard / Security Check)
 * ถ้ายังไม่ได้ล็อกอิน จะไม่อนุญาตให้เปิดหน้านี้ แต่จะเด้ง (Redirect) กลับไปหน้าล็อกอินทันที
 */
function requireLogin(string $redirect = 'login.php'): void {
    if (!isLoggedIn()) {
        // ฝากข้อความแจ้งเตือนไว้ใน Session เพื่อนำไปแสดงในหน้า login.php
        $_SESSION['flash_error'] = 'กรุณาเข้าสู่ระบบก่อนทำรายการ';
        header("Location: {$redirect}");
        exit(); // สั่งหยุดทำงานทันที ไม่ให้ส่งเนื้อหาหน้าเว็บลับออกไป
    }
}

/**
 * 🛡️ ฟังก์ชันจำกัดสิทธิ์เฉพาะระดับผู้จัดการ (Admin Guard)
 * ถ้าผู้ใช้ไม่ใช่ Admin (เช่น พนักงาน Staff แอบพิมพ์ URL เข้ามา) จะบล็อกและส่งกลับไปหน้า POS
 */
function requireAdmin(string $redirect = '../login.php'): void {
    // ขั้นแรก: ต้องล็อกอินก่อน
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'กรุณาเข้าสู่ระบบก่อนทำรายการ';
        header("Location: {$redirect}");
        exit();
    }

    // ขั้นที่สอง: ต้องมีตำแหน่งเป็น Admin เท่านั้น
    if (!isAdmin()) {
        $_SESSION['flash_error'] = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (เฉพาะผู้จัดการ Admin เท่านั้น)';
        // คำนวณ path เพื่อส่งกลับไปหน้า POS ตามตำแหน่งของไฟล์ปัจจุบัน
        $target = (strpos($_SERVER['PHP_SELF'] ?? '', '/admin/') !== false) ? '../pos.php' : 'pos.php';
        header("Location: {$target}");
        exit();
    }
}

/**
 * 🚪 ฟังก์ชันออกจากระบบ (Logout)
 * ล้างข้อมูลทุกอย่างทิ้งให้เกลี้ยง ทั้งตัวแปรใน Session, คุกกี้ใน Browser, และทำลาย Session บน Server
 */
function logout(): void {
    // 1. เคลียร์ตัวแปรทุกตัวในอาร์เรย์ $_SESSION
    $_SESSION = [];

    // 2. ลบคุกกี้ Session ID ที่เกาะอยู่ใน Browser ของผู้ใช้ โดยตั้งเวลาหมดอายุย้อนหลังในอดีต (-42000 วินาที)
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // 3. ทำลายไฟล์ Session ที่บันทึกไว้บน Server อย่างสมบูรณ์
    session_destroy();
}

