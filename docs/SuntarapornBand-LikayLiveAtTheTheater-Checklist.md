# Checklist หน้า SuntarapornBand และ LikayLiveAtTheTheater

เอกสารนี้ใช้สำหรับตรวจความพร้อมก่อนใช้งานจริงและหลังแก้ไขระบบผังที่นั่งของ 2 หน้า:

- `/SuntarapornBand`
- `/LikayLiveAtTheTheater`

อ้างอิงจาก route, controller, view, service, migration และ feature test ปัจจุบันในโปรเจกต์

---

## 1. Checklist รวมก่อนตรวจทั้ง 2 หน้า

### Route และสิทธิ์เข้าใช้งาน

- [ ] หน้า public view เปิดได้โดยไม่ต้อง login
- [ ] หน้า admin หลัก redirect ไปหน้า login เมื่อยังไม่ login
- [ ] login ใช้ throttle ป้องกัน brute force
- [ ] logout ล้าง session เฉพาะระบบนั้นถูกต้อง
- [ ] role staff เข้าใช้งานหน้าจองได้
- [ ] role manager เข้าใช้งานหน้าจองและเมนูจัดการพิเศษได้
- [ ] role ที่ไม่เกี่ยวข้องไม่สามารถเข้าใช้งานหน้าจองได้

### ผังที่นั่งและสถานะ

- [ ] จำนวนที่นั่งรวมแสดงตรงกับ `SeatMap::totalSeats()`
- [ ] seat key ทุกตัวในหน้าอยู่ใน seat map ฝั่ง server
- [ ] ไม่มี seat key ที่เลิกใช้หรือเกินผังแสดงบนหน้า
- [ ] สีโซนแสดงตรงกับข้อมูล zone/row zone ปัจจุบัน
- [ ] สถานะว่าง แสดงสีตามโซน
- [ ] สถานะกำลังเลือก แสดงเป็นสีส้ม/ล็อกชั่วคราว
- [ ] สถานะจองแล้ว แสดงเป็นสีเทาเข้ม
- [ ] zoom/pan หรือ touch interaction ใช้งานได้บนมือถือ

### การจอง

- [ ] เลือกได้หลายที่นั่งในรายการเดียว
- [ ] กรอกชื่อ นามสกุล เบอร์โทร และข้อมูลที่จำเป็นครบ
- [ ] แนบสลิปได้ตามชนิดไฟล์และขนาดที่กำหนด
- [ ] ระบบคำนวณราคาจาก server-side ไม่เชื่อราคาจาก client
- [ ] จองสำเร็จแล้วที่นั่งเปลี่ยนเป็นสถานะจองแล้วทันที
- [ ] กรณี seat ถูกจองไปก่อน ต้องตอบกลับ `409 Conflict`
- [ ] กรณี seat key ไม่ถูกต้อง ต้องตอบกลับ validation error
- [ ] หลังจองสำเร็จ selecting cache ของที่นั่งนั้นถูกล้าง

### Real-time และ fallback

- [ ] Pusher/Echo subscribe channel ของรอบ/ระบบถูกต้อง
- [ ] เมื่อเลือกที่นั่ง หน้าอื่นเห็นสถานะกำลังเลือก
- [ ] เมื่อปล่อยที่นั่ง หน้าอื่นกลับเป็นสถานะว่าง
- [ ] เมื่อจองสำเร็จ หน้า public และ admin อื่นเห็นสถานะจองแล้ว
- [ ] `live-state` คืนค่า `booked` และ `selecting` ถูกต้อง
- [ ] polling fallback ทำงานทุกประมาณ 8 วินาที
- [ ] ปิดแท็บหรือ reload แล้วระบบส่ง deselect ด้วย `sendBeacon` หรือ fallback ที่กำหนด

### หน้ารายการจอง

- [ ] หน้า `/bookings` แสดงรายการจองได้
- [ ] ค้นหาด้วยชื่อ นามสกุล เบอร์โทร หรือ seat key ได้
- [ ] เปิดดูข้อมูล booking ของที่นั่งที่จองแล้วได้
- [ ] ลิงก์ดูสลิปทำงานเมื่อมีไฟล์
- [ ] export Excel ดาวน์โหลดได้
- [ ] manager ยกเลิก booking ได้
- [ ] ยกเลิก booking แล้วปลดสถานะ seat กลับเป็นว่าง
- [ ] ยกเลิก booking แล้วลบไฟล์สลิปที่เกี่ยวข้อง

### จัดการโซนและ reset

- [ ] list/create/update/delete zone จำกัดสิทธิ์ manager
- [ ] update row zones จำกัดสิทธิ์ manager
- [ ] ห้ามลบ zone ที่ยังถูกใช้งานโดย row zone หากระบบกำหนดไว้
- [ ] reset จำกัดสิทธิ์ manager
- [ ] reset แล้ว booking ถูกล้าง
- [ ] reset แล้ว seat ทุกตัวกลับเป็นว่าง
- [ ] reset แล้ว selecting cache ถูกล้าง
- [ ] reset แล้วลบไฟล์สลิปที่เกี่ยวข้อง

### UI และ Responsive

- [ ] หน้า desktop ไม่ล้นแนวนอนโดยไม่จำเป็น
- [ ] หน้า mobile แตะที่นั่งได้แม่นและไม่บังปุ่มสำคัญ
- [ ] summary bar อ่านง่ายและไม่ทับผังที่นั่ง
- [ ] modal รายละเอียดจองไม่ล้นจอมือถือ
- [ ] ปุ่มยืนยัน/ยกเลิก/รีเซ็ตมี confirmation สำหรับ action เสี่ยง
- [ ] ข้อความภาษาไทยอ่านง่ายและไม่มีคำผิดสำคัญ
- [ ] ภาพ poster โหลดได้จาก `public/images`

---

## 2. Checklist เฉพาะ `/SuntarapornBand`

### ไฟล์หลักที่เกี่ยวข้อง

- [ ] `routes/web.php`
- [ ] `app/Http/Controllers/SuntarapornBandController.php`
- [ ] `app/Services/SuntarapornSeatMap.php`
- [ ] `app/Models/SuntarapornSeat.php`
- [ ] `app/Models/SuntarapornBooking.php`
- [ ] `app/Models/SuntarapornZone.php`
- [ ] `app/Events/SeatStatusUpdated.php`
- [ ] `resources/views/suntaraporn-band.blade.php`
- [ ] `resources/views/suntaraporn-public.blade.php`
- [ ] `resources/views/suntaraporn-login.blade.php`
- [ ] `resources/views/suntaraporn-bookings.blade.php`
- [ ] `tests/Feature/SuntarapornBandTest.php`

### URLs ที่ต้องตรวจ

- [ ] `GET /SuntarapornBand/view`
- [ ] `GET /SuntarapornBand/login`
- [ ] `POST /SuntarapornBand/login`
- [ ] `POST /SuntarapornBand/logout`
- [ ] `GET /SuntarapornBand`
- [ ] `POST /SuntarapornBand/book`
- [ ] `POST /SuntarapornBand/select`
- [ ] `POST /SuntarapornBand/deselect`
- [ ] `POST /SuntarapornBand/reset`
- [ ] `GET /SuntarapornBand/zones`
- [ ] `POST /SuntarapornBand/zones`
- [ ] `PUT /SuntarapornBand/zones/{id}`
- [ ] `DELETE /SuntarapornBand/zones/{id}`
- [ ] `PUT /SuntarapornBand/row-zones`
- [ ] `GET /SuntarapornBand/booking-info/{seatKey}`
- [ ] `GET /SuntarapornBand/bookings`
- [ ] `GET /SuntarapornBand/export`
- [ ] `DELETE /SuntarapornBand/booking/{id}`
- [ ] `GET /SuntarapornBand/live-state`

### ข้อมูลและ business rules

- [ ] จำนวนที่นั่งรวมต้องเป็น `585` ตาม test ปัจจุบัน
- [ ] แถว `V` และ `W` เป็น zone `vip`
- [ ] zone `vip` ราคา `0`
- [ ] ที่นั่ง `V_1`, `W_1`, `V_16`, `W_18` ถูก classify เป็น `vip`
- [ ] ที่นั่ง `A_1` เป็นโซนด้านหน้าและราคาคำนวณจาก server
- [ ] `BOXA_1` คำนวณราคาและ zone จาก `SuntarapornSeatMap`
- [ ] booking เก็บ `booker_name` จาก user ที่ login
- [ ] slip เก็บใน disk public ภายใต้ path ของ Suntaraporn
- [ ] รองรับ query/date สำหรับรอบแสดง ถ้าหน้าใช้งาน `showDate`

### Public view

- [ ] แสดง poster `public/images/suntaraporn-poster.jpg`
- [ ] แสดงจำนวนที่นั่งรวม `585`
- [ ] แสดงสถิติที่นั่งจองแล้ว/ว่างถูกต้อง
- [ ] ไม่สามารถกดจองหรือส่ง booking จาก public view
- [ ] รับ real-time event จาก channel ของ Suntaraporn
- [ ] polling เรียก `/SuntarapornBand/live-state`
- [ ] สลับวันที่แสดงได้ หากมีหลายรอบใน `showDates`

### Admin booking view

- [ ] staff role `suntaraporn` เข้า `/SuntarapornBand` ได้
- [ ] manager role เข้า `/SuntarapornBand` ได้
- [ ] staff จองที่นั่งได้
- [ ] staff ไม่มีปุ่ม/สิทธิ์ reset, cancel, zone management หากระบบกำหนดเฉพาะ manager
- [ ] manager เห็นเมนู bookings/export/zone/reset ที่จำเป็น
- [ ] summary bar แสดงที่นั่งที่เลือกและราคารวมถูกต้อง
- [ ] modal booking ส่ง seat keys ถูกต้อง
- [ ] จองที่นั่ง `vip` ราคา `0` ได้โดยยอดรวมถูกต้อง

### รายการจองและ export

- [ ] `/SuntarapornBand/bookings` เปิดได้หลัง login
- [ ] ค้นหาข้อมูล booking ได้
- [ ] `/SuntarapornBand/export` export รายการได้
- [ ] ชื่อไฟล์ export สื่อถึง `suntaraporn` และวันที่แสดง
- [ ] manager cancel booking แล้ว seat ที่เกี่ยวข้องกลับว่าง

### Test ที่ควรรัน

- [ ] `php artisan test --filter=SuntarapornBand`
- [ ] ตรวจผล `Tests passed: X, Failed: 0`

---

## 3. Checklist เฉพาะ `/LikayLiveAtTheTheater`

### ไฟล์หลักที่เกี่ยวข้อง

- [ ] `routes/web.php`
- [ ] `app/Http/Controllers/LikayLiveAtTheTheaterController.php`
- [ ] `app/Services/LikaySeatMap.php`
- [ ] `app/Models/LikaySeat.php`
- [ ] `app/Models/LikayBooking.php`
- [ ] `app/Models/LikayZone.php`
- [ ] `app/Events/LikaySeatStatusUpdated.php`
- [ ] `resources/views/likay-band.blade.php`
- [ ] `resources/views/likay-public.blade.php`
- [ ] `resources/views/likay-login.blade.php`
- [ ] `resources/views/likay-bookings.blade.php`
- [ ] `tests/Feature/LikayLiveAtTheTheaterTest.php`

### URLs ที่ต้องตรวจ

- [ ] `GET /LikayLiveAtTheTheater/view`
- [ ] `GET /LikayLiveAtTheTheater/login`
- [ ] `POST /LikayLiveAtTheTheater/login`
- [ ] `POST /LikayLiveAtTheTheater/logout`
- [ ] `GET /LikayLiveAtTheTheater`
- [ ] `POST /LikayLiveAtTheTheater/book`
- [ ] `POST /LikayLiveAtTheTheater/select`
- [ ] `POST /LikayLiveAtTheTheater/deselect`
- [ ] `POST /LikayLiveAtTheTheater/reset`
- [ ] `GET /LikayLiveAtTheTheater/zones`
- [ ] `POST /LikayLiveAtTheTheater/zones`
- [ ] `PUT /LikayLiveAtTheTheater/zones/{id}`
- [ ] `DELETE /LikayLiveAtTheTheater/zones/{id}`
- [ ] `PUT /LikayLiveAtTheTheater/row-zones`
- [ ] `GET /LikayLiveAtTheTheater/booking-info/{seatKey}`
- [ ] `GET /LikayLiveAtTheTheater/bookings`
- [ ] `GET /LikayLiveAtTheTheater/export`
- [ ] `DELETE /LikayLiveAtTheTheater/booking/{id}`
- [ ] `GET /LikayLiveAtTheTheater/live-state`

### ข้อมูลและ business rules

- [ ] จำนวนที่นั่งรวมต้องเป็น `585` ตาม test ปัจจุบัน
- [ ] หน้าไม่ render `data-key="L_32"` ตาม test ปัจจุบัน
- [ ] purple zone มี 81 ที่นั่ง
- [ ] migration จอง purple zone ให้ `king power`
- [ ] booking ของ `king power` มียอดรวม `405000`
- [ ] booking ของ `king power` ไม่มี slip path
- [ ] purple seats ทั้ง 81 ที่นั่งถูก mark เป็นจองแล้วและผูก booking เดียวกัน
- [ ] slip เก็บใน disk public ภายใต้ path ของ Likay
- [ ] row zones และ zone colors ตรงกับ `LikaySeatMap`

### Public view

- [ ] แสดง poster `public/images/likay-poster.jpg`
- [ ] แสดงจำนวนที่นั่งรวม `585`
- [ ] แสดงสถิติที่นั่งจองแล้ว/ว่างถูกต้อง
- [ ] purple zone ที่ถูกจองล่วงหน้าแสดงเป็นจองแล้ว
- [ ] ไม่สามารถกดจองหรือส่ง booking จาก public view
- [ ] รับ real-time event จาก channel ของ Likay
- [ ] polling เรียก `/LikayLiveAtTheTheater/live-state`

### Admin booking view

- [ ] staff role `likay` เข้า `/LikayLiveAtTheTheater` ได้
- [ ] manager role เข้า `/LikayLiveAtTheTheater` ได้
- [ ] staff จองที่นั่งได้
- [ ] staff ไม่มีปุ่ม/สิทธิ์ reset, cancel, zone management หากระบบกำหนดเฉพาะ manager
- [ ] manager เห็นเมนู bookings/export/zone/reset ที่จำเป็น
- [ ] summary bar แสดงที่นั่งที่เลือกและราคารวมถูกต้อง
- [ ] modal booking ส่ง seat keys ถูกต้อง
- [ ] ที่นั่ง purple ที่ถูกจองให้ `king power` จองซ้ำไม่ได้

### รายการจองและ export

- [ ] `/LikayLiveAtTheTheater/bookings` เปิดได้หลัง login
- [ ] ค้นหาข้อมูล booking ได้
- [ ] `/LikayLiveAtTheTheater/export` export รายการได้
- [ ] ชื่อไฟล์ export สื่อถึง `likay`
- [ ] manager cancel booking แล้ว seat ที่เกี่ยวข้องกลับว่าง

### Test ที่ควรรัน

- [ ] `php artisan test --filter=LikayLiveAtTheTheater`
- [ ] ตรวจผล `Tests passed: X, Failed: 0`

---

## 4. Regression Test ก่อนส่งงาน

- [ ] `php artisan test --filter=SuntarapornBand`
- [ ] `php artisan test --filter=LikayLiveAtTheTheater`
- [ ] `php artisan test`
- [ ] ตรวจว่าไม่มี failed test
- [ ] ถ้ามีแก้ logic เพิ่ม ต้องเพิ่มหรือปรับ test case ให้ครอบคลุม

---

## 5. หมายเหตุสำหรับผู้ตรวจ

- ใช้ migration เท่านั้นเมื่อต้องเปลี่ยนฐานข้อมูล ห้ามแก้ DB โดยตรง
- ถ้าต้องเพิ่มค่า `.env` ใหม่ ต้องแจ้งก่อน deploy
- หน้า public เป็น read-only แต่ยังต้องรับ real-time และ polling fallback
- Action ที่กระทบข้อมูลจริง เช่น reset/cancel/delete zone ต้องจำกัดสิทธิ์ manager และมี confirmation ใน UI
- ทุกการคำนวณราคา booking ต้องอ้างอิงข้อมูลจาก server-side เท่านั้น
