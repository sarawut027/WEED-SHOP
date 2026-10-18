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
        // ตรวจสอบค่า Environment Variables สำหรับการ Deploy บน Cloud (เช่น Railway, Render)
        // หากมีค่าในระบบจะดึงมาใช้ ถ้าไม่มีจะคงค่า localhost เริ่มต้นไว้
        self::$host     = getenv('DB_HOST')     ?: (getenv('MYSQLHOST')     ?: self::$host);
        self::$dbName   = getenv('DB_NAME')     ?: (getenv('MYSQLDATABASE') ?: self::$dbName);
        self::$username = getenv('DB_USER')     ?: (getenv('MYSQLUSER')     ?: self::$username);
        self::$port     = getenv('DB_PORT')     ?: (getenv('MYSQLPORT')     ?: self::$port);

        // จัดการ Password เป็นกรณีพิเศษ เพราะรหัสผ่านของ root บน XAMPP มักเป็นค่าว่าง ""
        $envPass = getenv('DB_PASS');
        if ($envPass === false) {
            $envPass = getenv('MYSQLPASSWORD');
        }
        if ($envPass !== false) {
            self::$password = $envPass;
        }

        // สร้าง DSN (Data Source Name) ระบุ Driver, Host, Port, Database Name และ Charset
        $dsn = "mysql:host=" . self::$host . ";port=" . self::$port . ";dbname=" . self::$dbName . ";charset=" . self::$charset;

        // กำหนดการตั้งค่าพฤติกรรมของ PDO (PDO Options)
        $options = [
            // เมื่อเกิดข้อผิดพลาดในการ Query ให้โยนเป็น Exception ออกมาเพื่อจัดการผ่าน try-catch
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // คืนค่าผลลัพธ์ในรูปแบบ Associative Array (key เป็นชื่อคอลัมน์) เสมอ
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // ปิดการ Emulate Prepared Statements เพื่อให้ใช้ Native Prepared Statements จริงของ MySQL ป้องกัน SQL Injection
            PDO::ATTR_EMULATE_PREPARES   => false,
            // บังคับให้การส่งข้อมูลภาษาไทยและ Emoji ทำงานบนมาตรฐาน UTF-8 อย่างสมบูรณ์
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            // ทำการสร้าง Object PDO เชื่อมต่อไปยังฐานข้อมูล MySQL
            $this->pdo = new PDO($dsn, self::$username, self::$password, $options);
        } catch (PDOException $e) {
            // หากเชื่อมต่อไม่สำเร็จ จะกำหนดให้ pdo เป็น null (ไม่ใช้ die() เพื่อให้ระบบทำงานต่อได้ในโหมด In-Memory / Mock)
            $this->pdo = null;
        }
    }

    /**
     * ป้องกันการ Clone Object (ตามหลัก Singleton Pattern)
     * เพื่อไม่ให้มีการสร้าง Instance ซ้ำซ้อนผ่านคำสั่ง clone $db
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
     * หากยังไม่เคยถูกสร้างจะทำคำสั่ง new self() แต่ถ้าเคยสร้างแล้วจะส่งคืน Instance เดิม
     *
     * @return Database
     */
    public static function getInstance(): Database {
        // ตรวจสอบว่ามี Instance อยู่แล้วหรือไม่
        if (self::$instance === null) {
            // หากยังไม่มี ให้สร้าง Instance ใหม่เพียงครั้งแรกครั้งเดียว
            self::$instance = new self();
        }
        // ส่งคืน Instance ที่เก็บอยู่ในหน่วยความจำ
        return self::$instance;
    }

    /**
     * ส่งคืน Object การเชื่อมต่อ PDO สำหรับนำไปใช้งาน Query หรือ Prepare Statement
     *
     * @return PDO|null
     */
    public function getConnection(): ?PDO {
        return $this->pdo;
    }

    /**
     * Magic Method __call: ส่งต่อการเรียกใช้งานคำสั่งของ PDO โดยตรง
     * เช่น Database::getInstance()->prepare(...) หรือ beginTransaction()
     *
     * @param string $name ชื่อเมธอดที่เรียก
     * @param array $arguments พารามิเตอร์ที่ส่งเข้ามา
     * @return mixed
     */
    public function __call(string $name, array $arguments) {
        // ตรวจสอบว่ามีการเชื่อมต่อฐานข้อมูลอยู่ และใน PDO มีเมธอดชื่อนี้หรือไม่
        if ($this->pdo !== null && method_exists($this->pdo, $name)) {
            // เรียกเมธอดของ PDO พร้อมส่งพารามิเตอร์เข้าไป
            return call_user_func_array([$this->pdo, $name], $arguments);
        }
        return null;
    }
}
