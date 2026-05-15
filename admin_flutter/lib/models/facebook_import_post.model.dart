class FacebookImportPost {
  final int id;
  final String facebookPostId;
  final String? message;
  final String? story;
  final String? permalinkUrl;
  final String? imageUrl;
  final DateTime? facebookCreatedTime;
  final DateTime? lastSyncedAt;

  FacebookImportPost({
    required this.id,
    required this.facebookPostId,
    this.message,
    this.story,
    this.permalinkUrl,
    this.imageUrl,
    this.facebookCreatedTime,
    this.lastSyncedAt,
  });

  factory FacebookImportPost.fromJson(Map<String, dynamic> json) {
    return FacebookImportPost(
      id: _asInt(json['id']) ?? 0,
      facebookPostId: (json['facebook_post_id'] ?? '').toString(),
      message: _asNullableString(json['message']),
      story: _asNullableString(json['story']),
      permalinkUrl: _asNullableString(json['permalink_url']),
      imageUrl: _asNullableString(json['image_url']),
      facebookCreatedTime: _parseDate(json['facebook_created_time']),
      lastSyncedAt: _parseDate(json['last_synced_at']),
    );
  }

  String get previewText {
    final value = (message?.trim().isNotEmpty ?? false)
        ? message!
        : (story ?? '');
    return value.trim().isEmpty ? '-' : value.trim();
  }

  static int? _asInt(dynamic value) {
    if (value is int) return value;
    if (value is String) return int.tryParse(value);
    return null;
  }

  static String? _asNullableString(dynamic value) {
    final text = value?.toString().trim() ?? '';
    return text.isEmpty ? null : text;
  }

  static DateTime? _parseDate(dynamic value) {
    final text = _asNullableString(value);
    if (text == null) return null;
    return DateTime.tryParse(text);
  }
}
