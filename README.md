# UniLink — Chula Activity Hub

แพลตฟอร์มรวมกิจกรรม / งาน / โปรเจกต์ / การแข่งขันภายในจุฬาฯ เชื่อม **Applicant** กับ **Recruiter**
สร้างด้วย **PHP 8 + MySQL (MariaDB)** — รันบนเครื่องตัวเองด้วย XAMPP ได้เลย ไม่ต้องใช้ server ของคณะ

---

## 1. ติดตั้งโปรแกรม (ทำครั้งเดียว)

| โปรแกรม | ใช้ทำอะไร | ดาวน์โหลด |
|---|---|---|
| **XAMPP** | มี Apache (web server) + MySQL + PHP + phpMyAdmin ในตัว | https://www.apachefriends.org |
| **VS Code** | เขียน/แก้โค้ด | https://code.visualstudio.com |
| **Git** (แนะนำมาก) | เก็บโค้ดขึ้น GitHub จะได้ไม่หายอีก | https://git-scm.com |

Extension ที่แนะนำใน VS Code: **PHP Intelephense**, **PHP Server** (ไม่บังคับ), **MySQL** (by Weijan Chen) สำหรับดูตารางใน VS Code

## 2. วางโปรเจกต์

1. แตกไฟล์ `unilink.zip` แล้ววางโฟลเดอร์ `unilink` ไว้ที่
   - Windows: `C:\xampp\htdocs\unilink`
   - macOS: `/Applications/XAMPP/htdocs/unilink`
2. เปิด VS Code → **File > Open Folder…** → เลือกโฟลเดอร์ `unilink`

## 3. สร้างฐานข้อมูล

1. เปิด **XAMPP Control Panel** → กด **Start** ที่ `Apache` และ `MySQL`
2. เปิด http://localhost/unilink/install.php → ระบบสร้าง database `unilink` + 3 ตาราง + ข้อมูลตัวอย่างให้อัตโนมัติ
   (อยากล้างข้อมูลกลับเป็นค่าเริ่มต้น: http://localhost/unilink/install.php?reset=1)
   หรือจะ import เองก็ได้: http://localhost/phpmyadmin → แท็บ **Import** → เลือก `database/unilink.sql`
3. ถ้า MySQL ของคุณตั้งรหัสผ่าน root ไว้ ให้แก้ใน `config/db_settings.php`

## 4. เปิดเว็บ

👉 http://localhost/unilink


| บทบาท | เข้าที่ | Username | Password |
|---|---|---|---|
| Admin | `/pages/admin_login.php` | `admin_01` | `admin1234` |
| User (Recruiter + Applicant) | `/pages/login.php` | `somchai@student.chula.ac.th` | `password123` |
| User อื่น ๆ | | `somying@…`, `nat@…`, `piti@…`, `chujai@…` (`@student.chula.ac.th`) | `password123` |

> สมัครสมาชิกใหม่ได้เฉพาะอีเมล `@chula.ac.th` หรือ `@student.chula.ac.th`
> ถ้าอีเมลเป็นรหัสนิสิต 10 หลัก ระบบจะใช้รหัสนั้นเป็น `user_id` ให้เลย

---

## 5. โครงสร้างไฟล์

```
unilink/
├── index.php                  → redirect ไป pages/home.php
├── install.php                → ติดตั้ง/รีเซ็ตฐานข้อมูลในคลิกเดียว (ใช้ได้จาก localhost เท่านั้น)
├── config/db_settings.php     → host / user / password ของ MySQL
├── config/db.php              → เชื่อมต่อ MySQL (PDO)
├── database/unilink.sql       → schema + seed data
├── includes/
│   ├── functions.php          → session, login check, CSRF, upload, helper
│   └── layout.php             → sidebar + topbar ที่ใช้ทุกหน้า
├── assets/css/style.css       → ธีมชมพู #E91E8C, responsive มือถือ
├── assets/js/app.js           → modal, filter, คำถามคัดกรอง, live preview
├── uploads/                   → รูปกิจกรรม / รูปโปรไฟล์ (มี .htaccess กันรันไฟล์ php)
└── pages/
    ├── home.php               → หน้าแรก: hero, หมวดหมู่, search + filter, การ์ด, modal รายละเอียด
    ├── login.php              → ?mode=login / ?mode=register
    ├── logout.php
    ├── profile.php            → แก้โปรไฟล์ตัวเอง / ?id= ดูโปรไฟล์+portfolio ของคนอื่น
    ├── create.php             → สร้างกิจกรรม / ?id= แก้ไขโพสต์ของตัวเอง
    ├── apply.php?id=          → ฟอร์มสมัคร + ตอบคำถามคัดกรอง
    ├── dashboard.php          → Managed Activities (Recruiter) + Applied Activities (Applicant)
    ├── view_applicants.php?act_id= → Pending / Approved / Rejected + accept / decline พร้อมเหตุผล
    ├── update_application.php → (POST) approve / reject
    ├── activity_action.php    → (POST) เปิด-ปิดรับสมัคร / ลบโพสต์
    ├── admin_login.php
    ├── admin_dashboard.php    → สถิติรวม, ประเภทงานยอดนิยม, กิจกรรมล่าสุด
    ├── admin_manage_user.php  → รายชื่อผู้ใช้ + ค้นหา
    ├── admin_edit_user.php?id= → แก้ไข / เปลี่ยน role / reset password / ลบ user
    ├── admin_manage_activity.php → รายการกิจกรรมทั้งหมด + ลบ
    └── admin_edit_activity.php?id= → แก้ไขกิจกรรมใดก็ได้ (ใช้ฟอร์มเดียวกับ create.php)
```

## 6. ฐานข้อมูล (ตาม ER Diagram ใน report)

- **users** (`user_id` PK) — ข้อมูลส่วนตัว, role `admin | user | guest`, skills
- **activity** (`act_id` PK, `user_id` FK) — โพสต์กิจกรรม, `questions` เก็บเป็น JSON array
- **application** (`id` PK, `user_id` FK, `activity_id` FK) — ใบสมัคร, `answers` เป็น JSON, status `pending | approved | rejected`, `reject_reason`

ความสัมพันธ์: User 1—N Activity (Creates), User 1—N Application (Submits), Activity 1—N Application (Receives)

### สิ่งที่ปรับจาก report
- `regis_date`, `act_date` เปลี่ยนจาก VARCHAR เป็น **DATE** → filter ตามเดือนได้ และปิดรับสมัครอัตโนมัติเมื่อเลยวัน
- เพิ่ม **UNIQUE (user_id, activity_id)** กันสมัครกิจกรรมเดียวกันซ้ำ
- ลบ user / กิจกรรม → ใบสมัครที่เกี่ยวข้องถูกลบตาม (ON DELETE CASCADE)
- role `guest` ใช้เป็น "ระงับบัญชี" (login ไม่ได้)

### ปรับตามผล Usability Test
- **Create Activity (คะแนน 2.2)** → แบ่งฟอร์มเป็น 5 ขั้น, มีเครื่องหมาย * ช่องบังคับ, **ตัวอย่างการ์ดแบบ live**, กด Enter เพิ่มคำถามได้
- **Search & Filter (3.1)** → แนะนำชื่อกิจกรรมขณะพิมพ์, แสดงจำนวนผลลัพธ์ + ปุ่มล้าง, filter "เฉพาะที่ยังเปิดรับ", ปุ่ม Filter บอกจำนวนเงื่อนไขที่เลือก
- **Limitation: มือถือใช้ยาก** → responsive, sidebar กลายเป็นเมนูด้านล่างบนจอเล็ก

## 7. ความปลอดภัยที่มีให้แล้ว
รหัสผ่าน hash ด้วย `password_hash` (bcrypt) · ทุก query ใช้ prepared statement (กัน SQL injection) · escape output ด้วย `e()` (กัน XSS) · CSRF token ทุกฟอร์ม · ตรวจไฟล์อัปโหลดว่าเป็นรูปจริง ≤ 5MB · เช็คสิทธิ์เจ้าของโพสต์ก่อนแก้/ลบ/ดูผู้สมัคร

---

## 8. กันโค้ดหายอีก — เอาขึ้น GitHub

```bash
cd C:\xampp\htdocs\unilink
git init
git add .
git commit -m "UniLink first version"
git branch -M main
# สร้าง repo ใหม่บน github.com แล้ว:
git remote add origin https://github.com/<username>/unilink.git
git push -u origin main
```
แชร์ repo ให้เพื่อนในกลุ่ม แล้วทุกคน `git pull` / `git push` ได้

## 9. ปัญหาที่พบบ่อย
| อาการ | วิธีแก้ |
|---|---|
| "เชื่อมต่อฐานข้อมูลไม่ได้" | ยังไม่ Start MySQL หรือยังไม่ import SQL / แก้รหัสผ่านใน `config/db_settings.php` |
| MySQL Start ไม่ขึ้น (port 3306 ชน) | ปิดโปรแกรม MySQL ตัวอื่น หรือเปลี่ยน port ใน XAMPP แล้วแก้ `$DB_PORT` |
| Apache Start ไม่ขึ้น (port 80 ชน) | ปิด Skype/IIS หรือเปลี่ยน port เป็น 8080 แล้วเข้า `localhost:8080/unilink` |
| อัปโหลดรูปไม่ได้ | เช็คว่าโฟลเดอร์ `uploads/` มีอยู่และเขียนได้ |
| ภาษาไทยเป็น ??? | ต้อง import ผ่านไฟล์ `.sql` นี้ (ตั้ง utf8mb4 ไว้แล้ว) |
