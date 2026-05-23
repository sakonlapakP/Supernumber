import 'package:dio/dio.dart';

import '../models/billing_customer.model.dart';
import '../models/easy_document.model.dart';
import 'api_service.dart';

class EasyDocumentService {
  Future<List<BillingCustomer>> listCustomers({String? search}) async {
    final response = await ApiService.dio.get(
      '/admin/billing-customers',
      queryParameters: {if (search != null && search.isNotEmpty) 'q': search},
    );

    final List<dynamic> raw = response.data['customers'] as List<dynamic>;
    return raw
        .map((entry) => BillingCustomer.fromJson(entry as Map<String, dynamic>))
        .toList();
  }

  Future<BillingCustomer> createCustomer({
    String? companyName,
    String? contactName,
    String? taxId,
    String? address,
    String? email,
    String? phone,
  }) async {
    final response = await ApiService.dio.post(
      '/admin/billing-customers',
      data: {
        'company_name': ?companyName,
        'contact_name': ?contactName,
        'tax_id': ?taxId,
        'address': ?address,
        'email': ?email,
        'phone': ?phone,
      },
    );

    return BillingCustomer.fromJson(response.data['customer'] as Map<String, dynamic>);
  }

  Future<List<QuotationSearchResult>> searchQuotations({String? search}) async {
    final response = await ApiService.dio.get(
      '/admin/quotations/search',
      queryParameters: {if (search != null && search.isNotEmpty) 'q': search},
    );

    final List<dynamic> raw = response.data['quotations'] as List<dynamic>;
    return raw
        .map((entry) => QuotationSearchResult.fromJson(entry as Map<String, dynamic>))
        .toList();
  }

  Future<CreatedEasyDocument> create({
    required int customerId,
    required EasyDocumentType documentType,
    required List<EasyDocumentItem> items,
    required TaxMethod taxMethod,
    required PaymentMethod paymentMethod,
    required PaymentCondition paymentCondition,
    String? paymentDetail,
    String? contactName,
    String? contactPhone,
    String? referenceNumber,
  }) async {
    final bool reverse = taxMethod == TaxMethod.wePay;

    try {
      final response = await ApiService.dio.post(
        '/admin/easy-documents',
        data: {
          'customerId': customerId,
          'documentType': documentType == EasyDocumentType.invoice ? 'invoice' : 'quotation',
          'items': items.map((item) => item.toApiPayload(reverse: reverse)).toList(),
          'taxMethod': taxMethod.apiValue,
          'paymentMethod': paymentMethod.apiValue,
          'paymentCondition': paymentCondition.apiValue,
          if (paymentDetail != null && paymentDetail.isNotEmpty) 'paymentDetail': paymentDetail,
          if (contactName != null && contactName.isNotEmpty) 'contactName': contactName,
          if (contactPhone != null && contactPhone.isNotEmpty) 'contactPhone': contactPhone,
          if (referenceNumber != null && referenceNumber.isNotEmpty) 'referenceNumber': referenceNumber,
        },
      );

      return CreatedEasyDocument.fromJson(response.data['document'] as Map<String, dynamic>);
    } on DioException catch (e) {
      final responseData = e.response?.data;
      if (responseData is Map<String, dynamic>) {
        final message = responseData['message']?.toString();
        if (message != null && message.isNotEmpty) {
          throw EasyDocumentException(message);
        }
      }
      rethrow;
    }
  }
}

class EasyDocumentException implements Exception {
  final String message;
  EasyDocumentException(this.message);

  @override
  String toString() => message;
}
