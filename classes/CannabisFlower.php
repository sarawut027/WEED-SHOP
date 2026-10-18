<?php
/**
 * ========================================================================
 * CannabisFlower Class (Inheritance & Polymorphism)
 * ========================================================================
 * สอดคล้องกับ Class Diagram รูปที่ 4 และรูปที่ 1
 * 
 * วัตถุประสงค์และการประยุกต์ใช้:
 * 1. Inheritance (การสืบทอดคุณสมบัติ):
 *    - CannabisFlower สืบทอดมาจาก CannabisProduct ซึ่งสืบทอดมาจาก Abstract class Product อีกทอดหนึ่ง
 *    - ได้รับแอตทริบิวต์พื้นฐาน: id, name, basePrice (จาก Product)
 *    - ได้รับแอตทริบิวต์เฉพาะ: strainType, thcPercent, stockGrams (จาก CannabisProduct)
 * 2. Polymorphism (การพ้องรูป):
 *    - Object ของ CannabisFlower สามารถถูกจัดเก็บลงในตระกร้าของ Order ในฐานะ Product ได้อย่างยืดหยุ่น
 *    - รองรับการตัดสต็อกแบบทศนิยม (กรัม) ผ่านเมธอด reduceStock() และ checkStock()
 */

// โหลดคลาสแม่ Product และ CannabisProduct
require_once __DIR__ . '/Product.php';
require_once __DIR__ . '/CannabisProduct.php';

class CannabisFlower extends CannabisProduct {
    /**
     * คอนสตรัคเตอร์สำหรับสร้าง Object สินค้าประเภทช่อดอกกัญชา
     * 
     * @param int $id รหัสสินค้าในฐานข้อมูล
     * @param string $name ชื่อสายพันธุ์สินค้า เช่น 'OG Kush', 'Thai Stick'
     * @param float $basePrice ราคาต่อกรัม (บาท)
     * @param string $strainType ประเภทสายพันธุ์ (Sativa, Indica, Hybrid)
     * @param float $thcPercent ค่าสารสำคัญ THC (%)
     * @param float $stockGrams ปริมาณน้ำหนักคงเหลือในสต็อก (กรัม รองรับทศนิยม)
     */
    public function __construct(
        int $id,
        string $name,
        float $basePrice,
        string $strainType,
        float $thcPercent,
        float $stockGrams
    ) {
        // บรรทัดที่ 1: ส่งต่อพารามิเตอร์ทั้งหมดขึ้นไปให้ Constructor ของคลาสแม่ (CannabisProduct) กำหนดค่า
        parent::__construct($id, $name, $basePrice, $strainType, $thcPercent, $stockGrams);
    }
}
