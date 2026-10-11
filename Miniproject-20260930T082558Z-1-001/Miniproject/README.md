# 🌿 CHILL42x Dispensary POS & Management System

> **ระบบบริหารจัดการร้านและจุดจำหน่ายสมุนไพรควบคุมทางการแพทย์ (Dispensary Point of Sale)**  
> พัฒนาด้วยภาษา **PHP (OOP)**, **MySQL** และออกแบบสถาปัตยกรรมซอฟต์แวร์ตามหลัก **Object-Oriented Design Patterns**

---

## 👥 ผู้พัฒนาโครงงาน (Developers)

* **1. Somphong (011)**
* **2. Sarawut (027)**

🏛️ **สาขาวิชาเทคโนโลยีสารสนเทศ**  
คณะวิทยาศาสตร์และเทคโนโลยี **มหาวิทยาลัยราชภัฏนครปฐม** (Nakhon Pathom Rajabhat University)

---

## 📖 ภาพรวมโครงงาน (Project Overview)

ระบบ **CHILL42x Dispensary POS** ได้รับการออกแบบขึ้นเพื่อรองรับการดำเนินธุรกิจร้านจำหน่ายสมุนไพรควบคุมและผลิตภัณฑ์กัญชาทางการแพทย์อย่างถูกต้องตามกฎหมาย (พ.ร.บ. คุ้มครองและส่งเสริมภูมิปัญญาการแพทย์แผนไทย) โดยครอบคลุมทั้ง:

1. **ระบบขายหน้าร้าน (Point of Sale - POS Terminal):** รองรับการชั่งน้ำหนักช่อดอกแบบทศนิยม (กรัม), น้ำมันสายหยอด (ขวด) และต้นพันธุ์ (ต้น)
2. **ระบบตรวจคุณสมบัติผู้ซื้อตามกฎหมาย (CustomerGuard):** ตรวจสอบอายุจากวันเดือนปีเกิดจริง บล็อกการขายทันทีหากลูกค้าอายุต่ำกว่า 20 ปีบริบูรณ์
3. **ระบบคำนวณราคาส่งแบบขั้นบันได (Bulk Pricing Strategy):** คำนวณส่วนลดอัตโนมัติตามปริมาณน้ำหนัก เช่น ซื้อ 3.5g (ลด 5%), 7g (ลด 10%), 14g (ลด 15%), 28g (ลด 20%)
4. **ระบบออกใบเสร็จรับเงิน/ใบกำกับภาษีอย่างย่อ (Thermal Receipt 80mm):** สลิปมาตรฐานเครื่องพิมพ์ความร้อน พร้อมปุ่มสั่งพิมพ์และ QR Code
5. **ระบบหลังร้านสำหรับผู้จัดการ (Admin Inventory):** ตรวจสอบสต็อกสินค้า เพิ่มรายการสินค้าใหม่ และควบคุมสิทธิ์การเข้าถึง

---

## 🏗️ สถาปัตยกรรมและ Design Patterns ที่นำมาใช้

ระบบนี้ออกแบบและพัฒนาตามหลักการเขียนโปรแกรมเชิงวัตถุ (Object-Oriented Programming: OOP) อย่างเคร่งครัด:

* **Singleton Pattern (`config/Database.php`):** จัดการการเชื่อมต่อ PDO MySQL ให้มี Instance เดียวตลอดทั้งการทำงาน ช่วยประหยัดทรัพยากรระบบ
* **Inheritance & Polymorphism:**
  * **Product Hierarchy:** คลาสแม่ `Product` ถ่ายทอดคุณสมบัติไปยัง `CannabisFlower` (ชั่งเป็นกรัม), `CannabisOil` (นับเป็นขวด) และ `CannabisPlant` (นับเป็นต้น)
  * **User Hierarchy:** คลาสแม่ `User` ถ่ายทอดคุณสมบัติไปยัง `Admin` (จัดการสต็อกได้) และ `Staff` (สิทธิ์เฉพาะหน้าร้าน POS)
* **Factory Pattern (`classes/ProductFactory.php`):** สร้าง Object สินค้าลูกอย่างยืดหยุ่นจากข้อมูลในฐานข้อมูล
* **Strategy Pattern (`classes/PricingStrategy.php`):** แยกตรรกะการคำนวณราคาเป็น `RegularPricing` และ `BulkPricing` สอดคล้องกับหลัก Open-Closed Principle (OCP)
* **Guard Pattern (`classes/CustomerGuard.php`):** ตรวจสอบเงื่อนไขข้อบังคับทางกฎหมายอายุ 20+ ก่อนเปิดบิล
* **Aggregation & Transactions (`classes/Order.php`):** รวมรายการสินค้าในคำสั่งซื้อ และบันทึกลงฐานข้อมูลแบบ ACID Transaction เพื่อป้องกันสต็อกคลาดเคลื่อน

---

## 💻 ความต้องการของระบบ (System Requirements)

* **PHP:** เวอร์ชัน 8.0 หรือใหม่กว่า (แนะนำ PHP 8.2 ขึ้นไป)
* **Web Server:** Apache (ผ่าน XAMPP) หรือ PHP Built-in Server
* **Database:** MySQL 5.7+ หรือ MariaDB 10.4+
* **ส่วนขยาย PHP ที่ต้องเปิดใช้งาน:** `pdo`, `pdo_mysql`, `mbstring`, `session`

---

## 🚀 ขั้นตอนการติดตั้งและเริ่มใช้งาน (Installation & Setup)

### 1. นำเข้าฐานข้อมูล (Import Database)
1. เปิดโปรแกรม XAMPP Control Panel แล้วกดปุ่ม **Start** ที่โมดูล **MySQL**
2. เปิดเบราว์เซอร์ไปที่ `http://localhost/phpmyadmin`
3. นำเข้าไฟล์ `database.sql` (ระบบจะสร้างฐานข้อมูล `dispensary_db` พร้อมตารางและข้อมูลตั้งต้นให้อัตโนมัติ)

### 2. ตั้งค่าการเชื่อมต่อฐานข้อมูล (Database Config)
ตรวจสอบการตั้งค่าในไฟล์ `config/Database.php` (ค่าเริ่มต้นสำหรับ XAMPP คือ user: `root` และ password ว่าง):
```php
private static string $host = 'localhost';
private static string $dbName = 'dispensary_db';
private static string $username = 'root';
private static string $password = '';
```

### 3. รันระบบ (Start Server)
สามารถรันผ่าน Terminal ในโฟลเดอร์โปรเจกต์ด้วยคำสั่ง:
```bash
php -S localhost:8000
```
จากนั้นเปิดเบราว์เซอร์ไปที่: **`http://localhost:8000`** *(หรือเข้าผ่าน `http://localhost/Miniproject` หากวางในโฟลเดอร์ htdocs)*

---

## 🔑 บัญชีผู้ใช้สำหรับการทดสอบระบบ (Demo Accounts)

| บทบาท (Role) | บัญชีผู้ใช้ (Username) | รหัสผ่าน (Password) | สิทธิ์การเข้าถึง |
| :--- | :--- | :--- | :--- |
| **ผู้จัดการร้าน (Admin)** | `admin` | `admin123` *(หรือ `1234`)* | เข้าถึงได้ทุกหน้า, จัดการสต็อก, เพิ่ม/ลบสินค้า |
| **พนักงานขาย (Staff)** | `staff` | `staff123` *(หรือ `1234`)* | ใช้งานหน้าร้าน POS, เปิดบิล, ออกใบเสร็จ |

*(มีปุ่ม Quick Fill ในหน้า `login.php` เพื่อความสะดวกรวดเร็วในการตรวจงาน)*

---

## 🧪 การทดสอบระบบ (Unit Testing)

ระบบมีชุดทดสอบครอบคลุมการทำงานทุกคลาสหลักตาม Diagram และเงื่อนไขกฎหมาย:
* **รันผ่านเบราว์เซอร์:** `http://localhost:8000/test_runner.php`
* **รันผ่าน Command Line:**
```bash
php test_runner.php
```
* **ผลการทดสอบปัจจุบัน:** **ผ่าน 41 / 41 การทดสอบ (100% Pass Rate)**

---

## 📂 โครงสร้างไดเรกทอรี (Directory Structure)

```text
Miniproject/
├── admin/
│   ├── product-add.php       # หน้าเพิ่มสินค้าใหม่ (Admin Only)
│   └── product-list.php      # หน้ารายการและจัดการสต็อกสินค้า (Admin Only)
├── assets/
│   ├── css/style.css         # ไฟล์ CSS สไตล์ Dark Luxury
│   └── images/               # รูปภาพประกอบสินค้า
├── classes/
│   ├── CannabisFlower.php    # คลาสช่อดอก (ขายเป็นกรัม)
│   ├── CannabisOil.php       # คลาสน้ำมันสายหยอด (ขายเป็นขวด)
│   ├── CannabisPlant.php     # คลาสต้นพันธุ์ (ขายเป็นต้น)
│   ├── CannabisProduct.php   # คลาสผลิตภัณฑ์กัญชาทั่วไป
│   ├── Customer.php          # คลาสข้อมูลลูกค้า
│   ├── CustomerGuard.php     # Guard Pattern ตรวจสอบอายุ 20+
│   ├── Order.php             # จัดการคำสั่งซื้อและ Transaction
│   ├── PricingStrategy.php   # Strategy Pattern คำนวณราคา/ส่วนลด
│   ├── Product.php           # Abstract Base Class ของสินค้า
│   ├── ProductFactory.php    # Factory Pattern สำหรับสร้างสินค้า
│   └── User.php              # คลาสผู้ใช้, Staff และ Admin
├── config/
│   └── Database.php          # Singleton Pattern เชื่อมต่อฐานข้อมูล PDO
├── includes/
│   ├── auth.php              # ตัวช่วยจัดการ Session และระบบ Guard สิทธิ์
│   └── navbar.php            # เมนูด้านบนแบบไดนามิกตาม Role
├── database.sql              # สคริปต์ SQL สร้างตารางและข้อมูลตัวอย่าง
├── index.php                 # หน้าร้านค้าหลัก (Storefront Showcase)
├── login.php                 # หน้าเข้าสู่ระบบ
├── logout.php                # สคริปต์ออกจากระบบ
├── pos.php                   # ระบบจุดขายหน้าร้าน (POS Terminal)
├── receipt.php               # สลิปใบเสร็จรับเงินขนาด 80mm
├── test_runner.php           # สคริปต์ Unit Test Runner
└── README.md                 # เอกสารแนะนำและคู่มือการใช้งานระบบ
```

---

*จัดทำขึ้นเพื่อการศึกษา รายวิชาการพัฒนาเว็บแอปพลิเคชันและสถาปัตยกรรมเชิงวัตถุ*  
*สาขาวิชาเทคโนโลยีสารสนเทศ คณะวิทยาศาสตร์และเทคโนโลยี มหาวิทยาลัยราชภัฏนครปฐม*
