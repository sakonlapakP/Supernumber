# Estimate Flow Documentation

This file documents the `/estimate` recommendation flow in both Thai and English so the UI, backend, and tests stay aligned.

## Thai

### ภาพรวม
หน้า `estimate` ใช้เก็บข้อมูลผู้ใช้เพื่อคัดเลือกเบอร์ที่เหมาะกับอาชีพและเป้าหมายชีวิต จากนั้นระบบจะสร้างผลลัพธ์แบบ signed URL และส่งผู้ใช้ไปยังหน้าประมวลผลก่อนแสดงผลจริง

### ฟิลด์ที่ใช้
- `first_name`, `last_name`: ชื่อ-นามสกุลของลูกค้า
- `gender`: เพศ
- `birthday`: วันเกิด
- `work_type`: ลักษณะงานหลักที่ทำ
- `current_phone`: เบอร์ปัจจุบัน
- `main_phone`: เบอร์ที่ใช้งานมากที่สุด
- `email`: อีเมล
- `goal`: วัตถุประสงค์ในการเปลี่ยนเบอร์

### ลักษณะงานหลักที่ทำ
ฟิลด์ `work_type` เป็นตัวกำหนดหมวดอาชีพที่ระบบใช้ match กับเบอร์แนะนำ โดยค่าในฟอร์มต้องตรงกับ `EstimateLead::workTypeLabels()`

ตัวเลือกปัจจุบัน:
- `owner` - เจ้าของธุรกิจ / ผู้ประกอบการ
- `manager` - ผู้บริหาร / หัวหน้างาน
- `freelance` - ฟรีแลนซ์ / อาชีพอิสระ
- `finance` - บัญชี / การเงิน / ธนาคาร
- `real_estate` - อสังหา / นายหน้า
- `government` - งานราชการ / รัฐวิสาหกิจ
- `health_beauty` - แพทย์ / สุขภาพ / ความงาม
- `technical` - ช่าง / วิศวกรรม / เทคนิค
- `logistics` - ขนส่ง / โลจิสติกส์
- `student` - นักเรียน / นักศึกษา
- `sales` - งานขาย / เจรจา
- `service` - งานบริการ / ดูแลลูกค้า
- `office` - งานออฟฟิศ / ธุรการ
- `online` - งานออนไลน์ / คอนเทนต์

### วัตถุประสงค์
ฟิลด์ `goal` ใช้กำหนดกติกาการคัดเลขหลักของผลลัพธ์

ตัวเลือกปัจจุบัน:
- `work` - การงาน
- `money` - การเงิน
- `love` - ความรัก
- `health` - สุขภาพ

### Flow
1. ผู้ใช้กรอกข้อมูลและส่งฟอร์มที่ `/estimate`
2. ระบบ validate และบันทึกเป็น `estimate_leads`
3. ระบบส่งแจ้งเตือน LINE
4. ระบบ redirect ไปหน้า `/estimate/processing/{estimateLead}`
5. หน้าประมวลผลแสดงข้อความ “กำลังประมวณผล...” และ “กำลังวิเคราะห์และคัดเบอร์ที่เหมาะกับคุณ...”
6. ประมาณ 5 วินาทีถัดไป ระบบ redirect ไปหน้า `/estimate/results/{estimateLead}`
7. หน้า results แสดงผลแนะนำเบอร์และคำอธิบายเงื่อนไขที่ใช้คัด

### หมายเหตุเชิงระบบ
- หน้า processing และ results ใช้ signed URL เพื่อกันการเรียกตรงแบบไม่ผ่าน flow
- หน้า results แสดงเบอร์รายเดือนและเบอร์เติมเงินแยกกัน
- เบอร์ที่แนะนำจะถูกสุ่มจากชุดที่ตรงเงื่อนไขแทนการเรียงลำดับตายตัว
- list/grid toggle บนหน้า results ใช้แนวทางเดียวกับหน้า catalog หลัก

### หลักการวิเคราะห์
ระบบวิเคราะห์ผลจาก 2 แกนหลักคือ `ลักษณะงานหลักที่ทำ` และ `วัตถุประสงค์ในการเปลี่ยนเบอร์`

1. `work_type` ใช้จับหมวดอาชีพให้ตรงกับชุดหัวข้อที่ควรเสริม เช่น การสื่อสาร การเงิน ความรัก การงาน หรือการคุ้มครอง
2. `goal` ใช้กำหนดกฎเลขเฉพาะของผลลัพธ์ เช่น เลขที่ต้องมี เลขที่ควรหลีกเลี่ยง หรือเลขตำแหน่งที่ต้องอยู่ในเบอร์
3. ถ้าอาชีพมีหมวดตรงหลายอย่าง ระบบจะให้ความสำคัญกับเบอร์ที่ match ครบทุกหมวดก่อน
4. ถ้า inventory มีไม่พอ ระบบจะผ่อนจาก exact match เป็น partial match แต่ยังต้องผ่านกฎเป้าหมาย
5. ผลลัพธ์ที่แสดงจะสุ่มจากกลุ่มที่ผ่านเงื่อนไขแทนการเรียงตายตัว เพื่อให้หน้า results ดูหลากหลาย
6. เบอร์รายเดือนและเบอร์เติมเงินจะถูกประเมินแยกกัน แต่ใช้กติกาเป้าหมายเดียวกัน
7. หน้า results จะสรุปทั้งอาชีพและเป้าหมายที่ใช้วิเคราะห์ เพื่อให้ลูกค้าเห็นเหตุผลของเบอร์ที่แนะนำ

## English

### Overview
The `estimate` page collects user input to recommend phone numbers based on occupation and life goals. After submission, the system generates a signed results URL and sends the user through a processing screen before showing the actual recommendations.

### Input Fields
- `first_name`, `last_name`: customer first and last name
- `gender`: gender
- `birthday`: date of birth
- `work_type`: primary work type
- `current_phone`: current phone number
- `main_phone`: most-used phone number
- `email`: email address
- `goal`: number-change objective

### Primary Work Type
The `work_type` field drives the occupation category used to match recommended numbers. The form values must match `EstimateLead::workTypeLabels()`.

Current options:
- `owner` - เจ้าของธุรกิจ / ผู้ประกอบการ
- `manager` - ผู้บริหาร / หัวหน้างาน
- `freelance` - ฟรีแลนซ์ / อาชีพอิสระ
- `finance` - บัญชี / การเงิน / ธนาคาร
- `real_estate` - อสังหา / นายหน้า
- `government` - งานราชการ / รัฐวิสาหกิจ
- `health_beauty` - แพทย์ / สุขภาพ / ความงาม
- `technical` - ช่าง / วิศวกรรม / เทคนิค
- `logistics` - ขนส่ง / โลจิสติกส์
- `student` - นักเรียน / นักศึกษา
- `sales` - งานขาย / เจรจา
- `service` - งานบริการ / ดูแลลูกค้า
- `office` - งานออฟฟิศ / ธุรการ
- `online` - งานออนไลน์ / คอนเทนต์

### Goal
The `goal` field determines the main recommendation rules applied to the result set.

Current options:
- `work` - การงาน
- `money` - การเงิน
- `love` - ความรัก
- `health` - สุขภาพ

### Flow
1. The user submits the form at `/estimate`
2. The system validates and stores the submission in `estimate_leads`
3. LINE notification is sent
4. The user is redirected to `/estimate/processing/{estimateLead}`
5. The processing page shows “กำลังประมวณผล...” and “กำลังวิเคราะห์และคัดเบอร์ที่เหมาะกับคุณ...”
6. About 5 seconds later, the browser redirects to `/estimate/results/{estimateLead}`
7. The results page shows the recommended numbers and the rules used to pick them

### Implementation Notes
- Processing and results pages use signed URLs so users cannot jump straight into the flow
- The results page separates postpaid and prepaid recommendations
- Recommended numbers are randomized from the qualifying pool instead of shown in a fixed order
- The list/grid toggle on results follows the same pattern as the main catalog page

### Analysis Principles
The recommendation engine is driven by two main inputs: `work_type` and `goal`.

1. `work_type` maps the occupation to the set of topics that should be strengthened, such as communication, money, love, career growth, or protection.
2. `goal` defines the number rules used in the final recommendation set, including required digits, blocked digits, or positional patterns.
3. When an occupation matches multiple topic groups, the system prefers numbers that satisfy all relevant topic groups first.
4. If the inventory is too small, the engine relaxes from exact matches to partial matches while still enforcing the selected goal rules.
5. The displayed recommendations are randomized from the qualifying pool instead of being shown in a fixed order.
6. Postpaid and prepaid numbers are evaluated separately, but both follow the same goal logic.
7. The results page summarizes both the occupation and the goal so the customer can understand why a number was recommended.
