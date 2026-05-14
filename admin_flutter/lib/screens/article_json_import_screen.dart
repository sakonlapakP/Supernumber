import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../providers/article_provider.dart';

class ArticleJsonImportScreen extends StatefulWidget {
  const ArticleJsonImportScreen({super.key});

  @override
  State<ArticleJsonImportScreen> createState() => _ArticleJsonImportScreenState();
}

class _ArticleJsonImportScreenState extends State<ArticleJsonImportScreen> {
  static const _sampleJson = '''[
  {
    "title": "หัวข้อบทความ",
    "content": "<p>เนื้อหาบทความ</p>",
    "excerpt": "คำโปรยบทความ",
    "meta_description": "คำอธิบายสำหรับ Google",
    "keywords": ["เบอร์มงคล", "ตัวเลข"],
    "lsi_keywords": "เลขศาสตร์, ความหมายตัวเลข",
    "image_guidelines": {
      "landscape_prompt": "Prompt สำหรับรูป 16:9",
      "square_prompt": "Prompt สำหรับรูป 1:1"
    },
    "is_published": true,
    "is_auto_post": false
  }
]''';

  final _jsonController = TextEditingController();

  @override
  void dispose() {
    _jsonController.dispose();
    super.dispose();
  }

  Future<void> _pasteFromClipboard() async {
    final data = await Clipboard.getData(Clipboard.kTextPlain);
    final text = data?.text?.trim();

    if (text == null || text.isEmpty) {
      if (!mounted) return;
      _showSnack('ไม่มี JSON ใน clipboard', isError: true);
      return;
    }

    setState(() => _jsonController.text = text);
  }

  void _insertSample() {
    setState(() => _jsonController.text = _sampleJson);
  }

  void _formatJson() {
    try {
      const encoder = JsonEncoder.withIndent('  ');
      final decoded = jsonDecode(_jsonController.text);
      setState(() => _jsonController.text = encoder.convert(decoded));
    } catch (e) {
      _showSnack('รูปแบบ JSON ไม่ถูกต้อง', isError: true);
    }
  }

  Future<void> _importJson() async {
    final rawJson = _jsonController.text.trim();

    if (rawJson.isEmpty) {
      _showSnack('กรุณาวาง JSON ก่อนนำเข้า', isError: true);
      return;
    }

    try {
      final decoded = jsonDecode(rawJson);
      if (decoded is! Map<String, dynamic> && decoded is! List<dynamic>) {
        _showSnack('JSON ต้องเป็น Object หรือ Array ของบทความ', isError: true);
        return;
      }
    } catch (e) {
      _showSnack('รูปแบบ JSON ไม่ถูกต้อง', isError: true);
      return;
    }

    final provider = context.read<ArticleProvider>();
    final imported = await provider.importArticlesFromJson(rawJson);

    if (!mounted) return;

    if (imported != null) {
      _showSnack('นำเข้าบทความสำเร็จ $imported รายการ');
      Navigator.pop(context);
      return;
    }

    _showSnack(provider.lastErrorMessage ?? 'นำเข้า JSON ไม่สำเร็จ', isError: true);
  }

  void _showSnack(String message, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        backgroundColor: isError ? const Color(0xFFC54B3D) : const Color(0xFF1B8B6F),
        content: Text(message),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isTablet = MediaQuery.of(context).size.width > 600;

    return Scaffold(
      appBar: AppBar(
        centerTitle: true,
        title: Text(
          'เพิ่มบทความด้วย JSON',
          style: GoogleFonts.kanit(fontWeight: FontWeight.bold, fontSize: 18),
        ),
        actions: [
          TextButton(
            onPressed: _importJson,
            child: const Text('นำเข้า', style: TextStyle(fontWeight: FontWeight.bold)),
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: Consumer<ArticleProvider>(
        builder: (context, provider, _) {
          return Stack(
            children: [
              Center(
                child: Container(
                  constraints: BoxConstraints(maxWidth: isTablet ? 820 : double.infinity),
                  child: ListView(
                    padding: const EdgeInsets.all(20),
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              'วาง JSON เป็นบทความเดียวหรือ Array หลายบทความ',
                              style: GoogleFonts.kanit(
                                fontWeight: FontWeight.bold,
                                color: const Color(0xFF223A63),
                                fontSize: 16,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          OutlinedButton.icon(
                            onPressed: _pasteFromClipboard,
                            icon: const Icon(Icons.content_paste_rounded),
                            label: const Text('วางจาก Clipboard'),
                          ),
                          OutlinedButton.icon(
                            onPressed: _formatJson,
                            icon: const Icon(Icons.auto_fix_high_rounded),
                            label: const Text('จัดรูปแบบ'),
                          ),
                          OutlinedButton.icon(
                            onPressed: _insertSample,
                            icon: const Icon(Icons.description_outlined),
                            label: const Text('ตัวอย่าง'),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      TextFormField(
                        controller: _jsonController,
                        minLines: isTablet ? 18 : 14,
                        maxLines: null,
                        keyboardType: TextInputType.multiline,
                        style: const TextStyle(
                          fontFamily: 'Menlo',
                          fontSize: 13,
                          height: 1.45,
                          color: Color(0xFF1E2D45),
                        ),
                        decoration: InputDecoration(
                          hintText: _sampleJson,
                          alignLabelWithHint: true,
                          labelText: 'JSON บทความ',
                          filled: true,
                          fillColor: Colors.white,
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(16),
                            borderSide: const BorderSide(color: Color(0xFFD7E1F0)),
                          ),
                        ),
                      ),
                      const SizedBox(height: 20),
                      ElevatedButton.icon(
                        onPressed: provider.isLoading ? null : _importJson,
                        icon: const Icon(Icons.upload_file_rounded),
                        label: const Text('นำเข้าบทความ'),
                        style: ElevatedButton.styleFrom(
                          minimumSize: const Size.fromHeight(56),
                        ),
                      ),
                      const SizedBox(height: 60),
                    ],
                  ),
                ),
              ),
              if (provider.isLoading)
                Container(
                  color: Colors.white.withValues(alpha: 0.58),
                  child: const Center(child: CircularProgressIndicator()),
                ),
            ],
          );
        },
      ),
    );
  }
}

