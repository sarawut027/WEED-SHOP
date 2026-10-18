<?php
/**
 * ========================================================================
 * CustomerGuard Class (Guard Design Pattern)
 * ========================================================================
 * สอดคล้องกับ Class Diagram รูปที่ 1, Flowchart รูปที่ 3 และ Sequence Diagram รูปที่ 5
 * 
 * วัตถุประสงค์และการประยุกต์ใช้:
 * 1. ใช้ Guard Pattern เพื่อทำหน้าที่เป็น "ประตูด่านหน้า" ตรวจสอบเงื่อนไขข้อบังคับทางกฎหมาย
 *    ตาม พ.ร.บ. คุ้มครองและส่งเสริมภูมิปัญญาการแพทย์แผนไทย (กัญชาเป็นสมุนไพรควบคุม)
 * 2. ข้อกำหนด: ผู้ซื้อต้องมีอายุไม่ต่ำกว่า 20 ปีบริบูรณ์ (คำนวณจากวันเดือนปีเกิดจริง)
 * 3. หากไม่ผ่านเงื่อนไข ระบบจะปฏิเสธการทำรายการทันที ไม่สร้างคำสั่งซื้อ และไม่ตัดสต็อก
 */

class CustomerGuard {
    // กำหนดค่าคงที่อายุขั้นต่ำตามกฎหมาย (20 ปีบริบูรณ์)
    public const MINIMUM_AGE = 20;

    /**
     * verifyAge: ตรวจสอบความถูกต้องของอายุจากสตริงวันเกิด (YYYY-MM-DD)
     * สอดคล้องกับ Class Diagram: +verifyAge(String birthdate) : bool
     *
     * @param string $birthdate วันเดือนปีเกิด
     * @return bool คืนค่า true หากอายุ >= 20 ปี, false หากอายุน้อยกว่าหรือวันที่ผิดพลาด
     */
    public static function verifyAge(string $birthdate): bool {
        // บรรทัดที่ 1: ตรวจสอบว่ามีการส่งสตริงวันเกิดเข้ามาหรือไม่ หากเป็นค่าว่างตัดช่องว่างแล้วไม่มี ให้ถือว่าไม่ผ่านทันที
        if (empty(trim($birthdate))) {
            return false;
        }

        try {
            // บรรทัดที่ 2: แปลงสตริงวันเกิดให้เป็น Object ของ PHP DateTime
            $dob = new DateTime($birthdate);

            // บรรทัดที่ 3: ดึงวันเวลาปัจจุบัน ณ วันนี้เพื่อนำมาเปรียบเทียบ
            $today = new DateTime('today');

            // บรรทัดที่ 4: ตรวจสอบความสมเหตุสมผล หากวันเกิดที่กรอกเป็นวันที่ในอนาคต (มากกว่าวันนี้) ถือว่าข้อมูลไม่ถูกต้อง
            if ($dob > $today) {
                return false;
            }

            // บรรทัดที่ 5: ใช้คำสั่ง diff() คำนวณผลต่างระหว่างวันเกิดกับวันนี้ และดึงคุณสมบัติ ->y (จำนวนปีเต็มบริบูรณ์)
            $age = $dob->diff($today)->y;

            // บรรทัดที่ 6: นำอายุที่คำนวณได้มาเปรียบเทียบกับค่าคงที่ MINIMUM_AGE (20 ปี) ส่งคืน true หรือ false
            return $age >= self::MINIMUM_AGE;
        } catch (Exception $e) {
            // บรรทัดที่ 7: กรณีที่รูปแบบวันที่ไม่ถูกต้องและ DateTime โยน Exception ออกมา ให้ดักจับและส่งคืน false
            return false;
        }
    }

    /**
     * check: เมธอดสำหรับเรียกใช้งานผ่าน Instance Object (ทางเลือกเพิ่มเติม)
     */
    public function check(string $birthdate): bool {
        // ส่งต่อการตรวจสอบไปยัง static method verifyAge()
        return self::verifyAge($birthdate);
    }

    /**
     * getAge: คำนวณอายุจริงเป็นจำนวนปีบริบูรณ์
     *
     * @param string $birthdate วันเกิดในรูปแบบ 'YYYY-MM-DD'
     * @return int อายุเต็มปี (หากคำนวณไม่ได้จะคืนค่า 0)
     */
    public static function getAge(string $birthdate): int {
        try {
            // สร้าง Object DateTime วันเกิด
            $dob = new DateTime($birthdate);
            // สร้าง Object DateTime วันนี้
            $today = new DateTime('today');
            // ส่งคืนค่าผลต่างเฉพาะส่วนของปีบริบูรณ์
            return $dob->diff($today)->y;
        } catch (Exception $e) {
            // กรณีรูปแบบวันที่ผิดพลาด ส่งคืน 0 ปี
            return 0;
        }
    }

    /**
     * calculateAge: ฟังก์ชัน Alias เพื่อรองรับการเรียกใช้ที่หลากหลาย
     */
    public static function calculateAge(string $birthdate): int {
        return self::getAge($birthdate);
    }

    /**
     * getMaxAllowedBirthdate: คำนวณหาวันเกิดล่าสุดที่ยังผ่านเกณฑ์ 20 ปี
     * ใช้สำหรับกำหนดค่า attribute max="..." ในแท็ก <input type="date"> หน้าบ้าน
     *
     * @return string วันที่ในรูปแบบ YYYY-MM-DD
     */
    public static function getMaxAllowedBirthdate(): string {
        // ดึงวันที่ปัจจุบัน
        $date = new DateTime('today');
        // ลบออก 20 ปี (ตามค่า MINIMUM_AGE)
        $date->modify('-' . self::MINIMUM_AGE . ' years');
        // คืนค่ารูปแบบ Y-m-d เช่น ถ้าวันนี้คือ 2026-10-18 จะได้ '2006-10-18'
        return $date->format('Y-m-d');
    }

    /**
     * verifyAgeNumber: ตรวจสอบอายุโดยตรงจากตัวเลขจำนวนเต็ม
     *
     * @param int $age อายุเป็นจำนวนปี
     * @return bool
     */
    public static function verifyAgeNumber(int $age): bool {
        // ตรวจสอบว่าตัวเลขอายุมากกว่าหรือเท่ากับ 20 ปีหรือไม่
        return $age >= self::MINIMUM_AGE;
    }

    /**
     * verifyCustomer: ตรวจสอบความถูกต้องแบบ Polymorphic จากตัวแปรหลายรูปแบบ
     * (รองรับทั้ง Object Customer, ตัวเลขอายุ int, หรือสตริงวันเกิด)
     *
     * @param mixed $customer
     * @return bool
     */
    public static function verifyCustomer($customer): bool {
        // กรณีที่ 1: ส่งมาเป็น Object ของคลาส Customer
        if (is_object($customer) && method_exists($customer, 'canPurchase')) {
            // เรียกเมธอด canPurchase() ของตัว Customer เอง
            return $customer->canPurchase();
        }

        // กรณีที่ 2: ส่งมาเป็นตัวเลขอายุ เช่น 25
        if (is_numeric($customer)) {
            // ส่งให้ verifyAgeNumber ตรวจสอบ
            return self::verifyAgeNumber((int)$customer);
        }

        // กรณีที่ 3: ส่งมาเป็นสตริงวันเกิด เช่น '2000-05-15'
        if (is_string($customer)) {
            // ส่งให้ verifyAge ตรวจสอบ
            return self::verifyAge($customer);
        }

        // หากไม่ใช่ประเภทข้อมูลที่รองรับ ให้ปฏิเสธทันที
        return false;
    }
}
