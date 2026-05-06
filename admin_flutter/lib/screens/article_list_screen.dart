import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:google_fonts/google_fonts.dart';
import '../providers/article_provider.dart';
import '../providers/auth_provider.dart';
import '../models/article.model.dart';
import 'article_edit_screen.dart';

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
                border: Border.all(color: const Color(0xFFD8A34A).withOpacity(0.5)),
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
                  Icon(Icons.article_outlined, size: 64, color: Colors.grey[400]),
                  const SizedBox(height: 16),
                  const Text('ยังไม่มีบทความในระบบ', style: TextStyle(color: Colors.grey)),
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
                  itemBuilder: (context, index) => _ArticleItem(article: provider.articles[index]),
                ),
              );
            },
          );
        },
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => const ArticleEditScreen()),
          ).then((_) => context.read<ArticleProvider>().fetchArticles());
        },
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

  const _ArticleItem({required this.article});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(24),
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => ArticleEditScreen(article: article)),
          ).then((_) => context.read<ArticleProvider>().fetchArticles());
        },
        child: Padding(
          padding: const EdgeInsets.all(16),
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
                maxLines: 1,
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
                  _StatusPill(isPublished: article.isPublished),
                  const Spacer(),
                  Text(
                    article.createdAt != null ? DateFormat('dd MMM yyyy').format(article.createdAt!) : '',
                    style: const TextStyle(fontSize: 11, color: Color(0xFF7488A8), fontWeight: FontWeight.w600),
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

class _StatusPill extends StatelessWidget {
  final bool isPublished;
  const _StatusPill({required this.isPublished});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: isPublished ? const Color(0xFFEDF9F5) : const Color(0xFFFFF8E8),
        borderRadius: BorderRadius.circular(99),
        border: Border.all(
          color: isPublished ? const Color(0xFFCBE9DE) : const Color(0xFFF0DBAD),
        ),
      ),
      child: Text(
        isPublished ? 'เผยแพร่แล้ว' : 'ฉบับร่าง',
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.bold,
          color: isPublished ? const Color(0xFF1B8B6F) : const Color(0xFFA66A14),
        ),
      ),
    );
  }
}
