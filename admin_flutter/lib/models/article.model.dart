class Article {
  final int? id;
  final String title;
  final String? slug;
  final String? excerpt;
  final String content;
  final String? coverImagePath;
  final String? coverImageLandscapePath;
  final String? coverImageSquarePath;
  final String? metaDescription;
  final String? keywords;
  final String? lsiKeywords;
  final Map<String, dynamic>? imageGuidelines;
  final bool isPublished;
  final bool isAutoPost;
  final DateTime? publishedAt;
  final DateTime? createdAt;

  Article({
    this.id,
    required this.title,
    this.slug,
    this.excerpt,
    required this.content,
    this.coverImagePath,
    this.coverImageLandscapePath,
    this.coverImageSquarePath,
    this.metaDescription,
    this.keywords,
    this.lsiKeywords,
    this.imageGuidelines,
    this.isPublished = false,
    this.isAutoPost = true,
    this.publishedAt,
    this.createdAt,
  });

  factory Article.fromJson(Map<String, dynamic> json) {
    return Article(
      id: json['id'],
      title: json['title'] ?? '',
      slug: json['slug'],
      excerpt: json['excerpt'],
      content: json['content'] ?? '',
      coverImagePath: json['cover_image_path'],
      coverImageLandscapePath: json['cover_image_landscape_path'],
      coverImageSquarePath: json['cover_image_square_path'],
      metaDescription: json['meta_description'],
      keywords: json['keywords'],
      lsiKeywords: json['lsi_keywords'],
      imageGuidelines: json['image_guidelines'] is Map
          ? json['image_guidelines']
          : null,
      isPublished: json['is_published'] == 1 || json['is_published'] == true,
      isAutoPost: json['is_auto_post'] == 1 || json['is_auto_post'] == true,
      publishedAt: _parseDateTime(json['published_at']),
      createdAt: _parseDateTime(json['created_at']),
    );
  }

  static DateTime? _parseDateTime(dynamic value) {
    if (value == null) return null;
    if (value is int) {
      return DateTime.fromMillisecondsSinceEpoch(value * 1000).toLocal();
    }
    if (value is String) {
      // Handle numeric strings (Unix timestamps)
      final int? intValue = int.tryParse(value);
      if (intValue != null) {
        return DateTime.fromMillisecondsSinceEpoch(intValue * 1000).toLocal();
      }
      // Handle ISO 8601 strings
      try {
        return DateTime.parse(value).toLocal();
      } catch (e) {
        return null;
      }
    }
    return null;
  }

  bool get isScheduled =>
      isPublished &&
      publishedAt != null &&
      publishedAt!.isAfter(DateTime.now());

  String get publishStatusLabel {
    if (!isPublished) return 'ฉบับร่าง';
    return isScheduled ? 'ตั้งเวลาเผยแพร่' : 'เผยแพร่แล้ว';
  }

  String? get publicUrl {
    if (slug == null || slug!.trim().isEmpty) return null;
    return 'https://www.supernumber.co.th/articles/$slug';
  }

  String? get adminPreviewUrl {
    if (id == null) return null;
    return 'https://www.supernumber.co.th/admin/articles/$id/preview';
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'slug': slug,
      'excerpt': excerpt,
      'content': content,
      'cover_image_path': coverImagePath,
      'cover_image_landscape_path': coverImageLandscapePath,
      'cover_image_square_path': coverImageSquarePath,
      'meta_description': metaDescription,
      'keywords': keywords,
      'lsi_keywords': lsiKeywords,
      'image_guidelines': imageGuidelines,
      'is_published': isPublished,
      'is_auto_post': isAutoPost,
      'published_at': publishedAt != null ? (publishedAt!.millisecondsSinceEpoch ~/ 1000) : null,
    };
  }
}

