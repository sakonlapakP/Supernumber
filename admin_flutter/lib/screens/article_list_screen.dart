import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import '../providers/article_provider.dart';
import '../providers/auth_provider.dart';
import '../providers/article_plan_provider.dart';
import '../models/article.model.dart';
import '../models/article_plan.model.dart';
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
  int _selectedPlanYear = DateTime.now().year;
  static const Map<int, String> _thaiMonths = {
    1: 'มกราคม', 2: 'กุมภาพันธ์', 3: 'มีนาคม', 4: 'เมษายน',
    5: 'พฤษภาคม', 6: 'มิถุนายน', 7: 'กรกฎาคม', 8: 'สิงหาคม',
    9: 'กันยายน', 10: 'ตุลาคม', 11: 'พฤศจิกายน', 12: 'ธันวาคม',
  };


  @override
  void initState() {
    super.initState();
    // ดึงข้อมูลอัตโนมัติเมื่อเข้าหน้าจอนี้
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<ArticleProvider>().fetchArticles(monthPlan: _selectedMonthPlan);
      context.read<ArticlePlanProvider>().fetchPlans(year: _selectedPlanYear);
    });
  }

  Future<void> _openJsonImport() async {
    final articleProvider = context.read<ArticleProvider>();
    final articlePlanProvider = context.read<ArticlePlanProvider>();
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => const ArticleJsonImportScreen(),
        fullscreenDialog: true,
      ),
    );

    if (!mounted) return;
    await articleProvider.fetchArticles(monthPlan: _selectedMonthPlan);
    await articlePlanProvider.fetchPlans(year: _selectedPlanYear);
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
                  ..._monthPlansForSelectedYear().map<DropdownMenuItem<String>>((String value) {
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
                    selectedYear: _selectedPlanYear,
                    articles: provider.articles,
                    onYearChanged: (year) {
                      setState(() => _selectedPlanYear = year);
                      if (_selectedMonthPlan != null && !_monthPlansForSelectedYear().contains(_selectedMonthPlan)) {
                        _selectedMonthPlan = null;
                      }
                      context.read<ArticleProvider>().fetchArticles(monthPlan: _selectedMonthPlan);
                      context.read<ArticlePlanProvider>().fetchPlans(year: year);
                    },
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

  List<String> _monthPlansForSelectedYear() {
    return _thaiMonths.values
        .map((m) => '$m ${(_selectedPlanYear + 543) % 100}')
        .toList();
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
  final int selectedYear;
  final List<Article> articles;
  final ValueChanged<int> onYearChanged;

  const _ContentPlanSection({
    required this.selectedMonth,
    required this.selectedYear,
    required this.articles,
    required this.onYearChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Consumer2<ArticlePlanProvider, AuthProvider>(
      builder: (context, planProvider, auth, _) {
        final grouped = <String, List<ArticlePlan>>{};
        for (final p in planProvider.plans) {
          final label = DateFormat('MMMM', 'th').format(p.publishDate);
          grouped.putIfAbsent(label, () => []).add(p);
        }

        final selectedGroup = selectedMonth == null
            ? planProvider.plans
            : (grouped[selectedMonth!.split(' ').first] ?? <ArticlePlan>[]);
        final totalPlanned = selectedGroup.length;
        final totalDone = selectedGroup.where((p) => p.type == 'หวย' || _isDoneByRule(p)).length;

        return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: const Color(0xFF223A63).withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.assignment_outlined, color: Color(0xFF223A63), size: 20),
            ),
            const SizedBox(width: 12),
            Text('CONTENT ROADMAP', style: GoogleFonts.kanit(fontSize: 18, fontWeight: FontWeight.bold)),
            const Spacer(),
            DropdownButton<int>(
              value: selectedYear,
              items: List.generate(12, (i) => 2026 + i)
                  .map((y) => DropdownMenuItem(value: y, child: Text('ปี ${y + 543 - 2500}')))
                  .toList(),
              onChanged: (v) {
                if (v == null) return;
                onYearChanged(v);
              },
            ),
            if (auth.user?['role'] == 'manager')
              IconButton(
                onPressed: () => _openPlanDialog(context, selectedYear),
                icon: const Icon(Icons.add_circle_outline),
              ),
          ]),
          const SizedBox(height: 16),
          Row(
            children: [
              Text('แผนทั้งหมด $totalPlanned', style: GoogleFonts.kanit(fontWeight: FontWeight.w600)),
              const SizedBox(width: 12),
              Text('ทำแล้ว $totalDone', style: GoogleFonts.kanit(fontWeight: FontWeight.w600, color: const Color(0xFF1B8B6F))),
            ],
          ),
          const SizedBox(height: 10),
          if (planProvider.isLoading) const Center(child: CircularProgressIndicator()),
          ...grouped.entries.map((e) => _MonthPlanCard(monthName: e.key, items: e.value, articles: articles, year: selectedYear)),
        ]);
      },
    );
  }

  bool _isDoneByRule(ArticlePlan item) {
    final planDate = DateFormat('yyyy-MM-dd').format(item.publishDate);
    for (final a in articles) {
      if (a.publishedAt != null && DateFormat('yyyy-MM-dd').format(a.publishedAt!) == planDate) {
        return true;
      }
    }
    return item.publishDate.isBefore(DateTime.now());
  }

  Future<void> _openPlanDialog(BuildContext context, int year, {ArticlePlan? plan}) async {
    DateTime selectedDate = plan?.publishDate ?? DateTime.now();
    TimeOfDay selectedTime = _parseTime(plan?.publishTime) ?? const TimeOfDay(hour: 9, minute: 0);
    final dateCtrl = TextEditingController(text: DateFormat('yyyy-MM-dd').format(selectedDate));
    final timeCtrl = TextEditingController(text: _formatTime(selectedTime));
    final typeCtrl = TextEditingController(text: plan?.type ?? '');
    final topicCtrl = TextEditingController(text: plan?.topic ?? '');
    bool isLottery = plan?.isLottery ?? false;
    final ok = await showDialog<bool>(context: context, builder: (ctx) => StatefulBuilder(builder: (ctx, setS) => AlertDialog(
      title: Text(plan == null ? 'เพิ่มแผน' : 'แก้ไขแผน'),
      content: Column(mainAxisSize: MainAxisSize.min, children: [
        TextField(
          controller: dateCtrl,
          readOnly: true,
          decoration: const InputDecoration(labelText: 'วันที่'),
          onTap: () async {
            final picked = await showDatePicker(
              context: ctx,
              initialDate: selectedDate,
              firstDate: DateTime(2026, 1, 1),
              lastDate: DateTime(2037, 12, 31),
            );
            if (picked == null) return;
            selectedDate = picked;
            dateCtrl.text = DateFormat('yyyy-MM-dd').format(picked);
          },
        ),
        TextField(
          controller: timeCtrl,
          readOnly: true,
          decoration: const InputDecoration(labelText: 'เวลา'),
          onTap: () async {
            final picked = await showTimePicker(context: ctx, initialTime: selectedTime);
            if (picked == null) return;
            selectedTime = picked;
            timeCtrl.text = _formatTime(picked);
          },
        ),
        TextField(controller: typeCtrl, decoration: const InputDecoration(labelText: 'ประเภท')),
        TextField(controller: topicCtrl, decoration: const InputDecoration(labelText: 'หัวข้อ')),
        SwitchListTile(value: isLottery, onChanged: (v)=>setS(()=>isLottery=v), title: const Text('เป็นบทความหวย')),
      ]),
      actions: [TextButton(onPressed: ()=>Navigator.pop(ctx,false), child: const Text('ยกเลิก')), FilledButton(onPressed: ()=>Navigator.pop(ctx,true), child: const Text('บันทึก'))],
    )));
    if (ok != true) return;
    if (!context.mounted) return;
    if (topicCtrl.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('กรุณากรอกหัวข้อ')));
      return;
    }
    final payload = {'publish_date': dateCtrl.text.trim(), 'publish_time': timeCtrl.text.trim(), 'type': typeCtrl.text.trim(), 'topic': topicCtrl.text.trim(), 'is_lottery': isLottery};
    final p = context.read<ArticlePlanProvider>();
    final success = plan == null ? await p.createPlan(payload, year) : await p.updatePlan(plan.id, payload, year);
    if (context.mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(success ? 'บันทึกสำเร็จ' : (p.lastErrorMessage ?? 'ไม่สำเร็จ'))));
  }

  String _formatTime(TimeOfDay value) {
    final hh = value.hour.toString().padLeft(2, '0');
    final mm = value.minute.toString().padLeft(2, '0');
    return '$hh:$mm';
  }

  TimeOfDay? _parseTime(String? value) {
    if (value == null || value.isEmpty) return null;
    final parts = value.split(':');
    if (parts.length < 2) return null;
    final h = int.tryParse(parts[0]);
    final m = int.tryParse(parts[1]);
    if (h == null || m == null) return null;
    return TimeOfDay(hour: h.clamp(0, 23), minute: m.clamp(0, 59));
  }
}

class _MonthPlanCard extends StatelessWidget {
  final String monthName;
  final List<ArticlePlan> items;
  final List<Article> articles;
  final int year;
  const _MonthPlanCard({required this.monthName, required this.items, required this.articles, required this.year});

  @override
  Widget build(BuildContext context) {
    return Container(margin: const EdgeInsets.only(bottom: 16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(monthName, style: GoogleFonts.kanit(fontWeight: FontWeight.bold)),
      ...items.map((item) {
        final isDone = _isDone(item);
        final role = context.read<AuthProvider>().user?['role'];
        final isManager = role == 'manager';
        return ListTile(
          title: Text(item.topic),
          subtitle: Text('${DateFormat('yyyy-MM-dd').format(item.publishDate)} ${item.publishTime} • ${item.type ?? '-'}'),
          trailing: Row(mainAxisSize: MainAxisSize.min, children: [
            Icon(isDone ? Icons.check_circle : Icons.radio_button_unchecked, color: isDone ? Colors.green : Colors.grey),
            if (isManager)
              IconButton(onPressed: () => (context.findAncestorWidgetOfExactType<_ContentPlanSection>())?._openPlanDialog(context, year, plan: item), icon: const Icon(Icons.edit_outlined)),
            if (isManager)
              IconButton(onPressed: () async { await context.read<ArticlePlanProvider>().deletePlan(item.id, year); }, icon: const Icon(Icons.delete_outline)),
          ]),
        );
      }),
    ]));
  }

  bool _isDone(ArticlePlan item) {
    final planDate = DateFormat('yyyy-MM-dd').format(item.publishDate);
    if (item.publishDate.isBefore(DateTime.now())) return true;
    for (final a in articles) {
      if (a.publishedAt != null && DateFormat('yyyy-MM-dd').format(a.publishedAt!) == planDate) return true;
      if (item.isLottery && a.slug != null) {
        final monthStr = item.publishDate.month.toString().padLeft(2, '0');
        final isRound1 = item.publishDate.day <= 15;
        final pattern = 'thai-goverment-lottery-${item.publishDate.year}$monthStr${isRound1 ? 'first' : 'second'}';
        if (a.slug == pattern) return true;
      }
    }
    return false;
  }
}
