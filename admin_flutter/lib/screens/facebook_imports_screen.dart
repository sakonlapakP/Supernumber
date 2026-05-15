import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import '../models/facebook_import_post.model.dart';
import '../providers/facebook_import_provider.dart';
import '../utils/date_formatter.dart';

class FacebookImportsScreen extends StatefulWidget {
  const FacebookImportsScreen({super.key});

  @override
  State<FacebookImportsScreen> createState() => _FacebookImportsScreenState();
}

class _FacebookImportsScreenState extends State<FacebookImportsScreen> {
  final TextEditingController _searchController = TextEditingController();
  final ScrollController _scrollController = ScrollController();

  DateTime? _fromDate;
  DateTime? _toDate;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<FacebookImportProvider>().fetchFirstPage();
    });

    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController
      ..removeListener(_onScroll)
      ..dispose();
    super.dispose();
  }

  void _onScroll() {
    if (!_scrollController.hasClients) return;

    final provider = context.read<FacebookImportProvider>();
    if (!provider.hasMore || provider.isLoadingMore || provider.isLoading) {
      return;
    }

    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 240) {
      provider.fetchNextPage();
    }
  }

  Future<void> _pickFromDate() async {
    final selected = await showDatePicker(
      context: context,
      firstDate: DateTime(2010),
      lastDate: DateTime.now(),
      initialDate: _fromDate ?? DateTime.now(),
    );

    if (selected == null) return;
    setState(() => _fromDate = selected);
  }

  Future<void> _pickToDate() async {
    final selected = await showDatePicker(
      context: context,
      firstDate: DateTime(2010),
      lastDate: DateTime.now(),
      initialDate: _toDate ?? DateTime.now(),
    );

    if (selected == null) return;
    setState(() => _toDate = selected);
  }

  Future<void> _applyFilter() async {
    final provider = context.read<FacebookImportProvider>();

    await provider.fetchFirstPage(
      search: _searchController.text,
      fromDate: _formatDateOnly(_fromDate),
      toDate: _formatDateOnly(_toDate),
    );

    if (!mounted) return;
    final error = provider.lastErrorMessage;
    if (error != null) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(error)));
    }
  }

  Future<void> _clearFilter() async {
    _searchController.clear();
    setState(() {
      _fromDate = null;
      _toDate = null;
    });

    await _applyFilter();
  }

  String _formatDateOnly(DateTime? value) {
    if (value == null) return '';
    return DateFormat('yyyy-MM-dd').format(value);
  }

  Future<void> _openExternalUrl(String? url) async {
    if (url == null || url.trim().isEmpty) return;

    final uri = Uri.tryParse(url.trim());
    if (uri == null) return;

    final launched = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!mounted || launched) return;

    ScaffoldMessenger.of(
      context,
    ).showSnackBar(const SnackBar(content: Text('เปิดลิงก์ไม่สำเร็จ')));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'นำเข้าโพสต์ Facebook',
          style: GoogleFonts.kanit(fontWeight: FontWeight.w700),
        ),
      ),
      body: Consumer<FacebookImportProvider>(
        builder: (context, provider, _) {
          return Column(
            children: [
              _FilterPanel(
                searchController: _searchController,
                fromDate: _fromDate,
                toDate: _toDate,
                onPickFromDate: _pickFromDate,
                onPickToDate: _pickToDate,
                onApply: _applyFilter,
                onClear: _clearFilter,
                isLoading: provider.isLoading,
              ),
              Expanded(child: _buildContent(provider)),
            ],
          );
        },
      ),
    );
  }

  Widget _buildContent(FacebookImportProvider provider) {
    if (provider.isLoading && provider.posts.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }

    if (provider.posts.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.facebook_rounded, size: 54, color: Colors.grey.shade400),
            const SizedBox(height: 10),
            Text(
              provider.lastErrorMessage ?? 'ยังไม่มีข้อมูลโพสต์ Facebook',
              style: GoogleFonts.kanit(
                color: const Color(0xFF64748B),
                fontSize: 15,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _applyFilter,
      child: ListView.separated(
        controller: _scrollController,
        padding: const EdgeInsets.fromLTRB(12, 8, 12, 20),
        itemCount: provider.posts.length + (provider.isLoadingMore ? 1 : 0),
        separatorBuilder: (context, index) => const SizedBox(height: 10),
        itemBuilder: (context, index) {
          if (index >= provider.posts.length) {
            return const Padding(
              padding: EdgeInsets.symmetric(vertical: 12),
              child: Center(child: CircularProgressIndicator()),
            );
          }

          final post = provider.posts[index];
          return _FacebookPostCard(
            post: post,
            onOpenImage: () => _openExternalUrl(post.imageUrl),
            onOpenPost: () => _openExternalUrl(post.permalinkUrl),
          );
        },
      ),
    );
  }
}

class _FilterPanel extends StatelessWidget {
  final TextEditingController searchController;
  final DateTime? fromDate;
  final DateTime? toDate;
  final VoidCallback onPickFromDate;
  final VoidCallback onPickToDate;
  final VoidCallback onApply;
  final VoidCallback onClear;
  final bool isLoading;

  const _FilterPanel({
    required this.searchController,
    required this.fromDate,
    required this.toDate,
    required this.onPickFromDate,
    required this.onPickToDate,
    required this.onApply,
    required this.onClear,
    required this.isLoading,
  });

  @override
  Widget build(BuildContext context) {
    final dateTextStyle = GoogleFonts.kanit(
      fontSize: 13,
      color: const Color(0xFF334155),
      fontWeight: FontWeight.w600,
    );

    return Container(
      margin: const EdgeInsets.fromLTRB(12, 12, 12, 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: const Color(0xFFD7E1F0)),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        children: [
          TextField(
            controller: searchController,
            textInputAction: TextInputAction.search,
            onSubmitted: (_) => onApply(),
            decoration: const InputDecoration(
              hintText: 'ค้นหา message, story, post id',
              prefixIcon: Icon(Icons.search_rounded),
            ),
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: isLoading ? null : onPickFromDate,
                  icon: const Icon(Icons.calendar_today_rounded, size: 16),
                  label: Text(
                    fromDate == null
                        ? 'จากวันที่'
                        : DateFormat('dd/MM/yyyy').format(fromDate!),
                    style: dateTextStyle,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: isLoading ? null : onPickToDate,
                  icon: const Icon(Icons.event_rounded, size: 16),
                  label: Text(
                    toDate == null
                        ? 'ถึงวันที่'
                        : DateFormat('dd/MM/yyyy').format(toDate!),
                    style: dateTextStyle,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: isLoading ? null : onClear,
                  child: Text('ล้างค่า', style: GoogleFonts.kanit()),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: FilledButton(
                  onPressed: isLoading ? null : onApply,
                  child: Text(
                    'ค้นหา',
                    style: GoogleFonts.kanit(fontWeight: FontWeight.w700),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _FacebookPostCard extends StatelessWidget {
  final FacebookImportPost post;
  final VoidCallback onOpenImage;
  final VoidCallback onOpenPost;

  const _FacebookPostCard({
    required this.post,
    required this.onOpenImage,
    required this.onOpenPost,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      color: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: const BorderSide(color: Color(0xFFD7E1F0)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            InkWell(
              onTap: post.imageUrl == null ? null : onOpenImage,
              borderRadius: BorderRadius.circular(10),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(10),
                child: _Thumbnail(imageUrl: post.imageUrl),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    post.facebookPostId,
                    style: GoogleFonts.kanit(
                      fontWeight: FontWeight.w700,
                      fontSize: 13,
                      color: const Color(0xFF1E2D45),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    'โพสต์: ${DateFormatter.formatBangkok(post.facebookCreatedTime, format: 'dd/MM/yyyy HH:mm')}',
                    style: GoogleFonts.kanit(
                      fontSize: 12,
                      color: const Color(0xFF64748B),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    'ซิงก์: ${DateFormatter.formatBangkok(post.lastSyncedAt, format: 'dd/MM/yyyy HH:mm')}',
                    style: GoogleFonts.kanit(
                      fontSize: 12,
                      color: const Color(0xFF64748B),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    post.previewText,
                    maxLines: 3,
                    overflow: TextOverflow.ellipsis,
                    style: GoogleFonts.kanit(
                      fontSize: 14,
                      color: const Color(0xFF334155),
                      height: 1.3,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Align(
                    alignment: Alignment.centerLeft,
                    child: OutlinedButton.icon(
                      onPressed: post.permalinkUrl == null ? null : onOpenPost,
                      icon: const Icon(Icons.open_in_new_rounded, size: 16),
                      label: Text('เปิดโพสต์', style: GoogleFonts.kanit()),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Thumbnail extends StatelessWidget {
  final String? imageUrl;

  const _Thumbnail({required this.imageUrl});

  @override
  Widget build(BuildContext context) {
    if (imageUrl == null || imageUrl!.trim().isEmpty) {
      return Container(
        width: 86,
        height: 86,
        color: const Color(0xFFF1F5F9),
        alignment: Alignment.center,
        child: const Icon(
          Icons.image_not_supported_outlined,
          color: Color(0xFF94A3B8),
        ),
      );
    }

    return Image.network(
      imageUrl!,
      width: 86,
      height: 86,
      fit: BoxFit.cover,
      errorBuilder: (context, error, stackTrace) => Container(
        width: 86,
        height: 86,
        color: const Color(0xFFF1F5F9),
        alignment: Alignment.center,
        child: const Icon(
          Icons.broken_image_outlined,
          color: Color(0xFF94A3B8),
        ),
      ),
      loadingBuilder: (context, child, progress) {
        if (progress == null) return child;
        return Container(
          width: 86,
          height: 86,
          color: const Color(0xFFF8FAFC),
          alignment: Alignment.center,
          child: const SizedBox(
            width: 18,
            height: 18,
            child: CircularProgressIndicator(strokeWidth: 2),
          ),
        );
      },
    );
  }
}
