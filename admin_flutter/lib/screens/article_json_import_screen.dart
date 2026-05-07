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
                      const SizedBox(height: 40),
                      const _ContentPlanTable(),
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

class _ContentPlanTable extends StatelessWidget {
  const _ContentPlanTable();

  static const _planData = [
    {
      'month': 'พฤษภาคม 69',
      'items': [
        {'d': 11, 't': '09:00', 'type': 'วันสำคัญ', 'topic': 'วันพืชมงคล: เลขมงคลการเงินและความมั่งคั่ง', 'date': '2026-05-11'},
        {'d': 16, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย (สถิติ/วิเคราะห์)', 'date': '2026-05-16', 'is_lottery': true},
        {'d': 22, 't': '09:09', 'type': 'Evergreen', 'topic': '(Pillar Content เติมช่องว่างปลายเดือน)', 'date': '2026-05-22'},
        {'d': 27, 't': '09:09', 'type': 'Evergreen', 'topic': '(Pillar Content เลี้ยงกระแสก่อยวันพระใหญ่)', 'date': '2026-05-27'},
        {'d': 31, 't': '09:00', 'type': 'วันสำคัญ', 'topic': 'วันวิสาขบูชา: เลขสติปัญญาและการเริ่มต้นใหม่', 'date': '2026-05-31'},
      ]
    },
    {
      'month': 'มิถุนายน 69',
      'items': [
        {'d': 1, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2026-06-01', 'is_lottery': true},
        {'d': 8, 't': '09:09', 'type': 'Evergreen', 'topic': '(คั่นกลางต้นเดือน)', 'date': '2026-06-08'},
        {'d': 16, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2026-06-16', 'is_lottery': true},
        {'d': 21, 't': '09:09', 'type': 'Evergreen', 'topic': '(คั่นกลางปลายเดือน)', 'date': '2026-06-21'},
        {'d': 26, 't': '09:00', 'type': 'วันมู', 'topic': 'วันสุนทรภู่: เลขมงคลสายวาทศิลป์และการเจรจา', 'date': '2026-06-26'},
      ]
    },
    {
      'month': 'กรกฎาคม 69',
      'items': [
        {'d': 1, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2026-07-01', 'is_lottery': true},
        {'d': 8, 't': '09:09', 'type': 'Evergreen', 'topic': '(คั่นกลางต้นเดือน)', 'date': '2026-07-08'},
        {'d': 16, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2026-07-16', 'is_lottery': true},
        {'d': 23, 't': '09:09', 'type': 'Evergreen', 'topic': '(คั่นกลางปลายเดือน)', 'date': '2026-07-23'},
        {'d': 28, 't': '09:00', 'type': 'วันสำคัญ', 'topic': 'คอนเทนต์มงคลรวมใจ (ร.10)', 'date': '2026-07-28'},
        {'d': 29, 't': '09:00', 'type': 'วันสำคัญ', 'topic': 'วันอาสาฬหบูชา: ปรับพลังงานตัวเลข', 'date': '2026-07-29'},
      ]
    },
    {
      'month': 'สิงหาคม 69',
      'items': [
        {'d': 1, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2026-08-01', 'is_lottery': true},
        {'d': 8, 't': '09:09', 'type': 'Evergreen', 'topic': '(คั่นกลางต้นเดือน)', 'date': '2026-08-08'},
        {'d': 12, 't': '09:00', 'type': 'วันสำคัญ', 'topic': 'วันแม่: เลขมงคลสุขภาพ', 'date': '2026-08-12'},
        {'d': 15, 't': '09:00', 'type': 'วันมู', 'topic': 'วันคเณศจตุรถี: เลขมงคลประทานพร', 'date': '2026-08-15'},
        {'d': 16, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2026-08-16', 'is_lottery': true},
        {'d': 25, 't': '09:09', 'type': 'Evergreen', 'topic': '(คั่นกลางปลายเดือน)', 'date': '2026-08-25'},
      ]
    },
    {
      'month': 'กันยายน 69',
      'items': [
        {'d': 1, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2026-09-01', 'is_lottery': true},
        {'d': 8, 't': '09:09', 'type': 'Evergreen', 'topic': '(คั่นกลางต้นเดือน)', 'date': '2026-09-08'},
        {'d': 16, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2026-09-16', 'is_lottery': true},
        {'d': 21, 't': '09:09', 'type': 'Evergreen', 'topic': '(คั่นกลางปลายเดือน)', 'date': '2026-09-21'},
        {'d': 25, 't': '09:00', 'type': 'วันมู', 'topic': 'วันไหว้พระจันทร์: เลขเมตตามหานิยม', 'date': '2026-09-25'},
        {'d': 30, 't': '09:09', 'type': 'Evergreen', 'topic': '(ปิดท้ายเดือน)', 'date': '2026-09-30'},
      ]
    },
    {
      'month': 'ตุลาคม 69',
      'items': [
        {'d': 1, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2026-10-01', 'is_lottery': true},
        {'d': 8, 't': '09:09', 'type': 'Evergreen', 'topic': '(คั่นกลางต้นเดือน)', 'date': '2026-10-08'},
        {'d': 16, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2026-10-16', 'is_lottery': true},
        {'d': 20, 't': '09:00', 'type': 'วันมู', 'topic': 'เทศกาลกินเจ: เลขสายขาว', 'date': '2026-10-20'},
        {'d': 23, 't': '09:00', 'type': 'วันสำคัญ', 'topic': 'วันปิยมหาราช: เลขมงคลการงาน', 'date': '2026-10-23'},
      ]
    },
    {
      'month': 'พฤศจิกายน 69',
      'items': [
        {'d': 1, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2026-11-01', 'is_lottery': true},
        {'d': 8, 't': '09:09', 'type': 'Evergreen', 'topic': '(คั่นกลางต้นเดือน)', 'date': '2026-11-08'},
        {'d': 16, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2026-11-16', 'is_lottery': true},
        {'d': 24, 't': '09:00', 'type': 'วันสำคัญ', 'topic': 'วันลอยกระทง: เลขขอพรโชคลาภ', 'date': '2026-11-24'},
        {'d': 29, 't': '09:09', 'type': 'Evergreen', 'topic': '(คั่นกลางปลายเดือน)', 'date': '2026-11-29'},
      ]
    },
    {
      'month': 'ธันวาคม 69',
      'items': [
        {'d': 1, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2026-12-01', 'is_lottery': true},
        {'d': 5, 't': '09:00', 'type': 'วันสำคัญ', 'topic': 'วันพ่อ: เลขมงคลความมั่นคง', 'date': '2026-12-05'},
        {'d': 10, 't': '09:00', 'type': 'วันสำคัญ', 'topic': 'วันรัฐธรรมนูญ: เลขมงคลระเบียบวินัย', 'date': '2026-12-10'},
        {'d': 16, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2026-12-16', 'is_lottery': true},
        {'d': 24, 't': '09:09', 'type': 'Evergreen', 'topic': '(คั่นกลางก่อนปีใหม่)', 'date': '2026-12-24'},
        {'d': 31, 't': '09:00', 'type': 'วันสำคัญ', 'topic': 'วันสิ้นปี: สรุปเลขปี 69', 'date': '2026-12-31'},
      ]
    },
    {
      'month': 'มกราคม 70',
      'items': [
        {'d': 1, 't': '09:00', 'type': 'หวย/สำคัญ', 'topic': 'วันขึ้นปีใหม่: เปิดดวงตัวเลขปี 70 + หวย', 'date': '2027-01-01', 'is_lottery': true},
        {'d': 9, 't': '09:00', 'type': 'วันสำคัญ', 'topic': 'วันเด็ก: เลขมงคลเสริม IQ', 'date': '2027-01-09'},
        {'d': 16, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2027-01-16', 'is_lottery': true},
        {'d': 23, 't': '09:09', 'type': 'Evergreen', 'topic': '(อุดช่องว่างปลายเดือน)', 'date': '2027-01-23'},
      ]
    },
    {
      'month': 'กุมภาพันธ์ 70',
      'items': [
        {'d': 1, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2027-02-01', 'is_lottery': true},
        {'d': 6, 't': '09:00', 'type': 'วันมู', 'topic': 'วันตรุษจีน: เลขรับทรัพย์', 'date': '2027-02-06'},
        {'d': 14, 't': '09:00', 'type': 'วันสำคัญ', 'topic': 'วันวาเลนไทน์: คู่เลขความรัก', 'date': '2027-02-14'},
        {'d': 16, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2027-02-16', 'is_lottery': true},
        {'d': 21, 't': '09:00', 'type': 'วันสำคัญ', 'topic': 'วันมาฆบูชา: เลขสายบุญ', 'date': '2027-02-21'},
        {'d': 26, 't': '09:09', 'type': 'Evergreen', 'topic': '(อุดช่องว่างปลายเดือน)', 'date': '2027-02-26'},
      ]
    },
    {
      'month': 'มีนาคม 70',
      'items': [
        {'d': 1, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2027-03-01', 'is_lottery': true},
        {'d': 8, 't': '09:09', 'type': 'Evergreen', 'topic': '(คั่นกลางต้นเดือน)', 'date': '2027-03-08'},
        {'d': 16, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2027-03-16', 'is_lottery': true},
        {'d': 24, 't': '09:09', 'type': 'Evergreen', 'topic': '(คั่นกลางปลายเดือน)', 'date': '2027-03-24'},
        {'d': 30, 't': '09:09', 'type': 'Evergreen', 'topic': '(ปิดท้ายเดือน)', 'date': '2027-03-30'},
      ]
    },
    {
      'month': 'เมษายน 70',
      'items': [
        {'d': 1, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2027-04-01', 'is_lottery': true},
        {'d': 6, 't': '09:00', 'type': 'วันสำคัญ', 'topic': 'วันจักรี: เลขเสริมอำนาจ', 'date': '2027-04-06'},
        {'d': 13, 't': '09:00', 'type': 'วันสำคัญ', 'topic': 'วันสงกรานต์: เลขปลอดภัยในการเดินทาง', 'date': '2027-04-13'},
        {'d': 16, 't': '09:00', 'type': 'หวย', 'topic': 'คอนเทนต์หวย', 'date': '2027-04-16', 'is_lottery': true},
        {'d': 24, 't': '09:09', 'type': 'Evergreen', 'topic': '(อุดช่องว่างปลายเดือน)', 'date': '2027-04-24'},
      ]
    },
  ];

  @override
  Widget build(BuildContext context) {
    final articles = context.watch<ArticleProvider>().articles;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const Icon(Icons.calendar_month_rounded, color: Color(0xFF7C3AED)),
            const SizedBox(width: 8),
            Text(
              'ตารางแผนการเผยแพร่บทความ',
              style: GoogleFonts.kanit(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: const Color(0xFF1E2D45),
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: const Color(0xFFE5EAF2)),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFF1E2D45).withValues(alpha: 0.05),
                blurRadius: 10,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Column(
            children: _planData.map((month) {
              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                    decoration: const BoxDecoration(
                      color: Color(0xFFF8FAFC),
                      border: Border(bottom: BorderSide(color: Color(0xFFE5EAF2))),
                    ),
                    child: Text(
                      month['month'] as String,
                      style: GoogleFonts.kanit(
                        fontWeight: FontWeight.bold,
                        color: const Color(0xFF475569),
                      ),
                    ),
                  ),
                  ...(month['items'] as List<Map<String, dynamic>>).map((item) {
                    final dateStr = item['date'] as String;
                    final isLottery = item['is_lottery'] == true;
                    
                    final isDone = articles.any((a) {
                      // Check date
                      final aDate = a.publishedAt ?? a.createdAt;
                      if (aDate != null) {
                        final formatted = DateFormat('yyyy-MM-dd').format(aDate);
                        if (formatted == dateStr) return true;
                      }
                      
                      // Check lottery slug
                      if (isLottery) {
                        final date = DateTime.parse(dateStr);
                        final year = date.year;
                        final monthStr = date.month.toString().padLeft(2, '0');
                        final isRound1 = date.day <= 15;
                        final pattern = 'thai-government-lottery-$year$monthStr${isRound1 ? "first" : "second"}';
                        if (a.slug == pattern) return true;
                      }
                      
                      return false;
                    });

                    return Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                      decoration: BoxDecoration(
                        color: isDone ? const Color(0xFFF0FDF4) : null,
                        border: const Border(bottom: BorderSide(color: Color(0xFFF1F5F9))),
                      ),
                      child: Row(
                        children: [
                          SizedBox(
                            width: 100,
                            child: Row(
                              children: [
                                if (isDone)
                                  const Padding(
                                    padding: EdgeInsets.only(right: 4.0),
                                    child: Text('✅', style: TextStyle(fontSize: 14)),
                                  )
                                else if (item['type'] == 'หวย')
                                  const Padding(
                                    padding: EdgeInsets.only(right: 8.0),
                                    child: Text('!', 
                                      style: TextStyle(
                                        fontSize: 16, 
                                        fontWeight: FontWeight.black, 
                                        color: Color(0xFFEAB308)
                                      )
                                    ),
                                  )
                                else
                                  const SizedBox(width: 22),
                                Text(
                                  '${item['d']} | ${item['t']}',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 13,
                                    color: Color(0xFF475569),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          _TypePill(type: item['type'] as String),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              item['topic'] as String,
                              style: TextStyle(
                                fontSize: 14,
                                color: isDone ? const Color(0xFF166534) : const Color(0xFF1E2D45),
                                fontWeight: isDone ? FontWeight.bold : null,
                              ),
                            ),
                          ),
                        ],
                      ),
                    );
                  }),
                ],
              );
            }).toList(),
          ),
        ),
      ],
    );
  }
}

class _TypePill extends StatelessWidget {
  final String type;
  const _TypePill({required this.type});

  @override
  Widget build(BuildContext context) {
    Color bg = const Color(0xFFF1F5F9);
    Color text = const Color(0xFF475569);

    if (type.contains('หวย')) {
      bg = const Color(0xFFFEF2F2);
      text = const Color(0xFFEF4444);
    } else if (type == 'วันสำคัญ') {
      bg = const Color(0xFFEFF6FF);
      text = const Color(0xFF3B82F6);
    } else if (type == 'วันมู') {
      bg = const Color(0xFFFAF5FF);
      text = const Color(0xFF9333EA);
    } else if (type.toLowerCase() == 'evergreen') {
      bg = const Color(0xFFF0FDF4);
      text = const Color(0xFF16A34A);
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        type,
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.bold,
          color: text,
        ),
      ),
    );
  }
}

