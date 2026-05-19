import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:html_editor_enhanced/html_editor.dart';
import 'package:image_picker/image_picker.dart';
import 'package:flutter/services.dart';
import 'dart:io';
import '../models/article.model.dart';
import '../providers/article_provider.dart';
import '../services/api_service.dart';
import '../utils/date_formatter.dart';

class ArticleEditScreen extends StatefulWidget {
  final Article? article;

  const ArticleEditScreen({super.key, this.article});

  @override
  State<ArticleEditScreen> createState() => _ArticleEditScreenState();
}

class _ArticleEditScreenState extends State<ArticleEditScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _titleController;
  late TextEditingController _excerptController;
  late TextEditingController _metaDescController;
  late TextEditingController _keywordsController;
  late TextEditingController _lsiKeywordsController;
  late TextEditingController _promptLandscapeController;
  late TextEditingController _promptSquareController;

  late HtmlEditorController _htmlController;
  final GlobalKey _editorKey = GlobalKey();

  late bool _isPublished;
  late bool _isAutoPost;
  DateTime? _publishedAt;
  bool _isFetchingDetail = false;
  bool _isSaving = false;
  String _initialHtml = '';

  String? _newLandscapePath;
  String? _newSquarePath;
  final ImagePicker _picker = ImagePicker();

  @override
  void initState() {
    super.initState();
    _titleController = TextEditingController(text: widget.article?.title ?? '');
    _excerptController = TextEditingController(
      text: widget.article?.excerpt ?? '',
    );
    _metaDescController = TextEditingController(
      text: widget.article?.metaDescription ?? '',
    );
    _keywordsController = TextEditingController(
      text: widget.article?.keywords ?? '',
    );
    _lsiKeywordsController = TextEditingController(
      text: widget.article?.lsiKeywords ?? '',
    );

    _htmlController = HtmlEditorController();
    _initialHtml = widget.article?.content ?? '';

    final guidelines = widget.article?.imageGuidelines;
    _promptLandscapeController = TextEditingController(
      text: guidelines?['landscape_prompt'] ?? '',
    );
    _promptSquareController = TextEditingController(
      text: guidelines?['square_prompt'] ?? '',
    );

    _isPublished = widget.article?.isPublished ?? false;
    _isAutoPost = widget.article?.isAutoPost ?? true;
    _publishedAt = widget.article?.publishedAt;

    if (widget.article?.id != null) {
      _loadFullDetail();
    }
  }

  Future<void> _loadFullDetail() async {
    final provider = context.read<ArticleProvider>();
    setState(() => _isFetchingDetail = true);
    final fullArticle = await provider.fetchArticleDetail(widget.article!.id!);
    if (mounted && fullArticle != null) {
      setState(() {
        _metaDescController.text = fullArticle.metaDescription ?? '';
        _keywordsController.text = fullArticle.keywords ?? '';
        _lsiKeywordsController.text = fullArticle.lsiKeywords ?? '';

        final guidelines = fullArticle.imageGuidelines;
        _promptLandscapeController.text = guidelines?['landscape_prompt'] ?? '';
        _promptSquareController.text = guidelines?['square_prompt'] ?? '';
        _isPublished = fullArticle.isPublished;
        _isAutoPost = fullArticle.isAutoPost;
        _publishedAt = fullArticle.publishedAt;

        _isFetchingDetail = false;
      });
      _htmlController.setText(fullArticle.content);
    } else {
      if (mounted) setState(() => _isFetchingDetail = false);
    }
  }

  Future<void> _pickImage(bool isLandscape) async {
    final XFile? image = await _picker.pickImage(source: ImageSource.gallery);
    if (image != null) {
      setState(() {
        if (isLandscape) {
          _newLandscapePath = image.path;
        } else {
          _newSquarePath = image.path;
        }
      });
    }
  }

  Future<void> _selectDateTime() async {
    final DateTime? pickedDate = await showDatePicker(
      context: context,
      initialDate: _publishedAt ?? DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2101),
    );
    if (pickedDate != null) {
      if (!mounted) return;
      final TimeOfDay? pickedTime = await showTimePicker(
        context: context,
        initialTime: TimeOfDay.fromDateTime(_publishedAt ?? DateTime.now()),
      );
      if (pickedTime != null) {
        setState(() {
          _publishedAt = DateTime(
            pickedDate.year,
            pickedDate.month,
            pickedDate.day,
            pickedTime.hour,
            pickedTime.minute,
          );
        });
      }
    }
  }

  void _copyToClipboard(String text, String label) {
    if (text.isEmpty) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('ไม่มีข้อความให้คัดลอก')));
      return;
    }
    Clipboard.setData(ClipboardData(text: text));
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        backgroundColor: const Color(0xFF223A63),
        content: Text('คัดลอก $label เรียบร้อยแล้ว'),
      ),
    );
  }

  String get _publishStatusLabel {
    if (!_isPublished) return 'Draft';
    if (_publishedAt != null && _publishedAt!.isAfter(DateTime.now())) {
      return 'ตั้งเวลาเผยแพร่';
    }
    return 'Published';
  }

  Future<void> _save() async {
    if (_isSaving) return;

    // 1. ปิดคีย์บอร์ดก่อนเพื่อให้ WebView นิ่ง
    FocusScope.of(context).unfocus();

    if (_formKey.currentState!.validate()) {
      final provider = context.read<ArticleProvider>();

      String content = '';
      try {
        // 2. ดึงเนื้อหา HTML มาเก็บไว้ก่อนที่จะสั่ง setState (Loading)
        // วิธีนี้จะช่วยเลี่ยงปัญหา Controller ถูก Dispose ระหว่างทาง
        content = await _htmlController.getText();
      } catch (e) {
        debugPrint('Error getting HTML content: $e');
        // ถ้าดึงไม่ได้จริงๆ ให้ลองใช้ค่าเริ่มต้นหรือแจ้งเตือน
      }

      setState(() => _isSaving = true);

      try {
        final article = Article(
          id: widget.article?.id,
          title: _titleController.text,
          excerpt: _excerptController.text,
          content: content,
          metaDescription: _metaDescController.text,
          keywords: _keywordsController.text,
          lsiKeywords: _lsiKeywordsController.text,
          imageGuidelines: {
            'landscape_prompt': _promptLandscapeController.text,
            'square_prompt': _promptSquareController.text,
          },
          isPublished: _isPublished,
          isAutoPost: _isAutoPost,
          publishedAt: _publishedAt,
        );

        final success = await provider.saveArticle(
          article,
          landscapePath: _newLandscapePath,
          squarePath: _newSquarePath,
        );

        if (!mounted) return;
        if (success) {
          Navigator.pop(context);
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              backgroundColor: Color(0xFF1B8B6F),
              content: Text('บันทึกเรียบร้อย'),
            ),
          );
          return;
        }

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: const Color(0xFFC2410C),
            content: Text(
              provider.lastErrorMessage ??
                  'บันทึกบทความไม่สำเร็จ กรุณาลองใหม่อีกครั้ง',
            ),
          ),
        );
      } catch (e) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            backgroundColor: const Color(0xFFC2410C),
            content: Text('บันทึกบทความไม่สำเร็จ: $e'),
          ),
        );
      } finally {
        if (mounted) setState(() => _isSaving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isTablet = MediaQuery.of(context).size.width >= 760;
    final pagePadding = isTablet ? 24.0 : 16.0;

    return Scaffold(
      appBar: AppBar(
        centerTitle: true,
        title: Text(
          widget.article == null ? 'เขียนบทความใหม่' : 'แก้ไขบทความ',
          style: GoogleFonts.kanit(fontWeight: FontWeight.bold, fontSize: 18),
        ),
        actions: [
          TextButton(
            onPressed: _isSaving ? null : _save,
            child: Text(
              _isSaving ? 'กำลังบันทึก...' : 'บันทึก',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: Center(
        child: Container(
          constraints: BoxConstraints(
            maxWidth: isTablet ? 1180 : double.infinity,
          ),
          child: Form(
            key: _formKey,
            child: ListView(
              padding: EdgeInsets.all(pagePadding),
              children: [
                if (_isFetchingDetail) ...[
                  const LinearProgressIndicator(minHeight: 3),
                  const SizedBox(height: 16),
                ],
                if (isTablet)
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        flex: 7,
                        child: Column(children: _buildPrimaryFields()),
                      ),
                      const SizedBox(width: 24),
                      Expanded(
                        flex: 4,
                        child: Column(children: _buildArticleSettings()),
                      ),
                    ],
                  )
                else ...[
                  ..._buildPrimaryFields(),
                  const Divider(height: 40),
                  ..._buildArticleSettings(),
                ],

                const SizedBox(height: 40),
                ElevatedButton(
                  onPressed: _isSaving ? null : _save,
                  child: _isSaving
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Text('บันทึกบทความ'),
                ),
                const SizedBox(height: 60),
              ],
            ),
          ),
        ),
      ),
    );
  }

  List<Widget> _buildPrimaryFields() {
    return [
      _buildSectionTitle('เนื้อหาหลัก'),
      _buildTextField('หัวข้อบทความ', _titleController, isBold: true),
      if (widget.article?.slug != null) _buildArticleIdentity(),
      _buildTextField('คำโปรย (Excerpt)', _excerptController, maxLines: 2),
      const Text(
        'เนื้อหาบทความ',
        style: TextStyle(
          fontSize: 13,
          fontWeight: FontWeight.w600,
          color: Color(0xFF7488A8),
        ),
      ),
      const SizedBox(height: 8),
      Container(
        decoration: BoxDecoration(
          border: Border.all(color: const Color(0xFFD7E1F0)),
          borderRadius: BorderRadius.circular(16),
        ),
        clipBehavior: Clip.antiAlias,
        child: HtmlEditor(
          key: _editorKey,
          controller: _htmlController,
          htmlEditorOptions: HtmlEditorOptions(
            hint: 'เขียนเนื้อหาบทความที่นี่...',
            initialText: _initialHtml,
            shouldEnsureVisible: true,
          ),
          htmlToolbarOptions: const HtmlToolbarOptions(
            toolbarPosition: ToolbarPosition.aboveEditor,
            toolbarType: ToolbarType.nativeExpandable,
            defaultToolbarButtons: [
              StyleButtons(),
              FontSettingButtons(fontSizeUnit: false, fontSize: false),
              ListButtons(listStyles: false),
              ParagraphButtons(
                textDirection: false,
                lineHeight: false,
                caseConverter: false,
              ),
              InsertButtons(
                video: false,
                audio: false,
                table: true,
                hr: true,
                otherFile: false,
              ),
              OtherButtons(fullscreen: false, codeview: false, help: false),
            ],
          ),
          otherOptions: const OtherOptions(height: 440),
        ),
      ),
    ];
  }

  List<Widget> _buildArticleSettings() {
    return [
      _buildSectionTitle('SEO Meta Description'),
      _buildTextField(
        'Meta Description (สำหรับ Google)',
        _metaDescController,
        maxLines: 2,
      ),
      _buildTextField('Keywords (คั่นด้วยจุลภาค)', _keywordsController),
      _buildTextField('LSI Keywords', _lsiKeywordsController),
      const Divider(height: 36),
      _buildPromptHeader('รูปหน้าแรก (Landscape 16:9)', () {
        _copyToClipboard(_promptLandscapeController.text, 'Landscape Prompt');
      }),
      _buildImagePicker(
        label: 'รูปหน้าแรก (Landscape)',
        imagePath: _newLandscapePath,
        serverPath:
            widget.article?.coverImageLandscapePath ??
            widget.article?.coverImagePath,
        isLandscape: true,
      ),
      _buildTextField(
        'Prompt สำหรับรูป 16:9',
        _promptLandscapeController,
        maxLines: 2,
      ),
      const SizedBox(height: 16),
      _buildPromptHeader('รูปบทความ (Square 1:1)', () {
        _copyToClipboard(_promptSquareController.text, 'Square Prompt');
      }),
      _buildImagePicker(
        label: 'รูปบทความ (Square)',
        imagePath: _newSquarePath,
        serverPath:
            widget.article?.coverImageSquarePath ??
            widget.article?.coverImagePath,
        isLandscape: false,
      ),
      _buildTextField(
        'Prompt สำหรับรูป 1:1',
        _promptSquareController,
        maxLines: 2,
      ),
      const Divider(height: 36),
      _buildSectionTitle('การเผยแพร่'),
      _buildPublishToggle(),
      _buildAutoPostToggle(),
      _buildDateTimePicker(),
    ];
  }

  Widget _buildPromptHeader(String title, VoidCallback onCopy) {
    return Row(
      children: [
        Expanded(child: _buildSectionTitle(title)),
        IconButton(
          icon: const Icon(
            Icons.copy_rounded,
            size: 20,
            color: Color(0xFF223A63),
          ),
          onPressed: onCopy,
          tooltip: 'คัดลอก Prompt',
        ),
      ],
    );
  }

  Widget _buildArticleIdentity() {
    final article = widget.article!;
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Wrap(
        runSpacing: 8,
        spacing: 8,
        crossAxisAlignment: WrapCrossAlignment.center,
        children: [
          const Icon(Icons.link_rounded, size: 18, color: Color(0xFF64748B)),
          SelectableText(
            article.slug ?? '',
            style: const TextStyle(
              fontSize: 12,
              color: Color(0xFF475569),
              fontWeight: FontWeight.w600,
            ),
          ),
          if (article.isLineBroadcasted)
            const _SmallInfoPill(
              label: 'LINE Broadcast แล้ว',
              color: Color(0xFF065F46),
            ),
        ],
      ),
    );
  }

  Widget _buildImagePicker({
    required String label,
    String? imagePath,
    String? serverPath,
    required bool isLandscape,
  }) {
    String? imageUrl;
    if (serverPath != null) {
      imageUrl =
          '${ApiService.dio.options.baseUrl.replaceAll('/api', '')}/storage/$serverPath';
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          height: isLandscape ? 180 : 160,
          width: isLandscape ? double.infinity : 160,
          margin: const EdgeInsets.only(bottom: 12),
          decoration: BoxDecoration(
            color: const Color(0xFFF7FAFF),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFD7E1F0)),
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(15),
            child: imagePath != null
                ? Image.file(File(imagePath), fit: BoxFit.cover)
                : (imageUrl != null
                      ? Image.network(
                          imageUrl,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) =>
                              _buildPlaceholder(),
                        )
                      : _buildPlaceholder()),
          ),
        ),
        OutlinedButton.icon(
          onPressed: () => _pickImage(isLandscape),
          icon: const Icon(Icons.photo_library_outlined),
          label: const Text('เปลี่ยนรูปภาพ (Browse)'),
          style: OutlinedButton.styleFrom(
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            side: const BorderSide(color: Color(0xFF223A63)),
          ),
        ),
        const SizedBox(height: 12),
      ],
    );
  }

  Widget _buildPlaceholder() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.image_outlined, color: Colors.grey[400], size: 48),
          const SizedBox(height: 8),
          const Text(
            'ยังไม่มีรูปภาพ',
            style: TextStyle(color: Colors.grey, fontSize: 12),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 0),
      child: Text(
        title,
        style: GoogleFonts.kanit(
          fontWeight: FontWeight.bold,
          fontSize: 16,
          color: const Color(0xFF223A63),
        ),
      ),
    );
  }

  Widget _buildTextField(
    String label,
    TextEditingController controller, {
    bool isBold = false,
    int maxLines = 1,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: Color(0xFF7488A8),
            ),
          ),
          const SizedBox(height: 6),
          TextFormField(
            controller: controller,
            maxLines: maxLines,
            style: TextStyle(
              fontSize: isBold ? 16 : 14,
              fontWeight: isBold ? FontWeight.bold : FontWeight.normal,
              color: const Color(0xFF1E2D45),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPublishToggle() {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFD7E1F0)),
      ),
      child: SwitchListTile(
        title: const Text(
          'เปิดเผยแพร่',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
        ),
        subtitle: Text(
          _publishStatusLabel,
          style: const TextStyle(fontSize: 12, color: Color(0xFF7488A8)),
        ),
        value: _isPublished,
        activeThumbColor: const Color(0xFF1B8B6F),
        onChanged: (v) => setState(() => _isPublished = v),
      ),
    );
  }

  Widget _buildAutoPostToggle() {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFD7E1F0)),
      ),
      child: SwitchListTile(
        title: const Text(
          'แชร์อัตโนมัติ (Auto Social Post)',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
        ),
        subtitle: const Text(
          'แชร์ไป Facebook/LINE อัตโนมัติเมื่อถึงเวลา',
          style: TextStyle(fontSize: 12, color: Color(0xFF7488A8)),
        ),
        value: _isAutoPost,
        activeThumbColor: const Color(0xFF6366F1),
        onChanged: (v) => setState(() => _isAutoPost = v),
      ),
    );
  }

  Widget _buildDateTimePicker() {
    return InkWell(
      onTap: _selectDateTime,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: const Color(0xFFD7E1F0)),
        ),
        child: Row(
          children: [
            const Icon(
              Icons.calendar_today_outlined,
              size: 20,
              color: Color(0xFF223A63),
            ),
            const SizedBox(width: 12),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'เวลาเผยแพร่',
                  style: TextStyle(fontSize: 12, color: Color(0xFF7488A8)),
                ),
                Text(
                  _publishedAt != null
                      ? DateFormatter.formatBangkok(_publishedAt!)
                      : 'เผยแพร่ทันที',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1E2D45),
                  ),
                ),
              ],
            ),
            const Spacer(),
            const Icon(Icons.chevron_right, color: Color(0xFF7488A8)),
          ],
        ),
      ),
    );
  }
}

class _SmallInfoPill extends StatelessWidget {
  final String label;
  final Color color;

  const _SmallInfoPill({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(99),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontSize: 10,
          fontWeight: FontWeight.bold,
        ),
      ),
    );
  }
}
