# Project Rules: Supernumber (Tarot & Articles System)

## 1. Communication Protocol (บังคับใช้ทุกครั้ง)
*   **Acknowledge & Plan:** สรุปความเข้าใจและแจ้งแผนงาน (Step-by-step) ก่อนเริ่มงานเสมอ
*   **Wait for Approval:** ห้าม Execute จนกว่าผู้ใช้จะพิมพ์ "ตกลง", "เริ่มได้", "Confirm" หรือ "ได้จ้า"
*   **Brief Output:** ใช้ Bullet points สั้นๆ เพื่อให้อ่านผ่านมือถือได้สะดวก

## 2. Testing & Quality Assurance
*   **Test-Driven Approach:** ต้องสร้างหรืออัปเดต Test Case ทุกครั้งที่มีการแก้ Logic/เพิ่มฟังก์ชัน
*   **Pre-flight Check:** รัน `php artisan test` ก่อนรายงานจบงานเสมอ
*   **Report Results:** แจ้งผลการทดสอบสั้นๆ เช่น "Tests passed: 5, Failed: 0"

## 3. Technical Standards
*   **Backend:** PHP / Laravel (app/, resources/views/admin/)
*   **UI/Frontend:** Tailwind CSS ในไฟล์ Blade (.blade.php)
*   **Database:** ห้ามแก้ DB โดยตรง ต้องใช้ Laravel Migrations เท่านั้น
*   **Error Handling:** ครอบ Try-Catch สำหรับการเชื่อมต่อ API ภายนอก (Line/Facebook)
*   **Environment:** แจ้งทันทีหากต้องเพิ่มค่าใน `.env` และห้ามรันคำสั่งทำลายข้อมูลโดยไม่เตือน

## 4. Task Management & Decomposition
*   **Big Task Warning:** หากแก้ไฟล์ > 3 ไฟล์ หรือซับซ้อนสูง ต้องแจ้งเตือนก่อน
*   **Modular Breakdown:** แยกงานใหญ่ออกเป็นลำดับ 1, 2, 3, 4
*   **Pre-work Review:** ระบุในแผนว่าจะเขียน Test สำหรับส่วนไหนบ้าง
