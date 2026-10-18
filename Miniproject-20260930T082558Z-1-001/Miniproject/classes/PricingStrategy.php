<?php
/**
 * ========================================================================
 * PricingStrategy & BulkPricing (Strategy Design Pattern)
 * ========================================================================
 * สอดคล้องกับ Class Diagram รูปที่ 1 และหลักการ Object-Oriented Design
 * 
 * วัตถุประสงค์และการประยุกต์ใช้:
 * 1. Strategy Pattern: ใช้แยกตรรกะการคำนวณราคา (Algorithm) ออกมาจากคลาสคำสั่งซื้อ Order
 * 2. Strategy Interface: กำหนดสัญญาข้อตกลงร่วม (calculatePrice) ให้คลาสลูกปฏิบัติตาม
 * 3. RegularPricing: คำนวณราคาปกติโดยไม่มีส่วนลด
 * 4. BulkPricing: คำนวณราคาส่งแบบขั้นบันได (Bulk Discount) ตามน้ำหนักการซื้อ (3.5g, 7g, 14g, 28g)
 * 5. Open-Closed Principle (OCP): เมื่อในอนาคตต้องการเพิ่มกลยุทธ์ใหม่ เช่น VIPPricing, MemberPricing
 *    เราสามารถสร้างคลาสใหม่ที่ implements PricingStrategy ได้ทันที โดยไม่ต้องแก้ไขโค้ดเดิมใน Order
 */

/**
 * Interface PricingStrategy: ข้อตกลงร่วมสำหรับอัลกอริทึมการคิดราคา
 * สอดคล้องกับ Class Diagram: +calculatePrice(float weight, float basePrice) : float
 */
interface PricingStrategy {
    /**
     * คำนวณราคาสุทธิที่ต้องชำระหลังหักส่วนลด (บาท)
     *
     * @param float $weight ปริมาณหรือน้ำหนักสินค้า (เช่น กรัม, ขวด, หรือชิ้น)
     * @param float $basePrice ราคาต่อหน่วย (บาท)
     * @return float ราคาสุทธิ
     */
    public function calculatePrice(float $weight, float $basePrice): float;

    /**
     * คืนค่าเปอร์เซ็นต์ส่วนลดที่ลูกค้าได้รับ (%)
     */
    public function getDiscountPercent(float $weight): float;

    /**
     * คืนค่ามูลค่าส่วนลดที่เป็นจำนวนเงิน (บาท)
     */
    public function getDiscountAmount(float $weight, float $basePrice): float;

    /**
     * คืนค่าชื่อหรือคำอธิบายของกลยุทธ์ราคา
     */
    public function getStrategyName(): string;
}

/**
 * 1. RegularPricing (Concrete Strategy): คิดราคาตามปกติ ไม่มีส่วนลดใดๆ
 */
class RegularPricing implements PricingStrategy {
    /**
     * คำนวณราคาสุทธิ = น้ำหนัก x ราคาต่อหน่วย ตรงไปตรงมา
     */
    public function calculatePrice(float $weight, float $basePrice): float {
        // บรรทัดที่ 1: คูณน้ำหนักกับราคาต่อหน่วย และปัดเศษทศนิยม 2 ตำแหน่ง
        return round($weight * $basePrice, 2);
    }

    /**
     * ราคาปกติไม่มีส่วนลด จึงคืนค่าเปอร์เซ็นต์ส่วนลดเป็น 0.0%
     */
    public function getDiscountPercent(float $weight): float {
        return 0.0;
    }

    /**
     * ราคาปกติไม่มีส่วนลด จึงคืนค่าจำนวนเงินส่วนลดเป็น 0.0 บาท
     */
    public function getDiscountAmount(float $weight, float $basePrice): float {
        return 0.0;
    }

    /**
     * ชื่อแสดงผลของกลยุทธ์นี้
     */
    public function getStrategyName(): string {
        return 'ราคาปกติ (Regular Pricing)';
    }
}

/**
 * 2. BulkPricing (Concrete Strategy ตาม Class Diagram รูปที่ 1):
 * คิดราคาส่งแบบขั้นบันไดตามปริมาณน้ำหนัก เช่น ซื้อ 3.5g ลด 5%, 7g ลด 10%, 14g ลด 15%, 28g ลด 20%
 */
class BulkPricing implements PricingStrategy {
    // อาร์เรย์สำหรับเก็บขั้นบันไดส่วนลด (Tiers)
    private array $tiers = [];

    /**
     * คอนสตรัคเตอร์กำหนดระดับส่วนลด
     * หากไม่ได้ส่งพารามิเตอร์มา จะกำหนดค่าเริ่มต้นตามมาตรฐานร้านสมุนไพรควบคุมสากล
     */
    public function __construct(array $tiers = []) {
        // บรรทัดที่ 1: ตรวจสอบว่ามีการส่ง Tiers กำหนดเองมาหรือไม่
        if (!empty($tiers)) {
            // บรรทัดที่ 2: หากมี ให้บันทึกและจัดเรียงตามที่ส่งมา
            $this->setTiers($tiers);
        } else {
            // บรรทัดที่ 3: หากไม่มี ให้ใช้ขั้นบันไดส่วนลดมาตรฐาน (1/8 oz, 1/4 oz, 1/2 oz, 1 oz)
            $this->setTiers([
                ['min_quantity' => 28.0, 'discount_percent' => 20.0, 'desc' => 'ซื้อ 1 ออนซ์ (28g) ลด 20%'],
                ['min_quantity' => 14.0, 'discount_percent' => 15.0, 'desc' => 'ซื้อ 1/2 ออนซ์ (14g) ลด 15%'],
                ['min_quantity' => 7.0,  'discount_percent' => 10.0, 'desc' => 'ซื้อ 1/4 ออนซ์ (7g) ลด 10%'],
                ['min_quantity' => 3.5,  'discount_percent' => 5.0,  'desc' => 'ซื้อ 1/8 ออนซ์ (3.5g) ลด 5%'],
            ]);
        }
    }

    /**
     * setTiers: กำหนดระดับส่วนลด พร้อมจัดเรียงลำดับจากปริมาณมากไปหาน้อยอัตโนมัติ
     * (เพื่อให้ลูปเช็คจากเงื่อนไขที่ได้รับส่วนลดสูงสุดก่อน)
     */
    public function setTiers(array $tiers): void {
        // จัดเรียงอาร์เรย์ด้วย usort โดยใช้ยานอวกาศโอเปอเรเตอร์ (<=>) เทียบ min_quantity จากมากไปน้อย
        usort($tiers, function ($a, $b) {
            return $b['min_quantity'] <=> $a['min_quantity'];
        });
        $this->tiers = $tiers;
    }

    /**
     * createFromDatabase: เมธอด Factory ช่วยดึงระดับขั้นส่วนลดจากฐานข้อมูล
     */
    public static function createFromDatabase(PDO $pdo): self {
        try {
            // ดึงระดับส่วนลดที่เปิดใช้งาน (is_active = 1) จากตาราง bulk_pricing_tiers
            $stmt = $pdo->query("SELECT min_quantity, discount_percent FROM bulk_pricing_tiers WHERE is_active = 1 ORDER BY min_quantity DESC");
            $tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return new self($tiers);
        } catch (Exception $e) {
            // หากเกิดข้อผิดพลาดในการต่อ DB ให้สร้างออบเจกต์ด้วยค่า Default Tiers
            return new self();
        }
    }

    /**
     * getDiscountPercent: ค้นหาเปอร์เซ็นต์ส่วนลดที่ลูกค้าได้รับตามปริมาณน้ำหนัก
     */
    public function getDiscountPercent(float $weight): float {
        // วนลูปตรวจสอบขั้นบันไดส่วนลด (ที่เรียงจากมากไปน้อยไว้แล้ว)
        foreach ($this->tiers as $tier) {
            // หากน้ำหนักที่ซื้อมากกว่าหรือเท่ากับเกณฑ์ขั้นต่ำของระดับนั้น
            if ($weight >= (float)$tier['min_quantity']) {
                // ส่งคืนเปอร์เซ็นต์ส่วนลดของระดับนั้นทันที เช่น 20.0, 15.0, 10.0, 5.0
                return (float)$tier['discount_percent'];
            }
        }
        // หากซื้อไม่ถึงเกณฑ์ขั้นต่ำสุด (เช่น น้อยกว่า 3.5g) ไม่ได้รับส่วนลด (0.0%)
        return 0.0;
    }

    /**
     * getDiscountAmount: คำนวณมูลค่าส่วนลดเป็นจำนวนเงินบาท
     */
    public function getDiscountAmount(float $weight, float $basePrice): float {
        // ดึงเปอร์เซ็นต์ส่วนลดที่ได้
        $percent = $this->getDiscountPercent($weight);
        // คำนวณยอดเงินรวมก่อนลด (น้ำหนัก x ราคาต่อหน่วย)
        $subtotal = $weight * $basePrice;
        // คำนวณมูลค่าส่วนลด = ยอดก่อนลด x (เปอร์เซ็นต์ / 100) ปัดทศนิยม 2 ตำแหน่ง
        return round($subtotal * ($percent / 100.0), 2);
    }

    /**
     * calculatePrice: สอดคล้องกับ Class Diagram รูปที่ 1
     * คำนวณราคาสุทธิหลังหักส่วนลดที่ลูกค้าต้องจ่ายจริง
     */
    public function calculatePrice(float $weight, float $basePrice): float {
        // ยอดรวมก่อนหักส่วนลด
        $subtotal = $weight * $basePrice;
        // มูลค่าส่วนลดที่ได้รับ
        $discount = $this->getDiscountAmount($weight, $basePrice);
        // ราคาสุทธิ = ยอดรวมก่อนลด - มูลค่าส่วนลด ปัดทศนิยม 2 ตำแหน่ง
        return round($subtotal - $discount, 2);
    }

    /**
     * คืนค่าชื่อกลยุทธ์สำหรับแสดงในใบเสร็จ
     */
    public function getStrategyName(): string {
        return 'โปรโมชั่นราคาส่ง (Bulk Pricing)';
    }

    /**
     * คืนค่าระดับส่วนลดทั้งหมดสำหรับแสดงหน้าจอ POS
     */
    public function getTiers(): array {
        return $this->tiers;
    }
}
