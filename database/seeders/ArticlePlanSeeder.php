<?php

namespace Database\Seeders;

use App\Models\ArticlePlan;
use Illuminate\Database\Seeder;

class ArticlePlanSeeder extends Seeder
{
    public function run(): void
    {
        $planData = [
            // ปี 69 (2026)
            ['month' => 'พฤษภาคม 69', 'items' => [
                ['publish_date' => '2026-05-11', 'publish_time' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันพืชมงคล: เลขมงคลการเงินและความมั่งคั่ง', 'is_lottery' => false],
                ['publish_date' => '2026-05-16', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย (สถิติ/วิเคราะห์)', 'is_lottery' => true],
                ['publish_date' => '2026-05-22', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(Pillar Content เติมช่องว่างปลายเดือน)', 'is_lottery' => false],
                ['publish_date' => '2026-05-27', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(Pillar Content เลี้ยงกระแสก่อยวันพระใหญ่)', 'is_lottery' => false],
                ['publish_date' => '2026-05-31', 'publish_time' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันวิสาขบูชา: เลขสติปัญญาและการเริ่มต้นใหม่', 'is_lottery' => false],
            ]],
            ['month' => 'มิถุนายน 69', 'items' => [
                ['publish_date' => '2026-06-01', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2026-06-08', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางต้นเดือน)', 'is_lottery' => false],
                ['publish_date' => '2026-06-16', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2026-06-21', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางปลายเดือน)', 'is_lottery' => false],
                ['publish_date' => '2026-06-26', 'publish_time' => '09:00', 'type' => 'วันมู', 'topic' => 'วันสุนทรภู่: เลขมงคลสายวาทศิลป์และการเจรจา', 'is_lottery' => false],
            ]],
            ['month' => 'กรกฎาคม 69', 'items' => [
                ['publish_date' => '2026-07-01', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2026-07-08', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางต้นเดือน)', 'is_lottery' => false],
                ['publish_date' => '2026-07-16', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2026-07-23', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางปลายเดือน)', 'is_lottery' => false],
                ['publish_date' => '2026-07-28', 'publish_time' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'คอนเทนต์มงคลรวมใจ (ร.10)', 'is_lottery' => false],
                ['publish_date' => '2026-07-29', 'publish_time' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันอาสาฬหบูชา: ปรับพลังงานตัวเลข', 'is_lottery' => false],
            ]],
            ['month' => 'สิงหาคม 69', 'items' => [
                ['publish_date' => '2026-08-01', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2026-08-08', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางต้นเดือน)', 'is_lottery' => false],
                ['publish_date' => '2026-08-12', 'publish_time' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันแม่: เลขมงคลสุขภาพ', 'is_lottery' => false],
                ['publish_date' => '2026-08-15', 'publish_time' => '09:00', 'type' => 'วันมู', 'topic' => 'วันคเณศจตุรถี: เลขมงคลประทานพร', 'is_lottery' => false],
                ['publish_date' => '2026-08-16', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2026-08-25', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางปลายเดือน เพราะวันสำคัญกระจุกต้นเดือน)', 'is_lottery' => false],
            ]],
            ['month' => 'กันยายน 69', 'items' => [
                ['publish_date' => '2026-09-01', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2026-09-08', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางต้นเดือน)', 'is_lottery' => false],
                ['publish_date' => '2026-09-16', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2026-09-21', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางปลายเดือน)', 'is_lottery' => false],
                ['publish_date' => '2026-09-25', 'publish_time' => '09:00', 'type' => 'วันมู', 'topic' => 'วันไหว้พระจันทร์: เลขเมตตามหานิยม', 'is_lottery' => false],
                ['publish_date' => '2026-09-30', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(ปิดท้ายเดือน)', 'is_lottery' => false],
            ]],
            ['month' => 'ตุลาคม 69', 'items' => [
                ['publish_date' => '2026-10-01', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2026-10-08', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางต้นเดือน)', 'is_lottery' => false],
                ['publish_date' => '2026-10-16', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2026-10-20', 'publish_time' => '09:00', 'type' => 'วันมู', 'topic' => 'เทศกาลกินเจ: เลขสายขาว (เลือกลงวันที่ 20)', 'is_lottery' => false],
                ['publish_date' => '2026-10-23', 'publish_time' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันปิยมหาราช: เลขมงคลการงาน', 'is_lottery' => false],
            ]],
            ['month' => 'พฤศจิกายน 69', 'items' => [
                ['publish_date' => '2026-11-01', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2026-11-08', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางต้นเดือน)', 'is_lottery' => false],
                ['publish_date' => '2026-11-16', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2026-11-24', 'publish_time' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันลอยกระทง: เลขขอพรโชคลาภ', 'is_lottery' => false],
                ['publish_date' => '2026-11-29', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางปลายเดือน)', 'is_lottery' => false],
            ]],
            ['month' => 'ธันวาคม 69', 'items' => [
                ['publish_date' => '2026-12-01', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2026-12-05', 'publish_time' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันพ่อ: เลขมงคลความมั่นคง', 'is_lottery' => false],
                ['publish_date' => '2026-12-10', 'publish_time' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันรัฐธรรมนูญ: เลขมงคลระเบียบวินัย', 'is_lottery' => false],
                ['publish_date' => '2026-12-16', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2026-12-24', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางก่อนปีใหม่)', 'is_lottery' => false],
                ['publish_date' => '2026-12-31', 'publish_time' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันสิ้นปี: สรุปเลขปี 69', 'is_lottery' => false],
            ]],
            // ปี 70 (2027)
            ['month' => 'มกราคม 70', 'items' => [
                ['publish_date' => '2027-01-01', 'publish_time' => '09:00', 'type' => 'หวย/สำคัญ', 'topic' => 'วันขึ้นปีใหม่: เปิดดวงตัวเลขปี 70 + หวย', 'is_lottery' => true],
                ['publish_date' => '2027-01-09', 'publish_time' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันเด็ก: เลขมงคลเสริม IQ', 'is_lottery' => false],
                ['publish_date' => '2027-01-16', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2027-01-23', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(อุดช่องว่างปลายเดือน)', 'is_lottery' => false],
            ]],
            ['month' => 'กุมภาพันธ์ 70', 'items' => [
                ['publish_date' => '2027-02-01', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2027-02-06', 'publish_time' => '09:00', 'type' => 'วันมู', 'topic' => 'วันตรุษจีน: เลขรับทรัพย์', 'is_lottery' => false],
                ['publish_date' => '2027-02-14', 'publish_time' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันวาเลนไทน์: คู่เลขความรัก', 'is_lottery' => false],
                ['publish_date' => '2027-02-16', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2027-02-21', 'publish_time' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันมาฆบูชา: เลขสายบุญ', 'is_lottery' => false],
                ['publish_date' => '2027-02-26', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(อุดช่องว่างปลายเดือน)', 'is_lottery' => false],
            ]],
            ['month' => 'มีนาคม 70', 'items' => [
                ['publish_date' => '2027-03-01', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2027-03-08', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางต้นเดือน - เดือนนี้ไม่มีเทศกาล)', 'is_lottery' => false],
                ['publish_date' => '2027-03-16', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2027-03-24', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(คั่นกลางปลายเดือน)', 'is_lottery' => false],
                ['publish_date' => '2027-03-30', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(ปิดท้ายเดือน)', 'is_lottery' => false],
            ]],
            ['month' => 'เมษายน 70', 'items' => [
                ['publish_date' => '2027-04-01', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2027-04-06', 'publish_time' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันจักรี: เลขเสริมอำนาจ', 'is_lottery' => false],
                ['publish_date' => '2027-04-13', 'publish_time' => '09:00', 'type' => 'วันสำคัญ', 'topic' => 'วันสงกรานต์: เลขปลอดภัยในการเดินทาง', 'is_lottery' => false],
                ['publish_date' => '2027-04-16', 'publish_time' => '09:00', 'type' => 'หวย', 'topic' => 'คอนเทนต์หวย', 'is_lottery' => true],
                ['publish_date' => '2027-04-24', 'publish_time' => '09:09', 'type' => 'Evergreen', 'topic' => '(อุดช่องว่างปลายเดือน)', 'is_lottery' => false],
            ]],
        ];

        foreach ($planData as $month) {
            foreach ($month['items'] as $item) {
                ArticlePlan::create([
                    'publish_date' => $item['publish_date'],
                    'publish_time' => $item['publish_time'],
                    'type' => $item['type'],
                    'topic' => $item['topic'],
                    'is_lottery' => $item['is_lottery'],
                ]);
            }
        }
    }
}
