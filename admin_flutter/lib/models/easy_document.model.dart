enum EasyDocumentType { quotation, invoice }

enum TaxMethod {
  customerPays,
  wePay;

  String get apiValue => switch (this) {
        TaxMethod.customerPays => 'customer-pays',
        TaxMethod.wePay => 'we-pay',
      };

  String get label => switch (this) {
        TaxMethod.customerPays => 'Standard — ลูกค้ารับภาระภาษี',
        TaxMethod.wePay => 'Reverse — เรารับภาระภาษี (กรอกรายได้เป้าหมาย)',
      };
}

enum PaymentMethod {
  bank,
  qr,
  cash;

  String get apiValue => name;

  String get label => switch (this) {
        PaymentMethod.bank => 'โอนเข้าบัญชีธนาคาร',
        PaymentMethod.qr => 'PromptPay / QR',
        PaymentMethod.cash => 'เงินสด',
      };
}

enum PaymentCondition {
  full,
  installment,
  specificDate;

  String get apiValue => switch (this) {
        PaymentCondition.full => 'full',
        PaymentCondition.installment => 'installment',
        PaymentCondition.specificDate => 'specific-date',
      };

  String get label => switch (this) {
        PaymentCondition.full => 'ชำระเต็มจำนวน',
        PaymentCondition.installment => 'แบ่งงวด',
        PaymentCondition.specificDate => 'กำหนดวันชำระเอง',
      };
}

class EasyDocumentItem {
  String name;
  // The user-entered base price. In Reverse mode this is the target income.
  double price;
  int quantity;

  EasyDocumentItem({
    this.name = '',
    this.price = 0,
    this.quantity = 1,
  });

  Map<String, dynamic> toApiPayload({required bool reverse}) {
    final double sellingPrice = reverse ? price / 0.97 : price;
    return {
      'name': name,
      'price': double.parse(sellingPrice.toStringAsFixed(2)),
      'originalPrice': price,
      'qty': quantity,
    };
  }
}

class QuotationSearchResult {
  final int id;
  final String documentNumber;
  final String? customerName;
  final String? documentDate;
  final String? status;
  final String? statusLabel;
  final double? grandTotal;
  final String? grandTotalDisplay;

  QuotationSearchResult({
    required this.id,
    required this.documentNumber,
    this.customerName,
    this.documentDate,
    this.status,
    this.statusLabel,
    this.grandTotal,
    this.grandTotalDisplay,
  });

  factory QuotationSearchResult.fromJson(Map<String, dynamic> json) {
    return QuotationSearchResult(
      id: (json['id'] as num).toInt(),
      documentNumber: (json['document_number'] ?? '').toString(),
      customerName: json['customer_name']?.toString(),
      documentDate: json['document_date']?.toString(),
      status: json['status']?.toString(),
      statusLabel: json['status_label']?.toString(),
      grandTotal: (json['grand_total'] as num?)?.toDouble(),
      grandTotalDisplay: json['grand_total_display']?.toString(),
    );
  }
}

class CreatedEasyDocument {
  final int id;
  final String documentType;
  final String documentNumber;
  final String? status;
  final String? statusLabel;
  final bool isDraft;

  CreatedEasyDocument({
    required this.id,
    required this.documentType,
    required this.documentNumber,
    this.status,
    this.statusLabel,
    required this.isDraft,
  });

  factory CreatedEasyDocument.fromJson(Map<String, dynamic> json) {
    return CreatedEasyDocument(
      id: (json['id'] as num).toInt(),
      documentType: (json['document_type'] ?? '').toString(),
      documentNumber: (json['document_number'] ?? '').toString(),
      status: json['status']?.toString(),
      statusLabel: json['status_label']?.toString(),
      isDraft: json['is_draft'] == true,
    );
  }
}
