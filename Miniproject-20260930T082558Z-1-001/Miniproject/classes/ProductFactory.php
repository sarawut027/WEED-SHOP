<?php
/**
 * ========================================================================
 * ProductFactory Class (Factory Design Pattern)
 * ========================================================================
 * สอดคล้องกับหลักการ OOP Polymorphism และ Factory Method Pattern
 * 
 * วัตถุประสงค์และการประยุกต์ใช้:
 * 1. Factory Pattern: ซ่อนความซับซ้อนในการสร้าง Object ของสินค้าแต่ละชนิด
 * 2. คลาสผู้เรียก (เช่น หน้า pos.php หรือ admin) ไม่จำเป็นต้องรู้ว่าสินค้าแต่ละประเภท
 *    ต้อง new คลาสอะไร หรือรับพารามิเตอร์คอนสตรัคเตอร์ต่างกันอย่างไร
 * 3. ส่งข้อมูลแถวจาก MySQL หรือข้อมูลฟอร์มเข้ามา แล้ว ProductFactory จะคืนค่า Object
 *    (CannabisFlower, CannabisOil, CannabisPlant) ภายใต้ Polymorphism ของคลาสแม่ Product
 */

// โหลดคลาสแม่ Product และคลาสลูกแต่ละประเภท
require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/CannabisProduct.php';
require_once __DIR__ . '/CannabisFlower.php';
require_once __DIR__ . '/CannabisOil.php';
require_once __DIR__ . '/CannabisPlant.php';

class ProductFactory {
    /**
     * createFromRow: สร้าง Instance ของสินค้าจากแถวข้อมูลในฐานข้อมูล (MySQL Associative Array)
     *
     * @param array $row แถวข้อมูลจากตาราง products
     * @return Product|null คืนค่า Object สินค้า หรือ null หากข้อมูลไม่ถูกต้อง
     */
    public static function createFromRow(array $row): ?Product {
        try {
            // บรรทัดที่ 1: เรียกเมธอด createFromRow ของคลาส Product เพื่อสร้าง Object ตามประเภท
            return Product::createFromRow($row);
        } catch (Exception $e) {
            // บรรทัดที่ 2: หากเกิด Exception ระหว่างการแปลงข้อมูล ให้คืนค่า null เพื่อป้องกันหน้าเว็บพัง
            return null;
        }
    }

    /**
     * create: สร้าง Object สินค้าตามประเภทที่ระบุและข้อมูลที่ส่งเข้ามา
     *
     * @param string $type ประเภทสินค้า: 'flower', 'oil', หรือ 'plant'
     * @param array $data ข้อมูลแอตทริบิวต์ของสินค้า
     * @return Product
     * @throws InvalidArgumentException เมื่อระบุประเภทสินค้าไม่ถูกต้อง
     */
    public static function create(string $type, array $data): Product {
        // บรรทัดที่ 1: ดึงรหัสสินค้า id แปลงเป็น integer (ค่าเริ่มต้น 0)
        $id = (int)($data['id'] ?? 0);

        // บรรทัดที่ 2: ดึงชื่อสินค้า name แปลงเป็น string (ค่าเริ่มต้น '')
        $name = (string)($data['name'] ?? '');

        // บรรทัดที่ 3: ดึงราคาต่อหน่วย base_price แปลงเป็น float (ค่าเริ่มต้น 0.0)
        $basePrice = (float)($data['base_price'] ?? $data['price'] ?? 0);

        // บรรทัดที่ 4: ตรวจสอบประเภทสินค้าด้วยคำสั่ง switch-case (แปลงเป็นตัวพิมพ์เล็ก)
        switch (strtolower($type)) {
            // กรณีที่ 1: สินค้าประเภทช่อดอกกัญชา (Flower) จำหน่ายโดยชั่งน้ำหนัก (กรัม)
            case 'flower':
                // ดึงสายพันธุ์ เช่น Sativa, Indica, Hybrid (ค่าเริ่มต้น Hybrid)
                $strain = (string)($data['strain_type'] ?? 'Hybrid');
                // ดึงเปอร์เซ็นต์สาร THC (ค่าเริ่มต้น 20.0%)
                $thc = (float)($data['thc_percent'] ?? 20.0);
                // ดึงสต็อกคงเหลือเป็นกรัม (รองรับทศนิยม)
                $stock = (float)($data['stock_grams'] ?? $data['stock_quantity'] ?? 0.0);
                // สร้างและส่งคืน Object ของคลาส CannabisFlower
                return new CannabisFlower($id, $name, $basePrice, $strain, $thc, $stock);

            // กรณีที่ 2: สินค้าประเภทน้ำมันสายหยอด (Oil) จำหน่ายเป็นขวด
            case 'oil':
                // ดึงกรรมวิธีการสกัด เช่น CO2, Distillate (ค่าเริ่มต้น Supercritical CO2)
                $method = (string)($data['extraction_method'] ?? 'Supercritical CO2');
                // ดึงสต็อกคงเหลือเป็นขวด (จำนวนเต็ม)
                $stock = (int)($data['stock_bottles'] ?? $data['stock_quantity'] ?? 0);
                // สร้างและส่งคืน Object ของคลาส CannabisOil
                return new CannabisOil($id, $name, $basePrice, $method, $stock);

            // กรณีที่ 3: สินค้าประเภทต้นกล้าพันธุ์กัญชา (Plant) จำหน่ายเป็นต้น
            case 'plant':
                // ดึงอายุของต้นกล้าเป็นสัปดาห์ (ค่าเริ่มต้น 4 สัปดาห์)
                $age = (int)($data['age_weeks'] ?? 4);
                // ดึงสต็อกคงเหลือเป็นต้น (จำนวนเต็ม)
                $stock = (int)($data['stock_pieces'] ?? $data['stock_quantity'] ?? 0);
                // สร้างและส่งคืน Object ของคลาส CannabisPlant
                return new CannabisPlant($id, $name, $basePrice, $age, $stock);

            // กรณีไม่ตรงกับประเภทใดเลย
            default:
                // โยน Exception แจ้งว่าไม่พบประเภทสินค้าที่ระบุ
                throw new InvalidArgumentException("ไม่พบประเภทสินค้า: {$type}");
        }
    }
}
