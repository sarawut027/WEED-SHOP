<?php
/**
 * ========================================================================
 * Database Connection (PDO Singleton Pattern)
 * รับผิดชอบโดย: [คนที่ 1]
 * ========================================================================
 * 
 * จุดประสงค์:
 * 1. ใช้ Singleton Design Pattern เพื่อควบคุมให้มี Object การเชื่อมต่อฐานข้อมูลเพียง 1 Instance
 *    ตลอดวงจรชีวิตของ Request ช่วยประหยัด Memory และ Connection Pool ของ MySQL
 * 2. รองรับทั้งการทำงานบน Localhost (XAMPP) และ Cloud Platforms (Railway, Render, InfinityFree)
 *    โดยอ่านค่าจาก Environment Variables อัตโนมัติ หากไม่มีจะใช้ค่าเริ่มต้น localhost
 * 3. ให้อ็อบเจกต์ภายนอกเรียกใช้งานผ่าน Database::getInstance()->getConnection()
 *    หรือส่งต่อ Method เรียกเข้า PDO โดยตรงได้ทันทีผ่าน __call()
 * 4. มีระบบ Auto-Init สร้างตารางและ Seed ข้อมูลอัตโนมัติบน Cloud เมื่อเชื่อมต่อฐานข้อมูลสำเร็จครั้งแรก
 */

class Database {
    // ตัวแปร static สำหรับเก็บ Instance เดียวของคลาส Database ตามหลัก Singleton
    private static ?Database $instance = null;

    // ตัวแปรสำหรับเก็บ Object การเชื่อมต่อฐานข้อมูลของ PHP Data Objects (PDO)
    private ?PDO $pdo = null;

    // ข้อมูลการเชื่อมต่อฐานข้อมูล (มีค่าเริ่มต้นสำหรับ XAMPP Localhost)
    private static string $host = 'localhost';
    private static string $dbName = 'dispensary_db';
    private static string $username = 'root';
    private static string $password = '';
    private static string $port = '3306';
    private static string $charset = 'utf8mb4';

    /**
     * คอนสตรัคเตอร์ถูกกำหนดให้เป็น private (ตามหลัก Singleton Pattern)
     * เพื่อป้องกันไม่ให้โค้ดภายนอกสามารถใช้คำสั่ง new Database() ได้โดยตรง
     */
    private function __construct() {
        try {
            // ตรวจสอบ DATABASE_URL / MYSQL_URL ก่อน (มักถูกเซ็ตโดย Railway และ Cloud Providers)
            $dbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
            if (!empty($dbUrl)) {
                $parsed = parse_url($dbUrl);
                if (!empty($parsed['host'])) self::$host = $parsed['host'];
                if (!empty($parsed['port'])) self::$port = (string)$parsed['port'];
                if (!empty($parsed['user'])) self::$username = $parsed['user'];
                if (isset($parsed['pass'])) self::$password = $parsed['pass'];
                if (!empty($parsed['path'])) self::$dbName = ltrim($parsed['path'], '/');
            } else {
                self::$host     = getenv('DB_HOST')     ?: (getenv('MYSQLHOST')     ?: self::$host);
                self::$dbName   = getenv('DB_NAME')     ?: (getenv('MYSQLDATABASE') ?: self::$dbName);
                self::$username = getenv('DB_USER')     ?: (getenv('MYSQLUSER')     ?: self::$username);
                self::$port     = getenv('DB_PORT')     ?: (getenv('MYSQLPORT')     ?: self::$port);

                $envPass = getenv('DB_PASS');
                if ($envPass === false) {
                    $envPass = getenv('MYSQLPASSWORD');
                }
                if ($envPass !== false) {
                    self::$password = $envPass;
                }
            }

            // สร้าง DSN (Data Source Name)
            $dsn = "mysql:host=" . self::$host . ";port=" . self::$port . ";dbname=" . self::$dbName . ";charset=" . self::$charset;

            // กำหนดการตั้งค่าพฤติกรรมของ PDO พร้อม timeout 3 วินาที เพื่อไม่ให้เว็บค้างหาก host ไม่ตอบสนอง
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 3,
            ];

            if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
            }

            // ทำการสร้าง Object PDO เชื่อมต่อไปยังฐานข้อมูล MySQL
            $this->pdo = new PDO($dsn, self::$username, self::$password, $options);

            // เมื่อเชื่อมต่อสำเร็จ ทำการตรวจสอบและสร้างตารางพร้อมข้อมูลเริ่มต้นให้อัตโนมัติ (Zero-Config บน Cloud)
            $this->autoInitTables();

        } catch (\Throwable $e) {
            // หากเชื่อมต่อไม่สำเร็จ จะกำหนดให้ pdo เป็น null เพื่อให้ระบบสลับไปใช้ In-Memory Mock แบบไร้รอยต่อ
            $this->pdo = null;
        }
    }

    /**
     * ระบบสร้างตารางและใส่ข้อมูลเริ่มต้นให้อัตโนมัติกรณี Deploy ขึ้น Cloud ใหม่
     */
    private function autoInitTables(): void {
        if ($this->pdo === null) return;
        try {
            // 1. ตาราง users
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(50) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `name` VARCHAR(100) NULL,
                `full_name` VARCHAR(100) NOT NULL,
                `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Seed users (admin / staff)
            $uCount = $this->pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
            if ((int)$uCount === 0) {
                $adminHash = password_hash('admin123', PASSWORD_BCRYPT);
                $staffHash = password_hash('staff123', PASSWORD_BCRYPT);
                $uStmt = $this->pdo->prepare("INSERT INTO `users` (`id`, `username`, `password`, `name`, `full_name`, `role`) VALUES 
                    (1, 'admin', ?, 'ผู้จัดการร้าน (Admin)', 'ผู้จัดการร้าน (Admin)', 'admin'),
                    (2, 'staff', ?, 'พนักงานขายหน้าร้าน (Staff)', 'พนักงานขายหน้าร้าน (Staff)', 'staff')");
                $uStmt->execute([$adminHash, $staffHash]);
            }

            // 2. ตาราง products
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `products` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(150) NOT NULL,
                `type` ENUM('flower', 'oil', 'plant') NOT NULL,
                `product_type` ENUM('flower', 'oil', 'plant') NOT NULL,
                `base_price` DECIMAL(10, 2) NOT NULL,
                `strain_type` VARCHAR(100) NULL,
                `thc_percent` DECIMAL(5, 2) NULL DEFAULT 20.00,
                `stock_grams` DECIMAL(10, 2) NULL DEFAULT 0.00,
                `extraction_method` VARCHAR(100) NULL,
                `stock_bottles` INT NULL DEFAULT 0,
                `age_weeks` INT NULL DEFAULT 0,
                `stock_pieces` INT NULL DEFAULT 0,
                `stock_quantity` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                `image_url` VARCHAR(255) NULL,
                `description` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Seed products
            $pCount = $this->pdo->query("SELECT COUNT(*) FROM `products`")->fetchColumn();
            if ((int)$pCount === 0) {
                $this->pdo->exec("INSERT INTO `products` (`id`, `name`, `type`, `product_type`, `base_price`, `strain_type`, `thc_percent`, `stock_grams`, `extraction_method`, `stock_bottles`, `age_weeks`, `stock_pieces`, `stock_quantity`, `image_url`, `description`) VALUES
                (1, 'OG Kush Flower (Top Shelf)', 'flower', 'flower', 450.00, 'Hybrid (55% Indica / 45% Sativa)', 24.50, 150.50, NULL, 0, 0, 0, 150.50, 'assets/images/flower_kush.jpg', 'ช่อดอกพรีเมียมเกรด Top Shelf เพาะปลูกระบบ Indoor ควบคุมสภาพแวดล้อม กลิ่นหอมสน เอิร์ธโทน และซิตรัสเข้มข้น'),
                (2, 'Thai Sticky Sativa (หางกระรอกแท้)', 'flower', 'flower', 350.00, 'Sativa 100% (Landrace)', 18.50, 65.00, NULL, 0, 0, 0, 65.00, 'assets/images/flower_thai_sativa.jpg', 'กัญชาสายพันธุ์แลนด์เรซแท้จากเทือกเขาภูพาน กลิ่นหอมสมุนไพรสดชื่น ผสานมะนาวป่า'),
                (3, 'Granddaddy Purple (Indica)', 'flower', 'flower', 500.00, 'Indica (80% Indica / 20% Sativa)', 22.00, 45.00, NULL, 0, 0, 0, 45.00, 'assets/images/flower_gdp.jpg', 'ช่อดอกสีม่วงเข้มปกคลุมด้วยไตรโคมหนาแน่น กลิ่นหอมหวานเด่นของเบอร์รี่ป่าและองุ่นสุก'),
                (4, 'Full Spectrum CBD Oil Drops 1000mg', 'oil', 'oil', 1200.00, NULL, 0.00, 0.00, 'Supercritical CO2 Extraction', 18, 0, 0, 18.00, 'assets/images/cannabis_oil.jpg', 'น้ำมันสกัดสายหยอดสูตร Full Spectrum เข้มข้น 1,000 มก. ในน้ำมัน MCT ออร์แกนิก'),
                (5, 'Pure THC Distillate Drops (สายหยอด)', 'oil', 'oil', 1500.00, NULL, 0.00, 0.00, 'Fractional Distillation', 10, 0, 0, 10.00, 'assets/images/oil_thc_distillate.jpg', 'สารสกัดหยดใต้ลิ้นความบริสุทธิ์สูง 85% ผ่านกระบวนการกลั่นลำดับส่วนมาตรฐานห้องปฏิบัติการ'),
                (6, 'White Widow Clone (ต้นแม่พันธุ์)', 'plant', 'plant', 850.00, NULL, 0.00, 0.00, NULL, 0, 4, 15, 15.00, 'assets/images/cannabis_plant.jpg', 'ต้นกล้าตัดชำจากต้นแม่พันธุ์ White Widow แท้ อายุ 4 สัปดาห์ ระบบรากเดินเต็มก้อนร็อควูล'),
                (7, 'Amnesia Haze Young Plant', 'plant', 'plant', 950.00, NULL, 0.00, 0.00, NULL, 0, 6, 8, 8.00, 'assets/images/plant_amnesia_haze.jpg', 'ต้นพันธุ์รุ่นเจริญเติบโต (Vegetative Stage) อายุ 6 สัปดาห์ ฟอร์มพุ่มกิ่งก้านสมบูรณ์')");
            }

            // 3. ตาราง bulk_pricing_tiers
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `bulk_pricing_tiers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `min_quantity` DECIMAL(10, 2) NOT NULL,
                `discount_percent` DECIMAL(5, 2) NOT NULL,
                `description` VARCHAR(100) NULL,
                `is_active` TINYINT(1) DEFAULT 1,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $bCount = $this->pdo->query("SELECT COUNT(*) FROM `bulk_pricing_tiers`")->fetchColumn();
            if ((int)$bCount === 0) {
                $this->pdo->exec("INSERT INTO `bulk_pricing_tiers` (`min_quantity`, `discount_percent`, `description`, `is_active`) VALUES
                (3.50, 5.00, 'ซื้อ 3.5 กรัมขึ้นไป (1/8 oz) ลด 5%', 1),
                (7.00, 10.00, 'ซื้อ 7 กรัมขึ้นไป (1/4 oz) ลด 10%', 1),
                (14.00, 15.00, 'ซื้อ 14 กรัมขึ้นไป (1/2 oz) ลด 15%', 1),
                (28.00, 20.00, 'ซื้อ 28 กรัมขึ้นไป (1 oz) ลด 20%', 1)");
            }

            // 4. ตาราง orders
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `order_code` VARCHAR(50) NOT NULL UNIQUE,
                `order_number` VARCHAR(50) NULL,
                `user_id` INT NULL,
                `staff_name` VARCHAR(100) NULL,
                `customer_name` VARCHAR(100) NOT NULL DEFAULT 'ลูกค้าทั่วไป',
                `customer_age` INT NOT NULL DEFAULT 20,
                `customer_gender` VARCHAR(20) NOT NULL DEFAULT 'ไม่ระบุ',
                `customer_birthdate` DATE NOT NULL,
                `total_amount` DECIMAL(10, 2) NOT NULL,
                `discount_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                `net_amount` DECIMAL(10, 2) NOT NULL,
                `cash_received` DECIMAL(10, 2) NOT NULL,
                `change_returned` DECIMAL(10, 2) NOT NULL,
                `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cash',
                `status` VARCHAR(20) NOT NULL DEFAULT 'completed',
                `notes` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // 5. ตาราง order_items
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `order_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `order_id` INT NOT NULL,
                `product_id` INT NOT NULL,
                `quantity` DECIMAL(10, 2) NOT NULL,
                `unit_price` DECIMAL(10, 2) NOT NULL,
                `discount_percent` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
                `discount_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                `subtotal` DECIMAL(10, 2) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        } catch (\Throwable $t) {
            // ละเว้นข้อผิดพลาดระหว่าง auto-init เพื่อให้หน้าเว็บทำงานต่อไปได้
        }
    }

    /**
     * ป้องกันการ Clone Object (ตามหลัก Singleton Pattern)
     */
    private function __clone() {}

    /**
     * ป้องกันการ Unserialize Object (ตามหลัก Singleton Pattern)
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton instance");
    }

    /**
     * เมธอด static จุดศูนย์กลางสำหรับดึง Instance ของคลาส Database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * ส่งคืน Object การเชื่อมต่อ PDO สำหรับนำไปใช้งาน Query หรือ Prepare Statement
     */
    public function getConnection(): ?PDO {
        return $this->pdo;
    }

    /**
     * Magic Method __call: ส่งต่อการเรียกใช้งานคำสั่งของ PDO โดยตรง
     */
    public function __call(string $name, array $arguments) {
        if ($this->pdo !== null && method_exists($this->pdo, $name)) {
            return call_user_func_array([$this->pdo, $name], $arguments);
        }
        return null;
    }
}
