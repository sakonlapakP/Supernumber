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
      imageGuidelines: json['image_guidelines'] is Map ? json['image_guidelines'] : null,
      isPublished: json['is_published'] == 1 || json['is_published'] == true,
      publishedAt: json['published_at'] != null ? DateTime.parse(json['published_at']) : null,
      createdAt: json['created_at'] != null ? DateTime.parse(json['created_at']) : null,
    );
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
      'published_at': publishedAt?.toIso8601String(),
    };
  }
}
