-- =========================================================
--  UniLink Database (MySQL / MariaDB)
--  นำเข้าผ่าน phpMyAdmin > Import หรือ: mysql -u root < unilink.sql
--  บัญชีทดสอบ:
--    Admin : admin_01 / admin1234   (หน้า admin_login.php)
--    User  : somchai@student.chula.ac.th / password123  (และทุก user ในไฟล์นี้ใช้ password123)
-- =========================================================

CREATE DATABASE IF NOT EXISTS unilink
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE unilink;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS application;
DROP TABLE IF EXISTS activity;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------- users ----------
CREATE TABLE users (
  user_id     VARCHAR(50)  NOT NULL PRIMARY KEY,            -- รหัสนิสิต / รหัสผู้ใช้
  user_name   VARCHAR(100) NOT NULL,                        -- ชื่อ-นามสกุล
  email       VARCHAR(100) NOT NULL UNIQUE,                 -- อีเมลจุฬาฯ
  password    VARCHAR(255) NOT NULL,                        -- รหัสผ่าน (hashed, bcrypt)
  role        ENUM('admin','user','guest') NOT NULL DEFAULT 'user',
  faculty     VARCHAR(100) NULL,
  major       VARCHAR(100) NULL,
  dob         DATE         NULL,
  phone       VARCHAR(20)  NULL,
  line_id     VARCHAR(50)  NULL,
  ig          VARCHAR(50)  NULL,
  bio         TEXT         NULL,
  profile_pic VARCHAR(255) NULL,
  skills      TEXT         NULL,                            -- คั่นด้วย comma
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- activity (post) ----------
CREATE TABLE activity (
  act_id      INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id     VARCHAR(50)  NOT NULL,                        -- ผู้โพสต์ (FK)
  title       VARCHAR(255) NOT NULL,
  category    VARCHAR(100) NOT NULL,
  organizer   VARCHAR(255) NOT NULL,
  logo_text   VARCHAR(50)  NULL,
  theme_color VARCHAR(20)  NOT NULL DEFAULT '#E91E8C',
  regis_date  DATE         NOT NULL,                        -- วันปิดรับสมัคร
  act_date    DATE         NOT NULL,                        -- วันที่จัดกิจกรรม
  fee         VARCHAR(100) NOT NULL DEFAULT 'ฟรี',
  benefits    VARCHAR(255) NULL,
  description TEXT         NULL,
  questions   TEXT         NULL,                            -- JSON array ของคำถามคัดกรอง
  contact     VARCHAR(255) NULL,
  image_path  VARCHAR(255) NULL,
  status      VARCHAR(50)  NOT NULL DEFAULT 'open',         -- open / closed
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_category (category),
  INDEX idx_status (status),
  CONSTRAINT fk_activity_user FOREIGN KEY (user_id)
    REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- application ----------
CREATE TABLE application (
  id            INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id       VARCHAR(50)  NOT NULL,                      -- ผู้สมัคร (FK)
  activity_id   INT(11)      NOT NULL,                      -- กิจกรรมที่สมัคร (FK)
  faculty       VARCHAR(255) NOT NULL,
  role          VARCHAR(255) NOT NULL,                      -- ตำแหน่ง/ฝ่ายที่สมัคร
  answers       TEXT         NULL,                          -- JSON [{q, a}]
  status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  reject_reason TEXT         NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_activity (user_id, activity_id),       -- กันสมัครซ้ำ
  CONSTRAINT fk_app_user FOREIGN KEY (user_id)
    REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_app_activity FOREIGN KEY (activity_id)
    REFERENCES activity(act_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
--  Seed data
-- =========================================================
INSERT INTO users (user_id,user_name,email,password,role,faculty,major,dob,phone,line_id,ig,bio,skills) VALUES
('admin_01','UniLink Admin','admin@chula.ac.th','$2y$10$4.9lkQk4eU4NtVf1Xreaw.wiqLBeDxTy5yqxzl/3pY1mj4vv.mRie','admin','Engineering','IE',NULL,NULL,NULL,NULL,'ผู้ดูแลระบบ',NULL),
('6700000121','สมชาย ใจดี','somchai@student.chula.ac.th','$2y$10$jlH3bFwfiGk3nfThoUTTb.VpxE5V0U0zyHdVD7Cn4T7XFi9S8EaAi','user','Engineering','IE','2005-05-15','0800000001','somchai_ul','@somchai.ul','สนใจด้าน Optimization','Optimization, Excel, Pitching'),
('6700000221','สมหญิง รักเรียน','somying@student.chula.ac.th','$2y$10$jlH3bFwfiGk3nfThoUTTb.VpxE5V0U0zyHdVD7Cn4T7XFi9S8EaAi','user','Engineering','IE','2005-08-20','0800000002','somying_ul','@somying.ul','ถนัด Python และ Data','Python, Data Analysis, SQL'),
('6700000321','ณัฐ มีสุข','nat@student.chula.ac.th','$2y$10$jlH3bFwfiGk3nfThoUTTb.VpxE5V0U0zyHdVD7Cn4T7XFi9S8EaAi','user','Engineering','IE','2006-01-10','0800000003','nat_ul','@nat.ul','ชอบงาน Design และ UI','Figma, UI Design'),
('6700000421','ปิติ ขยันยิ่ง','piti@student.chula.ac.th','$2y$10$jlH3bFwfiGk3nfThoUTTb.VpxE5V0U0zyHdVD7Cn4T7XFi9S8EaAi','user','Engineering','IE',NULL,NULL,NULL,NULL,'สนใจงานวิจัย','Research, Simulation'),
('6700000521','ชูใจ สายใย','chujai@student.chula.ac.th','$2y$10$jlH3bFwfiGk3nfThoUTTb.VpxE5V0U0zyHdVD7Cn4T7XFi9S8EaAi','user','Engineering','IE',NULL,NULL,NULL,NULL,NULL,'Leadership, Event');

INSERT INTO activity (act_id,user_id,title,category,organizer,logo_text,theme_color,regis_date,act_date,fee,benefits,description,questions,contact,status) VALUES
(1001,'6700000121','IE Case Competition 2026','Competition','IE Chula','IE','#C21919','2026-11-25','2026-12-05','ฟรี','Certificate, เงินรางวัล','การแข่งขันแก้ปัญหาธุรกิจด้าน Operations สำหรับนิสิตทุกชั้นปี ทีมละ 3-4 คน','["ชอบทำเคสไหม","เคยแข่ง case มาก่อนหรือไม่"]','02-000-0001','open'),
(1002,'6700000221','Research Assistant – Supply Chain Lab','Recruitment','IE Dept','R','#111111','2026-11-30','2026-12-10','ฟรี','Experience, ค่าตอบแทน','ช่วยงานวิจัยภาควิชาด้าน Supply Chain เก็บข้อมูลและวิเคราะห์ด้วย Python','["ทนร้อนได้ไหม","อยู่กับความลำบากได้ไหม"]','02-000-0002','open'),
(1003,'6700000121','UI Workshop with Figma','Tutor','T4 Ronaldo','UI','#43CEEA','2026-11-05','2026-11-15','100 บาท','อาหาร, ทักษะ Figma','สอนพื้นฐานการใช้ Figma ตั้งแต่ศูนย์จนทำ prototype ได้','[]','02-000-0003','open'),
(1004,'6700000521','ค่ายฟันเฟืองสานฝัน ครั้งที่ 15','Camp','คณะวิศวกรรมศาสตร์','FF','#8E7CF0','2026-11-20','2026-12-20','ฟรี','ชั่วโมงกิจกรรม, เพื่อนใหม่','ค่ายอาสาพัฒนาโรงเรียนในต่างจังหวัด เปิดรับสตาฟหลายฝ่าย','["ทนร้อนได้ไหม","อยู่กับความลำบากได้ไหม"]','Line: funfueng','open'),
(1005,'6700000321','Intania Poker Club','Club','คณะวิศวกรรมศาสตร์','IPC','#1A1B2E','2026-12-01','2026-12-08','ฟรี','Community','ชมรมฝึกคิดเชิงกลยุทธ์และความน่าจะเป็น','[]','IG: @intaniapoker','open'),
(1006,'6700000221','Freelance: ทำสไลด์นำเสนอธุรกิจ','Freelance','SME Partner','SL','#F39C12','2026-10-01','2026-10-10','ฟรี','ค่าจ้าง 2,000 บาท','งานจ้างทำสไลด์ pitch deck 10 หน้า','["แนบลิงก์ผลงานเก่า"]','somying@student.chula.ac.th','closed');

INSERT INTO application (user_id,activity_id,faculty,role,answers,status,reject_reason,created_at) VALUES
('6700000321',1001,'Engineering','Designer','[{"q":"ชอบทำเคสไหม","a":"ชอบมาก อยากลองทำ Slide ธุรกิจ"},{"q":"เคยแข่ง case มาก่อนหรือไม่","a":"ยังไม่เคย"}]','pending',NULL,'2026-10-10 15:00:00'),
('6700000221',1001,'Engineering','Analyst','[{"q":"ชอบทำเคสไหม","a":"ถนัดวิเคราะห์ข้อมูล"},{"q":"เคยแข่ง case มาก่อนหรือไม่","a":"เคย 2 ครั้ง"}]','approved',NULL,'2026-10-10 15:30:00'),
('6700000421',1002,'Engineering','Researcher','[{"q":"ทนร้อนได้ไหม","a":"ได้"},{"q":"อยู่กับความลำบากได้ไหม","a":"ได้"}]','rejected','ตำแหน่งเต็มแล้ว ขอบคุณที่สนใจนะครับ','2026-10-11 16:00:00'),
('6700000521',1003,'Engineering','ผู้เข้าร่วม','[]','pending',NULL,'2026-10-12 09:00:00');

ALTER TABLE activity AUTO_INCREMENT = 1007;
