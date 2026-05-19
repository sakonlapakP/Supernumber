import 'dart:convert';

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
  final bool isLineBroadcasted;
  final DateTime? publishedAt;
  final DateTime? notifiedAt;
  final DateTime? contentUpdatedAt;
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
    this.isLineBroadcasted = false,
    this.publishedAt,
    this.notifiedAt,
    this.contentUpdatedAt,
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
      imageGuidelines: _parseMap(json['image_guidelines']),
      isPublished: _parseBool(json['is_published']),
      isAutoPost: _parseBool(json['is_auto_post'], defaultValue: true),
      isLineBroadcasted: _parseBool(json['is_line_broadcasted']),
      publishedAt: _parseDateTime(json['published_at']),
      notifiedAt: _parseDateTime(json['notified_at']),
      contentUpdatedAt: _parseDateTime(json['content_updated_at']),
      createdAt: _parseDateTime(json['created_at']),
    );
  }

  static bool _parseBool(dynamic value, {bool defaultValue = false}) {
    if (value == null) return defaultValue;
    if (value is bool) return value;
    if (value is num) return value != 0;
    if (value is String) {
      final normalized = value.trim().toLowerCase();
      if (normalized.isEmpty) return defaultValue;
      return normalized == '1' ||
          normalized == 'true' ||
          normalized == 'yes' ||
          normalized == 'on';
    }
    return defaultValue;
  }

  static Map<String, dynamic>? _parseMap(dynamic value) {
    if (value == null) return null;
    if (value is Map<String, dynamic>) return value;
    if (value is Map) {
      return value.map((key, mapValue) => MapEntry(key.toString(), mapValue));
    }
    if (value is String && value.trim().isNotEmpty) {
      try {
        final decoded = jsonDecode(value);
        return decoded is Map ? _parseMap(decoded) : null;
      } catch (_) {
        return null;
      }
    }
    return null;
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
    if (!isPublished) return 'Draft';
    return isScheduled ? 'ตั้งเวลาเผยแพร่' : 'Published';
  }

  String? get publicUrl {
    if (slug == null || slug!.trim().isEmpty) return null;
    return 'https://www.supernumber.co.th/articles/$slug';
  }

  String? get adminPreviewUrl {
    if (id == null) return null;
    return 'https://www.supernumber.co.th/admin/articles/$id/preview';
  }

  bool get hasLandscapeCover =>
      (coverImageLandscapePath ?? coverImagePath)?.trim().isNotEmpty == true;

  bool get hasSquareCover => coverImageSquarePath?.trim().isNotEmpty == true;

  String? get preferredThumbnailPath {
    final candidates = [
      coverImagePath,
      coverImageSquarePath,
      coverImageLandscapePath,
    ];
    for (final candidate in candidates) {
      if (candidate != null && candidate.trim().isNotEmpty) {
        return candidate;
      }
    }
    return null;
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
      'is_line_broadcasted': isLineBroadcasted,
      'published_at': publishedAt != null
          ? (publishedAt!.millisecondsSinceEpoch ~/ 1000)
          : null,
      'notified_at': notifiedAt != null
          ? (notifiedAt!.millisecondsSinceEpoch ~/ 1000)
          : null,
      'content_updated_at': contentUpdatedAt != null
          ? (contentUpdatedAt!.millisecondsSinceEpoch ~/ 1000)
          : null,
    };
  }
}
