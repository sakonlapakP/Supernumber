import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:url_launcher/url_launcher.dart';
import '../providers/article_provider.dart';
import '../providers/auth_provider.dart';
import '../models/article.model.dart';
import 'article_edit_screen.dart';
import 'article_json_import_screen.dart';

class ArticleListScreen extends StatefulWidget {
  const ArticleListScreen({super.key});

  @override
  State<ArticleListScreen> createState() => _ArticleListScreenState();
}

class _ArticleListScreenState extends State<ArticleListScreen> {
  @override
  void initState() {
    super.initState();
    // ดึงข้อมูลอัตโนมัติเมื่อเข้าหน้าจอนี้
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<ArticleProvider>().fetchArticles();
    });
  }

  Future<void> _openJsonImport() async {
    await Navigator.push(
      context,
      MaterialPageRoute(builder: (context) => const ArticleJsonImportScreen()),
    );

    if (!mounted) return;
    await context.read<ArticleProvider>().fetchArticles();
  }

  Future<void> _openArticleEditor([Article? article]) async {
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => ArticleEditScreen(article: article),
      ),
    );

    if (!mounted) return;
    await context.read<ArticleProvider>().fetchArticles();
  }

  Future<void> _openArticlePreview(Article article) async {
    final url = article.publicUrl;
    if (url == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('บทความนี้ยังไม่มี slug สำหรับเปิดดู')),
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
    ).showSnackBar(const SnackBar(content: Text('เปิด Safari ไม่สำเร็จ')));
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
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
          IconButton(
            icon: const Icon(
              Icons.data_object_rounded,
              color: Color(0xFF7C3AED),
            ),
            tooltip: 'เพิ่มบทความด้วย JSON',
            onPressed: _openJsonImport,
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
                  const SizedBox(height: 16),
                  const Text(
                    'ยังไม่มีบทความในระบบ',
                    style: TextStyle(color: Colors.grey),
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () => provider.fetchArticles(),
                    child: const Text('ลองโหลดใหม่อีกครั้ง'),
                  ),
                ],
              ),
            );
          }

          return LayoutBuilder(
            builder: (context, constraints) {
              final isTablet = constraints.maxWidth > 600;
              return RefreshIndicator(
                onRefresh: () => provider.fetchArticles(),
                child: GridView.builder(
                  padding: const EdgeInsets.all(16),
                  gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: isTablet ? 2 : 1,
                    childAspectRatio: isTablet ? 2.5 : 3.0,
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
                ),
              );
            },
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
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                article.title,
                style: GoogleFonts.kanit(
                  fontWeight: FontWeight.bold,
                  fontSize: 17,
                  color: const Color(0xFF1E2D45),
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
              const SizedBox(height: 4),
              Text(
                article.excerpt ?? 'ไม่มีคำโปรย...',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: Color(0xFF7488A8), fontSize: 12),
              ),
              const Spacer(),
              Row(
                children: [
                  _StatusPill(article: article),
                  const SizedBox(width: 8),
                  Text(
                    _formatArticleDate(article),
                    style: GoogleFonts.kanit(
                      fontSize: 12,
                      color: const Color(0xFF94A3B8),
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  const Spacer(),
                  Wrap(
                    spacing: 6,
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
          ),
        ),
      ),
    );
  }
}

String _formatArticleDate(Article article) {
  final date = article.publishedAt ?? article.createdAt;
  return date != null ? DateFormat('dd MMM yyyy').format(date) : '';
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
          onPressed: onPressed,
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
