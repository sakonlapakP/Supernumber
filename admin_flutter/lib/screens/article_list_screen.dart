import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import '../providers/article_provider.dart';
import '../providers/auth_provider.dart';
import '../models/article.model.dart';
import '../utils/date_formatter.dart';
import 'article_edit_screen.dart';
import 'article_json_import_screen.dart';
import 'admin_register_screen.dart';

class ArticleListScreen extends StatefulWidget {
  const ArticleListScreen({super.key});

  @override
  State<ArticleListScreen> createState() => _ArticleListScreenState();
}

class _ArticleListScreenState extends State<ArticleListScreen> {
  String? _selectedMonthPlan;
  final List<String> _monthPlans = [
    'พฤษภาคม 69', 'มิถุนายน 69', 'กรกฎาคม 69', 'สิงหาคม 69',
    'กันยายน 69', 'ตุลาคม 69', 'พฤศจิกายน 69', 'ธันวาคม 69',
    'มกราคม 70', 'กุมภาพันธ์ 70', 'มีนาคม 70', 'เมษายน 70'
  ];


  @override
  void initState() {
    super.initState();
    // ดึงข้อมูลอัตโนมัติเมื่อเข้าหน้าจอนี้
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<ArticleProvider>().fetchArticles(monthPlan: _selectedMonthPlan);
    });
  }

  Future<void> _openJsonImport() async {
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => const ArticleJsonImportScreen(),
        fullscreenDialog: true,
      ),
    );

    if (!mounted) return;
    await context.read<ArticleProvider>().fetchArticles(monthPlan: _selectedMonthPlan);
  }

  Future<void> _openArticleEditor([Article? article]) async {
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => ArticleEditScreen(article: article),
      ),
    );

    if (!mounted) return;
    await context.read<ArticleProvider>().fetchArticles(monthPlan: _selectedMonthPlan);
  }

  Future<void> _openArticlePreview(Article article) async {
    final provider = context.read<ArticleProvider>();
    
    // Show loading indicator
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('กำลังเตรียมลิงก์สำหรับดูบทความ...'),
        duration: Duration(seconds: 1),
      ),
    );

    final url = await provider.fetchPreviewUrl(article);
    
    if (url == null) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(provider.lastErrorMessage ?? 'บทความนี้ยังไม่มี slug สำหรับเปิดดู')),
      );
      return;
    }

    final launched = await launchUrl(
      Uri.parse(url),
      mode: LaunchMode.externalApplication,
    );

    if (!mounted || launched) return;

    ScaffoldMessenger.of(
      context,
    ).showSnackBar(const SnackBar(content: Text('เปิดเบราว์เซอร์ไม่สำเร็จ')));
  }

  Future<void> _shareArticle(Article article) async {
    final platform = await showModalBottomSheet<String>(
      context: context,
      showDragHandle: true,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.facebook_rounded),
              title: const Text('Facebook Page'),
              onTap: () => Navigator.pop(context, 'facebook'),
            ),
            ListTile(
              leading: const Icon(Icons.chat_bubble_outline_rounded),
              title: const Text('LINE'),
              onTap: () => Navigator.pop(context, 'line'),
            ),
          ],
        ),
      ),
    );

    if (platform == null || !mounted) return;

    final provider = context.read<ArticleProvider>();
    final shared = await provider.shareArticle(article, platform);
    if (!mounted) return;

    final label = platform == 'facebook' ? 'Facebook Page' : 'LINE';
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          shared
              ? 'แชร์ไปที่ $label สำเร็จ'
              : provider.lastErrorMessage ?? 'แชร์ไปที่ $label ไม่สำเร็จ',
        ),
      ),
    );
  }

  Future<void> _confirmDeleteArticle(Article article) async {
    final shouldDelete = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('ลบบทความ'),
        content: Text('ต้องการลบ "${article.title}" ใช่ไหม'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('ยกเลิก'),
          ),
          FilledButton(
            style: FilledButton.styleFrom(
              backgroundColor: const Color(0xFFC54B3D),
            ),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('ลบ'),
          ),
        ],
      ),
    );

    if (shouldDelete != true || !mounted) return;

    final provider = context.read<ArticleProvider>();
    final deleted = await provider.deleteArticle(article);
    if (!mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          deleted
              ? 'ลบบทความแล้ว'
              : provider.lastErrorMessage ?? 'ลบบทความไม่สำเร็จ',
        ),
      ),
    );
  }

  Future<void> _showAiPromptDialog() async {
    final TextEditingController subjectController = TextEditingController();

    await showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF6366F1), Color(0xFFA855F7)],
                ),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.auto_awesome_rounded, color: Colors.white),
            ),
            const SizedBox(width: 12),
            Text(
              'Generate prompt AI',
              style: GoogleFonts.kanit(fontWeight: FontWeight.bold),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'หัวข้อบทความที่ต้องการ',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: subjectController,
              decoration: InputDecoration(
                hintText: 'เช่น ใครสวยที่สุดในปฐพี...',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                filled: true,
                fillColor: Colors.grey[50],
              ),
              autofocus: true,
            ),
          ],
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(bottom: 8.0),
            child: Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                      side: const BorderSide(color: Color(0xFFE0E7FF), width: 2),
                    ),
                    onPressed: () => _copyPrompt(subjectController.text, 'short'),
                    child: Text(
                      'บทความสั้น',
                      style: GoogleFonts.kanit(
                        fontWeight: FontWeight.bold,
                        color: const Color(0xFF4F46E5),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: FilledButton(
                    style: FilledButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      backgroundColor: const Color(0xFF4F46E5),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                    onPressed: () => _copyPrompt(subjectController.text, 'long'),
                    child: Text(
                      'บทความยาว',
                      style: GoogleFonts.kanit(fontWeight: FontWeight.bold),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _copyPrompt(String subjectText, String type) {
    final subject = subjectText.isEmpty ? '.....................' : subjectText;
    final now = DateTime.now();
    final formattedDate =
        "${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')} 09:00:00";

    final constraints = type == 'long'
        ? 'CONTENT_MUST_BE_1000_WORDS_MINIMUM | NO_YEAR_IN_SLUG | HTML_FORMAT_ONLY'
        : 'NO_YEAR_IN_SLUG | HTML_FORMAT_ONLY';

    final promptTemplate = """ช่วยเขียนบทความเกี่ยวกับ $subject หาจากแหล่งข้อมูลที่น่าเชื่อถือเท่านั้น ใน Format [
  {
   "_constraints": "$constraints",
    "title": "พาดหัวที่ดึงดูดใจ (ใส่ปี พ.ศ. ได้)",
    "slug": "url-slug-no-year",
    "excerpt": "คำเกริ่นนำสั้นๆ",
    "content": "เนื้อหา HTML (ใช้ <h2>, <h3>, <p>, <ul>, <li>)",
    "meta_description": "สรุปเนื้อหาสำหรับ Google Search (120-155 characters)",
    "keywords": "Focus Keyword หลัก 5 คำ",
    "lsi_keywords": "ใส่ LSI Keywords คั่นด้วยจุลภาค 10 คำ" ,
    "is_published": false,
    "published_at": "$formattedDate",
    "is_auto_post": true,
    "image_guidelines": {
      "landscape_prompt": "Prompt สำหรับรูปที่ relate กับรูป 16:9",
      "square_prompt": "Prompt สำหรับรูปที่ relate กับรูป 16:9"
    }
  }
]""";

    Clipboard.setData(ClipboardData(text: promptTemplate)).then((_) {
      if (!mounted) return;
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('✓ คัดลอก Prompt เรียบร้อยแล้ว!'),
          behavior: SnackBarBehavior.floating,
          backgroundColor: Color(0xFF10B981),
        ),
      );
    });
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
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    const CircleAvatar(
                      backgroundColor: Color(0xFFD8A34A),
                      child: Icon(Icons.person, color: Colors.white),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            auth.user?['name'] ?? 'Admin',
                            style: GoogleFonts.kanit(
                              color: Colors.white,
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                    Text(
                      (auth.user?['role']?.toString().toUpperCase() ?? 'ADMIN'),
                      style: GoogleFonts.kanit(
                        color: const Color(0xFFD8A34A),
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        letterSpacing: 1,
                      ),
                    ),
                  ],
                ),
              ),
              ListTile(
                leading: const Icon(Icons.article_outlined),
                title: Text('จัดการบทความ', style: GoogleFonts.kanit()),
                onTap: () => Navigator.pop(context),
              ),
              if (auth.user?['role'] == 'manager')
                ListTile(
                  leading: const Icon(Icons.person_add_alt_1_outlined),
                  title: Text('เพิ่มผู้ดูแลระบบ', style: GoogleFonts.kanit()),
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.push(
                      context,
                      MaterialPageRoute(builder: (context) => const AdminRegisterScreen()),
                    );
                  },
                ),
              const Spacer(),
              const Divider(),
              ListTile(
                leading: const Icon(Icons.logout_rounded, color: Color(0xFFC54B3D)),
                title: Text(
                  'ออกจากระบบ',
                  style: GoogleFonts.kanit(color: const Color(0xFFC54B3D), fontWeight: FontWeight.bold),
                ),
                onTap: () => auth.logout(),
              ),
              const SizedBox(height: 16),
            ],
          ),
        ),
      ),
      appBar: AppBar(
        title: Row(
          children: [
            Container(
              width: 28,
              height: 28,
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF1D1816), Color(0xFF46372B)],
                ),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(
                  color: const Color(0xFFD8A34A).withValues(alpha: 0.5),
                ),
              ),
              alignment: Alignment.center,
              child: Text(
                'S',
                style: GoogleFonts.cinzel(
                  color: const Color(0xFFD8A34A),
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
            ),
            const SizedBox(width: 12),
            Text(
              'ARTICLES',
              style: GoogleFonts.kanit(
                fontWeight: FontWeight.bold,
                fontSize: 18,
                letterSpacing: 0.5,
                color: const Color(0xFF1D1816),
              ),
            ),
          ],
        ),
        actions: [
          Container(
            height: 36,
            padding: const EdgeInsets.symmetric(horizontal: 12),
            decoration: BoxDecoration(
              color: Colors.grey[100],
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.grey[300]!),
            ),
            child: DropdownButtonHideUnderline(
              child: DropdownButton<String>(
                value: _selectedMonthPlan,
                hint: Text(
                  'ทั้งหมด (แผนงาน)',
                  style: GoogleFonts.kanit(fontSize: 13, fontWeight: FontWeight.w600),
                ),
                icon: const Icon(Icons.calendar_month_rounded, size: 16),
                style: GoogleFonts.kanit(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: const Color(0xFF1E293B),
                ),
                onChanged: (String? newValue) {
                  setState(() {
                    _selectedMonthPlan = newValue;
                  });
                  context.read<ArticleProvider>().fetchArticles(monthPlan: newValue);
                },
                items: [
                  DropdownMenuItem<String>(
                    value: null,
                    child: Text('ทั้งหมด (แผนงาน)', style: GoogleFonts.kanit()),
                  ),
                  ..._monthPlans.map<DropdownMenuItem<String>>((String value) {
                    return DropdownMenuItem<String>(
                      value: value,
                      child: Text(value, style: GoogleFonts.kanit()),
                    );
                  }),
                ],
              ),
            ),
          ),
          const SizedBox(width: 8),

          LayoutBuilder(
            builder: (context, constraints) {
              final isWide = MediaQuery.of(context).size.width > 600;
              if (isWide) {
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: FilledButton.icon(
                    onPressed: _openJsonImport,
                    style: FilledButton.styleFrom(
                      backgroundColor: const Color(0xFF7C3AED).withValues(alpha: 0.1),
                      foregroundColor: const Color(0xFF7C3AED),
                      elevation: 0,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    icon: const Icon(Icons.data_object_rounded, size: 20),
                    label: Text(
                      'Import JSON',
                      style: GoogleFonts.kanit(fontWeight: FontWeight.w600),
                    ),
                  ),
                );
              }
              return IconButton(
                icon: const Icon(
                  Icons.data_object_rounded,
                  color: Color(0xFF7C3AED),
                ),
                tooltip: 'Import JSON',
                onPressed: _openJsonImport,
              );
            },
          ),
          LayoutBuilder(
            builder: (context, constraints) {
              final isWide = MediaQuery.of(context).size.width > 600;
              if (isWide) {
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: FilledButton.icon(
                    onPressed: _showAiPromptDialog,
                    style: FilledButton.styleFrom(
                      backgroundColor: const Color(0xFF6366F1).withValues(alpha: 0.1),
                      foregroundColor: const Color(0xFF6366F1),
                      elevation: 0,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    icon: const Icon(Icons.auto_awesome_rounded, size: 20),
                    label: Text(
                      'Generate prompt AI',
                      style: GoogleFonts.kanit(fontWeight: FontWeight.w600),
                    ),
                  ),
                );
              }
              return IconButton(
                icon: const Icon(
                  Icons.auto_awesome_rounded,
                  color: Color(0xFF6366F1),
                ),
                tooltip: 'Generate prompt AI',
                onPressed: _showAiPromptDialog,
              );
            },
          ),
          IconButton(
            icon: const Icon(Icons.logout_rounded, color: Color(0xFFC54B3D)),
            onPressed: () => context.read<AuthProvider>().logout(),
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: Consumer<ArticleProvider>(
        builder: (context, provider, _) {
          if (provider.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          if (provider.articles.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    Icons.article_outlined,
                    size: 64,
                    color: Colors.grey[400],
                  ),
                  const SizedBox(height: 12),
                  const Text(
                    'ยังไม่มีบทความในระบบสำหรับเดือนนี้',
                    style: TextStyle(
                      color: Color(0xFF64748B),
                      fontSize: 16,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'คุณสามารถเริ่มเขียนบทความใหม่หรือนำเข้าข้อมูลได้',
                    style: TextStyle(color: Colors.grey[500], fontSize: 13),
                  ),
                  const SizedBox(height: 24),
                  OutlinedButton.icon(
                    onPressed: () => provider.fetchArticles(monthPlan: _selectedMonthPlan),
                    icon: const Icon(Icons.refresh_rounded, size: 20),
                    label: const Text('ลองโหลดใหม่อีกครั้ง'),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                      side: const BorderSide(color: Color(0xFFE2E8F0)),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      foregroundColor: const Color(0xFF475569),
                    ),
                  ),
                ],
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () => provider.fetchArticles(monthPlan: _selectedMonthPlan),
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              physics: const AlwaysScrollableScrollPhysics(),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  LayoutBuilder(
                    builder: (context, constraints) {
                      final isWide = constraints.maxWidth > 900;
                      final isNarrowTablet = constraints.maxWidth > 600 && constraints.maxWidth <= 900;
                      
                      return GridView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: isWide ? 2 : (isNarrowTablet ? 2 : 1),
                          childAspectRatio: isWide ? 2.5 : (isNarrowTablet ? 2.0 : 2.8),
                          crossAxisSpacing: 12,
                          mainAxisSpacing: 12,
                        ),
                        itemCount: provider.articles.length,
                        itemBuilder: (context, index) => _ArticleItem(
                          article: provider.articles[index],
                          onTap: () => _openArticleEditor(provider.articles[index]),
                          onView: () => _openArticlePreview(provider.articles[index]),
                          onEdit: () => _openArticleEditor(provider.articles[index]),
                          onShare: () => _shareArticle(provider.articles[index]),
                          onDelete: () =>
                              _confirmDeleteArticle(provider.articles[index]),
                        ),
                      );
                    },
                  ),
                  const SizedBox(height: 32),
                  _ContentPlanSection(
                    selectedMonth: _selectedMonthPlan,
                    articles: provider.articles,
                  ),
                  const SizedBox(height: 80), // Space for FAB
                ],
              ),
            ),
          );
        },
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openArticleEditor(),
        backgroundColor: const Color(0xFF223A63),
        foregroundColor: Colors.white,
        icon: const Icon(Icons.add_rounded),
        label: const Text('เขียนบทความ'),
      ),
    );
  }
}

class _ArticleItem extends StatelessWidget {
  final Article article;
  final VoidCallback onTap;
  final VoidCallback onView;
  final VoidCallback onEdit;
  final VoidCallback onShare;
  final VoidCallback onDelete;

  const _ArticleItem({
    required this.article,
    required this.onTap,
    required this.onView,
    required this.onEdit,
    required this.onShare,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(20),
        side: BorderSide(color: const Color(0xFFE5EAF2), width: 1),
      ),
      color: Colors.white,
      shadowColor: const Color(0xFF1E2D45).withValues(alpha: 0.1),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: LayoutBuilder(
            builder: (context, cardConstraints) {
              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          article.title,
                          style: GoogleFonts.kanit(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                            color: const Color(0xFF1E2D45),
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                        if (cardConstraints.maxHeight > 80 && cardConstraints.maxWidth > 200) ...[
                          const SizedBox(height: 4),
                          Text(
                            article.excerpt ?? 'ไม่มีคำโปรย...',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(color: Color(0xFF7488A8), fontSize: 11),
                          ),
                        ],
                      ],
                    ),
                  ),
                  const SizedBox(height: 4),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Flexible(
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Flexible(child: _StatusPill(article: article)),
                            if (cardConstraints.maxWidth > 250) ...[
                              const SizedBox(width: 8),
                              Flexible(
                                child: Text(
                                  _formatArticleDate(article),
                                  style: GoogleFonts.kanit(
                                    fontSize: 12,
                                    color: const Color(0xFF64748B),
                                    fontWeight: FontWeight.w500,
                                  ),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                      const SizedBox(width: 4),
                      Wrap(
                        spacing: 4,
                        runSpacing: 4,
                        alignment: WrapAlignment.end,
                        children: [
                          _ArticleActionButton(
                            icon: Icons.visibility_outlined,
                            tooltip: 'ดูบทความ',
                            onPressed: onView,
                          ),
                          _ArticleActionButton(
                            icon: Icons.edit_outlined,
                            tooltip: 'แก้ไข',
                            onPressed: onEdit,
                          ),
                          _ArticleActionButton(
                            icon: Icons.ios_share_outlined,
                            tooltip: 'แชร์',
                            onPressed: onShare,
                          ),
                          _ArticleActionButton(
                            icon: Icons.delete_rounded,
                            tooltip: 'ลบ',
                            color: const Color(0xFFC54B3D),
                            onPressed: onDelete,
                          ),
                        ],
                      ),
                    ],
                  ),
                ],
              );
            },
          ),
        ),
      ),
    );
  }
}

String _formatArticleDate(Article article) {
  // ให้ความสำคัญกับวันเผยแพร่ (Published At) ก่อนเสมอ
  final date = article.publishedAt;
  if (date != null) {
    return DateFormatter.formatArticleList(date);
  }
  
  // ถ้ายังไม่มีวันเผยแพร่ (เป็นฉบับร่างที่ยังไม่ได้กำหนดวัน) ให้ใช้วันที่สร้างแทน
  final created = article.createdAt;
  return created != null ? DateFormatter.formatArticleList(created) : '-';
}

class _ArticleActionButton extends StatelessWidget {
  final IconData icon;
  final String tooltip;
  final Color color;
  final VoidCallback onPressed;

  const _ArticleActionButton({
    required this.icon,
    required this.tooltip,
    required this.onPressed,
    this.color = const Color(0xFF223A63),
  });

  @override
  Widget build(BuildContext context) {
    return Tooltip(
      message: tooltip,
      child: Material(
        color: color.withValues(alpha: 0.1),
        shape: const CircleBorder(),
        child: InkWell(
          onTap: onPressed,
          customBorder: const CircleBorder(),
          child: Container(
            width: 32,
            height: 32,
            alignment: Alignment.center,
            child: Icon(icon, size: 18, color: color),
          ),
        ),
      ),
    );
  }
}

class _StatusPill extends StatelessWidget {
  final Article article;
  const _StatusPill({required this.article});

  @override
  Widget build(BuildContext context) {
    final Color backgroundColor;
    final Color borderColor;
    final Color textColor;

    if (!article.isPublished) {
      backgroundColor = const Color(0xFFFFF8E8);
      borderColor = const Color(0xFFF0DBAD);
      textColor = const Color(0xFFA66A14);
    } else if (article.isScheduled) {
      backgroundColor = const Color(0xFFEEF6FF);
      borderColor = const Color(0xFFBFDBFE);
      textColor = const Color(0xFF2563EB);
    } else {
      backgroundColor = const Color(0xFFEDF9F5);
      borderColor = const Color(0xFFCBE9DE);
      textColor = const Color(0xFF1B8B6F);
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(99),
        border: Border.all(color: borderColor),
      ),
      child: Text(
        article.publishStatusLabel,
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.bold,
          color: textColor,
        ),
      ),
    );
  }
}

class _ContentPlanSection extends StatelessWidget {
  final String? selectedMonth;
  final List<Article> articles;

  const _ContentPlanSection({
    required this.selectedMonth,
    required this.articles,
  });

  static const List<Map<String, dynamic>> _allPlanData = [
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
        {'d': 25, 't': '09:09', 'type': 'Evergreen', 'topic': '(คั่นกลางปลายเดือน เพราะวันสำคัญกระจุกต้นเดือน)', 'date': '2026-08-25'},
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
        {'d': 20, 't': '09:00', 'type': 'วันมู', 'topic': 'เทศกาลกินเจ: เลขสายขาว (เลือกลงวันที่ 20)', 'date': '2026-10-20'},
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
        {'d': 8, 't': '09:09', 'type': 'Evergreen', 'topic': '(คั่นกลางต้นเดือน - เดือนนี้ไม่มีเทศกาล)', 'date': '2027-03-08'},
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
    final filteredData = selectedMonth == null
        ? _allPlanData
        : _allPlanData.where((m) => m['month'] == selectedMonth).toList();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: const Color(0xFF223A63).withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.assignment_outlined, color: Color(0xFF223A63), size: 20),
            ),
            const SizedBox(width: 12),
            Text(
              'CONTENT ROADMAP',
              style: GoogleFonts.kanit(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                letterSpacing: 0.5,
                color: const Color(0xFF1D1816),
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),
        ...filteredData.map((monthData) => _MonthPlanCard(
          monthName: monthData['month'],
          items: monthData['items'],
          articles: articles,
        )),
      ],
    );
  }
}

class _MonthPlanCard extends StatelessWidget {
  final String monthName;
  final List<dynamic> items;
  final List<Article> articles;

  const _MonthPlanCard({
    required this.monthName,
    required this.items,
    required this.articles,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF1E2D45).withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
            color: const Color(0xFFF8FAFC),
            width: double.infinity,
            child: Text(
              monthName,
              style: GoogleFonts.kanit(
                fontWeight: FontWeight.bold,
                fontSize: 16,
                color: const Color(0xFF1E293B),
              ),
            ),
          ),
          const Divider(height: 1, color: Color(0xFFE2E8F0)),
          ...items.map((item) {
            final isDone = _checkIfDone(item);
            return _PlanItemRow(item: item, isDone: isDone);
          }),
        ],
      ),
    );
  }

  bool _checkIfDone(Map<String, dynamic> item) {
    final planDate = item['date'] as String;
    final isLottery = item['is_lottery'] == true;
    
    return articles.any((a) {
      if (a.publishedAt == null) return false;
      final articleDate = DateFormat('yyyy-MM-dd').format(a.publishedAt!);
      
      if (isLottery) {
        // สำหรับหวย เช็คแค่วันที่ตรงกัน
        return articleDate == planDate;
      } else {
        // สำหรับทั่วไป เช็คทั้งวันที่และหัวข้อที่คล้ายกัน (หรือแค่มีบทความในวันนั้น)
        return articleDate == planDate;
      }
    });
  }
}

class _PlanItemRow extends StatelessWidget {
  final Map<String, dynamic> item;
  final bool isDone;

  const _PlanItemRow({required this.item, required this.isDone});

  @override
  Widget build(BuildContext context) {
    final bool isLottery = item['type'] == 'หวย';
    final Color? rowColor = isDone 
        ? const Color(0xFFF0FDF4) 
        : (isLottery ? const Color(0xFFFFFBEB) : null);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
      decoration: BoxDecoration(
        color: rowColor,
        border: const Border(bottom: BorderSide(color: Color(0xFFF1F5F9))),
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: isDone 
                  ? const Color(0xFFEDF9F5) 
                  : const Color(0xFFF8FAFC),
              borderRadius: BorderRadius.circular(10),
            ),
            alignment: Alignment.center,
            child: Text(
              '${item['d']}',
              style: GoogleFonts.kanit(
                fontWeight: FontWeight.bold,
                fontSize: 16,
                color: isDone ? const Color(0xFF1B8B6F) : const Color(0xFF64748B),
              ),
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: _getTypeColor(item['type']).withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        item['type'],
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          color: _getTypeColor(item['type']),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Text(
                      item['t'],
                      style: const TextStyle(fontSize: 11, color: Color(0xFF94A3B8), fontWeight: FontWeight.w500),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Text(
                  item['topic'],
                  style: GoogleFonts.kanit(
                    fontSize: 14,
                    fontWeight: FontWeight.w500,
                    color: const Color(0xFF1E293B),
                    decoration: isDone ? TextDecoration.lineThrough : null,
                  ),
                ),
              ],
            ),
          ),
          Icon(
            isDone ? Icons.check_circle_rounded : Icons.radio_button_unchecked_rounded,
            color: isDone ? const Color(0xFF10B981) : const Color(0xFFCBD5E1),
          ),
        ],
      ),
    );
  }

  Color _getTypeColor(String type) {
    switch (type) {
      case 'หวย': return const Color(0xFFC54B3D);
      case 'วันสำคัญ': return const Color(0xFF6366F1);
      case 'วันมู': return const Color(0xFFD8A34A);
      default: return const Color(0xFF64748B);
    }
  }
}
