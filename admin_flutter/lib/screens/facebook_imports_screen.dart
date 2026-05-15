import 'dart:math' as math;

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

  Future<void> _deletePost(FacebookImportPost post) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text(
            'ยืนยันการลบ',
            style: GoogleFonts.kanit(fontWeight: FontWeight.w700),
          ),
          content: Text(
            'ต้องการลบโพสต์ ${post.facebookPostId} ออกจากฐานข้อมูลหรือไม่?',
            style: GoogleFonts.kanit(),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: Text('ยกเลิก', style: GoogleFonts.kanit()),
            ),
            FilledButton(
              onPressed: () => Navigator.of(context).pop(true),
              style: FilledButton.styleFrom(
                backgroundColor: const Color(0xFFB91C1C),
              ),
              child: Text(
                'ลบ',
                style: GoogleFonts.kanit(fontWeight: FontWeight.w700),
              ),
            ),
          ],
        );
      },
    );

    if (confirmed != true || !mounted) return;

    final provider = context.read<FacebookImportProvider>();
    final deleted = await provider.deletePost(post.id);
    if (!mounted) return;

    if (deleted) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('ลบโพสต์เรียบร้อยแล้ว')));
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(provider.lastErrorMessage ?? 'ลบโพสต์ไม่สำเร็จ')),
    );
  }

  Future<void> _deleteSelected(FacebookImportProvider provider) async {
    if (!provider.hasSelection) return;

    final selectedCount = provider.selectedCount;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text(
            'ยืนยันการลบหลายรายการ',
            style: GoogleFonts.kanit(fontWeight: FontWeight.w700),
          ),
          content: Text(
            'ต้องการลบโพสต์ที่เลือกทั้งหมด $selectedCount รายการหรือไม่?',
            style: GoogleFonts.kanit(),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: Text('ยกเลิก', style: GoogleFonts.kanit()),
            ),
            FilledButton(
              onPressed: () => Navigator.of(context).pop(true),
              style: FilledButton.styleFrom(
                backgroundColor: const Color(0xFFB91C1C),
              ),
              child: Text(
                'ลบทั้งหมด',
                style: GoogleFonts.kanit(fontWeight: FontWeight.w700),
              ),
            ),
          ],
        );
      },
    );

    if (confirmed != true || !mounted) return;

    final deleted = await provider.deleteSelected();
    if (!mounted) return;

    if (deleted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('ลบโพสต์ที่เลือกแล้ว $selectedCount รายการ')),
      );
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(provider.lastErrorMessage ?? 'ลบโพสต์ที่เลือกไม่สำเร็จ'),
      ),
    );
  }

  String _formatDateOnly(DateTime? value) {
    if (value == null) return '';
    return DateFormat('yyyy-MM-dd').format(value);
  }

  int _resolveGridColumnCount(double width) {
    if (width >= 1200) return 3;
    if (width >= 760) return 2;
    return 1;
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
      child: CustomScrollView(
        controller: _scrollController,
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(12, 8, 12, 10),
              child: _SelectionActionBar(
                selectedCount: provider.selectedCount,
                totalCount: provider.posts.length,
                hasSelection: provider.hasSelection,
                onSelectAll: provider.selectAllLoaded,
                onClearSelection: provider.clearSelection,
                onDeleteSelected: () => _deleteSelected(provider),
              ),
            ),
          ),
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(12, 0, 12, 20),
            sliver: SliverLayoutBuilder(
              builder: (context, constraints) {
                final columns = _resolveGridColumnCount(
                  constraints.crossAxisExtent,
                );

                return SliverGrid(
                  delegate: SliverChildBuilderDelegate((context, index) {
                    final post = provider.posts[index];
                    return _FacebookPostCard(
                      post: post,
                      isSelected: provider.isSelected(post.id),
                      onToggleSelection: () =>
                          provider.toggleSelection(post.id),
                      onDelete: () => _deletePost(post),
                      onOpenImage: () => _openExternalUrl(post.imageUrl),
                      onOpenPost: () => _openExternalUrl(post.permalinkUrl),
                    );
                  }, childCount: provider.posts.length),
                  gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: columns,
                    mainAxisSpacing: 10,
                    crossAxisSpacing: 10,
                    childAspectRatio: columns == 3
                        ? 0.95
                        : (columns == 2 ? 1.05 : 1.75),
                  ),
                );
              },
            ),
          ),
          if (provider.isLoadingMore)
            const SliverToBoxAdapter(
              child: Padding(
                padding: EdgeInsets.only(bottom: 20),
                child: Center(child: CircularProgressIndicator()),
              ),
            ),
        ],
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
      child: LayoutBuilder(
        builder: (context, constraints) {
          final rowWidth = math.max(constraints.maxWidth, 980.0);
          return SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: SizedBox(
              width: rowWidth,
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: searchController,
                      textInputAction: TextInputAction.search,
                      onSubmitted: (_) => onApply(),
                      decoration: const InputDecoration(
                        hintText: 'ค้นหา message, story, post id',
                        prefixIcon: Icon(Icons.search_rounded),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  SizedBox(
                    width: 160,
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
                  SizedBox(
                    width: 160,
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
                  const SizedBox(width: 8),
                  SizedBox(
                    width: 110,
                    child: OutlinedButton(
                      onPressed: isLoading ? null : onClear,
                      child: Text('ล้างค่า', style: GoogleFonts.kanit()),
                    ),
                  ),
                  const SizedBox(width: 8),
                  SizedBox(
                    width: 120,
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
            ),
          );
        },
      ),
    );
  }
}

class _SelectionActionBar extends StatelessWidget {
  final int selectedCount;
  final int totalCount;
  final bool hasSelection;
  final VoidCallback onSelectAll;
  final VoidCallback onClearSelection;
  final VoidCallback onDeleteSelected;

  const _SelectionActionBar({
    required this.selectedCount,
    required this.totalCount,
    required this.hasSelection,
    required this.onSelectAll,
    required this.onClearSelection,
    required this.onDeleteSelected,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        border: Border.all(color: const Color(0xFFD7E1F0)),
        borderRadius: BorderRadius.circular(12),
      ),
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(
          children: [
            Text(
              'เลือกแล้ว $selectedCount จาก $totalCount รายการ',
              style: GoogleFonts.kanit(
                fontWeight: FontWeight.w600,
                color: const Color(0xFF334155),
              ),
            ),
            const SizedBox(width: 16),
            TextButton(
              onPressed: onSelectAll,
              child: Text('เลือกทั้งหมด', style: GoogleFonts.kanit()),
            ),
            TextButton(
              onPressed: hasSelection ? onClearSelection : null,
              child: Text('ยกเลิกเลือก', style: GoogleFonts.kanit()),
            ),
            const SizedBox(width: 8),
            FilledButton.icon(
              onPressed: hasSelection ? onDeleteSelected : null,
              style: FilledButton.styleFrom(
                backgroundColor: const Color(0xFFB91C1C),
              ),
              icon: const Icon(Icons.delete_outline_rounded, size: 16),
              label: Text(
                'ลบที่เลือก',
                style: GoogleFonts.kanit(fontWeight: FontWeight.w700),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _FacebookPostCard extends StatelessWidget {
  final FacebookImportPost post;
  final bool isSelected;
  final VoidCallback onToggleSelection;
  final VoidCallback onDelete;
  final VoidCallback onOpenImage;
  final VoidCallback onOpenPost;

  const _FacebookPostCard({
    required this.post,
    required this.isSelected,
    required this.onToggleSelection,
    required this.onDelete,
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
        padding: const EdgeInsets.all(10),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Checkbox(
                  value: isSelected,
                  onChanged: (_) => onToggleSelection(),
                  activeColor: const Color(0xFF1D4ED8),
                ),
                Expanded(
                  child: Text(
                    post.facebookPostId,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: GoogleFonts.kanit(
                      fontWeight: FontWeight.w700,
                      fontSize: 13,
                      color: const Color(0xFF1E2D45),
                    ),
                  ),
                ),
                IconButton(
                  onPressed: onDelete,
                  tooltip: 'ลบข้อมูลโพสต์',
                  icon: const Icon(
                    Icons.delete_outline_rounded,
                    color: Color(0xFFB91C1C),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            InkWell(
              onTap: post.imageUrl == null ? null : onOpenImage,
              borderRadius: BorderRadius.circular(10),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(10),
                child: _Thumbnail(imageUrl: post.imageUrl),
              ),
            ),
            const SizedBox(height: 6),
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
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: GoogleFonts.kanit(
                fontSize: 14,
                color: const Color(0xFF334155),
                height: 1.3,
              ),
            ),
            const SizedBox(height: 8),
            OutlinedButton.icon(
              onPressed: post.permalinkUrl == null ? null : onOpenPost,
              icon: const Icon(Icons.open_in_new_rounded, size: 16),
              label: Text('เปิดโพสต์', style: GoogleFonts.kanit()),
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
    final imageRadius = BorderRadius.circular(10);

    if (imageUrl == null || imageUrl!.trim().isEmpty) {
      return AspectRatio(
        aspectRatio: 16 / 9,
        child: Container(
          decoration: BoxDecoration(
            color: const Color(0xFFF1F5F9),
            borderRadius: imageRadius,
          ),
          alignment: Alignment.center,
          child: const Icon(
            Icons.image_not_supported_outlined,
            color: Color(0xFF94A3B8),
          ),
        ),
      );
    }

    return AspectRatio(
      aspectRatio: 16 / 9,
      child: Container(
        decoration: BoxDecoration(
          color: const Color(0xFFF8FAFC),
          borderRadius: imageRadius,
        ),
        child: Image.network(
          imageUrl!,
          fit: BoxFit.contain,
          errorBuilder: (context, error, stackTrace) => const Center(
            child: Icon(Icons.broken_image_outlined, color: Color(0xFF94A3B8)),
          ),
          loadingBuilder: (context, child, progress) {
            if (progress == null) return child;
            return const Center(
              child: SizedBox(
                width: 18,
                height: 18,
                child: CircularProgressIndicator(strokeWidth: 2),
              ),
            );
          },
        ),
      ),
    );
  }
}
