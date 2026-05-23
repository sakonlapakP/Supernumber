import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';

import '../models/billing_customer.model.dart';
import '../models/easy_document.model.dart';
import '../services/easy_document_service.dart';
import '../utils/document_calculator.dart';

class EasyDocumentWizardScreen extends StatefulWidget {
  const EasyDocumentWizardScreen({
    super.key,
    required this.documentType,
  });

  final EasyDocumentType documentType;

  @override
  State<EasyDocumentWizardScreen> createState() => _EasyDocumentWizardScreenState();
}

class _EasyDocumentWizardScreenState extends State<EasyDocumentWizardScreen> {
  final EasyDocumentService _service = EasyDocumentService();
  final NumberFormat _money = NumberFormat.decimalPatternDigits(decimalDigits: 2);

  int _currentStep = 0;
  bool _loadingCustomers = false;
  bool _submitting = false;
  bool _loadingQuotations = false;

  List<BillingCustomer> _customers = [];
  BillingCustomer? _selectedCustomer;

  List<QuotationSearchResult> _quotations = [];
  QuotationSearchResult? _selectedQuotation;

  final List<EasyDocumentItem> _items = [EasyDocumentItem(quantity: 1)];
  TaxMethod _taxMethod = TaxMethod.customerPays;
  PaymentMethod _paymentMethod = PaymentMethod.bank;
  PaymentCondition _paymentCondition = PaymentCondition.full;
  final TextEditingController _paymentDetailController = TextEditingController();
  final TextEditingController _contactNameController = TextEditingController();
  final TextEditingController _contactPhoneController = TextEditingController();

  bool get _isInvoice => widget.documentType == EasyDocumentType.invoice;

  @override
  void initState() {
    super.initState();
    _loadCustomers();
    if (_isInvoice) {
      _loadQuotations();
    }
  }

  @override
  void dispose() {
    _paymentDetailController.dispose();
    _contactNameController.dispose();
    _contactPhoneController.dispose();
    super.dispose();
  }

  Future<void> _loadCustomers({String? search}) async {
    setState(() => _loadingCustomers = true);
    try {
      final customers = await _service.listCustomers(search: search);
      if (!mounted) return;
      setState(() => _customers = customers);
    } catch (e) {
      _showError('โหลดรายชื่อลูกค้าไม่สำเร็จ: $e');
    } finally {
      if (mounted) setState(() => _loadingCustomers = false);
    }
  }

  Future<void> _loadQuotations({String? search}) async {
    setState(() => _loadingQuotations = true);
    try {
      final quotations = await _service.searchQuotations(search: search);
      if (!mounted) return;
      setState(() => _quotations = quotations);
    } catch (e) {
      _showError('โหลดใบเสนอราคาไม่สำเร็จ: $e');
    } finally {
      if (mounted) setState(() => _loadingQuotations = false);
    }
  }

  void _showError(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: const Color(0xFFC54B3D)),
    );
  }

  void _showSuccess(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: const Color(0xFF1B8B6F)),
    );
  }

  bool _canAdvance() {
    if (_currentStep == 0) {
      return _selectedCustomer != null;
    }
    if (_currentStep == 1) {
      return _items.any((i) => i.name.trim().isNotEmpty && i.price > 0 && i.quantity > 0);
    }
    return true;
  }

  Future<void> _submit() async {
    final validItems = _items
        .where((i) => i.name.trim().isNotEmpty && i.price > 0 && i.quantity > 0)
        .toList();

    if (_selectedCustomer == null || validItems.isEmpty) {
      _showError('กรุณาตรวจสอบข้อมูลลูกค้าและรายการให้ครบ');
      return;
    }

    setState(() => _submitting = true);
    try {
      final document = await _service.create(
        customerId: _selectedCustomer!.id,
        documentType: widget.documentType,
        items: validItems,
        taxMethod: _taxMethod,
        paymentMethod: _paymentMethod,
        paymentCondition: _paymentCondition,
        paymentDetail: _paymentDetailController.text.trim(),
        contactName: _contactNameController.text.trim(),
        contactPhone: _contactPhoneController.text.trim(),
        referenceNumber: _selectedQuotation?.documentNumber,
      );

      _showSuccess('สร้าง ${document.documentNumber} เรียบร้อย');
      if (mounted) Navigator.of(context).pop(document);
    } on EasyDocumentException catch (e) {
      _showError(e.message);
    } catch (e) {
      _showError('สร้างเอกสารไม่สำเร็จ: $e');
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final totals = DocumentCalculator.compute(items: _items, taxMethod: _taxMethod);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          _isInvoice ? 'สร้างใบแจ้งหนี้' : 'สร้างใบเสนอราคา',
          style: GoogleFonts.kanit(fontWeight: FontWeight.bold),
        ),
      ),
      body: Stepper(
        type: StepperType.vertical,
        currentStep: _currentStep,
        onStepTapped: (step) => setState(() => _currentStep = step),
        onStepContinue: () {
          if (!_canAdvance()) {
            _showError(_currentStep == 0
                ? 'กรุณาเลือกลูกค้าก่อน'
                : 'กรุณาเพิ่มรายการสินค้าให้ถูกต้อง');
            return;
          }
          if (_currentStep < 2) {
            setState(() => _currentStep++);
          } else {
            _submit();
          }
        },
        onStepCancel: () {
          if (_currentStep > 0) setState(() => _currentStep--);
        },
        controlsBuilder: (context, details) {
          final isLast = _currentStep == 2;
          return Padding(
            padding: const EdgeInsets.only(top: 12),
            child: Row(
              children: [
                ElevatedButton(
                  onPressed: _submitting ? null : details.onStepContinue,
                  child: _submitting && isLast
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : Text(
                          isLast ? 'สร้างเอกสาร' : 'ถัดไป',
                          style: GoogleFonts.kanit(),
                        ),
                ),
                if (_currentStep > 0) ...[
                  const SizedBox(width: 8),
                  TextButton(
                    onPressed: _submitting ? null : details.onStepCancel,
                    child: Text('ย้อนกลับ', style: GoogleFonts.kanit()),
                  ),
                ],
              ],
            ),
          );
        },
        steps: [
          Step(
            title: Text('ลูกค้า / อ้างอิง', style: GoogleFonts.kanit()),
            isActive: _currentStep >= 0,
            state: _selectedCustomer != null ? StepState.complete : StepState.indexed,
            content: _buildCustomerStep(),
          ),
          Step(
            title: Text('รายการและภาษี', style: GoogleFonts.kanit()),
            isActive: _currentStep >= 1,
            state: _currentStep > 1 ? StepState.complete : StepState.indexed,
            content: _buildItemsStep(totals),
          ),
          Step(
            title: Text('พรีวิวและยืนยัน', style: GoogleFonts.kanit()),
            isActive: _currentStep >= 2,
            content: _buildPreviewStep(totals),
          ),
        ],
      ),
    );
  }

  Widget _buildCustomerStep() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _loadingCustomers
            ? const Padding(
                padding: EdgeInsets.symmetric(vertical: 12),
                child: LinearProgressIndicator(),
              )
            : DropdownButtonFormField<BillingCustomer>(
                initialValue: _selectedCustomer,
                decoration: InputDecoration(
                  labelText: 'เลือกลูกค้า',
                  labelStyle: GoogleFonts.kanit(),
                  border: const OutlineInputBorder(),
                ),
                isExpanded: true,
                items: _customers
                    .map((c) => DropdownMenuItem(
                          value: c,
                          child: Text(
                            c.displayName,
                            overflow: TextOverflow.ellipsis,
                            style: GoogleFonts.kanit(),
                          ),
                        ))
                    .toList(),
                onChanged: (value) => setState(() => _selectedCustomer = value),
              ),
        const SizedBox(height: 10),
        Align(
          alignment: Alignment.centerLeft,
          child: TextButton.icon(
            onPressed: _openCreateCustomerDialog,
            icon: const Icon(Icons.add),
            label: Text('เพิ่มลูกค้าใหม่', style: GoogleFonts.kanit()),
          ),
        ),
        if (_isInvoice) ...[
          const Divider(height: 24),
          Text(
            'อ้างอิงใบเสนอราคา (ถ้ามี)',
            style: GoogleFonts.kanit(fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 6),
          Text(
            'หาก quotation มี invoice อยู่แล้ว ระบบจะแจ้งและไม่สร้างซ้ำ',
            style: GoogleFonts.kanit(fontSize: 12, color: const Color(0xFF64748B)),
          ),
          const SizedBox(height: 10),
          _loadingQuotations
              ? const LinearProgressIndicator()
              : DropdownButtonFormField<QuotationSearchResult?>(
                  initialValue: _selectedQuotation,
                  decoration: InputDecoration(
                    labelText: 'เลือกใบเสนอราคา',
                    labelStyle: GoogleFonts.kanit(),
                    border: const OutlineInputBorder(),
                  ),
                  isExpanded: true,
                  items: [
                    DropdownMenuItem<QuotationSearchResult?>(
                      value: null,
                      child: Text('— ไม่อ้างอิง —', style: GoogleFonts.kanit()),
                    ),
                    ..._quotations.map((q) => DropdownMenuItem<QuotationSearchResult?>(
                          value: q,
                          child: Text(
                            '${q.documentNumber} • ${q.customerName ?? "-"}',
                            overflow: TextOverflow.ellipsis,
                            style: GoogleFonts.kanit(),
                          ),
                        )),
                  ],
                  onChanged: (value) => setState(() => _selectedQuotation = value),
                ),
        ],
        const SizedBox(height: 16),
        Text('ผู้ติดต่อ (override) — ไม่บังคับ',
            style: GoogleFonts.kanit(fontWeight: FontWeight.w600)),
        const SizedBox(height: 8),
        TextField(
          controller: _contactNameController,
          style: GoogleFonts.kanit(),
          decoration: InputDecoration(
            labelText: 'ชื่อผู้ติดต่อ',
            labelStyle: GoogleFonts.kanit(),
            border: const OutlineInputBorder(),
          ),
        ),
        const SizedBox(height: 8),
        TextField(
          controller: _contactPhoneController,
          style: GoogleFonts.kanit(),
          keyboardType: TextInputType.phone,
          decoration: InputDecoration(
            labelText: 'เบอร์โทรผู้ติดต่อ',
            labelStyle: GoogleFonts.kanit(),
            border: const OutlineInputBorder(),
          ),
        ),
      ],
    );
  }

  Widget _buildItemsStep(DocumentTotals totals) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        for (int i = 0; i < _items.length; i++) _buildItemRow(i),
        Align(
          alignment: Alignment.centerLeft,
          child: TextButton.icon(
            onPressed: () => setState(() => _items.add(EasyDocumentItem(quantity: 1))),
            icon: const Icon(Icons.add),
            label: Text('เพิ่มรายการ', style: GoogleFonts.kanit()),
          ),
        ),
        const Divider(),
        Text('วิธีคำนวณภาษี', style: GoogleFonts.kanit(fontWeight: FontWeight.w600)),
        const SizedBox(height: 6),
        RadioGroup<TaxMethod>(
          groupValue: _taxMethod,
          onChanged: (value) {
            if (value != null) setState(() => _taxMethod = value);
          },
          child: Column(
            children: TaxMethod.values
                .map((method) => RadioListTile<TaxMethod>(
                      value: method,
                      title: Text(method.label, style: GoogleFonts.kanit(fontSize: 14)),
                      contentPadding: EdgeInsets.zero,
                      dense: true,
                    ))
                .toList(),
          ),
        ),
        const SizedBox(height: 8),
        Text('วิธีการชำระเงิน', style: GoogleFonts.kanit(fontWeight: FontWeight.w600)),
        const SizedBox(height: 6),
        DropdownButtonFormField<PaymentMethod>(
          initialValue: _paymentMethod,
          decoration: const InputDecoration(border: OutlineInputBorder()),
          isExpanded: true,
          items: PaymentMethod.values
              .map((m) => DropdownMenuItem(value: m, child: Text(m.label, style: GoogleFonts.kanit())))
              .toList(),
          onChanged: (value) {
            if (value != null) setState(() => _paymentMethod = value);
          },
        ),
        const SizedBox(height: 8),
        Text('เงื่อนไขการชำระเงิน', style: GoogleFonts.kanit(fontWeight: FontWeight.w600)),
        const SizedBox(height: 6),
        DropdownButtonFormField<PaymentCondition>(
          initialValue: _paymentCondition,
          decoration: const InputDecoration(border: OutlineInputBorder()),
          isExpanded: true,
          items: PaymentCondition.values
              .map((c) => DropdownMenuItem(value: c, child: Text(c.label, style: GoogleFonts.kanit())))
              .toList(),
          onChanged: (value) {
            if (value != null) setState(() => _paymentCondition = value);
          },
        ),
        const SizedBox(height: 8),
        TextField(
          controller: _paymentDetailController,
          style: GoogleFonts.kanit(),
          maxLines: 2,
          decoration: InputDecoration(
            labelText: 'หมายเหตุการชำระเงิน (ถ้ามี)',
            labelStyle: GoogleFonts.kanit(),
            border: const OutlineInputBorder(),
          ),
        ),
        const SizedBox(height: 16),
        _buildTotalsCard(totals),
      ],
    );
  }

  Widget _buildItemRow(int index) {
    final item = _items[index];
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Card(
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: const BorderSide(color: Color(0xFFE2E8F0)),
        ),
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            children: [
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      initialValue: item.name,
                      style: GoogleFonts.kanit(),
                      decoration: InputDecoration(
                        labelText: 'รายการที่ ${index + 1}',
                        labelStyle: GoogleFonts.kanit(),
                        border: const OutlineInputBorder(),
                      ),
                      onChanged: (v) => item.name = v,
                    ),
                  ),
                  if (_items.length > 1)
                    IconButton(
                      icon: const Icon(Icons.delete_outline, color: Color(0xFFC54B3D)),
                      onPressed: () => setState(() => _items.removeAt(index)),
                    ),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    flex: 2,
                    child: TextFormField(
                      initialValue: item.price > 0 ? item.price.toString() : '',
                      style: GoogleFonts.kanit(),
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: InputDecoration(
                        labelText: _taxMethod == TaxMethod.wePay ? 'รายได้เป้า/หน่วย' : 'ราคา/หน่วย',
                        labelStyle: GoogleFonts.kanit(),
                        border: const OutlineInputBorder(),
                      ),
                      onChanged: (v) {
                        setState(() => item.price = double.tryParse(v) ?? 0);
                      },
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: TextFormField(
                      initialValue: item.quantity.toString(),
                      style: GoogleFonts.kanit(),
                      keyboardType: TextInputType.number,
                      decoration: InputDecoration(
                        labelText: 'จำนวน',
                        labelStyle: GoogleFonts.kanit(),
                        border: const OutlineInputBorder(),
                      ),
                      onChanged: (v) {
                        setState(() => item.quantity = int.tryParse(v) ?? 1);
                      },
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTotalsCard(DocumentTotals totals) {
    return Card(
      color: const Color(0xFFF8FAFC),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('สรุปยอด', style: GoogleFonts.kanit(fontWeight: FontWeight.w700)),
            const SizedBox(height: 8),
            if (_taxMethod == TaxMethod.wePay)
              _totalRow('รายได้เป้าหมาย', _money.format(totals.targetIncome)),
            _totalRow('ราคาขาย (Sub Total)', _money.format(totals.subtotal)),
            _totalRow('VAT 7%', _money.format(totals.vatAmount)),
            _totalRow('Grand Total', _money.format(totals.grandTotal), bold: true),
            _totalRow('หัก WHT 3%', '- ${_money.format(totals.withholdingAmount)}'),
            _totalRow('Net to Pay', _money.format(totals.netToPay),
                bold: true, color: const Color(0xFF1B8B6F)),
          ],
        ),
      ),
    );
  }

  Widget _totalRow(String label, String value, {bool bold = false, Color? color}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: GoogleFonts.kanit(color: color)),
          Text(
            value,
            style: GoogleFonts.kanit(
              fontWeight: bold ? FontWeight.w700 : FontWeight.w400,
              color: color,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPreviewStep(DocumentTotals totals) {
    final validItems = _items.where((i) => i.name.trim().isNotEmpty && i.price > 0).toList();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'ลูกค้า: ${_selectedCustomer?.displayName ?? "-"}',
          style: GoogleFonts.kanit(fontWeight: FontWeight.w600),
        ),
        if (_isInvoice && _selectedQuotation != null) ...[
          const SizedBox(height: 4),
          Text(
            'อ้างอิง: ${_selectedQuotation!.documentNumber}',
            style: GoogleFonts.kanit(fontSize: 13, color: const Color(0xFF64748B)),
          ),
        ],
        const Divider(height: 24),
        for (final item in validItems)
          Padding(
            padding: const EdgeInsets.only(bottom: 6),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    '${item.name} × ${item.quantity}',
                    style: GoogleFonts.kanit(),
                  ),
                ),
                Text(
                  _money.format(item.price * item.quantity),
                  style: GoogleFonts.kanit(),
                ),
              ],
            ),
          ),
        const Divider(height: 24),
        _buildTotalsCard(totals),
      ],
    );
  }

  Future<void> _openCreateCustomerDialog() async {
    final result = await showDialog<BillingCustomer>(
      context: context,
      builder: (_) => const _CreateCustomerDialog(),
    );

    if (result != null && mounted) {
      setState(() {
        _customers = [result, ..._customers];
        _selectedCustomer = result;
      });
    }
  }
}

class _CreateCustomerDialog extends StatefulWidget {
  const _CreateCustomerDialog();

  @override
  State<_CreateCustomerDialog> createState() => _CreateCustomerDialogState();
}

class _CreateCustomerDialogState extends State<_CreateCustomerDialog> {
  final _companyController = TextEditingController();
  final _contactController = TextEditingController();
  final _taxIdController = TextEditingController();
  final _addressController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  bool _saving = false;
  String? _error;

  @override
  void dispose() {
    _companyController.dispose();
    _contactController.dispose();
    _taxIdController.dispose();
    _addressController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      final customer = await EasyDocumentService().createCustomer(
        companyName: _companyController.text.trim(),
        contactName: _contactController.text.trim(),
        taxId: _taxIdController.text.trim(),
        address: _addressController.text.trim(),
        email: _emailController.text.trim(),
        phone: _phoneController.text.trim(),
      );

      if (!mounted) return;
      Navigator.of(context).pop(customer);
    } catch (e) {
      setState(() => _error = 'บันทึกไม่สำเร็จ: $e');
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Text('เพิ่มลูกค้า', style: GoogleFonts.kanit(fontWeight: FontWeight.bold)),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            _field('ชื่อบริษัท / ชื่อลูกค้า', _companyController),
            _field('ชื่อผู้ติดต่อ', _contactController),
            _field('เลขผู้เสียภาษี', _taxIdController),
            _field('ที่อยู่', _addressController, maxLines: 2),
            _field('อีเมล', _emailController, keyboardType: TextInputType.emailAddress),
            _field('เบอร์โทร', _phoneController, keyboardType: TextInputType.phone),
            if (_error != null)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Text(_error!, style: GoogleFonts.kanit(color: const Color(0xFFC54B3D))),
              ),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: _saving ? null : () => Navigator.of(context).pop(),
          child: Text('ยกเลิก', style: GoogleFonts.kanit()),
        ),
        ElevatedButton(
          onPressed: _saving ? null : _save,
          child: _saving
              ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
              : Text('บันทึก', style: GoogleFonts.kanit()),
        ),
      ],
    );
  }

  Widget _field(String label, TextEditingController controller,
      {int maxLines = 1, TextInputType? keyboardType}) {
    return Padding(
      padding: const EdgeInsets.only(top: 8),
      child: TextField(
        controller: controller,
        style: GoogleFonts.kanit(),
        maxLines: maxLines,
        keyboardType: keyboardType,
        decoration: InputDecoration(
          labelText: label,
          labelStyle: GoogleFonts.kanit(),
          border: const OutlineInputBorder(),
        ),
      ),
    );
  }
}
