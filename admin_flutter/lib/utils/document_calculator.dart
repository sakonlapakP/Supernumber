import '../models/easy_document.model.dart';

class DocumentTotals {
  final double subtotal;
  final double targetIncome;
  final double vatAmount;
  final double withholdingAmount;
  final double grandTotal;
  final double netToPay;

  DocumentTotals({
    required this.subtotal,
    required this.targetIncome,
    required this.vatAmount,
    required this.withholdingAmount,
    required this.grandTotal,
    required this.netToPay,
  });
}

class DocumentCalculator {
  static const double vatRate = 0.07;
  static const double withholdingRate = 0.03;

  static DocumentTotals compute({
    required List<EasyDocumentItem> items,
    required TaxMethod taxMethod,
  }) {
    final bool reverse = taxMethod == TaxMethod.wePay;

    double subtotal = 0;
    double targetIncome = 0;

    for (final item in items) {
      final double sellingUnit = reverse ? item.price / 0.97 : item.price;
      subtotal += _round(sellingUnit * item.quantity);
      targetIncome += _round(item.price * item.quantity);
    }

    final double baseAmount = subtotal;
    final double vatAmount = _round(baseAmount * vatRate);
    final double withholdingAmount = _round(baseAmount * withholdingRate);
    final double grandTotal = _round(baseAmount + vatAmount);
    final double netToPay = _round(grandTotal - withholdingAmount);

    return DocumentTotals(
      subtotal: _round(subtotal),
      targetIncome: _round(targetIncome),
      vatAmount: vatAmount,
      withholdingAmount: withholdingAmount,
      grandTotal: grandTotal,
      netToPay: netToPay,
    );
  }

  static double _round(double value) => double.parse(value.toStringAsFixed(2));
}
