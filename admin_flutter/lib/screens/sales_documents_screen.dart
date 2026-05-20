import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../providers/auth_provider.dart';
import '../services/api_service.dart';

class SalesDocumentsScreen extends StatefulWidget {
  const SalesDocumentsScreen({super.key});

  @override
  State<SalesDocumentsScreen> createState() => _SalesDocumentsScreenState();
}

class _SalesDocumentsScreenState extends State<SalesDocumentsScreen> {
  String? _loadingAction;

  Future<void> _openAdminDocumentPage({
    required String action,
    required String target,
    String? documentType,
  }) async {
    setState(() => _loadingAction = action);

    try {
      final response = await ApiService.dio.post(
        '/mobile-admin/session-link',
        data: {
          'target': target,
          if (documentType != null) 'document_type': documentType,
        },
      );

      final url = response.data is Map<String, dynamic>
          ? response.data['url']?.toString()
          : null;

      if (url == null || url.isEmpty) {
        throw Exception('ไม่พบลิงก์สำหรับเปิดหน้าเอกสาร');
      }

      final launched = await launchUrl(
        Uri.parse(url),
        mode: LaunchMode.externalApplication,
      );

      if (!launched) {
        throw Exception('เปิดหน้าเอกสารไม่สำเร็จ');
      }
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(_extractErrorMessage(error)),
          backgroundColor: const Color(0xFFC54B3D),
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _loadingAction = null);
      }
    }
  }

  String _extractErrorMessage(Object error) {
    final message = error.toString().replaceFirst('Exception: ', '').trim();

    return message.isEmpty ? 'ไม่สามารถเปิดหน้าเอกสารได้' : message;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      drawer: Consumer<AuthProvider>(
        builder: (context, auth, _) => Drawer(
          child: Column(
            children: [
              DrawerHeader(
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    colors: [Color(0xFF1D1816), Color(0xFF46372B)],
                  ),
                ),
                child: Align(
                  alignment: Alignment.bottomLeft,
                  child: Text(
                    auth.user?['name']?.toString() ?? 'Admin',
                    style: GoogleFonts.kanit(
                      color: Colors.white,
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ),
              ListTile(
                leading: const Icon(Icons.receipt_long_outlined),
                title: Text('เอกสารขาย', style: GoogleFonts.kanit()),
                selected: true,
                onTap: () => Navigator.pop(context),
              ),
              const Spacer(),
              const Divider(),
              ListTile(
                leading: const Icon(
                  Icons.logout_rounded,
                  color: Color(0xFFC54B3D),
                ),
                title: Text(
                  'ออกจากระบบ',
                  style: GoogleFonts.kanit(
                    color: const Color(0xFFC54B3D),
                    fontWeight: FontWeight.bold,
                  ),
                ),
                onTap: () => auth.logout(),
              ),
              const SizedBox(height: 16),
            ],
          ),
        ),
      ),
      appBar: AppBar(
        title: Text(
          'เอกสารขาย',
          style: GoogleFonts.kanit(fontWeight: FontWeight.bold),
        ),
      ),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            _DocumentActionCard(
              title: 'สร้างใบเสนอราคา',
              subtitle: 'Quotation',
              icon: Icons.request_quote_outlined,
              color: const Color(0xFF223A63),
              loading: _loadingAction == 'quotation',
              onTap: () => _openAdminDocumentPage(
                action: 'quotation',
                target: 'sales-documents-quick',
                documentType: 'quotation',
              ),
            ),
            const SizedBox(height: 14),
            _DocumentActionCard(
              title: 'สร้างใบแจ้งหนี้',
              subtitle: 'Invoice',
              icon: Icons.receipt_long_outlined,
              color: const Color(0xFF1B8B6F),
              loading: _loadingAction == 'invoice',
              onTap: () => _openAdminDocumentPage(
                action: 'invoice',
                target: 'sales-documents-quick',
                documentType: 'invoice',
              ),
            ),
            const SizedBox(height: 14),
            _DocumentActionCard(
              title: 'เอกสารที่บันทึกแล้ว',
              subtitle: 'Saved documents',
              icon: Icons.folder_copy_outlined,
              color: const Color(0xFFD8A34A),
              loading: _loadingAction == 'saved',
              onTap: () => _openAdminDocumentPage(
                action: 'saved',
                target: 'saved-sales-documents',
              ),
            ),
            const SizedBox(height: 14),
            _DocumentActionCard(
              title: 'เปิด Studio เต็ม',
              subtitle: 'Quotation / Invoice Studio',
              icon: Icons.dashboard_customize_outlined,
              color: const Color(0xFF6D5D4E),
              loading: _loadingAction == 'studio',
              onTap: () => _openAdminDocumentPage(
                action: 'studio',
                target: 'sales-documents',
                documentType: 'quotation',
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DocumentActionCard extends StatelessWidget {
  const _DocumentActionCard({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.color,
    required this.loading,
    required this.onTap,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final Color color;
  final bool loading;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(24),
        onTap: loading ? null : onTap,
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Icon(icon, color: color),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: GoogleFonts.kanit(
                        fontSize: 17,
                        fontWeight: FontWeight.w700,
                        color: const Color(0xFF1E2D45),
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
                      style: GoogleFonts.kanit(
                        fontSize: 13,
                        color: const Color(0xFF64748B),
                      ),
                    ),
                  ],
                ),
              ),
              loading
                  ? const SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.open_in_new_rounded),
            ],
          ),
        ),
      ),
    );
  }
}
