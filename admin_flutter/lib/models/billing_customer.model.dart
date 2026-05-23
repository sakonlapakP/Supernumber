class BillingCustomer {
  final int id;
  final String displayName;
  final String? companyName;
  final String? contactName;
  final String? taxId;
  final String? address;
  final String? email;
  final String? phone;
  final String? paymentTerm;

  BillingCustomer({
    required this.id,
    required this.displayName,
    this.companyName,
    this.contactName,
    this.taxId,
    this.address,
    this.email,
    this.phone,
    this.paymentTerm,
  });

  factory BillingCustomer.fromJson(Map<String, dynamic> json) {
    return BillingCustomer(
      id: (json['id'] as num).toInt(),
      displayName: (json['display_name'] ?? '').toString(),
      companyName: json['company_name']?.toString(),
      contactName: json['contact_name']?.toString(),
      taxId: json['tax_id']?.toString(),
      address: json['address']?.toString(),
      email: json['email']?.toString(),
      phone: json['phone']?.toString(),
      paymentTerm: json['payment_term']?.toString(),
    );
  }
}
