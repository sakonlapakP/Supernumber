# กฎเหล็กการคำนวณใบเสนอราคา

เอกสารนี้กำหนดกฎการคำนวณใบเสนอราคาของ Supernumber สำหรับกรณีที่มีภาษีมูลค่าเพิ่มและภาษีหัก ณ ที่จ่ายตามเงื่อนไขต่อไปนี้

- ภาษีมูลค่าเพิ่ม (`VAT`) = `7%`
- ภาษีหัก ณ ที่จ่าย (`Withholding Tax` หรือ `WHT`) = `3%`
- ทั้ง `VAT` และ `WHT` ต้องคำนวณจากราคาก่อน VAT เท่านั้น
- ห้ามคำนวณ `WHT` จากยอดที่รวม VAT แล้ว

## 1. รูปแบบการคำนวณที่ระบบต้องรองรับ

ระบบออกใบเสนอราคาต้องรองรับการคำนวณ 2 แบบเท่านั้น

1. `Standard Calculation`
2. `Reverse Calculation`

## 2. คำจำกัดความกลาง

| คำ | ความหมาย |
| --- | --- |
| `Base Price` | ราคาค่าจ้างตั้งต้นก่อน VAT สำหรับแบบ Standard |
| `Target Income` | รายได้ค่าบริการสุทธิที่ต้องการได้รับหลังหัก WHT แต่ยังไม่รวม VAT สำหรับแบบ Reverse |
| `Selling Price` | ราคาขายก่อน VAT ที่ระบบคำนวณได้ในแบบ Reverse |
| `VAT` | ภาษีมูลค่าเพิ่ม 7% ของราคาก่อน VAT |
| `Grand Total` | ราคาก่อน VAT บวก VAT |
| `WHT` | ภาษีหัก ณ ที่จ่าย 3% ของราคาก่อน VAT |
| `Customer Net Payment` | ยอดที่ลูกค้าต้องชำระหลังหัก WHT โดยยอดนี้ยังรวม VAT |
| `Service Net Income` | รายได้ค่าบริการหลังหัก WHT โดยไม่รวม VAT |

## 3. กฎแบบ Standard Calculation

### 3.1 Input

ผู้ใช้กรอก `Base Price`

### 3.2 สูตรบังคับ

```text
Selling Price = Base Price
VAT = Base Price * 0.07
Grand Total = Base Price + VAT
WHT = Base Price * 0.03
Customer Net Payment = Grand Total - WHT
Service Net Income = Base Price - WHT
```

### 3.3 ผลลัพธ์ที่ต้องแสดง

ระบบต้องแสดงอย่างน้อยรายการต่อไปนี้

1. ราคาค่าจ้างก่อนภาษี = `Base Price`
2. ภาษีมูลค่าเพิ่ม 7% = `VAT`
3. ยอดรวมทั้งหมด = `Grand Total`
4. ภาษีหัก ณ ที่จ่าย 3% = `WHT`
5. ยอดสุทธิที่ลูกค้าต้องชำระ = `Customer Net Payment`

### 3.4 ตัวอย่าง

เมื่อกรอก `Base Price = 50,000.00`

| รายการ | จำนวน |
| --- | ---: |
| ราคาค่าจ้างก่อนภาษี | 50,000.00 |
| VAT 7% | 3,500.00 |
| Grand Total | 53,500.00 |
| WHT 3% | 1,500.00 |
| Customer Net Payment | 52,000.00 |

## 4. กฎแบบ Reverse Calculation

### 4.1 Input

ผู้ใช้กรอก `Target Income`

`Target Income` หมายถึงยอดรายได้ค่าบริการที่ต้องการได้รับหลังหัก WHT แล้ว โดยไม่รวม VAT

### 4.2 สูตรบังคับ

ระบบต้องคำนวณราคาขายก่อน VAT จากสูตรนี้

```text
Selling Price = Target Income / 0.97
VAT = Selling Price * 0.07
Grand Total = Selling Price + VAT
WHT = Selling Price * 0.03
Customer Net Payment = Grand Total - WHT
Service Net Income = Selling Price - WHT
```

เหตุผลที่ต้องหารด้วย `0.97` คือ WHT 3% ถูกหักจากราคาขายก่อน VAT ทำให้รายได้ค่าบริการที่ได้รับหลังหัก WHT เหลือ 97% ของ `Selling Price`

### 4.3 ผลลัพธ์ที่ต้องแสดง

ระบบต้องแสดงอย่างน้อยรายการต่อไปนี้

1. ราคาขายก่อนภาษี = `Selling Price`
2. ภาษีมูลค่าเพิ่ม 7% = `VAT`
3. ยอดรวมทั้งหมด = `Grand Total`
4. ภาษีหัก ณ ที่จ่าย 3% = `WHT`
5. ยอดที่ลูกค้าต้องชำระหลังหัก WHT = `Customer Net Payment`
6. ยอดรายได้ค่าบริการหลังหัก WHT = `Service Net Income`

### 4.4 กฎตรวจสอบผล

แบบ Reverse ต้องตรวจสอบ `Target Income` กับ `Service Net Income` เท่านั้น

```text
Service Net Income = Target Income
```

ห้ามใช้ `Customer Net Payment` ไปตรวจสอบว่าเท่ากับ `Target Income` เพราะ `Customer Net Payment` ยังรวม VAT อยู่

### 4.5 ตัวอย่าง

เมื่อกรอก `Target Income = 50,000.00`

| รายการ | จำนวน |
| --- | ---: |
| Selling Price | 51,546.39 |
| VAT 7% | 3,608.25 |
| Grand Total | 55,154.64 |
| WHT 3% | 1,546.39 |
| Customer Net Payment | 53,608.25 |
| Service Net Income | 50,000.00 |

## 5. กฎการปัดเศษและการแสดงผล

1. ระบบต้องคำนวณจากค่าทศนิยมเต็มเท่าที่ระบบรองรับก่อนปัดเพื่อแสดงผล
2. จำนวนเงินที่แสดงในเอกสารต้องแสดงทศนิยม 2 ตำแหน่ง
3. การตรวจสอบแบบ Reverse ต้องยอมรับความคลาดเคลื่อนจากการปัดเศษไม่เกิน `0.01` บาท
4. หากระบบบันทึกยอดเงินลงฐานข้อมูล ให้บันทึกเป็นจำนวนเงินที่มีทศนิยม 2 ตำแหน่งหรือรูปแบบ money type ที่โปรเจกต์กำหนดไว้

## 6. ข้อห้าม

1. ห้ามสลับความหมายระหว่าง `Target Income`, `Customer Net Payment` และ `Service Net Income`
2. ห้ามใช้สูตร `Selling Price = Target Income / 1.04` สำหรับ Reverse Calculation
3. ห้ามคิด WHT จาก `Grand Total`
4. ห้ามรวม VAT เข้าใน `Target Income`
5. ห้ามให้ใบเสนอราคาแสดงยอด Reverse ว่าได้ `Target Income` จาก `Customer Net Payment`

## 7. สรุปสูตรหลัก

```text
Standard:
Customer Net Payment = Base Price + (Base Price * 0.07) - (Base Price * 0.03)

Reverse:
Selling Price = Target Income / 0.97
Service Net Income = Selling Price - (Selling Price * 0.03)
Customer Net Payment = Selling Price + (Selling Price * 0.07) - (Selling Price * 0.03)
```
